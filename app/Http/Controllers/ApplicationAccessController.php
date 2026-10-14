<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangeApplicationAccessRequest;
use App\Models\Application;
use App\Services\AccessControlService;

class ApplicationAccessController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:applications.manage']);
    }

    public function update(ChangeApplicationAccessRequest $request, Application $application, AccessControlService $accessControl)
    {
        $this->authorize('update', $application);

        $accessControl->changeStatus(
            $application,
            $request->input('access_status'),
            $request->input('reason'),
            $request->user(),
        );

        return redirect()
            ->route('applications.show', $application)
            ->with('success', 'Status akses diperbarui dan sinkronisasi gateway diproses.');
    }
}
