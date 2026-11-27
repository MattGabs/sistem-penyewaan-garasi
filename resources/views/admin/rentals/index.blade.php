<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Perpanjangan & Laporan Penyewaan Garasi
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 bg-red-100 text-red-800 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 bg-red-100 text-red-800 rounded-lg">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Ringkasan Statistik -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-white p-4 rounded-lg shadow-sm border">
                    <p class="text-xs text-gray-500 uppercase font-semibold">Total Garasi</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['total_garages'] }}</p>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-sm border">
                    <p class="text-xs text-gray-500 uppercase font-semibold">Garasi Tersedia</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['available_garages'] }}</p>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-sm border">
                    <p class="text-xs text-gray-500 uppercase font-semibold">Garasi Terpakai</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">{{ $stats['used_garages'] }}</p>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-sm border">
                    <p class="text-xs text-gray-500 uppercase font-semibold">Transaksi (Filter)</p>
                    <p class="text-2xl font-bold text-indigo-600 mt-1">{{ $stats['filtered_rentals'] }}</p>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-sm border">
                    <p class="text-xs text-gray-500 uppercase font-semibold">Total Pendapatan</p>
                    <p class="text-xl font-bold text-emerald-700 mt-1">Rp {{ number_format($stats['filtered_revenue'], 0, ',', '.') }}</p>
                </div>
            </div>

            <!-- Poin 4: Perpanjang Status Garasi (Daftar Garasi yang Sedang Disewa Hari Ini) -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6"
                 x-data="{
                    activeList: @js($activeRentalsToday->map(fn ($r) => [
                        'id' => $r->id,
                        'code' => $r->garage->code,
                        'tenant' => $r->user->name,
                        'end_date' => $r->end_date->toDateString(),
                        'daily_rate' => $r->garage->daily_rate,
                    ])->values()),
                    selectedRentalId: '{{ $activeRentalsToday->first()?->id ?? '' }}',
                    newEndDate: '',

                    get selectedRental() {
                        return this.activeList.find(item => String(item.id) === String(this.selectedRentalId)) || null;
                    },

                    get minNextDate() {
                        if (!this.selectedRental) return '';
                        const [y, m, d] = this.selectedRental.end_date.split('-').map(Number);
                        const dt = new Date(y, m - 1, d + 1);
                        const yyyy = dt.getFullYear();
                        const mm = String(dt.getMonth() + 1).padStart(2, '0');
                        const dd = String(dt.getDate()).padStart(2, '0');
                        return `${yyyy}-${mm}-${dd}`;
                    },

                    get addedDays() {
                        if (!this.selectedRental || !this.newEndDate) return 0;
                        const [y1, m1, d1] = this.selectedRental.end_date.split('-').map(Number);
                        const [y2, m2, d2] = this.newEndDate.split('-').map(Number);
                        const start = new Date(y1, m1 - 1, d1);
                        const end = new Date(y2, m2 - 1, d2);
                        const diff = Math.round((end - start) / (1000 * 60 * 60 * 24));
                        return diff > 0 ? diff : 0;
                    },

                    get remainingCost() {
                        if (!this.selectedRental || this.addedDays <= 0) return 0;
                        const rate = Number(this.selectedRental.daily_rate);
                        const months = Math.floor(this.addedDays / 30);
                        const remDays = this.addedDays % 30;
                        return (months * rate * 25) + (remDays * rate);
                    },

                    formatRupiah(num) {
                        return 'Rp ' + Number(num).toLocaleString('id-ID');
                    }
                 }">
                <h3 class="text-base font-semibold text-gray-800 mb-1">Perpanjang Status Garasi (Sewa Aktif Hari Ini)</h3>
                <p class="text-xs text-gray-500 mb-4">Pilih salah satu garasi yang sedang disewa hari ini ({{ now()->format('d M Y') }}), tentukan tanggal selesai baru, dan unggah bukti pembayaran.</p>

                @if ($activeRentalsToday->isEmpty())
                    <p class="text-sm text-gray-500">Tidak ada garasi yang sedang disewa pada hari ini.</p>
                @else
                    <form :action="'{{ url('admin/monitoring-sewa') }}/' + selectedRentalId + '/perpanjang'" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Pilih Garasi Sedang Disewa</label>
                            <select x-model="selectedRentalId" @change="newEndDate = ''" class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm" required>
                                <template x-for="item in activeList" :key="item.id">
                                    <option :value="item.id" x-text="item.code + ' - ' + item.tenant + ' (s/d ' + item.end_date + ')'"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Tanggal Selesai Sewa Baru</label>
                            <input type="date" name="new_end_date" x-model="newEndDate" :min="minNextDate" class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm" required>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Upload Bukti Bayar (Gambar)</label>
                            <input type="file" name="payment_proof" accept=".jpg,.jpeg,.png" class="mt-1 block w-full text-xs text-gray-600" required>
                        </div>

                        <div class="flex flex-col gap-1">
                            <div class="text-xs font-bold text-indigo-700">
                                Sisa Biaya (<span x-text="addedDays"></span> hari): <span x-text="formatRupiah(remainingCost)"></span>
                            </div>
                            <button type="submit" :disabled="addedDays <= 0" class="px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700 disabled:opacity-50">
                                Simpan Perpanjangan
                            </button>
                        </div>
                    </form>
                @endif
            </div>

            <!-- Poin 5: Form Filter Laporan Sewa -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3">Laporan Persewaan Garasi</h3>

                <form method="GET" action="{{ route('admin.rentals.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Tahun</label>
                        <select name="year" class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm">
                            <option value="">Semua Tahun</option>
                            @for ($y = now()->year - 1; $y <= now()->year + 2; $y++)
                                <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700">Bulan</label>
                        <select name="month" class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm">
                            <option value="">Semua Bulan</option>
                            @foreach ([
                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                            ] as $num => $monthName)
                                <option value="{{ $num }}" {{ request('month') == $num ? 'selected' : '' }}>{{ $monthName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700">Hari (Tanggal)</label>
                        <select name="day" class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm">
                            <option value="">Semua Tanggal</option>
                            @for ($d = 1; $d <= 31; $d++)
                                <option value="{{ $d }}" {{ request('day') == $d ? 'selected' : '' }}>Tanggal {{ $d }}</option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700">Tipe Garasi</label>
                        <select name="garage_type" class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm">
                            <option value="">Semua Tipe</option>
                            <option value="sepeda" {{ request('garage_type') === 'sepeda' ? 'selected' : '' }}>Sepeda</option>
                            <option value="mobil" {{ request('garage_type') === 'mobil' ? 'selected' : '' }}>Mobil</option>
                            <option value="mobil_listrik" {{ request('garage_type') === 'mobil_listrik' ? 'selected' : '' }}>Mobil Listrik</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700">Penyewa</label>
                        <select name="user_id" class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm">
                            <option value="">Semua Penyewa</option>
                            @foreach ($tenants as $tenant)
                                <option value="{{ $tenant->id }}" {{ request('user_id') == $tenant->id ? 'selected' : '' }}>
                                    {{ $tenant->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                            Tampilkan
                        </button>
                        <a href="{{ route('admin.rentals.index') }}" class="px-3 py-2 bg-gray-200 text-gray-700 text-sm font-semibold rounded-md hover:bg-gray-300">
                            Reset
                        </a>
                    </div>
                </form>

                <!-- Tabel Hasil Laporan Persewaan -->
                <div class="overflow-x-auto mt-6">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b bg-gray-50">
                                <th class="p-3">Garasi</th>
                                <th class="p-3">Penyewa</th>
                                <th class="p-3">Kendaraan</th>
                                <th class="p-3">Periode Sewa</th>
                                <th class="p-3">Total Biaya</th>
                                <th class="p-3">Bukti Bayar</th>
                                <th class="p-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rentals as $rental)
                                <tr class="border-b">
                                    <td class="p-3">
                                        <div class="font-mono font-bold text-indigo-700">{{ $rental->garage->code }}</div>
                                        <div class="text-xs text-gray-500 capitalize">{{ str_replace('_', ' ', $rental->garage->type) }}</div>
                                    </td>
                                    <td class="p-3 text-sm">
                                        <div class="font-semibold">{{ $rental->user->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $rental->user->phone }}</div>
                                    </td>
                                    <td class="p-3 text-sm">
                                        <div class="font-semibold">{{ $rental->vehicle_brand_type }}</div>
                                        <div class="text-xs text-gray-500 capitalize">
                                            {{ str_replace('_', ' ', $rental->vehicle_type) }}
                                            @if ($rental->plate_number)
                                                • <span class="font-mono">{{ $rental->plate_number }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="p-3 text-sm">
                                        <div class="capitalize">{{ $rental->rental_type }} ({{ $rental->duration_days }} hari)</div>
                                        <div class="text-xs text-gray-500">
                                            {{ \Carbon\Carbon::parse($rental->start_date)->format('d M Y') }} -
                                            {{ \Carbon\Carbon::parse($rental->end_date)->format('d M Y') }}
                                        </div>
                                    </td>
                                    <td class="p-3 font-semibold text-sm">
                                        Rp {{ number_format($rental->total_cost, 0, ',', '.') }}
                                    </td>
                                    <td class="p-3 text-xs space-y-1">
                                        @foreach ($rental->payments as $payment)
                                            <div>
                                                <a href="{{ asset('storage/' . $payment->payment_proof_path) }}" target="_blank" class="text-indigo-600 underline hover:text-indigo-800">
                                                    {{ $payment->payment_type === 'initial' ? 'Awal' : 'Perpanjang' }} (Rp {{ number_format($payment->amount, 0, ',', '.') }})
                                                </a>
                                            </div>
                                        @endforeach
                                    </td>
                                    <td class="p-3">
                                        <span class="px-2 py-1 text-xs rounded font-semibold {{ $rental->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' }}">
                                            {{ strtoupper($rental->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-4 text-center text-gray-500">Tidak ada data penyewaan yang sesuai dengan filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>