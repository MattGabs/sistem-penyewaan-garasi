<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (auth()->user()->role === 'penyewa')
                @if (auth()->user()->verification_status === 'pending')
                    <div class="p-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 rounded-r-lg shadow-sm">
                        <p class="font-bold">Menunggu Verifikasi Admin</p>
                        <p class="text-sm mt-1">Akun Anda sedang dalam proses peninjauan. Anda baru dapat menyewa garasi setelah akun diverifikasi oleh Admin.</p>
                    </div>
                @elseif (auth()->user()->verification_status === 'approved')
                    <div class="p-4 bg-green-100 border-l-4 border-green-500 text-green-800 rounded-r-lg shadow-sm">
                        <p class="font-bold">Akun Terverifikasi</p>
                        <p class="text-sm mt-1">Selamat! Akun Anda telah disetujui oleh Admin dan siap digunakan untuk menyewa garasi.</p>
                    </div>
                @elseif (auth()->user()->verification_status === 'rejected')
                    <div class="p-4 bg-red-100 border-l-4 border-red-500 text-red-800 rounded-r-lg shadow-sm">
                        <p class="font-bold">Pendaftaran Ditolak</p>
                        <p class="text-sm mt-1">Maaf, pendaftaran akun Anda ditolak oleh Admin. Silakan hubungi pengelola untuk informasi lebih lanjut.</p>
                    </div>
                @endif
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        Selamat datang, <span class="font-semibold">{{ auth()->user()->name }}</span>! Gunakan menu navigasi di atas untuk mengelola sistem.
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>