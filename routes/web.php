<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AirportController;
use App\Models\Airport;
use App\Http\Controllers\UserController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    // Arahkan ke halaman login jika belum login, atau ke dashboard jika sudah
    return auth()->check() ? redirect()->route('dashboard') : view('auth.login');
});

Route::get('/dashboard', function (Request $request) {
    // Ambil input filter dari request
    $selectedYear = $request->input('year');
    $selectedMonth = $request->input('month');
    $selectedAirport = $request->input('airport_id');

    // --- PERBAIKAN DI SINI ---
    // Mengambil daftar tahun dari kolom 'created_at' karena 'actual_time' sudah tidak ada
    $years = Invoice::selectRaw("strftime('%Y', created_at) as year")
                    ->whereNotNull('created_at')
                    ->distinct()
                    ->orderBy('year', 'desc')
                    ->pluck('year');
    
    $airports = Airport::orderBy('iata_code')->get();

    $user = auth()->user();
    
    $invoicesQuery = Invoice::query()->with(['airport', 'details']);

    // Logika pembatasan data berdasarkan role
    if ($user->role === 'admin' || $user->role === 'user') {
        $invoicesQuery->where('airport_id', $user->airport_id);
    }
    
    // Buat query dasar, lalu terapkan filter jika ada
    $invoices = $invoicesQuery
        ->when($selectedYear, function ($query, $year) {
            // Filter berdasarkan tahun dari 'created_at'
            return $query->whereRaw("strftime('%Y', created_at) = ?", [$year]);
        })
        ->when($selectedMonth, function ($query, $month) {
            // Filter berdasarkan bulan dari 'created_at'
            return $query->whereRaw("strftime('%m', created_at) = ?", [str_pad($month, 2, '0', STR_PAD_LEFT)]);
        })
        ->when($selectedAirport && $user->role === 'master', function ($query, $airportId) { // Terapkan filter bandara
            return $query->where('airport_id', $airportId);
        })
        // Mengurutkan berdasarkan tanggal pembuatan invoice
        ->orderBy('created_at', 'desc')
        ->get();

    // Kirim semua data yang dibutuhkan ke view
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
    Route::get('/invoice/pdf', [InvoiceController::class, 'generateUsersPDF'])->name('report.users.pdf');

    // Rute spesifik harus didefinisikan sebelum rute dinamis
    Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::get('/invoices/export', [ReportController::class, 'exportInvoicesExcel'])->name('invoices.export.excel');

    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');

    Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::patch('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');

    // Rute dinamis (dengan parameter) diletakkan setelahnya
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'downloadPDF'])->name('invoices.download');
    Route::patch('/invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.updateStatus');
    
    Route::middleware('can:manage-airports')->group(function () {
        Route::get('/airports', [AirportController::class, 'index'])->name('airports.index');
        Route::get('/airports/create', [AirportController::class, 'create'])->name('airports.create');
        Route::post('/airports', [AirportController::class, 'store'])->name('airports.store');
        Route::get('/airports/{airport}/edit', [AirportController::class, 'edit'])->name('airports.edit');
        Route::patch('/airports/{airport}', [AirportController::class, 'update'])->name('airports.update');
    });

    Route::middleware('can:manage-users')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
    });
});

require __DIR__.'/auth.php';