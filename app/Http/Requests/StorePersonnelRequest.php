<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePersonnelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->hasPermission('user.create') ?? true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'nrp' => ['nullable', 'string', 'max:64', 'unique:users,nrp'],
            'rank_id' => ['nullable', 'exists:ranks,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'phone' => ['nullable', 'string', 'max:32'],
            'whatsapp_number' => ['nullable', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:12'],
            'role' => ['required', 'string', 'in:super_admin,admin,investigator,viewer'],
            'active_from' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap personel wajib diisi.',
            'email.required' => 'Email kedinasan wajib diisi.',
            'email.unique' => 'Email tersebut sudah terdaftar.',
            'nrp.unique' => 'NRP tersebut sudah terdaftar.',
            'password.min' => 'Password harus memiliki panjang minimal 12 karakter.',
            'role.required' => 'Role otorisasi wajib dipilih.',
        ];
    }
}
