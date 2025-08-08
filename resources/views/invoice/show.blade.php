<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Detail Invoice #{{ $invoice->id }}
            </h2>
            <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $invoice->status == 'Lunas' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                Status: {{ $invoice->status }}
            </span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 md:p-8 text-gray-900 dark:text-gray-100">
                    <div class="mb-8">
                        <p class="text-sm text-gray-500">DITAGIHKAN KEPADA</p>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ $invoice->airline }}</h1>
                    </div>

                    <h3 class="text-lg font-semibold border-b pb-2 mb-4">Data Penerbangan</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div><span class="font-medium text-gray-500">Bandara:</span><p>{{ $invoice->airport->name }}</p></div>
                        <div><span class="font-medium text-gray-500">Registrasi:</span><p>{{ $invoice->registration }}</p></div>
                        <div><span class="font-medium text-gray-500">Tipe Pesawat:</span><p>{{ $invoice->aircraft_type }}</p></div>
                        <div><span class="font-medium text-gray-500">Call Sign 1:</span><p>{{ $invoice->flight_number }}</p></div>
                        <div><span class="font-medium text-gray-500">Call Sign 2:</span><p>{{ $invoice->flight_number_2 ?? '-' }}</p></div>
                        <div><span class="font-medium text-gray-500">Jenis Penerbangan:</span><p>{{ $invoice->flight_type }}</p></div>
                        <div><span class="font-medium text-gray-500">Rute:</span><p>{{ $invoice->departure_airport }}</p></div>
                    </div>

                    {{-- LOOPING UNTUK SETIAP DETAIL PERGERAKAN --}}
                    @foreach($invoice->details as $detail)
                    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold">
                            Detail Perhitungan Biaya {{ $detail->charge_type }} ({{ $detail->movement_type }})
                        </h3>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div><span class="font-medium text-gray-500">Jenis Layanan:</span><p>{{ $invoice->service_type }}</p></div>
                            <div><span class="font-medium text-gray-500">Pergerakan:</span><p>{{ $detail->movement_type }}</p></div>
                            <div><span class="font-medium text-gray-500">Waktu Aktual:</span><p>{{ \Carbon\Carbon::parse($detail->actual_time)->format('d M Y, H:i') }}</p></div>
                            <div><span class="font-medium text-gray-500">Jam Operasional:</span><p>{{ \Carbon\Carbon::parse($invoice->airport->op_start)->format('H:i') }} - {{ \Carbon\Carbon::parse($invoice->airport->op_end)->format('H:i') }}</p></div>
                            <div><span class="font-medium text-gray-500">Durasi Terhitung:</span><p>{{ floor($detail->duration_minutes / 60) }} jam {{ $detail->duration_minutes % 60 }} menit</p></div>
                            <div><span class="font-medium text-gray-500">Jam Ditagihkan:</span><p>{{ $detail->billed_hours }} jam</p></div>
                        </div>
                        <div class="mt-6 flex justify-end">
                             <div class="w-full md:w-1/2 lg:w-2/3 space-y-2">
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-500">Tarif per Jam:</span>
                                    <span>{{ number_format($detail->base_rate, 2) }} {{ $invoice->currency }}</span>
                                </div>
                                 <div class="flex justify-between font-semibold">
                                    <span class="font-medium text-gray-500">Biaya Dasar ({{ $detail->movement_type }}):</span>
                                    <span>{{ number_format($detail->base_charge, 2) }} {{ $invoice->currency }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach

                    {{-- TOTAL KESELURUHAN --}}
                    <div class="mt-8 pt-6 border-t-2 border-gray-300 dark:border-gray-600">
                        <div class="flex justify-end">
                            <div class="w-full md:w-1/2 lg:w-1/3 space-y-2">
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-500">Subtotal:</span>
                                    <span>{{ number_format($invoice->details->sum('base_charge'), 2) }} {{ $invoice->currency }}</span>
                                </div>
                                @if($invoice->currency == 'IDR')
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-500">PPN (11%):</span>
                                    <span>{{ number_format($invoice->ppn_charge, 2) }} IDR</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-500">PPh (2%):</span>
                                    <span>- {{ number_format($invoice->pph_charge, 2) }} IDR</span>
                                </div>
                                @endif
                                <hr class="my-2 border-gray-200 dark:border-gray-700">
                                <div class="flex justify-between font-bold text-lg">
                                    <span>Total Tagihan:</span>
                                    <span>{{ number_format($invoice->total_charge, 2) }} {{ $invoice->currency }}</span>
                                </div>
                                
                                {{-- TAMBAHAN UNTUK MENAMPILKAN TOTAL IDR --}}
                                @if($invoice->flight_type == 'Internasional' && $invoice->total_charge_in_idr)
                                <div class="flex justify-between font-bold text-lg text-gray-600 dark:text-gray-400">
                                    <span>Setara Dengan:</span>
                                    <span>Rp {{ number_format($invoice->total_charge_in_idr, 2) }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end gap-4">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-gray-800 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            Kembali
                        </a>
                        <a href="{{ route('invoices.download', $invoice->id) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            Unduh PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
