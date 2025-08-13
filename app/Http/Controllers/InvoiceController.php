<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Airport;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function create()
    {
        $airports = Airport::all();
        return view('invoice.create', ['airports' => $airports]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Validasi Umum
            'airline' => 'required|string|max:255',
            'ground_handling' => 'nullable|string|max:255',
            'flight_number' => 'required|string|max:255',
            'flight_number_2' => 'nullable|string|max:255',
            'registration' => 'required|string|max:255',
            'aircraft_type' => 'required|string|max:255',
            'movements' => 'required|array|min:1',
            'movements.*' => 'in:Arrival,Departure',
            
            // Validasi Waktu (wajib jika checkbox dicentang)
            'arrival_time' => [
                Rule::requiredIf(fn() => in_array('Arrival', $request->input('movements', []))),
                'nullable', 'date_format:Y-m-d\TH:i'
            ],
            'departure_time' => [
                Rule::requiredIf(fn() => in_array('Departure', $request->input('movements', []))),
                'nullable', 'date_format:Y-m-d\TH:i'
            ],

            // Validasi Bandara & Rute (wajib jika checkbox dicentang)
            'arrival_airport_from' => [Rule::requiredIf(fn() => in_array('Arrival', $request->input('movements', []))), 'nullable', 'exists:airports,id'],
            'arrival_airport_to' => [Rule::requiredIf(fn() => in_array('Arrival', $request->input('movements', []))), 'nullable', 'exists:airports,id'],
            'departure_airport_from' => [Rule::requiredIf(fn() => in_array('Departure', $request->input('movements', [])) && !in_array('Arrival', $request->input('movements', []))), 'nullable', 'exists:airports,id'],
            'departure_airport_to' => [Rule::requiredIf(fn() => in_array('Departure', $request->input('movements', []))), 'nullable', 'exists:airports,id'],
            
            // Validasi Biaya
            'flight_type' => 'required|in:Domestik,Internasional',
            'usd_exchange_rate' => 'required_if:flight_type,Internasional|nullable|numeric|min:1',
            'service_type' => 'required|in:APP,TWR,AFIS',
            'apply_pph' => 'nullable|boolean',
        ]);
        
        // --- LOGIKA BARU UNTUK MENGATASI INPUT DISABLED ---
        $isArrivalChecked = in_array('Arrival', $validated['movements']);
        $isDepartureChecked = in_array('Departure', $validated['movements']);

        // Jika keduanya dicentang, paksa departure_from sama dengan arrival_to
        if ($isArrivalChecked && $isDepartureChecked) {
            $validated['departure_airport_from'] = $validated['arrival_airport_to'];
        }
        
        $mainAirportId = $validated['arrival_airport_to'] ?? $validated['departure_airport_from'];
        $airport = Airport::find($mainAirportId);

        $arrivalRoute = null;
        if ($isArrivalChecked) {
            $from = Airport::find($validated['arrival_airport_from'])->icao_code;
            $to = Airport::find($validated['arrival_airport_to'])->icao_code;
            $arrivalRoute = "$from - $to";
        }
        
        $departureRoute = null;
        if ($isDepartureChecked) {
            $from = Airport::find($validated['departure_airport_from'])->icao_code;
            $to = Airport::find($validated['departure_airport_to'])->icao_code;
            $departureRoute = "$from - $to";
        }
        
        // ... (Sisa kode pembuatan invoice tidak berubah)
        $invoice = new Invoice([
            'airport_id' => $mainAirportId,
            'airline' => $validated['airline'],
            'ground_handling' => $validated['ground_handling'],
            'flight_number' => $validated['flight_number'],
            'flight_number_2' => $validated['flight_number_2'],
            'registration' => $validated['registration'],
            'aircraft_type' => $validated['aircraft_type'],
            'operational_hour_start' => $airport->op_start,
            'operational_hour_end' => $airport->op_end,
            'departure_airport' => $departureRoute ?? $arrivalRoute,
            'arrival_airport' => $arrivalRoute ?? $departureRoute,
            'flight_type' => $validated['flight_type'],
            'service_type' => $validated['service_type'],
            'currency' => ($validated['flight_type'] == 'Domestik') ? 'IDR' : 'USD',
            'usd_exchange_rate' => $validated['usd_exchange_rate'] ?? null,
            'apply_pph' => $request->has('apply_pph'),
            'ppn_charge' => 0,
            'pph_charge' => 0,
            'total_charge' => 0,
        ]);
        
        $totalBaseCharge = 0;
        $overtimeMovements = 0;
        $detailsToCreate = [];

        foreach ($validated['movements'] as $movement) {
            $actual_time_str = ($movement == 'Arrival') ? $validated['arrival_time'] : $validated['departure_time'];
            $actual_time_obj = new \DateTime($actual_time_str);
            $charge_type = $this->determineChargeType($actual_time_obj, $airport);

            if (is_null($charge_type)) continue;

            $overtimeMovements++; 
            $duration_minutes = $this->calculateDuration($actual_time_obj, $airport, $charge_type);
            $billed_hours = $duration_minutes > 0 ? ceil($duration_minutes / 60) : 0;
            list($base_rate, $base_charge) = $this->calculateCharges($validated['flight_type'], $validated['service_type'], $billed_hours);

            $detailsToCreate[] = [
                'movement_type' => $movement,
                'actual_time' => $actual_time_obj->format('Y-m-d H:i:s'),
                'charge_type' => $charge_type,
                'duration_minutes' => $duration_minutes,
                'billed_hours' => $billed_hours,
                'base_rate' => $base_rate,
                'base_charge' => $base_charge,
            ];
            $totalBaseCharge += $base_charge;
        }

        if ($overtimeMovements === 0) {
            return back()->withErrors(['movements' => 'Tidak ada pergerakan yang masuk dalam kategori Advance/Extend. Invoice tidak dibuat.'])->withInput();
        }

        $invoice->save(); 
        $invoice->details()->createMany($detailsToCreate);

        // ... (Sisa kode tidak berubah)
        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice berhasil dibuat.');
    }
    
    // FUNGSI BARU UNTUK MENENTUKAN JENIS BIAYA
    private function determineChargeType(\DateTime $actual_time, Airport $airport): ?string
    {
        $flightTime = $actual_time->format('H:i:s');
        $opStart = $airport->op_start;
        $opEnd = $airport->op_end;

        if ($flightTime < $opStart) {
            return 'Advance';
        }

        if ($flightTime > $opEnd) {
            return 'Extend';
        }

        return null; // Return null jika berada dalam jam operasional
    }

    // Sisa kode... (calculateDuration, calculateCharges, show, edit, etc.) tidak berubah
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
        } else {
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

    public function edit(Invoice $invoice)
    {
        $airports = Airport::all();
        return view('invoice.edit', compact('invoice', 'airports'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $data = $request->validate([ 'airline' => 'required', 'flight_number' => 'required', ]);
        $invoice->update($data);
        return redirect()->route('dashboard')->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice)
    {
        // Hapus detail terkait terlebih dahulu
        $invoice->details()->delete();
        
        // Hapus invoice utamanya
        $invoice->delete();

        return redirect()->route('dashboard')->with('success', 'Invoice berhasil dihapus.');
    }

    public function downloadPDF(Invoice $invoice)
    {
        $invoice->load('details', 'airport');
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