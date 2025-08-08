<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Airport; // <-- PERBAIKAN: Import yang hilang
use App\Models\InvoiceDetail; // <-- PERBAIKAN: Import yang hilang
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule; // <-- PERBAIKAN: Diperlukan untuk validasi

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (auth()->user()->role !== 'admin') {
                return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki hak akses untuk mengubah status invoice.');
            }
            return $next($request);
        })->only('updateStatus');
    }

    public function create()
    {
        $airports = Airport::all();
        return view('invoice.create', ['airports' => $airports]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'airport_id' => 'required|exists:airports,id',
            'airline' => 'required|string|max:255',
            'ground_handling' => 'nullable|string|max:255',
            'flight_number' => 'required|string|max:255',
            'flight_number_2' => 'nullable|string|max:255',
            'registration' => 'required|string|max:255',
            'aircraft_type' => 'required|string|max:255',
            'other_airport' => 'required|string|max:255', // Rute
            
            'movements' => 'required|array|min:1',
            'movements.*' => 'in:Arrival,Departure',
            // PERBAIKAN: Validasi yang lebih baik untuk waktu
            'arrival_time' => [Rule::requiredIf(in_array('Arrival', $request->input('movements', []))), 'nullable', 'date_format:Y-m-d\TH:i'],
            'departure_time' => [Rule::requiredIf(in_array('Departure', $request->input('movements', []))), 'nullable', 'date_format:Y-m-d\TH:i'],

            'flight_type' => 'required|in:Domestik,Internasional',
            'usd_exchange_rate' => 'required_if:flight_type,Internasional|nullable|numeric|min:1',
            'service_type' => 'required|in:APP,TWR,AFIS',
            'charge_type' => 'required|in:Advance,Extend',
            'apply_pph' => 'nullable|boolean',
        ]);

        $airport = Airport::find($validated['airport_id']);

        // 1. Buat Invoice Induk
        $invoice = Invoice::create([
            'airport_id' => $validated['airport_id'],
            'airline' => $validated['airline'],
            'ground_handling' => $validated['ground_handling'],
            'flight_number' => $validated['flight_number'],
            'flight_number_2' => $validated['flight_number_2'],
            'registration' => $validated['registration'],
            'aircraft_type' => $validated['aircraft_type'],
            'operational_hour_start' => $airport->op_start,
            'operational_hour_end' => $airport->op_end,
            'departure_airport' => $validated['other_airport'],
            'arrival_airport' => $validated['other_airport'], // Disimpan sama untuk rute
            'flight_type' => $validated['flight_type'],
            'service_type' => $validated['service_type'],
            'currency' => ($validated['flight_type'] == 'Domestik') ? 'IDR' : 'USD',
            'usd_exchange_rate' => $validated['usd_exchange_rate'] ?? null,
            'ppn_charge' => 0,
            'pph_charge' => 0,
            'total_charge' => 0,
            'total_charge_in_idr' => null,
            'apply_pph' => $request->has('apply_pph'),
        ]);

        $totalBaseCharge = 0;

        // 2. Loop setiap pergerakan untuk membuat InvoiceDetail
        foreach ($validated['movements'] as $movement) {
            $actual_time_str = ($movement == 'Arrival') ? $validated['arrival_time'] : $validated['departure_time'];
            $actual_time_obj = new \DateTime($actual_time_str);

            $duration_minutes = $this->calculateDuration($actual_time_obj, $airport, $validated['charge_type']);
            $billed_hours = $duration_minutes > 0 ? ceil($duration_minutes / 60) : 0;
            
            list($base_rate, $base_charge) = $this->calculateCharges(
                $validated['flight_type'], 
                $validated['service_type'], 
                $billed_hours
            );

            $invoice->details()->create([
                'movement_type' => $movement,
                'actual_time' => $actual_time_obj->format('Y-m-d H:i:s'),
                'charge_type' => $validated['charge_type'],
                'duration_minutes' => $duration_minutes,
                'billed_hours' => $billed_hours,
                'base_rate' => $base_rate,
                'base_charge' => $base_charge,
            ]);

            $totalBaseCharge += $base_charge;
        }

        // 3. Hitung total akhir dan update invoice induk
        $totalPpn = 0;
        $totalPph = 0;

        if ($invoice->flight_type == 'Domestik') {
            $totalPpn = $totalBaseCharge * 0.11;
            if ($invoice->apply_pph) {
                $totalPph = $totalBaseCharge * 0.02;
            }
            $invoice->total_charge = $totalBaseCharge + $totalPpn - $totalPph;
        } else { // Internasional
            $invoice->total_charge = $totalBaseCharge; // Total dalam USD
            $invoice->total_charge_in_idr = $totalBaseCharge * $invoice->usd_exchange_rate; // Total dalam IDR
        }
        
        $invoice->ppn_charge = $totalPpn;
        $invoice->pph_charge = $totalPph;
        $invoice->save();

        return redirect()->route('invoices.show', $invoice);
    }

    private function calculateDuration(\DateTime $actual_time, Airport $airport, string $charge_type): int
    {
        $duration_minutes = 0;
        if ($charge_type == 'Advance') {
            $op_start_time = new \DateTime($actual_time->format('Y-m-d') . ' ' . $airport->op_start);
            if ($actual_time > $op_start_time) {
                $op_start_time->modify('+1 day');
            }
            $duration_minutes = round(($op_start_time->getTimestamp() - $actual_time->getTimestamp()) / 60);
        } else { // Extend
            $op_end_time = new \DateTime($actual_time->format('Y-m-d') . ' ' . $airport->op_end);
            $duration_minutes = round(($actual_time->getTimestamp() - $op_end_time->getTimestamp()) / 60);
        }
        return $duration_minutes < 0 ? 0 : $duration_minutes;
    }

    private function calculateCharges(string $flight_type, string $service_type, int $billed_hours): array
    {
        $rates_rupiah = ['APP' => 822000, 'TWR' => 575500, 'AFIS' => 246500];
        $rates_usd = ['APP' => 76, 'TWR' => 53, 'AFIS' => 23];
        
        $base_rate = 0;
        
        if ($flight_type == 'Domestik') {
            $base_rate = $rates_rupiah[$service_type];
        } else { // Internasional
            $base_rate = $rates_usd[$service_type];
        }
        
        $base_charge = $base_rate * $billed_hours;

        return [$base_rate, $base_charge];
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('details', 'airport');
        return view('invoice.show', ['invoice' => $invoice]);
    }
    
    public function downloadPDF(Invoice $invoice)
    {
        $invoice->load('details', 'airport'); // Pastikan relasi di-load
        $data = ['invoice' => $invoice];
        $pdf = PDF::loadView('invoice.invoice_pdf', $data);
        $fileName = 'invoice-' . $invoice->id . '-' . Str::slug($invoice->airline) . '.pdf';
        return $pdf->download($fileName);
    }

    public function updateStatus(Request $request, Invoice $invoice)
    {
        $request->validate(['status' => 'required|in:Lunas,Belum Lunas']);
        $invoice->status = $request->status;
        $invoice->save();
        return back()->with('success', 'Status invoice berhasil diperbarui.');
    }
}
