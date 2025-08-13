<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AirportController;
use App\Models\Airport;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : view('auth.login');
});

Route::get('/dashboard', function (Request $request) {
    $selectedYear = $request->input('year');
    $selectedMonth = $request->input('month');
    $selectedAirport = $request->input('airport_id');

    $years = Invoice::selectRaw("strftime('%Y', created_at) as year")
                    ->whereNotNull('created_at')
                    ->distinct()
                    ->orderBy('year', 'desc')
                    ->pluck('year');
    
    $airports = Airport::orderBy('iata_code')->get();

    $invoicesQuery = Invoice::with(['airport', 'details']);

    $invoices = $invoicesQuery
        ->when($selectedYear, fn ($query, $year) => $query->whereYear('created_at', $year))
        ->when($selectedMonth, fn ($query, $month) => $query->whereMonth('created_at', $month))
        ->when($selectedAirport, fn ($query, $airportId) => $query->where('airport_id', $airportId))
        ->orderBy('created_at', 'desc')
        ->get();

    return view('dashboard', [
        'invoices' => $invoices,
        'years' => $years,
        'airports' => $airports,
        'selectedYear' => $selectedYear,
        'selectedMonth' => $selectedMonth,
        'selectedAirport' => $selectedAirport,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // INVOICE ROUTES
    Route::resource('invoices', InvoiceController::class);
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'downloadPDF'])->name('invoices.download');
    Route::patch('/invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.updateStatus');
    
    // AIRPORT ROUTES
    Route::resource('airports', AirportController::class);
});

require __DIR__.'/auth.php';