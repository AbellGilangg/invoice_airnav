<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\InvoicesExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function exportInvoicesExcel(Request $request)
    {
        // Ambil semua parameter filter dari request
        $year = $request->query('year');
        $month = $request->query('month');
        $airportId = $request->query('airport_id'); // Tambahkan ini

        // --- PERBAIKAN DI SINI ---
        // Teruskan semua filter (termasuk airportId) ke kelas InvoicesExport
        return Excel::download(new InvoicesExport($year, $month, $airportId), 'riwayat-invoice.xlsx');
    }
}