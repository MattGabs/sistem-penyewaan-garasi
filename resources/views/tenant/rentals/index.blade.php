<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Daftar & Riwayat Sewa Garasi
            </h2>
            @if (auth()->user()->verification_status === 'approved')
                <a href="{{ route('tenant.rentals.create') }}" class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                    + Sewa Garasi Baru
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b bg-gray-50">
                                <th class="p-3">Kode Garasi</th>
                                <th class="p-3">Kendaraan</th>
                                <th class="p-3">Paket & Periode</th>
                                <th class="p-3">Total Biaya</th>
                                <th class="p-3">Bukti Bayar</th>
                                <th class="p-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rentals as $rental)
                                <tr class="border-b">
                                    <td class="p-3 font-mono font-bold text-indigo-700">{{ $rental->garage->code }}</td>
                                    <td class="p-3">
                                        <div class="font-semibold">{{ $rental->vehicle_brand_type }}</div>
                                        <div class="text-xs text-gray-500 capitalize">
                                            {{ str_replace('_', ' ', $rental->vehicle_type) }}
                                            @if ($rental->plate_number)
                                                • <span class="font-mono">{{ $rental->plate_number }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="p-3 text-sm">
                                        <div class="capitalize font-medium">{{ $rental->rental_type }} ({{ $rental->duration_days }} hari)</div>
                                        <div class="text-xs text-gray-500">
                                            {{ \Carbon\Carbon::parse($rental->start_date)->format('d M Y') }} s/d
                                            {{ \Carbon\Carbon::parse($rental->end_date)->format('d M Y') }}
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <div class="font-semibold">Rp {{ number_format($rental->total_cost, 0, ',', '.') }}</div>
                                        @if ($rental->electricity_cost > 0)
                                            <div class="text-xs text-gray-500">Termasuk listrik Rp {{ number_format($rental->electricity_cost, 0, ',', '.') }}</div>
                                        @endif
                                    </td>
                                    <td class="p-3 text-sm">
                                        @foreach ($rental->payments as $payment)
                                            <div>
                                                <a href="{{ asset('storage/' . $payment->payment_proof_path) }}" target="_blank" class="text-indigo-600 underline hover:text-indigo-800">
                                                    Lihat Bukti ({{ ucfirst($payment->payment_type) }})
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
                                    <td colspan="6" class="p-4 text-center text-gray-500">Anda belum memiliki riwayat penyewaan garasi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>