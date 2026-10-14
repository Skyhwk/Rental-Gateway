<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('clients.manage');
    }

    public function rules()
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'pic_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_keys(Client::statuses()))],
            'internal_notes' => ['nullable', 'string'],
        ];
    }

    public function attributes()
    {
        return [
            'company_name' => 'nama perusahaan',
            'pic_name' => 'PIC',
            'email' => 'email',
            'phone' => 'telepon',
            'address' => 'alamat',
            'status' => 'status',
            'internal_notes' => 'catatan internal',
        ];
    }
}
