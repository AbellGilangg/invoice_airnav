<?php

namespace App\Http\Controllers;

use App\Models\Airport;
use Illuminate\Http\Request;

class AirportController extends Controller
{
    public function index()
    {
        $airports = Airport::all();
        return view('airports.index', compact('airports'));
    }

    public function create()
    {
        return view('airports.create');
    }

    public function store(Request $request)
    {
        // SESUAIKAN VALIDASI DENGAN FORM
        $data = $request->validate([
            'iata_code' => 'required|string|max:3|unique:airports',
            'icao_code' => 'required|string|max:4|unique:airports',
            'name' => 'required|string|max:255',
            'op_start' => 'required|date_format:H:i',
            'op_end' => 'required|date_format:H:i',
        ]);

        Airport::create($data);
        return redirect()->route('airports.index')->with('success', 'Data bandara berhasil ditambahkan.');
    }

    public function edit(Airport $airport)
    {
        return view('airports.edit', compact('airport'));
    }

    public function update(Request $request, Airport $airport)
    {
        // SESUAIKAN VALIDASI DENGAN FORM
        $data = $request->validate([
            'iata_code' => 'required|string|max:3|unique:airports,iata_code,' . $airport->id,
            'icao_code' => 'required|string|max:4|unique:airports,icao_code,' . $airport->id,
            'name' => 'required|string|max:255',
            'op_start' => 'required|date_format:H:i',
            'op_end' => 'required|date_format:H:i',
        ]);

        $airport->update($data);
        return redirect()->route('airports.index')->with('success', 'Data bandara berhasil diperbarui.');
    }

    public function destroy(Airport $airport)
    {
        $airport->delete();
        return redirect()->route('airports.index')->with('success', 'Data bandara berhasil dihapus.');
    }
}