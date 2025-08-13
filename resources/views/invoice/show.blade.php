<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Detail Invoice: ' . $invoice->airline . ' (' . $invoice->flight_number . ')') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Informasi Penerbangan</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p><strong>Maskapai:</strong> {{ $invoice->airline }}</p>
                            <p><strong>No. Penerbangan:</strong> {{ $invoice->flight_number }}</p>
                            <p><strong>Registrasi Pesawat:</strong> {{ $invoice->registration }}</p>
                            <p><strong>Tipe Pesawat:</strong> {{ $invoice->aircraft_type }}</p>
                        </div>
                        <div>
                            <p><strong>Asal/Tujuan:</strong> {{ $invoice->departure_airport }}</p>
                            <p><strong>Ground Handling:</strong> {{ $invoice->ground_handling ?? '-' }}</p>
                            <p><strong>Tipe Penerbangan:</strong> {{ $invoice->flight_type }}</p>
                            <p><strong>Status Saat Ini:</strong> 
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $invoice->status == 'Lunas' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $invoice->status }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Rincian Biaya</h3>
                        @foreach($invoice->details as $detail)
                            <div class="mb-4 p-4 border rounded-md dark:border-gray-600">
                                <p><strong>Pergerakan:</strong> {{ $detail->movement_type }} ({{ $detail->charge_type }})</p>
                                <p><strong>Waktu Aktual:</strong> {{ \Carbon\Carbon::parse($detail->actual_time)->format('d M Y, H:i') }}</p>
                                <p><strong>Durasi Terhitung:</strong> {{ $detail->duration_minutes }} menit ({{ $detail->billed_hours }} jam ditagih)</p>
                                <p><strong>Rate per Jam:</strong> {{ number_format($detail->base_rate, 2, ',', '.') }} {{ $invoice->currency }}</p>
                                <p><strong>Subtotal:</strong> {{ number_format($detail->base_charge, 2, ',', '.') }} {{ $invoice->currency }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6 text-right">
                        @if($invoice->flight_type == 'Domestik')
                            <p><strong>PPN (11%):</strong> {{ number_format($invoice->ppn_charge, 2, ',', '.') }} IDR</p>
                            @if($invoice->apply_pph)
                                <p><strong>PPh 23 (2%):</strong> -{{ number_format($invoice->pph_charge, 2, ',', '.') }} IDR</p>
                            @endif
                        @endif
                        <p class="text-xl font-bold"><strong>Total Tagihan:</strong> {{ number_format($invoice->total_charge, 2, ',', '.') }} {{ $invoice->currency }}</p>
                         @if($invoice->flight_type == 'Internasional' && $invoice->total_charge_in_idr)
                            <p class="text-md"><strong>Total (IDR):</strong> Rp {{ number_format($invoice->total_charge_in_idr, 2, ',', '.') }}</p>
                        @endif
                    </div>
                     <div class="mt-6 flex justify-end">
                        <a href="{{ route('invoices.download', $invoice) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Download PDF</a>
                    </div>
                </div>
            </div>

            <div class="mt-8 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Ubah Status Pembayaran</h3>
                    <form action="{{ route('invoices.updateStatus', $invoice) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label for="status" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Status</label>
                            <select name="status" id="status" class="block mt-1 w-full md:w-1/3 rounded-md shadow-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="Belum Lunas" {{ $invoice->status == 'Belum Lunas' ? 'selected' : '' }}>
                                    Belum Lunas
                                </option>
                                <option value="Lunas" {{ $invoice->status == 'Lunas' ? 'selected' : '' }}>
                                    Lunas
                                </option>
                            </select>
                        </div>

                        <div class="mt-4">
                            <x-primary-button>
                                {{ __('Simpan Status') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>