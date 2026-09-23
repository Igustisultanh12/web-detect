<?php

namespace App\Contracts;

interface WhatsAppProviderInterface
{
    /**
     * Send WhatsApp text notification.
     * Note: strictly NO passwords, TOTP secrets, or sensitive IDs (KTP/KTA) via WhatsApp.
     */
    public function sendMessage(string $recipientPhone, string $message): array;
}
