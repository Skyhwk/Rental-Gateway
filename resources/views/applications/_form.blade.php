<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="client_id">Client</label>
            <select name="client_id" id="client_id" class="form-control @error('client_id') is-invalid @enderror" required>
                <option value="">— Pilih client —</option>
                @foreach ($clients as $c)
                    <option value="{{ $c->id }}" {{ (string) old('client_id', $application->client_id) === (string) $c->id ? 'selected' : '' }}>
                        {{ $c->company_name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="name">Nama aplikasi</label>
            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $application->name) }}" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="slug">Slug (opsional)</label>
            <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror"
                   value="{{ old('slug', $application->slug) }}" placeholder="auto dari nama jika kosong">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="internal_host">Host internal</label>
            <select name="internal_host" id="internal_host" class="form-control @error('internal_host') is-invalid @enderror" required>
                @foreach ($allowedHosts as $host)
                    <option value="{{ $host }}" {{ old('internal_host', $application->internal_host) === $host ? 'selected' : '' }}>{{ $host }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="internal_port">Port internal</label>
            <input type="number" name="internal_port" id="internal_port" class="form-control @error('internal_port') is-invalid @enderror"
                   value="{{ old('internal_port', $application->internal_port) }}" required
                   min="{{ config('gateway.port_min') }}" max="{{ config('gateway.port_max') }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="primary_hostname">Domain utama</label>
            <input type="text" name="primary_hostname" id="primary_hostname" class="form-control @error('primary_hostname') is-invalid @enderror"
                   value="{{ old('primary_hostname', $primaryHostname) }}" required placeholder="app.client.com">
        </div>
    </div>
    @if ($application->exists)
        <div class="col-md-6">
            <div class="form-group">
                <label>Status akses</label>
                <p class="form-control-plaintext mb-0">
                    @include('partials.status-badge', ['status' => $application->access_status, 'label' => $accessStatuses[$application->access_status] ?? $application->access_status])
                    <span class="text-muted small d-block">Ubah via halaman detail → Kontrol akses.</span>
                </p>
            </div>
        </div>
    @else
        <div class="col-md-6">
            <div class="form-group">
                <label for="access_status">Status akses awal</label>
                <select name="access_status" id="access_status" class="form-control @error('access_status') is-invalid @enderror" required>
                    @foreach ($accessStatuses as $value => $label)
                        <option value="{{ $value }}" {{ old('access_status', $application->access_status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif
    <div class="col-12">
        <div class="form-group">
            <label for="additional_hostnames">Domain tambahan</label>
            <textarea name="additional_hostnames" id="additional_hostnames" rows="3" class="form-control @error('additional_hostnames') is-invalid @enderror"
                      placeholder="Satu domain per baris">{{ old('additional_hostnames', $additionalHostnames) }}</textarea>
        </div>
    </div>
    <div class="col-12">
        <div class="form-group mb-0">
            <label for="technical_notes">Catatan teknis</label>
            <textarea name="technical_notes" id="technical_notes" rows="3" class="form-control @error('technical_notes') is-invalid @enderror">{{ old('technical_notes', $application->technical_notes) }}</textarea>
        </div>
    </div>
</div>
