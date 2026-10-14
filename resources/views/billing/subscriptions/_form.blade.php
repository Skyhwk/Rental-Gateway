<div class="card-body p-0">
    <div class="px-3 pt-3 pb-2 border-bottom bg-light">
        <h6 class="text-uppercase text-muted mb-0"><i class="fas fa-link mr-1"></i> Client &amp; aplikasi</h6>
    </div>
    <div class="p-3 row">
        @if (! empty($readonlyClientApp))
            <div class="form-group col-md-6 mb-md-0">
                <label class="text-muted small mb-1">Client</label>
                <p class="form-control-plaintext font-weight-bold mb-0">{{ $subscription->client->company_name }}</p>
            </div>
            <div class="form-group col-md-6 mb-0">
                <label class="text-muted small mb-1">Aplikasi</label>
                <p class="form-control-plaintext font-weight-bold mb-0">{{ $subscription->application->name }}</p>
            </div>
        @else
            <div class="form-group col-md-6">
                <label>Client <span class="text-danger">*</span></label>
                <select name="client_id" class="form-control" required>
                    <option value="">— Pilih client —</option>
                    @foreach ($clients as $c)
                        <option value="{{ $c->id }}" {{ old('client_id', $subscription->client_id) == $c->id ? 'selected' : '' }}>{{ $c->company_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label>Aplikasi <span class="text-danger">*</span></label>
                <select name="application_id" class="form-control" required>
                    <option value="">— Pilih aplikasi —</option>
                    @foreach ($applications as $app)
                        <option value="{{ $app->id }}" {{ old('application_id', $subscription->application_id) == $app->id ? 'selected' : '' }}>
                            {{ $app->name }} · {{ $app->client->company_name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>

    <div class="px-3 py-2 border-top border-bottom bg-light">
        <h6 class="text-uppercase text-muted mb-0"><i class="fas fa-tags mr-1"></i> Paket &amp; tagihan</h6>
    </div>
    <div class="p-3 row">
        <div class="form-group col-lg-6">
            <label>Paket <span class="text-danger">*</span></label>
            <select name="subscription_plan_id" class="form-control" required>
                @foreach ($plans as $p)
                    <option value="{{ $p->id }}" {{ old('subscription_plan_id', $subscription->subscription_plan_id) == $p->id ? 'selected' : '' }}>
                        {{ $p->name }} — {{ App\Models\SubscriptionPlan::intervals()[$p->billing_interval] ?? $p->billing_interval }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group col-lg-6">
            <label>Nominal per periode (Rp) <span class="text-danger">*</span></label>
            <input type="number" name="price_amount" class="form-control" value="{{ old('price_amount', $subscription->price_amount) }}" min="0" step="1" required placeholder="500000">
        </div>
    </div>

    <div class="px-3 py-2 border-top border-bottom bg-light">
        <h6 class="text-uppercase text-muted mb-0"><i class="fas fa-calendar-alt mr-1"></i> Jadwal &amp; toleransi</h6>
    </div>
    <div class="p-3">
        <div class="row">
            <div class="form-group col-md-6 col-lg-4">
                <label>Mulai berlangganan <span class="text-danger">*</span></label>
                <input type="date" name="starts_at" class="form-control" value="{{ old('starts_at', optional($subscription->starts_at)->format('Y-m-d')) }}" required>
            </div>
            <div class="form-group col-md-6 col-lg-4">
                <label>Toleransi keterlambatan (hari)</label>
                <input type="number" name="grace_days" class="form-control" value="{{ old('grace_days', $subscription->grace_days ?? 0) }}" min="0" max="90">
                <small class="text-muted">0 = suspend di hari jatuh tempo. 3 = suspend setelah +3 hari.</small>
            </div>
        </div>
        <div class="callout callout-info mb-0 py-2">
            <p class="mb-0 small">
                <strong>Jatuh tempo otomatis</strong> dihitung dari tanggal mulai + interval paket
                (contoh tahunan: mulai 01/12/2025 → bayar lagi 01/12/2026).
                Invoice dibuat otomatis <strong>H-{{ (int) config('billing.invoice_generate_days_before_due', 7) }}</strong> sebelum jatuh tempo.
            </p>
        </div>
    </div>

    <div class="px-3 py-2 border-top border-bottom bg-light">
        <h6 class="text-uppercase text-muted mb-0"><i class="fas fa-cog mr-1"></i> Lainnya</h6>
    </div>
    <div class="p-3 row">
        <div class="form-group col-md-6">
            <label>Status langganan <span class="text-danger">*</span></label>
            <select name="status" class="form-control" required>
                @foreach ($statuses as $k => $label)
                    <option value="{{ $k }}" {{ old('status', $subscription->status) === $k ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-6">
            <label>Catatan internal</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Opsional">{{ old('notes', $subscription->notes) }}</textarea>
        </div>
        @if (empty($readonlyClientApp))
            <div class="form-group col-12 mb-0">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="create_initial_invoice" value="1" id="create_initial_invoice" {{ old('create_initial_invoice') ? 'checked' : '' }}>
                    <label class="custom-control-label" for="create_initial_invoice">
                        Buat invoice periode pertama sekarang
                        <span class="text-muted">(biasanya tidak perlu — sistem yang buat H-{{ (int) config('billing.invoice_generate_days_before_due', 7) }})</span>
                    </label>
                </div>
            </div>
        @endif
    </div>
</div>
