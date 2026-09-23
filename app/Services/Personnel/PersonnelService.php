<?php

namespace App\Services\Personnel;

use App\Models\AuditLog;
use App\Models\Rank;
use App\Models\Unit;
use App\Models\Position;
use App\Models\User;
use App\Models\UserDocument;
use App\Models\UserDocumentVersion;
use App\Services\Providers\OfficialWhatsAppProvider;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PersonnelService
{
    /**
     * Store an identity document (KTP or KTA) in private storage with SHA-256 and versioning.
     */
    public function uploadDocument(User $user, UploadedFile $file, string $documentType): UserDocument
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        $mime = $file->getMimeType();

        if (!in_array($mime, $allowedMimes, true)) {
            throw new Exception("Format berkas '{$mime}' tidak diizinkan. Hanya JPG, PNG, WebP, dan PDF yang diterima.");
        }

        // 5MB limit
        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new Exception("Ukuran berkas melebihi batas maksimum 5MB.");
        }

        $content = file_get_contents($file->getRealPath());
        $hash = hash('sha256', $content);

        // Store outside /public in private directory
        $safeFilename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $storedPath = "private/user-documents/{$safeFilename}";
        Storage::disk('local')->put($storedPath, $content);

        // Check if user already has this document type
        $existing = UserDocument::where('user_id', $user->id)
            ->where('document_type', $documentType)
            ->first();

        if ($existing) {
            // Versioning: record current state in user_document_versions
            $maxVer = $existing->versions()->max('version') ?? 0;
            UserDocumentVersion::create([
                'document_id' => $existing->id,
                'version' => $maxVer + 1,
                'file_path' => $existing->file_path,
                'sha256' => $existing->sha256,
                'uploaded_by' => $existing->uploaded_by,
                'uploaded_at' => $existing->uploaded_at,
                'notes' => 'Diperbarui dengan versi baru oleh ' . (auth()->user()->name ?? 'System'),
            ]);

            $existing->update([
                'file_path' => $storedPath,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $mime,
                'file_size' => $file->getSize(),
                'sha256' => $hash,
                'uploaded_by' => auth()->id(),
                'uploaded_at' => now(),
            ]);

            AuditLog::log('DOCUMENT_REPLACED', 'UserDocument', (string) $existing->id, [
                'user_id' => $user->id,
                'document_type' => $documentType,
                'new_sha256' => $hash,
            ]);

            return $existing;
        }

        $document = UserDocument::create([
            'user_id' => $user->id,
            'document_type' => $documentType,
            'file_path' => $storedPath,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $mime,
            'file_size' => $file->getSize(),
            'sha256' => $hash,
            'uploaded_by' => auth()->id(),
            'uploaded_at' => now(),
        ]);

        AuditLog::log('DOCUMENT_UPLOADED', 'UserDocument', (string) $document->id, [
            'user_id' => $user->id,
            'document_type' => $documentType,
            'sha256' => $hash,
        ]);

        return $document;
    }

    /**
     * Activate a personnel account.
     */
    public function activateUser(User $user, ?int $adminId = null): void
    {
        $user->update([
            'status' => 'ACTIVE',
            'activated_by_admin_at' => now(),
            'activated_by_admin_id' => $adminId ?? auth()->id(),
            'deactivated_at' => null,
            'deactivated_by' => null,
            'deactivation_reason' => null,
        ]);

        AuditLog::log('USER_ACTIVATED', 'User', (string) $user->id, [
            'activated_by' => $adminId ?? auth()->id(),
        ]);
    }

    /**
     * Deactivate a personnel account with mandatory reason.
     */
    public function deactivateUser(User $user, string $reason, ?int $adminId = null): void
    {
        $user->update([
            'status' => 'INACTIVE',
            'deactivated_at' => now(),
            'deactivated_by' => $adminId ?? auth()->id(),
            'deactivation_reason' => $reason,
        ]);

        AuditLog::log('USER_DEACTIVATED', 'User', (string) $user->id, [
            'deactivated_by' => $adminId ?? auth()->id(),
            'reason' => $reason,
        ]);
    }

    /**
     * Suspend a personnel account.
     */
    public function suspendUser(User $user, string $reason): void
    {
        $user->update([
            'status' => 'SUSPENDED',
            'deactivated_at' => now(),
            'deactivated_by' => auth()->id(),
            'deactivation_reason' => $reason,
        ]);

        AuditLog::log('USER_SUSPENDED', 'User', (string) $user->id, [
            'reason' => $reason,
        ]);
    }
}
