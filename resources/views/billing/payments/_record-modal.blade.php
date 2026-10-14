@can('create', App\Models\Payment::class)
<div class="modal fade" id="paymentRecordModal" tabindex="-1" role="dialog" aria-labelledby="paymentRecordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="post" action="{{ route('billing.payments.store') }}" enctype="multipart/form-data" id="paymentRecordForm">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="paymentRecordModalLabel"><i class="fas fa-money-bill-wave mr-1"></i> Catat pembayaran</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    @if ($errors->any())
                        <div class="alert alert-danger py-2">
                            <ul class="mb-0 pl-3">
                                @foreach ($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="form-group">
                        <label class="font-weight-normal">Invoice <span class="text-danger">*</span></label>
                        <select name="invoice_id" class="form-control" required id="payment_invoice_id">
                            <option value="">— Pilih invoice —</option>
                            @foreach ($openInvoices as $inv)
                                <option value="{{ $inv->id }}"
                                    data-balance="{{ $inv->balanceDue() }}"
                                    data-label="{{ $inv->invoice_number }} · {{ optional($inv->subscription->client)->company_name }}"
                                    {{ (string) old('invoice_id', $preselectInvoiceId ?? '') === (string) $inv->id ? 'selected' : '' }}>
                                    {{ $inv->invoice_number }} — {{ optional($inv->subscription->client)->company_name }}
                                    (sisa Rp {{ number_format($inv->balanceDue(), 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <p class="small text-muted mb-3" id="payment_invoice_hint">Pilih invoice, nominal akan mengisi sisa tagihan.</p>

                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>Nominal (Rp) <span class="text-danger">*</span></label>
                            <input type="number" step="1" min="1" name="amount" id="payment_amount" class="form-control form-control-lg"
                                value="{{ old('amount') }}" required placeholder="0">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Tanggal bayar <span class="text-danger">*</span></label>
                            <input type="date" name="paid_at" class="form-control form-control-lg"
                                value="{{ old('paid_at', date('Y-m-d')) }}" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>Metode</label>
                            <select name="method" class="form-control">
                                @foreach (App\Models\Payment::methods() as $k => $label)
                                    <option value="{{ $k }}" {{ old('method', 'transfer') === $k ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>No. referensi / bukti transfer</label>
                            <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number') }}" placeholder="Opsional">
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label>Lampiran bukti (PDF/JPG)</label>
                        <input type="file" name="proof" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">Opsional — maks. {{ (int) config('billing.payment_proof_max_kb', 5120) }} KB</small>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check mr-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
