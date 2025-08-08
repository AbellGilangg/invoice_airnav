<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'airport_id',
        'airline',
        'ground_handling',
        'flight_number',
        'flight_number_2',
        'registration',
        'aircraft_type',
        'departure_airport',
        'arrival_airport',
        'service_type',
        'flight_type',
        'operational_hour_start',
        'operational_hour_end',
        'ppn_charge',
        'pph_charge',
        'apply_pph',
        'total_charge',
        'total_charge_in_idr', // <-- PERBAIKAN: Ditambahkan
        'currency',
        'usd_exchange_rate',   // <-- PERBAIKAN: Ditambahkan
        'status',
    ];

    /**
     * Mendefinisikan relasi bahwa Invoice milik sebuah Airport.
     */
    public function airport()
    {
        return $this->belongsTo(Airport::class);
    }

    /**
     * Mendefinisikan relasi bahwa Invoice memiliki banyak Detail.
     */
    public function details()
    {
        return $this->hasMany(InvoiceDetail::class);
    }
}