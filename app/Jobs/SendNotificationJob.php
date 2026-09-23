<?php

namespace App\Jobs;

use App\Contracts\WhatsAppProviderInterface;
use App\Models\Investigation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    public function __construct(public Investigation $investigation) {}

    public function handle(WhatsAppProviderInterface $whatsAppProvider): void
    {
        $user = $this->investigation->user;
        if ($user && !empty($user->whatsapp_number)) {
            $msg = "Pemberitahuan WebGuard: Investigasi untuk domain {$this->investigation->target_domain} ({$this->investigation->investigation_code}) telah selesai dianalisis. Laporan dan bukti digital dapat diakses pada platform.";
            $whatsAppProvider->sendMessage($user->whatsapp_number, $msg);
        }
    }
}
