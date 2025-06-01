<x-filament-panels::page>
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
</x-filament-panels::page>