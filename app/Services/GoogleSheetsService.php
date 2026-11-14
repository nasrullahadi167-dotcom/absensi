<?php

namespace App\Services;

use Google\Service\Sheets;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
use Google\Service\Sheets\Request as GoogleSheetRequest;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Log;

class GoogleSheetsService
{
    protected ?Sheets $sheets = null;

    protected ?string $spreadsheetId = null;

    public const SHEETS_CONFIG = [
        'Users' => [
            'headers' => ['ID', 'Username', 'Name', 'Email', 'Phone', 'Role', 'Division', 'Status', 'Created At', 'Updated At'],
        ],
        'Divisions' => [
            'headers' => ['ID', 'Code', 'Name', 'Description', 'Created At', 'Updated At'],
        ],
        'Events' => [
            'headers' => ['ID', 'Code', 'Title', 'Location', 'Date', 'Start Time', 'End Time', 'Status', 'Created By', 'Created At'],
        ],
        'EventParticipants' => [
            'headers' => ['ID', 'Event Code', 'Event Title', 'Username', 'Member Name', 'Registered At', 'Created At'],
        ],
        'Attendance' => [
            'headers' => ['ID', 'Event Code', 'Event Title', 'Username', 'Member Name', 'Check-in Time', 'Status', 'Drive File ID', 'Notes', 'Recorded At'],
        ],
        'LeaveRequests' => [
            'headers' => ['ID', 'Event Code', 'Username', 'Member Name', 'Type', 'Reason', 'Status', 'Reviewed By', 'Reviewed At', 'Admin Notes', 'Created At'],
        ],
        'AuditLog' => [
            'headers' => ['ID', 'Username', 'Action', 'Description', 'Entity Type', 'Entity ID', 'IP Address', 'Created At'],
        ],
        'SyncStatus' => [
            'headers' => ['Sheet Name', 'Total Records', 'Last Synced At', 'Status'],
        ],
    ];

    public function __construct(
        protected GoogleClientManager $clientManager
    ) {
        $client = $this->clientManager->getClient();
        if ($client) {
            $this->sheets = new Sheets($client);
        }
        $this->spreadsheetId = config('services.google.spreadsheet_id');
    }

    /**
     * Check if Google Sheets service is configured and spreadsheet is set.
     */
    public function isAvailable(): bool
    {
        return $this->sheets !== null && ! empty($this->spreadsheetId);
    }

    /**
     * Ensure all required sheets exist with proper headers.
     */
    public function initializeSpreadsheet(): array
    {
        if (! $this->isAvailable()) {
            return [
                'success' => false,
                'message' => 'Google Sheets API belum dikonfigurasi atau SPREADSHEET_ID belum diatur.',
            ];
        }

        try {
            $spreadsheet = $this->sheets->spreadsheets->get($this->spreadsheetId);
            $existingSheets = [];
            foreach ($spreadsheet->getSheets() as $sheet) {
                $existingSheets[] = $sheet->getProperties()->getTitle();
            }

            $addSheetRequests = [];
            foreach (self::SHEETS_CONFIG as $sheetName => $config) {
                if (! in_array($sheetName, $existingSheets, true)) {
                    $addSheetRequests[] = new GoogleSheetRequest([
                        'addSheet' => [
                            'properties' => [
                                'title' => $sheetName,
                            ],
                        ],
                    ]);
                }
            }

            if (! empty($addSheetRequests)) {
                $batchRequest = new BatchUpdateSpreadsheetRequest([
                    'requests' => $addSheetRequests,
                ]);
                $this->sheets->spreadsheets->batchUpdate($this->spreadsheetId, $batchRequest);
            }

            // Ensure headers are written for each sheet
            foreach (self::SHEETS_CONFIG as $sheetName => $config) {
                $range = "{$sheetName}!A1:Z1";
                $response = $this->sheets->spreadsheets_values->get($this->spreadsheetId, $range);
                $values = $response->getValues();

                if (empty($values) || empty($values[0])) {
                    $body = new ValueRange([
                        'values' => [$config['headers']],
                    ]);
                    $this->sheets->spreadsheets_values->update(
                        $this->spreadsheetId,
                        "{$sheetName}!A1",
                        $body,
                        ['valueInputOption' => 'USER_ENTERED']
                    );
                }
            }

            return [
                'success' => true,
                'message' => 'Spreadsheet berhasil diinisialisasi dan semua lembar kerja telah disiapkan.',
            ];
        } catch (\Throwable $e) {
            Log::error('Failed to initialize Google Sheets: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Gagal inisialisasi Spreadsheet: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Upsert a record into a specific sheet based on ID (Column A).
     */
    public function upsertRecord(string $sheetName, string|int $recordId, array $rowValues): bool
    {
        if (! $this->isAvailable()) {
            Log::info("Google Sheets not available. Record ID {$recordId} skipped for sheet {$sheetName}.");

            return false;
        }

        try {
            // 1. Fetch column A to find matching ID
            $range = "{$sheetName}!A:A";
            $response = $this->sheets->spreadsheets_values->get($this->spreadsheetId, $range);
            $rows = $response->getValues() ?? [];

            $targetRowIndex = null;
            $strRecordId = (string) $recordId;

            foreach ($rows as $index => $row) {
                if ($index === 0) {
                    continue; // Skip header
                }
                if (isset($row[0]) && (string) $row[0] === $strRecordId) {
                    $targetRowIndex = $index + 1; // 1-based index in Sheets
                    break;
                }
            }

            $body = new ValueRange([
                'values' => [$rowValues],
            ]);

            if ($targetRowIndex !== null) {
                // Update existing row
                $updateRange = "{$sheetName}!A{$targetRowIndex}";
                $this->sheets->spreadsheets_values->update(
                    $this->spreadsheetId,
                    $updateRange,
                    $body,
                    ['valueInputOption' => 'USER_ENTERED']
                );
            } else {
                // Append new row
                $appendRange = "{$sheetName}!A1";
                $this->sheets->spreadsheets_values->append(
                    $this->spreadsheetId,
                    $appendRange,
                    $body,
                    ['valueInputOption' => 'USER_ENTERED', 'insertDataOption' => 'INSERT_ROWS']
                );
            }

            return true;
        } catch (\Throwable $e) {
            Log::error("Failed to upsert record in sheet {$sheetName} (ID: {$recordId}): ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Update the SyncStatus sheet summary.
     */
    public function updateSyncStatusSummary(string $sheetName, int $totalRecords, string $status = 'OK'): void
    {
        if (! $this->isAvailable()) {
            return;
        }

        try {
            $now = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            $this->upsertRecord('SyncStatus', $sheetName, [
                $sheetName,
                $totalRecords,
                $now,
                $status,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to update SyncStatus in Google Sheets: '.$e->getMessage());
        }
    }
}
