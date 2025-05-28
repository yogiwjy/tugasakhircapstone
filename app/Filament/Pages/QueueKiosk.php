<?php

namespace App\Filament\Pages;

use App\Models\Service;
use App\Services\QueueService;
use App\Services\ThermalPrinterService;
use Filament\Pages\Page;

class QueueKiosk extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    // Ganti label sidebar di sini
    protected static ?string $navigationLabel = 'Ambil Antrian';

    protected static string $view = 'filament.pages.queue-kiosk';

    protected static string $layout= 'filament.layouts.base-kiosk';

    protected ThermalPrinterService $thermalPrinterService;

    protected QueueService $queueService;

    public function __construct()
    {
        $this->thermalPrinterService = app(ThermalPrinterService::class);

        $this->queueService = app(QueueService::class);
    }

    public function getViewData(): array
    {
        return [
            'services' => Service::where('is_active', true)->get()
        ];
    }

    public function print($serviceId)
    {
        $newQueue = $this->queueService->addQueue($serviceId);

        $text = $this->thermalPrinterService->createText([
            ['text' => 'Klinik Pratama Hadiana Sehat', 'align' => 'center'],
            ['text' => 'Jl. Raya Banjaran Barat No.658A', 'align' => 'center'],
            ['text' => '-----------------', 'align' => 'center'],
            ['text' => 'NOMOR ANTRIAN', 'align' => 'center'],
            ['text' => $newQueue->Number, 'align' => 'center', 'style' => 'double'],
            ['text' => $newQueue->created_at->format('d-M-Y H:i'), 'align' => 'center'],
            ['text' => '-----------------', 'align' => 'center'],
            ['text' => 'Mohon menunggu', 'align' => 'center']
        ]);

        $this->dispatch("print-start", $text);
    }
}
