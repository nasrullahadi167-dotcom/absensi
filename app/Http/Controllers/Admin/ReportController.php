<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Event;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    public function index(Request $request): View
    {
        $query = $this->reportService->getFilteredQuery($request);
        $attendances = (clone $query)->paginate(20)->withQueryString();

        // Summary counts based on current active filters
        $summaryQuery = $this->reportService->getFilteredQuery($request);
        $totalRecords = (clone $summaryQuery)->count();
        $totalHadir = (clone $summaryQuery)->where('status', 'HADIR')->count();
        $totalTerlambat = (clone $summaryQuery)->where('status', 'TERLAMBAT')->count();
        $totalIzin = (clone $summaryQuery)->where('status', 'IZIN')->count();
        $totalSakit = (clone $summaryQuery)->where('status', 'SAKIT')->count();
        $totalAlpa = (clone $summaryQuery)->where('status', 'ALPA')->count();

        $events = Event::orderBy('event_date', 'desc')->get();
        $divisions = Division::orderBy('name')->get();
        $members = User::where('role', 'MEMBER')->orderBy('name')->get();

        return view('admin.reports.index', compact(
            'attendances',
            'events',
            'divisions',
            'members',
            'totalRecords',
            'totalHadir',
            'totalTerlambat',
            'totalIzin',
            'totalSakit',
            'totalAlpa'
        ));
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $query = $this->reportService->getFilteredQuery($request);

        return $this->reportService->exportCsv($query);
    }

    public function exportPdf(Request $request): View
    {
        $query = $this->reportService->getFilteredQuery($request);
        $attendances = $query->get();

        $totalRecords = $attendances->count();
        $totalHadir = $attendances->where('status', 'HADIR')->count();
        $totalTerlambat = $attendances->where('status', 'TERLAMBAT')->count();
        $totalIzin = $attendances->where('status', 'IZIN')->count();
        $totalSakit = $attendances->where('status', 'SAKIT')->count();
        $totalAlpa = $attendances->where('status', 'ALPA')->count();

        $filters = [
            'event' => $request->filled('event_id') ? Event::find($request->input('event_id'))?->title : 'Semua Kegiatan',
            'division' => $request->filled('division_id') ? Division::find($request->input('division_id'))?->name : 'Semua Divisi',
            'status' => $request->input('status', 'Semua Status'),
            'start_date' => $request->input('start_date', '-'),
            'end_date' => $request->input('end_date', '-'),
        ];

        return view('admin.reports.print', compact(
            'attendances',
            'filters',
            'totalRecords',
            'totalHadir',
            'totalTerlambat',
            'totalIzin',
            'totalSakit',
            'totalAlpa'
        ));
    }
}
