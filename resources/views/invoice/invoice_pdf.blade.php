<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $invoice->id }}</title>
    <style>
        body {
            font-family: 'dejavu sans', sans-serif;
            font-size: 9px;
            margin: 15px;
            color: #000;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            vertical-align: top;
        }
        .text-strong { font-weight: bold; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .va-middle { vertical-align: middle; }
        .page-break { page-break-after: always; }

        /* Header */
        .header-table .logo { width: 80px; }
        .header-table .title {
            text-align: center;
            line-height: 1.3;
            font-size: 11px;
            padding: 0 10px;
        }
        .header-table .invoice-details-box {
            border: 1px solid #000;
            padding: 5px;
        }
        .invoice-details-box table td {
            padding: 1px;
            font-size: 10px;
        }

        /* Info Utama */
        .main-info-table { margin-top: 10px; }
        .main-info-table > tbody > tr > td {
            padding: 2px 0;
            font-size: 10px;
        }
        .main-info-table .label-col { width: 30%; }
        
        /* Tabel Biaya */
        .charges-table { margin-top: 10px; }
        .charges-table th, .charges-table td {
            border: 1px solid black;
            padding: 4px;
            text-align: center;
            vertical-align: middle;
        }
        .charges-table thead th { font-weight: bold; }
        
        /* Total Section */
        .total-section table{ width: 100%; }
        .total-section td {
            border: none;
            padding: 2px 5px;
            font-size: 10px;
        }
        
        /* Footer */
        .note-section { margin-top: 5px; font-size: 8px; }
        .bank-details-box {
            border: 1px solid #000;
            padding: 5px;
            margin-top: 5px;
        }
        .bank-details-box .bank-logo { width: 100px; }
        .footer-section { margin-top: 20px; }
        .footer-section .signature-block {
            text-align: center;
            padding-left: 65%;
        }
        .footer-section .signature-space { height: 40px; }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('images/logo.jpg');
        $logoSrc = file_exists($logoPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath)) : '';
        $bankLogoPath = public_path('images/bank_bri_logo.png');
        $bankLogoSrc = file_exists($bankLogoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($bankLogoPath)) : '';
    @endphp

    <table class="header-table">
        <tr>
            <td style="width: 15%; text-align: left;">
                @if($logoSrc) <img src="{{ $logoSrc }}" class="logo"> @endif
            </td>
            <td style="width: 55%;" class="title va-middle">
                <span class="text-strong">AIRNAV INDONESIA</span><br>
                PERUM LEMBAGA PENYELENGGARA PELAYANAN NAVIGASI PENERBANGAN INDONESIA<br>
                <span class="text-strong">ADVANCED/EXTENDED CHARGES</span>
            </td>
            <td style="width: 30%;">
                <div class="invoice-details-box">
                    <table>
                        <tr>
                            <td><span class="text-strong">NO</span></td>
                            <td>: {{ str_pad($invoice->id, 4, '0', STR_PAD_LEFT) }}/{{ $invoice->airport->iata_code ?? '' }}/{{ \Carbon\Carbon::parse($invoice->created_at)->format('m/Y') }}</td>
                        </tr>
                        <tr>
                            <td><span class="text-strong">TANGGAL/DATE</span></td>
                            <td>: {{ \Carbon\Carbon::parse($invoice->created_at)->format('d F Y') }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table class="main-info-table">
        <tr><td class="label-col">AIRLINE/Airline</td><td>: {{ $invoice->airline }}</td></tr>
        <tr><td class="label-col">GROUND HANDLING</td><td>: {{ $invoice->ground_handling ?? $invoice->airline }}</td></tr>
        <tr><td class="label-col">NOMOR PENERBANGAN/Flight Number</td><td>: {{ $invoice->flight_number }} {{ $invoice->flight_number_2 ? '/ '.$invoice->flight_number_2 : '' }}</td></tr>
        <tr><td class="label-col">REGISTRASI/Registration</td><td>: {{ $invoice->registration }}</td></tr>
        <tr><td class="label-col">JENIS PESAWAT/Type of Aircraft</td><td>: {{ $invoice->aircraft_type }}</td></tr>
        <tr><td class="label-col">RUTE/Route</td><td>: {{ $invoice->departure_airport }}</td></tr>
        <tr><td class="label-col">TANGGAL & WAKTU KEDATANGAN/Arrival</td><td>: {{ $invoice->details->where('movement_type', 'Arrival')->first() ? \Carbon\Carbon::parse($invoice->details->where('movement_type', 'Arrival')->first()->actual_time)->format('d M Y, H:i') : '-' }}</td></tr>
        <tr><td class="label-col">TANGGAL & WAKTU KEBERANGKATAN/Departure</td><td>: {{ $invoice->details->where('movement_type', 'Departure')->first() ? \Carbon\Carbon::parse($invoice->details->where('movement_type', 'Departure')->first()->actual_time)->format('d M Y, H:i') : '-' }}</td></tr>
        <tr><td class="label-col">DIBAYAR OLEH/Fee Will Be Paid By</td><td>: {{ $invoice->airline }}</td></tr>
    </table>

    <table class="charges-table">
        <thead>
            <tr>
                <th colspan="4" class="text-left" style="padding-left: 5px;">ADVANCED/EXTENDED CHARGE</th>
                <th rowspan="2" class="va-middle">RATE</th>
                <th rowspan="2" class="va-middle">GROSS</th>
                <th rowspan="2" class="va-middle">PPN/VAT 11%</th>
                <th rowspan="2" class="va-middle">NET</th>
            </tr>
            <tr>
                <th>MOVEMENT</th>
                <th>START</th>
                <th>END</th>
                <th>DURATION</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->details as $detail)
            <tr>
                <td class="text-left">{{ strtoupper($detail->movement_type) }} ({{ $detail->charge_type }})</td>
                {{-- Logika START dan END disederhanakan, sesuaikan jika perlu --}}
                <td>{{ \Carbon\Carbon::parse($detail->actual_time)->format('H:i') }}</td>
                <td>{{ \Carbon\Carbon::parse($detail->actual_time)->addMinutes($detail->duration_minutes)->format('H:i') }}</td>
                <td>{{ floor($detail->duration_minutes / 60) }}:{{ str_pad($detail->duration_minutes % 60, 2, '0', STR_PAD_LEFT) }}</td>
                <td class="text-right">{{ $invoice->currency }} {{ number_format($detail->base_rate, 2, ',', '.') }}</td>
                <td class="text-right">{{ $invoice->currency }} {{ number_format($detail->base_charge, 2, ',', '.') }}</td>
                {{-- PPN dan NET per baris dikosongkan agar fokus ke total --}}
                <td></td>
                <td></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <table>
             <tr>
                <td class="text-right" colspan="6" style="width:75%;"><span class="text-strong">SUBTOTAL</span></td>
                <td class="text-right" style="width:12.5%;"><span class="text-strong">{{ $invoice->currency }}</span></td>
                <td class="text-right" style="width:12.5%;"><span class="text-strong">{{ number_format($invoice->details->sum('base_charge'), 2, ',', '.') }}</span></td>
            </tr>
            @if($invoice->currency == 'IDR')
            <tr>
                <td class="text-right" colspan="6">PPN (11%)</td>
                <td class="text-right">IDR</td>
                <td class="text-right">{{ number_format($invoice->ppn_charge, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="text-right" colspan="6">PPH PASAL 23 (2%)</td>
                <td class="text-right">IDR</td>
                <td class="text-right">- {{ number_format($invoice->pph_charge, 2, ',', '.') }}</td>
            </tr>
            @endif
            
            {{-- Total Tagihan Pertama (USD atau IDR) --}}
            <tr>
                <td class="text-right text-strong" colspan="6">TOTAL ({{ $invoice->currency }})</td>
                <td class="text-right text-strong">{{ $invoice->currency }}</td>
                <td class="text-right text-strong">{{ number_format($invoice->total_charge, 2, ',', '.') }}</td>
            </tr>

            {{-- BARIS TAMBAHAN: Hanya muncul untuk Internasional --}}
            @if($invoice->flight_type == 'Internasional' && $invoice->total_charge_in_idr > 0)
            <tr>
                <td class="text-right text-strong" colspan="6">TOTAL (IDR)</td>
                <td class="text-right text-strong">IDR</td>
                <td class="text-right text-strong">{{ number_format($invoice->total_charge_in_idr, 2, ',', '.') }}</td>
            </tr>
            @endif
            {{-- AKHIR BARIS TAMBAHAN --}}
        </table>
    </div>

    <div class="note-section">
        Note : Berdasarkan PMK No. 131 Tahun 2024 menggunakan DPP Lain-Lain (11/12 x harga jual / penggantian)x 12%
    </div>

    <div class="bank-details-box">
        <table>
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <span class="text-strong">Detail Bank Transfer :</span>
                    <table>
                        <tr><td style="width: 30%;">Nama Bank</td><td>: PT. BANK BRI (PERSERO), TBK</td></tr>
                        <tr><td>Cabang/Branch</td><td>: KCP TUBAN - BALI</td></tr>
                        <tr><td>********</td><td>: UPPNPI BALI</td></tr>
                        <tr><td>********</td><td>: 2201 01 000 212 306</td></tr>
                    </table>
                </td>
                <td style="width: 50%; text-align: center; vertical-align: middle;">
                    @if($bankLogoSrc)
                        <img src="{{ $bankLogoSrc }}" class="bank-logo">
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="footer-section">
        <div class="signature-block">
            Petugas Official AIRNAV INDONESIA
            <div class="signature-space"></div>
            <div class="text-strong">( Furqaan Kurniawan Fuddy )</div>
        </div>
    </div>
</body>
</html>