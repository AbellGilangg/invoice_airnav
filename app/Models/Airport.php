<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Airport extends Model
{
    use HasFactory;

    protected $fillable = [
        'iata_code',
        'icao_code', // <-- TAMBAHKAN INI
        'name',
        'op_start',  // <-- TAMBAHKAN INI
        'op_end',    // <-- TAMBAHKAN INI
    ];

    // Relasi ke Invoice (jika diperlukan)
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}