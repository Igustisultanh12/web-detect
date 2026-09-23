<?php

namespace App\Services\Takedown;

use App\Contracts\WhatsAppProviderInterface;
use App\Models\TakedownCase;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TakedownNotificationService
{
    public function __construct(protected WhatsAppProviderInterface $whatsAppProvider) {}

    /**
     * Send case status notification to assigned personnel via WhatsApp (Sisinden style) and Email (Sisfoperskc style).
     */
    public function notifyCaseStatus(TakedownCase $case, string $event, ?string $additionalNote = null): void
    {
        $officer = $case->assignedOfficer ?: $case->creator;
        if (!$officer) {
            return;
        }

        // 1. Send WhatsApp via Sisinden intelligence alert format
        if (!empty($officer->whatsapp_number)) {
            $this->sendSisindenWhatsAppAlert($officer, $case, $event, $additionalNote);
        }

        // 2. Send Email via Sisfoperskc institutional layout
        if (!empty($officer->email)) {
            $this->sendSisfopersEmailNotification($officer, $case, $event, $additionalNote);
        }
    }

    /**
     * Official WhatsApp Notification in Sisinden (Detasemen Intelijen) Format.
     */
    public function sendSisindenWhatsAppAlert(User $officer, TakedownCase $case, string $event, ?string $note = null): array
    {
        $message = $this->formatSisindenWhatsAppAlert($case, $officer, $note);
        return $this->whatsAppProvider->sendMessage($officer->whatsapp_number, $message);
    }

    /**
     * Format Sisinden-style WhatsApp alert message.
     */
    public function formatSisindenWhatsAppAlert(TakedownCase $case, ?User $officer = null, ?string $note = null): string
    {
        $targetOfficer = $officer ?: ($case->assignedOfficer ?: $case->creator);
        $pangkat = $targetOfficer?->rank?->name ?? 'Personel';
        $nama = $targetOfficer?->name ?? 'Penyidik';
        $slaText = $case->next_follow_up_at
            ? $case->next_follow_up_at->translatedFormat('d M Y, H:i') . ' WIB'
            : ($case->provider?->sla_hours ? "{$case->provider->sla_hours} Jam dari Pengiriman" : 'Menunggu respons awal');

        $statusLabel = match ($case->status) {
            'READY_TO_SUBMIT' => 'SIAP DIAJUKAN (Ready to Submit)',
            'SUBMITTED' => 'LAPORAN TERKIRIM (Submitted to Provider)',
            'ACKNOWLEDGED' => 'DIKONFIRMASI PROVIDER (Acknowledged / Ticket Open)',
            'UNDER_REVIEW' => 'DALAM PENINJAUAN (Under Review)',
            'ADDITIONAL_INFORMATION_REQUESTED' => 'PERMINTAAN INFORMASI TAMBAHAN',
            'ACTION_TAKEN' => 'TINDAKAN SELESAI / WEBSITE DITAKEDOWN',
            'REJECTED' => 'LAPORAN DITOLAK PROVIDER',
            'CLOSED' => 'KASUS DITUTUP (Closed)',
            default => $case->status,
        };

        $unitName = $targetOfficer?->unit?->name ?? 'Detasemen Intelijen Cyber';

        $message = "🚨 *[ALERT INTELIJEN - PERMOHONAN TAKEDOWN]*\n"
                 . "*No. Kasus:* *{$case->case_number}*\n"
                 . "Klasifikasi: *[PERINGATAN OPERASIONAL DUKUNGAN HUKUM]*\n\n"
                 . "Kepada Yth:\n"
                 . "*{$pangkat} {$nama}*\n"
                 . "Satuan: {$unitName}\n\n"
                 . "*RINCIAN PENANGANAN WEBSITE/DOMAIN:*\n"
                 . "• *Target Domain:* *{$case->target_domain}*\n"
                 . "• Kategori Dugaan: {$case->category}\n"
                 . "• *Status:* *{$statusLabel}*\n"
                 . "• Provider / Otoritas: " . ($case->provider_name ?: 'Belum ditentukan') . "\n"
                 . "• No. Tiket Provider: " . ($case->external_reference_number ?: '-') . "\n\n"
                 . "*BATAS WAKTU TINDAK LANJUT (SLA):*\n"
                 . "Jatuh Tempo: *{$slaText}*\n";

        if (!empty($note)) {
            $message .= "\n*Catatan Operasional:*\n_{$note}_\n";
        }

        $message .= "\nMohon segera lakukan peninjauan berkala pada Dashboard WebGuard Investigasi.\n\n"
                 . "_Pesan ini dikirimkan otomatis secara terenkripsi oleh Detasemen Intelijen Cyber / WebGuard._";

        return $message;
    }

    /**
     * Official Email Notification in Sisfoperskc Institutional HTML Format.
     */
    public function sendSisfopersEmailNotification(User $officer, TakedownCase $case, string $event, ?string $note = null): void
    {
        try {
            $html = $this->formatSisfoperskcHtmlEmail($case, $officer, $note);

            Mail::html($html, function ($msg) use ($officer, $case) {
                $msg->to($officer->email)
                    ->subject("[WebGuard Incident Response] Penanganan Kasus Takedown {$case->case_number} - {$case->target_domain}");
            });
        } catch (\Exception $e) {
            Log::error("Failed to send Sisfoperskc email notification: " . $e->getMessage());
        }
    }

    /**
     * Format Sisfoperskc-style HTML email body.
     */
    public function formatSisfoperskcHtmlEmail(TakedownCase $case, ?User $officer = null, ?string $note = null): string
    {
        $targetOfficer = $officer ?: ($case->assignedOfficer ?: $case->creator);
        $pangkat = $targetOfficer?->rank?->name ?? '';
        $salutation = !empty($pangkat) ? "{$pangkat} {$targetOfficer->name}" : ($targetOfficer?->name ?: 'Penyidik');
        $caseUrl = config('app.url') . "/takedown/cases/{$case->uuid}";

        return $this->renderSisfopersEmailHtml($salutation, $case, $note, $caseUrl);
    }

    /**
     * Generates Sisfoperskc-style HTML email template.
     */
    protected function renderSisfopersEmailHtml(string $salutation, TakedownCase $case, ?string $note, string $caseUrl): string
    {
        $slaText = $case->next_follow_up_at
            ? $case->next_follow_up_at->translatedFormat('d F Y, H:i') . ' WIB'
            : 'Menunggu konfirmasi tiket';

        $noteSection = !empty($note) ? "
        <div style='background-color: #f1f5f9; border-left: 4px solid #2563eb; padding: 14px 18px; margin: 18px 0; border-radius: 6px; font-size: 13px; color: #1e293b;'>
            <strong>Catatan Tambahan Penyidik:</strong><br>{$note}
        </div>" : "";

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Pemberitahuan Kasus Takedown</title>
        </head>
        <body style='font-family: Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 30px 15px;'>
            <div style='max-width: 620px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;'>
                
                <!-- HEADER (SISFOPERSKC DARK BANNER WITH BLUE BORDER) -->
                <div style='background-color: #0f172a; padding: 28px 24px; text-align: center; border-bottom: 4px solid #2563eb;'>
                    <h1 style='color: #ffffff; margin: 0; font-size: 19px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase;'>
                        WEBGUARD INVESTIGASI & INCIDENT RESPONSE
                    </h1>
                    <p style='color: #94a3b8; margin: 6px 0 0 0; font-size: 11px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase;'>
                        SISTEM PENANGANAN INSIDEN & TAKEDOWN KONTEN ILEGAL
                    </p>
                </div>

                <!-- CONTENT BODY -->
                <div style='padding: 35px 30px; color: #334155;'>
                    <p style='font-size: 15px; color: #0f172a; margin-top: 0; font-weight: 700;'>
                        Yth. {$salutation},
                    </p>
                    
                    <p style='font-size: 14px; line-height: 1.6; color: #475569;'>
                        Berikut adalah pemberitahuan resmi pembaruan status penanganan laporan takedown website/domain bermasalah:
                    </p>

                    <!-- CASE DATA CARD -->
                    <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin: 20px 0;'>
                        <table style='width: 100%; border-collapse: collapse; font-size: 13px;'>
                            <tr>
                                <td style='padding: 6px 0; color: #64748b; width: 160px;'>Nomor Kasus:</td>
                                <td style='padding: 6px 0; font-weight: 700; color: #2563eb; font-family: monospace;'>{$case->case_number}</td>
                            </tr>
                            <tr>
                                <td style='padding: 6px 0; color: #64748b;'>Target Domain:</td>
                                <td style='padding: 6px 0; font-weight: 700; color: #0f172a;'>{$case->target_domain}</td>
                            </tr>
                            <tr>
                                <td style='padding: 6px 0; color: #64748b;'>Kategori Pelanggaran:</td>
                                <td style='padding: 6px 0; color: #0f172a;'>{$case->category}</td>
                            </tr>
                            <tr>
                                <td style='padding: 6px 0; color: #64748b;'>Status Penanganan:</td>
                                <td style='padding: 6px 0;'>
                                    <span style='background-color: #eff6ff; color: #1d4ed8; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 11px; border: 1px solid #bfdbfe;'>
                                        {$case->status}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 6px 0; color: #64748b;'>Penyedia / Otoritas:</td>
                                <td style='padding: 6px 0; color: #0f172a;'>{$case->provider_name}</td>
                            </tr>
                            <tr>
                                <td style='padding: 6px 0; color: #64748b;'>No. Tiket Provider:</td>
                                <td style='padding: 6px 0; font-family: monospace; color: #0f172a;'>" . ($case->external_reference_number ?: '-') . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 6px 0; color: #64748b;'>Jatuh Tempo Follow-up:</td>
                                <td style='padding: 6px 0; font-weight: 700; color: #dc2626;'>{$slaText}</td>
                            </tr>
                        </table>
                    </div>

                    {$noteSection}

                    <!-- CTA BUTTON -->
                    <div style='text-align: center; margin: 30px 0 20px 0;'>
                        <a href='{$caseUrl}' style='background-color: #2563eb; color: #ffffff; padding: 13px 30px; text-decoration: none; font-size: 13px; font-weight: 700; border-radius: 10px; display: inline-block; box-shadow: 0 4px 10px rgba(37,99,235,0.2);'>
                            Buka Rincian Kasus di WebGuard &rarr;
                        </a>
                    </div>

                    <p style='font-size: 12px; color: #94a3b8; line-height: 1.5; margin-top: 25px; border-top: 1px solid #f1f5f9; padding-top: 18px;'>
                        <strong>Ketentuan Kerahasiaan:</strong> Informasi dan barang bukti digital dalam notifikasi ini diklasifikasikan sebagai dokumen kedinasan resmi. Segala bentuk manipulasi atau penyalahgunaan dilarang sesuai peraturan perundang-undangan yang berlaku.
                    </p>
                </div>

                <!-- FOOTER -->
                <div style='background-color: #f8fafc; padding: 18px 24px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 11px; color: #64748b;'>
                    <strong>WEBGUARD INVESTIGASI & INCIDENT RESPONSE</strong><br/>
                    <span style='color: #94a3b8; margin-top: 4px; display: inline-block;'>Pesan ini dikirimkan otomatis oleh sistem dinas, mohon tidak membalas email ini secara langsung.</span>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
