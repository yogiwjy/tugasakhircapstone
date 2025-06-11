<?php
namespace App\Providers;

use App\Services\QueueService;
use App\Services\ThermalPrinterService;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ThermalPrinterService::class, function ($app) {
            return new ThermalPrinterService();
        });

        $this->app->singleton(QueueService::class, function ($app) {
            return new QueueService();
        });
    }

    public function boot(): void
    {
        FilamentAsset::register([
            Js::make('thermal-printer', asset('js/thermal-printer.js')),
            Js::make('call-queue', asset('js/call-queue.js')),
            Js::make('queue-audio', asset('js/queue-audio.js')), // Audio handler global
        ]);
    }   
}