<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class)->ignore($this->user()->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'timezone' => ['required', 'timezone'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'notify_email' => ['nullable', 'boolean'],
            'notify_inapp' => ['nullable', 'boolean'],
        ];
    }
}