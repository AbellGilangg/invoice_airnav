<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Extend/Advance') }}
        </h2>
    </x-slot>

     <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex justify-between items-center">
                        <p>{{ __("Selamat Datang") }}</p>
                        
                         @if(in_array(auth()->user()->role, ['master', 'admin']))
                            <a href="{{ route('invoices.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Buat Invoice Baru
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="pt-6 pb-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-4">Riwayat Invoice Advance/Extend</h3>

                    {{-- Form Filter --}}
                    <form method="GET" action="{{ route('dashboard') }}" class="mb-6 flex flex-wrap items-end gap-4">
                        <div>
                            <label for="airport_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bandara</label>
                            <select name="airport_id" id="airport_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="">Semua Bandara</option>
                                @foreach($airports as $airport)
                                    <option value="{{ $airport->id }}" {{ $selectedAirport == $airport->id ? 'selected' : '' }}>{{ $airport->iata_code }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- Select Tahun --}}
                        <div>
                            <label for="year" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tahun</label>
                            <select name="year" id="year" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="">Semua Tahun</option>
                                @foreach($years as $year)
                                    <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- Select Bulan --}}
                        <div>
                            <label for="month" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bulan</label>
                            <select name="month" id="month" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-600 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="">Semua Bulan</option>
                                @for ($m=1; $m<=12; $m++)
                                    <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m, 1, date('Y'))) }}</option>
                                @endfor
                            </select>
                        </div>
                        {{-- Tombol Aksi --}}
                        <div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">
                                Filter
                            </button>
                            <a href="{{ route('dashboard') }}" class="ml-2 inline-flex items-center px-4 py-2 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-500">
                                Reset
                            </a>
                            <a href="{{ route('invoices.export.excel', request()->query()) }}" class="ml-2 inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500">
                                Ekspor ke Excel
                            </a>
                        </div>
                    </form>

                    {{-- Tabel Invoice --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Airline</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No. Penerbangan</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Tagihan</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($invoices as $invoice)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $invoice->airline }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $invoice->flight_number }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ number_format($invoice->total_charge, 2) }} {{ $invoice->currency }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            {{-- Badge untuk status --}}
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $invoice->status == 'Lunas' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $invoice->status }}
                                            </span>
                                        </td>
                                        {{-- Bagian Aksi Baru dengan Pengecekan Role --}}
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <div class="flex items-center gap-4">
                                                <a href="{{ route('invoices.show', $invoice->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900">Detail</a>
                                                
                                                {{-- Tombol Edit & Dropdown Status hanya muncul jika user berhak --}}
                                                @can('update-invoice', $invoice)
                                                    <a href="{{ route('invoices.edit', $invoice->id) }}" class="text-green-600 dark:text-green-400 hover:text-green-900">Edit</a>
                                                    
                                                    <form action="{{ route('invoices.updateStatus', $invoice->id) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <select name="status" onchange="this.form.submit()" class="text-xs rounded-md ...">
                                                            <option value="Belum Lunas" {{ $invoice->status == 'Belum Lunas' ? 'selected' : '' }}>Belum Lunas</option>
                                                            <option value="Lunas" {{ $invoice->status == 'Lunas' ? 'selected' : '' }}>Lunas</option>
                                                        </select>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                            Tidak ada data invoice yang cocok dengan filter.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
