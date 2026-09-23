<?php

namespace App\Http\Requests;

use App\Exceptions\SsrfBlockedException;
use App\Services\Security\SsrfProtectionService;
use Illuminate\Foundation\Http\FormRequest;

class StoreInvestigationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('investigation.create') ?? true;
    }

    public function rules(): array
    {
        return [
            'target_url' => ['required', 'string', 'max:2048'],
            'category' => ['nullable', 'string', 'in:Phishing,Malware,Fraud,Illegal Content,Copyright,Spam,Suspicious Domain,Other'],
            'priority' => ['nullable', 'string', 'in:LOW,MEDIUM,HIGH,CRITICAL'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'tags' => ['nullable', 'array'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $url = $this->input('target_url');
            if (!empty($url)) {
                try {
                    $ssrf = app(SsrfProtectionService::class);
                    $ssrf->validateUrl($url);
                } catch (SsrfBlockedException $e) {
                    $validator->errors()->add('target_url', $e->getMessage());
                } catch (\Exception $e) {
                    $validator->errors()->add('target_url', 'Validasi keamanan URL gagal: ' . $e->getMessage());
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'target_url.required' => 'Target URL wajib diisi.',
            'category.in' => 'Kategori investigasi tidak valid.',
        ];
    }
}
