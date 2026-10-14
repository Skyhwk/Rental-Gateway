<?php

namespace App\Http\Requests;

use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeApplicationAccessRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('applications.manage');
    }

    public function rules()
    {
        return [
            'access_status' => ['required', Rule::in(array_keys(Application::accessStatuses()))],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }

    public function attributes()
    {
        return [
            'access_status' => 'status akses',
            'reason' => 'alasan',
        ];
    }
}
