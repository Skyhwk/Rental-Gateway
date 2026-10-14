<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Client::class, 'client');
    }

    public function index(Request $request)
    {
        $query = Client::query()->withCount('applications');

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('pic_name', 'like', '%'.$search.'%');
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $clients = $query->orderBy('company_name')->paginate(15)->withQueryString();

        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        return view('clients.create', [
            'client' => new Client(['status' => Client::STATUS_ACTIVE]),
            'statuses' => Client::statuses(),
        ]);
    }

    public function store(StoreClientRequest $request)
    {
        $client = Client::create($request->validated());

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Client berhasil ditambahkan.');
    }

    public function show(Client $client)
    {
        $client->load(['applications' => function ($q) {
            $q->with('primaryDomain')->orderBy('name');
        }]);

        return view('clients.show', compact('client'));
    }

    public function edit(Client $client)
    {
        return view('clients.edit', [
            'client' => $client,
            'statuses' => Client::statuses(),
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        $client->update($request->validated());

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Data client diperbarui.');
    }

    public function destroy(Client $client)
    {
        if ($client->applications()->exists()) {
            return back()->with('error', 'Client masih memiliki aplikasi. Hapus atau pindahkan aplikasi terlebih dahulu.');
        }

        $client->delete();

        return redirect()
            ->route('clients.index')
            ->with('success', 'Client dihapus.');
    }
}
