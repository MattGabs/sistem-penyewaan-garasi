<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Verifikasi Pendaftar Baru
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b bg-gray-50">
                            <th class="p-3">NIK</th>
                            <th class="p-3">Nama</th>
                            <th class="p-3">No. HP</th>
                            <th class="p-3">Email</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tenants as $tenant)
                            <tr class="border-b">
                                <td class="p-3 font-mono">{{ $tenant->nik }}</td>
                                <td class="p-3">{{ $tenant->name }}</td>
                                <td class="p-3">{{ $tenant->phone }}</td>
                                <td class="p-3">{{ $tenant->email }}</td>
                                <td class="p-3">
                                    <span class="px-2 py-1 text-xs rounded font-semibold
                                        {{ $tenant->verification_status === 'approved' ? 'bg-green-100 text-green-800' : ($tenant->verification_status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                        {{ strtoupper($tenant->verification_status) }}
                                    </span>
                                </td>
                                <td class="p-3 flex gap-2">
                                    @if ($tenant->verification_status === 'pending')
                                        <form action="{{ route('admin.users.update', $tenant) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="verification_status" value="approved">
                                            <button type="submit" class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                                                Terima
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.users.update', $tenant) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="verification_status" value="rejected">
                                            <button type="submit" class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700">
                                                Tolak
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-gray-400 text-sm">Sudah diverifikasi</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-4 text-center text-gray-500">Belum ada pendaftar penyewa.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>