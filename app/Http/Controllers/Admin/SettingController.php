<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GoogleClientManager;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(GoogleClientManager $clientManager): View
    {
        $googleConfig = [
            'credentials_path' => config('services.google.credentials_path'),
            'credentials_exists' => file_exists(config('services.google.credentials_path')) || file_exists(base_path(config('services.google.credentials_path'))),
            'client_ready' => $clientManager->isConfigured(),
            'spreadsheet_id' => config('services.google.spreadsheet_id'),
            'drive_folder_id' => config('services.google.drive_folder_id'),
            'sync_enabled' => config('services.google.sync_enabled'),
        ];

        $appInfo = [
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'timezone' => config('app.timezone'),
            'queue_connection' => config('queue.default'),
            'session_driver' => config('session.driver'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];

        return view('admin.settings.index', compact('googleConfig', 'appInfo'));
    }
}
