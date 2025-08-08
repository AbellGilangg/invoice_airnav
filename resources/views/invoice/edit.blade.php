{{-- resources/views/invoice/edit.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Edit Invoice #') }}{{ $invoice->id }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('invoices.update', $invoice->id) }}">
                        @csrf
                        @method('PATCH')

                        {{-- Tampilkan field yang bisa di-edit --}}
                        {{-- Contoh beberapa field, sesuaikan dengan kebutuhan --}}
                        <div class="mt-4">
                            <x-input-label for="airline" :value="__('Nama Airline')" />
                            <x-text-input id="airline" class="block mt-1 w-full" type="text" name="airline" :value="old('airline', $invoice->airline)" required />
                        </div>
                        <div class="mt-4">
                            <x-input-label for="flight_number" :value="__('Nomor Penerbangan')" />
                            <x-text-input id="flight_number" class="block mt-1 w-full" type="text" name="flight_number" :value="old('flight_number', $invoice->flight_number)" required />
                        </div>
                        <div class="mt-4">
                            <x-input-label for="registration" :value="__('Registrasi A/C')" />
                            <x-text-input id="registration" class="block mt-1 w-full" type="text" name="registration" :value="old('registration', $invoice->registration)" required />
                        </div>

                        {{-- Tambahkan field lain yang ingin bisa diedit --}}

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 dark:text-gray-400">Batal</a>
                            <x-primary-button class="ms-4">
                                {{ __('Simpan Perubahan') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>