<x-filament-panels::page>
    {{-- Status Alert --}}
    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
        <div class="flex items-center gap-3">
            <span class="text-2xl">🏥</span>
            <div>
                <h3 class="font-semibold text-green-800">Panel Dokter</h3>
                <p class="text-sm text-green-700">Audio sistem terpisah untuk panel dokter</p>
            </div>
        </div>
    </div>

    {{-- User Welcome Section --}}
    <div class="bg-white rounded-lg shadow border p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-x-4">
                <!-- Avatar dengan initial -->
                <div class="flex-shrink-0">
                    <div class="w-14 h-14 bg-gray-900 rounded-full flex items-center justify-center">
                        <span class="text-lg font-semibold text-white">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </span>
                    </div>
                </div>

                <!-- Text greeting -->
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">
                        Selamat Datang
                    </h1>
                    <p class="text-base text-gray-500">
                        {{ $user->name }}
                    </p>
                </div>
            </div>

            <!-- Tombol Keluar -->
            <div>
                <form action="{{ route('filament.dokter.auth.logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Quick Links Section --}}
    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <a href="{{ route('filament.dokter.resources.queues.index') }}" 
           class="block p-6 bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Kelola Antrian</h3>
                    <p class="text-sm text-gray-600">Lihat dan kelola antrian pasien</p>
                </div>
            </div>
        </a>

        <a href="{{ route('filament.dokter.resources.medical-records.index') }}" 
           class="block p-6 bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Rekam Medis</h3>
                    <p class="text-sm text-gray-600">Kelola rekam medis pasien</p>
                </div>
            </div>
        </a>

        <a href="{{ route('filament.dokter.resources.patients.index') }}" 
           class="block p-6 bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center gap-4">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Data Pasien</h3>
                    <p class="text-sm text-gray-600">Lihat data pasien terdaftar</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Debug button untuk test audio (hanya di development) --}}
    @if(app()->environment('local'))
    <div class="mt-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
        <h4 class="font-semibold mb-2">Debug Audio Panel Dokter:</h4>
        <button onclick="window.testDokterAudio('Test audio dari dashboard dokter')" 
                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
            Test Audio Dokter
        </button>
    </div>
    @endif
</x-filament-panels::page>