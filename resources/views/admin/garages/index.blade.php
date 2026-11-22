<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Kelola Data Garasi
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Form Tambah Garasi Massal -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="{ type: '{{ old('type', 'sepeda') }}' }">
                <h3 class="text-lg font-semibold mb-4">Tambah Unit Garasi Baru</h3>

                <form action="{{ route('admin.garages.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @csrf

                    <div>
                        <x-input-label for="type" :value="__('Tipe Garasi')" />
                        <select id="type" name="type" x-model="type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="sepeda">Garasi Sepeda / Motor (Kode S)</option>
                            <option value="mobil">Garasi Mobil (Kode C)</option>
                            <option value="mobil_listrik">Garasi Mobil Listrik (Kode E)</option>
                        </select>
                        <x-input-error :messages="$errors->get('type')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="quantity" :value="__('Jumlah Unit (Maks. 999)')" />
                        <x-text-input id="quantity" name="quantity" type="number" min="1" max="999" class="mt-1 block w-full" :value="old('quantity', 1)" required />
                        <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="daily_rate" :value="__('Harga Sewa Harian (Rp)')" />
                        <x-text-input id="daily_rate" name="daily_rate" type="number" min="1000" step="500" class="mt-1 block w-full" :value="old('daily_rate')" required />
                        <p class="text-xs text-gray-500 mt-1">Harga bulanan otomatis dihitung 25x harga harian.</p>
                        <x-input-error :messages="$errors->get('daily_rate')" class="mt-2" />
                    </div>

                    <div x-show="type === 'mobil_listrik'" x-cloak>
                        <x-input-label for="electricity_rate_per_kwh" :value="__('Tarif Listrik per kWh (Rp)')" />
                        <x-text-input id="electricity_rate_per_kwh" name="electricity_rate_per_kwh" type="number" min="100" step="100" class="mt-1 block w-full" :value="old('electricity_rate_per_kwh')" x-bind:required="type === 'mobil_listrik'" />
                        <x-input-error :messages="$errors->get('electricity_rate_per_kwh')" class="mt-2" />
                    </div>

                    <div class="md:col-span-2 flex justify-end mt-2">
                        <x-primary-button>
                            Buat Garasi
                        </x-primary-button>
                    </div>
                </form>
            </div>

            <!-- Tabel Daftar Garasi -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Daftar Unit Garasi</h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b bg-gray-50">
                                <th class="p-3">Kode</th>
                                <th class="p-3">Tipe Garasi</th>
                                <th class="p-3">Tarif Harian</th>
                                <th class="p-3">Tarif Bulanan (25x)</th>
                                <th class="p-3">Tarif Listrik / kWh</th>
                                <th class="p-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($garages as $garage)
                                <tr class="border-b">
                                    <td class="p-3 font-mono font-bold">{{ $garage->code }}</td>
                                    <td class="p-3 capitalize">{{ str_replace('_', ' ', $garage->type) }}</td>
                                    <td class="p-3">Rp {{ number_format($garage->daily_rate, 0, ',', '.') }}</td>
                                    <td class="p-3">Rp {{ number_format($garage->daily_rate * 25, 0, ',', '.') }}</td>
                                    <td class="p-3">
                                        {{ $garage->electricity_rate_per_kwh ? 'Rp ' . number_format($garage->electricity_rate_per_kwh, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="p-3">
                                        <span class="px-2 py-1 text-xs rounded font-semibold {{ $garage->status === 'available' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ strtoupper($garage->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-4 text-center text-gray-500">Belum ada unit garasi yang dibuat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $garages->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>