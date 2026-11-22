<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Monitoring & Laporan Penyewaan Garasi
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

            <!-- Ringkasan Statistik & Laporan -->
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
                    <p class="text-xs text-gray-500 uppercase font-semibold">Sewa Aktif</p>
                    <p class="text-2xl font-bold text-indigo-600 mt-1">{{ $stats['active_rentals'] }}</p>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-sm border">
                    <p class="text-xs text-gray-500 uppercase font-semibold">Total Pendapatan</p>
                    <p class="text-xl font-bold text-emerald-700 mt-1">Rp {{ number_format($stats['total_revenue'], 0, ',', '.') }}</p>
                </div>
            </div>

            <!-- Tabel Seluruh Transaksi Sewa -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Daftar Transaksi Penyewaan</h3>

                <div class="overflow-x-auto">
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
                                <th class="p-3">Aksi</th>
                            </tr>
                        </thead>
                        @forelse ($rentals as $rental)
                            <tbody x-data="{
                                showExtend: false,
                                rentalType: 'harian',
                                duration: 1,
                                dailyRate: {{ $rental->garage->daily_rate }},
                                get extensionCost() {
                                    let dur = Math.max(1, Number(this.duration) || 1);
                                    return this.rentalType === 'harian'
                                        ? this.dailyRate * dur
                                        : (this.dailyRate * 25) * dur;
                                },
                                formatRupiah(num) {
                                    return 'Rp ' + Number(num).toLocaleString('id-ID');
                                }
                            }" class="border-b">
                                <tr>
                                    <td class="p-3 font-mono font-bold text-indigo-700">{{ $rental->garage->code }}</td>
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
                                    <td class="p-3">
                                        @if ($rental->status === 'active')
                                            <button type="button" @click="showExtend = !showExtend" class="px-3 py-1 bg-indigo-600 text-white text-xs font-semibold rounded hover:bg-indigo-700">
                                                Perpanjang
                                            </button>
                                        @else
                                            <span class="text-gray-400 text-xs">-</span>
                                        @endif
                                    </td>
                                </tr>

                                <!-- Form Perpanjangan Sewa oleh Admin -->
                                @if ($rental->status === 'active')
                                    <tr x-show="showExtend" x-cloak class="bg-indigo-50/60">
                                        <td colspan="8" class="p-4">
                                            <form action="{{ route('admin.rentals.extend', $rental) }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                                                @csrf
                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-700">Paket Perpanjangan</label>
                                                    <select name="rental_type" x-model="rentalType" class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm">
                                                        <option value="harian">Harian</option>
                                                        <option value="bulanan">Bulanan (25x Harian)</option>
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-700" x-text="rentalType === 'harian' ? 'Tambah Durasi (Hari)' : 'Tambah Durasi (Bulan)'"></label>
                                                    <input type="number" name="duration" min="1" max="365" x-model="duration" class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm" required>
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-700">Bukti Bayar Perpanjangan</label>
                                                    <input type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf" class="mt-1 block w-full text-xs text-gray-600" required>
                                                </div>

                                                <div class="flex flex-col gap-1">
                                                    <div class="text-xs font-bold text-indigo-700">
                                                        Biaya Tambahan: <span x-text="formatRupiah(extensionCost)"></span>
                                                    </div>
                                                    <div class="flex gap-2">
                                                        <button type="submit" class="px-3 py-2 bg-green-600 text-white text-xs font-semibold rounded hover:bg-green-700">
                                                            Konfirmasi Perpanjang
                                                        </button>
                                                        <button type="button" @click="showExtend = false" class="px-3 py-2 bg-gray-300 text-gray-700 text-xs font-semibold rounded hover:bg-gray-400">
                                                            Batal
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        @empty
                            <tbody>
                                <tr>
                                    <td colspan="8" class="p-4 text-center text-gray-500">Belum ada transaksi penyewaan.</td>
                                </tr>
                            </tbody>
                        @endforelse
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>