<?php
namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DokterPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('dokter')
            ->path('dokter')
            ->colors([
                'primary' => Color::Green,
            ])
            ->login() // PASTIKAN INI ADA
            ->brandName('KLINIK PRATAMA HADIANA SEHAT') // TAMBAH INI
            ->discoverResources(in: app_path('Filament/Dokter/Resources'), for: 'App\\Filament\\Dokter\\Resources')
            ->discoverPages(in: app_path('Filament/Dokter/Pages'), for: 'App\\Filament\\Dokter\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                // SEMENTARA COMMENT MIDDLEWARE CUSTOM DULU
                // \App\Http\Middleware\EnsureDokterRole::class,
            ]);
    }
}