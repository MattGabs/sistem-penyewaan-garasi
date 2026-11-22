<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Form Sewa Garasi Baru
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6"
                 x-data="{
                    garages: @js($availableGarages),
                    vehicleType: '{{ old('vehicle_type', 'sepeda') }}',
                    garageType: '{{ old('garage_type', 'sepeda') }}',
                    selectedGarageId: '{{ old('garage_id', '') }}',
                    rentalType: '{{ old('rental_type', 'harian') }}',
                    duration: {{ old('duration', 1) }},
                    startDate: '{{ old('start_date', now()->toDateString()) }}',
                    batteryCapacity: {{ old('battery_capacity_kwh', 0) }},
                    remainingBattery: {{ old('remaining_battery_kwh', 0) }},

                    get allowedGarageOptions() {
                        if (this.vehicleType === 'sepeda' || this.vehicleType === 'sepeda_motor') {
                            return [{ value: 'sepeda', label: 'Garasi Sepeda' }];
                        }
                        if (this.vehicleType === 'mobil') {
                            return [{ value: 'mobil', label: 'Garasi Mobil' }];
                        }
                        return [
                            { value: 'mobil', label: 'Garasi Mobil' },
                            { value: 'mobil_listrik', label: 'Garasi Mobil Listrik (Wajib Isi Listrik Penuh)' }
                        ];
                    },

                    onVehicleChange() {
                        const allowed = this.allowedGarageOptions.map(o => o.value);
                        if (!allowed.includes(this.garageType)) {
                            this.garageType = allowed[0];
                        }
                        this.syncSelectedGarage();
                    },

                    get maxDailyDuration() {
                        if (!this.startDate) return 30;
                        const [y, m] = this.startDate.split('-').map(Number);
                        if (!y || !m) return 30;
                        const daysInMonth = new Date(y, m, 0).getDate();
                        return daysInMonth - 1;
                    },

                    get maxDuration() {
                        return this.rentalType === 'bulanan' ? 1 : this.maxDailyDuration;
                    },

                    onDurationConstraintChange() {
                        if (this.rentalType === 'bulanan') {
                            this.duration = 1;
                        } else if (Number(this.duration) > this.maxDailyDuration) {
                            this.duration = this.maxDailyDuration;
                        }
                    },

                    get filteredGarages() {
                        return this.garages.filter(g => g.type === this.garageType);
                    },

                    syncSelectedGarage() {
                        const list = this.filteredGarages;
                        if (!list.some(g => String(g.id) === String(this.selectedGarageId))) {
                            this.selectedGarageId = list.length > 0 ? String(list[0].id) : '';
                        }
                    },

                    get activeGarage() {
                        return this.filteredGarages.find(g => String(g.id) === String(this.selectedGarageId)) || null;
                    },

                    get clampedDuration() {
                        return Math.min(this.maxDuration, Math.max(1, Number(this.duration) || 1));
                    },

                    get endDateFormatted() {
                        if (!this.startDate) return '-';
                        const [y, m, d] = this.startDate.split('-').map(Number);
                        const date = new Date(y, m - 1, d);
                        const dur = this.clampedDuration;

                        if (this.rentalType === 'harian') {
                            date.setDate(date.getDate() + dur);
                        } else {
                            const targetMonth = date.getMonth() + dur;
                            const originalDay = date.getDate();
                            date.setMonth(targetMonth);
                            if (date.getDate() < originalDay) {
                                date.setDate(0);
                            }
                        }
                        return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
                    },

                    get rentCost() {
                        if (!this.activeGarage) return 0;
                        const rate = Number(this.activeGarage.daily_rate);
                        const dur = this.clampedDuration;
                        return this.rentalType === 'harian'
                            ? rate * dur
                            : (rate * 25) * dur;
                    },

                    get electricityCost() {
                        if (!this.activeGarage || this.garageType !== 'mobil_listrik') return 0;
                        const kwhNeeded = Math.max(0, (Number(this.batteryCapacity) || 0) - (Number(this.remainingBattery) || 0));
                        return Math.round(kwhNeeded * Number(this.activeGarage.electricity_rate_per_kwh || 0));
                    },

                    get totalCost() {
                        return this.rentCost + this.electricityCost;
                    },

                    formatRupiah(num) {
                        return 'Rp ' + Number(num).toLocaleString('id-ID');
                    }
                 }"
                 x-init="onVehicleChange(); onDurationConstraintChange()">

                <form action="{{ route('tenant.rentals.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <!-- Bagian 1: Detail Kendaraan -->
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 border-b pb-2 mb-4">1. Detail Kendaraan</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tipe Kendaraan</label>
                                <select name="vehicle_type" x-model="vehicleType" @change="onVehicleChange()" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                    <option value="sepeda">Sepeda</option>
                                    <option value="sepeda_motor">Sepeda Motor</option>
                                    <option value="mobil">Mobil</option>
                                    <option value="mobil_listrik">Mobil Listrik</option>
                                </select>
                            </div>

                            <div x-show="vehicleType !== 'sepeda'" x-cloak>
                                <label class="block text-sm font-medium text-gray-700">Nomor Plat Kendaraan</label>
                                <input type="text" name="plate_number" value="{{ old('plate_number') }}" placeholder="Contoh: L 1234 AB" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" :required="vehicleType !== 'sepeda'">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Merek & Tipe Kendaraan</label>
                                <input type="text" name="vehicle_brand_type" value="{{ old('vehicle_brand_type') }}" placeholder="Contoh: Wuling Air EV / Honda Vario" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            </div>

                            <div x-show="vehicleType === 'mobil_listrik'" x-cloak>
                                <label class="block text-sm font-medium text-gray-700">Kapasitas Listrik Baterai (kWh)</label>
                                <input type="number" step="0.1" min="1" name="battery_capacity_kwh" x-model="batteryCapacity" placeholder="Contoh: 26.7" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" :required="vehicleType === 'mobil_listrik'">
                            </div>
                        </div>
                    </div>

                    <!-- Bagian 2: Pilihan Tipe & Unit Garasi Tersedia -->
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 border-b pb-2 mb-4">2. Pemilihan Tipe & Unit Garasi Tersedia</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tipe Garasi (Sesuai Kendaraan)</label>
                                <select name="garage_type" x-model="garageType" @change="syncSelectedGarage()" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                    <template x-for="opt in allowedGarageOptions" :key="opt.value">
                                        <option :value="opt.value" x-text="opt.label" :selected="opt.value === garageType"></option>
                                    </template>
                                </select>
                            </div>

                            <div x-show="garageType === 'mobil_listrik'" x-cloak>
                                <label class="block text-sm font-medium text-gray-700">Sisa Kapasitas Baterai Saat Ini (kWh)</label>
                                <input type="number" step="0.1" min="0" name="remaining_battery_kwh" x-model="remainingBattery" placeholder="Contoh: 6.7" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" :required="garageType === 'mobil_listrik'">
                                <p class="text-xs text-gray-500 mt-1">Mobil listrik di garasi mobil listrik akan diisi hingga penuh.</p>
                            </div>
                        </div>

                        <!-- Daftar Garasi Tersedia -->
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Daftar Garasi Tersedia Pada Tanggal Hari Ini ({{ now()->format('d M Y') }})</label>

                            <template x-if="filteredGarages.length === 0">
                                <div class="p-3 bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm rounded-md">
                                    Maaf, saat ini tidak ada unit garasi yang tersedia untuk tipe yang dipilih.
                                </div>
                            </template>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3" x-show="filteredGarages.length > 0">
                                <template x-for="g in filteredGarages" :key="g.id">
                                    <label class="border rounded-lg p-3 cursor-pointer flex items-start gap-3 transition"
                                           :class="String(selectedGarageId) === String(g.id) ? 'border-indigo-600 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300'">
                                        <input type="radio" name="garage_id" :value="g.id" x-model="selectedGarageId" class="mt-1 text-indigo-600" required>
                                        <div class="text-sm">
                                            <div class="font-mono font-bold text-indigo-700" x-text="g.code"></div>
                                            <div class="text-xs text-gray-600">Harian: <span x-text="formatRupiah(g.daily_rate)"></span></div>
                                            <div class="text-xs text-gray-600">Bulanan: <span x-text="formatRupiah(g.daily_rate * 25)"></span></div>
                                            <template x-if="g.type === 'mobil_listrik'">
                                                <div class="text-xs text-emerald-700">Listrik: <span x-text="formatRupiah(g.electricity_rate_per_kwh)"></span>/kWh</div>
                                            </template>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Bagian 3: Paket & Durasi Sewa -->
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 border-b pb-2 mb-4">3. Paket, Periode & Pembayaran</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tipe Sewa</label>
                                <select name="rental_type" x-model="rentalType" @change="onDurationConstraintChange()" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                    <option value="harian">Harian</option>
                                    <option value="bulanan">Bulanan (Maks. 1 Bulan • 25x Harga Harian)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700"
                                       x-text="rentalType === 'harian' ? 'Durasi Sewa (Hari, Maks. ' + maxDailyDuration + ' Hari)' : 'Durasi Sewa (Bulan, Maks. 1 Bulan)'"></label>
                                <input type="number" name="duration" min="1" :max="maxDuration" x-model="duration" :readonly="rentalType === 'bulanan'" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" :class="rentalType === 'bulanan' ? 'bg-gray-100 text-gray-600' : ''" required>
                                <p class="text-xs text-gray-500 mt-1"
                                   x-text="rentalType === 'harian'
                                       ? 'Maksimal jumlah hari dalam bulan tersebut dikurangi 1 (' + maxDailyDuration + ' hari).'
                                       : 'Sewa bulanan awal maksimal 1 bulan. Perpanjangan masa sewa dilakukan oleh Admin.'"></p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tanggal Mulai Sewa</label>
                                <input type="date" name="start_date" x-model="startDate" @change="onDurationConstraintChange()" min="{{ now()->toDateString() }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tanggal Selesai Sewa (Otomatis)</label>
                                <input type="text" :value="endDateFormatted" class="mt-1 block w-full bg-gray-100 border-gray-300 rounded-md shadow-sm font-semibold text-gray-800" readonly>
                            </div>
                        </div>

                        <!-- Ringkasan Total Biaya -->
                        <div class="mt-4 p-4 bg-indigo-50 border border-indigo-200 rounded-lg space-y-1">
                            <div class="flex justify-between text-sm text-gray-700">
                                <span>Biaya Sewa Garasi:</span>
                                <span class="font-medium" x-text="formatRupiah(rentCost)"></span>
                            </div>
                            <div class="flex justify-between text-sm text-gray-700" x-show="garageType === 'mobil_listrik'">
                                <span>Biaya Pengisian Listrik Hingga Penuh:</span>
                                <span class="font-medium" x-text="formatRupiah(electricityCost)"></span>
                            </div>
                            <div class="flex justify-between text-base font-bold text-indigo-800 border-t border-indigo-200 pt-2 mt-2">
                                <span>Total Biaya Sewa yang Harus Dibayar:</span>
                                <span x-text="formatRupiah(totalCost)"></span>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">Upload Bukti Pelunasan Biaya (JPG/PNG/PDF)</label>
                            <input type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf" class="mt-1 block w-full text-sm text-gray-600" required>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <a href="{{ route('tenant.rentals.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-semibold rounded-md hover:bg-gray-300">
                            Batal
                        </a>
                        <button type="submit" :disabled="filteredGarages.length === 0" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700 disabled:opacity-50">
                            Simpan & Sewa Garasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>