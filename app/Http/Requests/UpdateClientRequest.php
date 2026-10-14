<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('clients.manage');
    }

    public function rules()
    {
        return (new StoreClientRequest())->rules();
    }

    public function attributes()
    {
        return (new StoreClientRequest())->attributes();
    }
}
