<?php

namespace App\Services\Takedown;

use App\Models\AuditLog;
use App\Models\TakedownCase;
use App\Models\TakedownFollowUp;
use App\Models\TakedownProvider;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TakedownCaseService
{
    public function __construct(
        protected TakedownNotificationService $notificationService,
        protected TakedownReportGeneratorService $reportGenerator,
        protected EvidencePackageManagerService $packageManager
    ) {}

    /**
     * Create a new Takedown Case from passive investigation results.
     */
    public function createCase(array $data, User $creator): TakedownCase
    {
        $provider = null;
        if (!empty($data['provider_id'])) {
            $provider = TakedownProvider::find($data['provider_id']);
        }

        // Calculate initial next follow-up date based on provider SLA
        $slaHours = $provider ? $provider->sla_hours : 72;
        $nextFollowUp = now()->addHours($slaHours);

        $case = TakedownCase::create([
            'uuid' => (string) Str::uuid(),
            'investigation_id' => $data['investigation_id'] ?? null,
            'provider_id' => $provider?->id,
            'target_domain' => strtolower(trim($data['target_domain'])),
            'target_url' => trim($data['target_url']),
            'target_ip' => $data['target_ip'] ?? null,
            'category' => $data['category'] ?? 'Konten Ilegal / Hoax',
            'allegation_summary' => $data['allegation_summary'],
            'legal_or_policy_basis' => $data['legal_or_policy_basis'] ?? null,
            'evidence_summary' => $data['evidence_summary'] ?? null,
            'selected_evidence_ids' => $data['selected_evidence_ids'] ?? null,
            'provider_type' => $provider?->type ?? ($data['provider_type'] ?? null),
            'provider_name' => $provider?->name ?? ($data['provider_name'] ?? null),
            'provider_contact' => $provider?->abuse_email ?: ($provider?->abuse_url ?: ($data['provider_contact'] ?? null)),
            'external_reference_number' => $data['external_reference_number'] ?? null,
            'status' => 'DRAFT',
            'priority' => $data['priority'] ?? 'MEDIUM',
            'assigned_to' => $data['assigned_to'] ?? $creator->id,
            'created_by' => $creator->id,
            'next_follow_up_at' => $nextFollowUp,
        ]);

        AuditLog::log('TAKEDOWN_CASE_CREATED', 'TakedownCase', (string) $case->id, [
            'case_number' => $case->case_number,
            'target_domain' => $case->target_domain,
            'category' => $case->category,
        ]);

        return $case;
    }

    /**
     * Transition status of Takedown Case with audit logging and notifications.
     */
    public function updateStatus(TakedownCase $case, string $newStatus, User $user, ?string $note = null): TakedownCase
    {
        $oldStatus = $case->status;
        $updates = ['status' => $newStatus];

        if ($newStatus === 'SUBMITTED' && !$case->submitted_at) {
            $updates['submitted_at'] = now();
            // Calculate next follow up
            $sla = $case->provider?->sla_hours ?: 48;
            $updates['next_follow_up_at'] = now()->addHours($sla);
        } elseif ($newStatus === 'ACKNOWLEDGED' && !$case->acknowledged_at) {
            $updates['acknowledged_at'] = now();
        } elseif (in_array($newStatus, ['ACTION_TAKEN', 'CLOSED', 'RESOLVED'])) {
            $updates['resolved_at'] = now();
        }

        $case->update($updates);

        AuditLog::log('TAKEDOWN_STATUS_UPDATED', 'TakedownCase', (string) $case->id, [
            'case_number' => $case->case_number,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'note' => $note,
        ]);

        // Send notifications (Sisinden WhatsApp + Sisfoperskc Email)
        $this->notificationService->notifyCaseStatus($case, "STATUS_{$newStatus}", $note);

        return $case;
    }

    /**
     * Add follow-up communication / ticket response.
     */
    public function addFollowUp(TakedownCase $case, array $data, User $officer): TakedownFollowUp
    {
        $attachmentPath = null;
        $attachmentSha256 = null;

        if (!empty($data['attachment']) && $data['attachment'] instanceof \Illuminate\Http\UploadedFile) {
            $file = $data['attachment'];
            $attachmentSha256 = hash_file('sha256', $file->getRealPath());
            $attachmentPath = $file->store("takedown/followups/{$case->case_number}", 'local');
        }

        $followUp = TakedownFollowUp::create([
            'uuid' => (string) Str::uuid(),
            'takedown_case_id' => $case->id,
            'user_id' => $officer->id,
            'channel' => $data['channel'] ?? 'PORTAL',
            'direction' => $data['direction'] ?? 'INBOUND',
            'ticket_number' => $data['ticket_number'] ?? $case->external_reference_number,
            'subject' => $data['subject'],
            'message' => $data['message'],
            'provider_status' => $data['provider_status'] ?? null,
            'attachment_path' => $attachmentPath,
            'attachment_sha256' => $attachmentSha256,
            'follow_up_date' => $data['follow_up_date'] ?? now(),
            'next_action' => $data['next_action'] ?? null,
        ]);

        // Update case reference and timestamps
        $caseUpdates = [
            'last_follow_up_at' => now(),
        ];

        if (!empty($data['ticket_number']) && empty($case->external_reference_number)) {
            $caseUpdates['external_reference_number'] = $data['ticket_number'];
        }

        if (!empty($data['next_follow_up_at'])) {
            $caseUpdates['next_follow_up_at'] = $data['next_follow_up_at'];
        }

        if (!empty($data['provider_status'])) {
            // Map common provider responses to case status
            $mappedStatus = match (strtoupper($data['provider_status'])) {
                'RESOLVED', 'TAKEN_DOWN', 'SUSPENDED' => 'ACTION_TAKEN',
                'REJECTED', 'DENIED' => 'REJECTED',
                'MORE_INFO', 'ADDITIONAL_INFO' => 'ADDITIONAL_INFORMATION_REQUESTED',
                'ACKNOWLEDGED', 'IN_PROGRESS' => 'UNDER_REVIEW',
                default => null,
            };
            if ($mappedStatus) {
                $caseUpdates['status'] = $mappedStatus;
            }
        }

        $case->update($caseUpdates);

        AuditLog::log('TAKEDOWN_FOLLOW_UP_ADDED', 'TakedownCase', (string) $case->id, [
            'case_number' => $case->case_number,
            'direction' => $followUp->direction,
            'ticket_number' => $followUp->ticket_number,
            'subject' => $followUp->subject,
        ]);

        // Trigger notification if provider responded or new action needed
        if ($followUp->direction === 'INBOUND') {
            $this->notificationService->notifyCaseStatus(
                $case,
                'PROVIDER_RESPONSE',
                "Tanggapan diterima dari provider ({$followUp->subject}): {$followUp->message}"
            );
        }

        return $followUp;
    }
}
