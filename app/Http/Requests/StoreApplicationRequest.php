<?php

namespace App\Http\Requests;

use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('applications.manage');
    }

    public function rules()
    {
        $portMin = (int) config('gateway.port_min', 1);
        $portMax = (int) config('gateway.port_max', 65535);
        $allowedHosts = config('gateway.allowed_hosts', ['127.0.0.1']);

        return [
            'client_id' => ['required', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash'],
            'internal_host' => ['required', Rule::in($allowedHosts)],
            'internal_port' => [
                'required',
                'integer',
                'min:'.$portMin,
                'max:'.$portMax,
                Rule::unique('applications', 'internal_port')->where(function ($query) {
                    $nodeId = \App\Models\GatewayNode::defaultNode()->id ?? 0;

                    return $query->where('gateway_node_id', $nodeId);
                }),
            ],
            'access_status' => ['required', Rule::in(array_keys(Application::accessStatuses()))],
            'primary_hostname' => ['required', 'string', 'max:255'],
            'additional_hostnames' => ['nullable', 'string'],
            'technical_notes' => ['nullable', 'string'],
        ];
    }

    public function attributes()
    {
        return [
            'client_id' => 'client',
            'name' => 'nama aplikasi',
            'internal_host' => 'host internal',
            'internal_port' => 'port internal',
            'access_status' => 'status akses',
            'primary_hostname' => 'domain utama',
            'additional_hostnames' => 'domain tambahan',
            'technical_notes' => 'catatan teknis',
        ];
    }
}
