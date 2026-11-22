<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Ajukan Sewa Garasi Baru
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Info Ketersediaan Unit -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @php
                    $labels = [
                        'sepeda' => 'Garasi Sepeda / Motor (S)',
                        'mobil' => 'Garasi Mobil (C)',
                        'mobil_listrik' => 'Garasi Mobil Listrik (E)',
                    ];
                @endphp

                @foreach ($labels as $key => $label)
                    @php $info = $garageSummary->get($key); @endphp
                    <div class="bg-white p-4 rounded-lg shadow-sm border">
                        <p class="text-sm font-semibold text-gray-700">{{ $label }}</p>
                        <p class="text-2xl font-bold mt-1 {{ ($info->available_count ?? 0) > 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $info->available_count ?? 0 }} <span class="text-sm font-normal text-gray-500">unit tersedia</span>
                        </p>
                        @if ($info)
                            <p class="text-xs text-gray-500 mt-1">
                                Rp {{ number_format($info->daily_rate, 0, ',', '.') }}/hari
                                @if ($info->electricity_rate)
                                    • Listrik Rp {{ number_format($info->electricity_rate, 0, ',', '.') }}/kWh
                                @endif
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Form Penyewaan -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6"
                 x-data="{
                    rates: @js($garageSummary),
                    vehicleType: '{{ old('vehicle_type', 'sepeda') }}',
                    rentalType: '{{ old('rental_type', 'harian') }}',
                    duration: {{ old('duration', 1) }},
                    startDate: '{{ old('start_date', date('Y-m-d')) }}',
                    batteryCapacity: {{ old('battery_capacity_kwh', 0) ?: 0 }},
                    remainingBattery: {{ old('remaining_battery_kwh', 0) ?: 0 }},

                    get garageKey() {
                        return (this.vehicleType === 'sepeda' || this.vehicleType === 'sepeda_motor')
                            ? 'sepeda'
                            : this.vehicleType;
                    },
                    get dailyRate() {
                        return this.rates[this.garageKey] ? Number(this.rates[this.garageKey].daily_rate) : 0;
                    },
                    get electricityRate() {
                        return this.rates[this.garageKey] && this.rates[this.garageKey].electricity_rate
                            ? Number(this.rates[this.garageKey].electricity_rate)
                            : 0;
                    },
                    get rentCost() {
                        let dur = Math.max(1, Number(this.duration) || 1);
                        return this.rentalType === 'harian'
                            ? this.dailyRate * dur
                            : (this.dailyRate * 25) * dur;
                    },
                    get electricityCost() {
                        if (this.vehicleType !== 'mobil_listrik') return 0;
                        let needed = Math.max(0, Number(this.batteryCapacity) - Number(this.remainingBattery));
                        return Math.round(needed * this.electricityRate);
                    },
                    get totalCost() {
                        return this.rentCost + this.electricityCost;
                    },
                    get endDateFormatted() {
                        if (!this.startDate) return '-';
                        let dur = Math.max(1, Number(this.duration) || 1);
                        let d = new Date(this.startDate + 'T00:00:00');
                        if (this.rentalType === 'harian') {
                            d.setDate(d.getDate() + dur);
                        } else {
                            let targetMonth = d.getMonth() + dur;
                            let targetYear = d.getFullYear() + Math.floor(targetMonth / 12);
                            targetMonth = targetMonth % 12;
                            let maxDay = new Date(targetYear, targetMonth + 1, 0).getDate();
                            d = new Date(targetYear, targetMonth, Math.min(d.getDate(), maxDay));
                        }
                        return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
                    },
                    formatRupiah(num) {
                        return 'Rp ' + Number(num).toLocaleString('id-ID');
                    }
                 }">

                <form action="{{ route('tenant.rentals.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Tipe Kendaraan -->
                        <div>
                            <x-input-label for="vehicle_type" :value="__('Jenis Kendaraan')" />
                            <select id="vehicle_type" name="vehicle_type" x-model="vehicleType" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="sepeda">Sepeda</option>
                                <option value="sepeda_motor">Sepeda Motor</option>
                                <option value="mobil">Mobil</option>
                                <option value="mobil_listrik">Mobil Listrik (EV)</option>
                            </select>
                            <x-input-error :messages="$errors->get('vehicle_type')" class="mt-2" />
                        </div>

                        <!-- Merek & Tipe Kendaraan -->
                        <div>
                            <x-input-label for="vehicle_brand_type" :value="__('Merek & Tipe Kendaraan')" />
                            <x-text-input id="vehicle_brand_type" name="vehicle_brand_type" type="text" class="mt-1 block w-full" placeholder="Contoh: Honda Vario / Wuling Air EV" :value="old('vehicle_brand_type')" required />
                            <x-input-error :messages="$errors->get('vehicle_brand_type')" class="mt-2" />
                        </div>

                        <!-- Nomor Plat (Wajib kecuali Sepeda) -->
                        <div x-show="vehicleType !== 'sepeda'" x-cloak>
                            <x-input-label for="plate_number" :value="__('Nomor Plat Kendaraan')" />
                            <x-text-input id="plate_number" name="plate_number" type="text" class="mt-1 block w-full uppercase" placeholder="Contoh: L 1234 AB" :value="old('plate_number')" x-bind:required="vehicleType !== 'sepeda'" />
                            <x-input-error :messages="$errors->get('plate_number')" class="mt-2" />
                        </div>

                        <!-- Input Khusus Mobil Listrik -->
                        <template x-if="vehicleType === 'mobil_listrik'">
                            <div class="contents">
                                <div>
                                    <x-input-label for="battery_capacity_kwh" :value="__('Kapasitas Baterai Maksimal (kWh)')" />
                                    <x-text-input id="battery_capacity_kwh" name="battery_capacity_kwh" type="number" step="0.01" min="1" class="mt-1 block w-full" x-model="batteryCapacity" required />
                                    <x-input-error :messages="$errors->get('battery_capacity_kwh')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="remaining_battery_kwh" :value="__('Sisa Baterai Saat Ini (kWh)')" />
                                    <x-text-input id="remaining_battery_kwh" name="remaining_battery_kwh" type="number" step="0.01" min="0" class="mt-1 block w-full" x-model="remainingBattery" required />
                                    <x-input-error :messages="$errors->get('remaining_battery_kwh')" class="mt-2" />
                                </div>
                            </div>
                        </template>

                        <!-- Paket Sewa -->
                        <div>
                            <x-input-label for="rental_type" :value="__('Paket Sewa')" />
                            <select id="rental_type" name="rental_type" x-model="rentalType" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="harian">Harian</option>
                                <option value="bulanan">Bulanan (Tarif 25x Harian / Bulan)</option>
                            </select>
                            <x-input-error :messages="$errors->get('rental_type')" class="mt-2" />
                        </div>

                        <!-- Durasi -->
                        <div>
                            <x-input-label for="duration" x-text="rentalType === 'harian' ? 'Durasi Sewa (Hari)' : 'Durasi Sewa (Bulan)'" />
                            <x-text-input id="duration" name="duration" type="number" min="1" max="365" class="mt-1 block w-full" x-model="duration" required />
                            <x-input-error :messages="$errors->get('duration')" class="mt-2" />
                        </div>

                        <!-- Tanggal Mulai -->
                        <div>
                            <x-input-label for="start_date" :value="__('Tanggal Mulai Sewa')" />
                            <x-text-input id="start_date" name="start_date" type="date" min="{{ date('Y-m-d') }}" class="mt-1 block w-full" x-model="startDate" required />
                            <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                        </div>

                        <!-- Upload Bukti Pembayaran -->
                        <div>
                            <x-input-label for="payment_proof" :value="__('Upload Bukti Pembayaran (JPG, PNG, PDF - Maks 2MB)')" />
                            <input id="payment_proof" name="payment_proof" type="file" accept=".jpg,.jpeg,.png,.pdf" class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" required />
                            <x-input-error :messages="$errors->get('payment_proof')" class="mt-2" />
                        </div>
                    </div>

                    <!-- Ringkasan Estimasi Biaya -->
                    <div class="mt-6 p-4 bg-gray-50 rounded-lg border space-y-2 text-sm">
                        <h4 class="font-semibold text-gray-800 text-base">Ringkasan Estimasi Sewa</h4>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tanggal Selesai Sewa:</span>
                            <span class="font-semibold" x-text="endDateFormatted"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Biaya Sewa Garasi:</span>
                            <span class="font-semibold" x-text="formatRupiah(rentCost)"></span>
                        </div>
                        <div class="flex justify-between" x-show="vehicleType === 'mobil_listrik'" x-cloak>
                            <span class="text-gray-600">Biaya Pengisian Listrik (Full Charge):</span>
                            <span class="font-semibold" x-text="formatRupiah(electricityCost)"></span>
                        </div>
                        <div class="flex justify-between border-t pt-2 text-base font-bold text-indigo-700">
                            <span>Total Pembayaran:</span>
                            <span x-text="formatRupiah(totalCost)"></span>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>
                            Konfirmasi & Sewa Garasi
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>