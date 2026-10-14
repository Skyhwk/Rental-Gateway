<?php

namespace App\Http\Requests;

use App\Models\Application;
use App\Models\GatewayNode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('applications.manage');
    }

    public function rules()
    {
        $application = $this->route('application');
        $portMin = (int) config('gateway.port_min', 1);
        $portMax = (int) config('gateway.port_max', 65535);
        $allowedHosts = config('gateway.allowed_hosts', ['127.0.0.1']);
        $nodeId = $application->gateway_node_id ?? (GatewayNode::defaultNode()->id ?? 0);

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
                Rule::unique('applications', 'internal_port')
                    ->where(function ($query) use ($nodeId) {
                        return $query->where('gateway_node_id', $nodeId);
                    })
                    ->ignore($application->id),
            ],
            'primary_hostname' => ['required', 'string', 'max:255'],
            'additional_hostnames' => ['nullable', 'string'],
            'technical_notes' => ['nullable', 'string'],
        ];
    }

    public function attributes()
    {
        return (new StoreApplicationRequest())->attributes();
    }
}
