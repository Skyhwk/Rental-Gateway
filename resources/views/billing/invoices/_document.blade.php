{{-- Layout invoice A4 — dipakai cetak/PDF & unduh --}}
<div class="invoice-a4">
    <header class="inv-header">
        <div class="inv-brand">
            <h1>{{ config('billing.issuer.name') }}</h1>
            @if (config('billing.issuer.subtitle'))
                <p class="inv-subtitle">{{ config('billing.issuer.subtitle') }}</p>
            @endif
            @if (config('billing.issuer.address'))
                <p class="inv-meta">{{ config('billing.issuer.address') }}</p>
            @endif
            @if (config('billing.issuer.email') || config('billing.issuer.phone'))
                <p class="inv-meta">
                    @if (config('billing.issuer.email')){{ config('billing.issuer.email') }}@endif
                    @if (config('billing.issuer.phone')) · {{ config('billing.issuer.phone') }}@endif
                </p>
            @endif
        </div>
        <div class="inv-title-box">
            <div class="inv-title">INVOICE</div>
            <div class="inv-number">{{ $invoice->invoice_number }}</div>
            <div class="inv-meta">Tanggal terbit: {{ $invoice->created_at->format('d/m/Y') }}</div>
            <div class="inv-meta">Jatuh tempo: <strong>{{ $invoice->due_date->format('d/m/Y') }}</strong></div>
        </div>
    </header>

    <section class="inv-parties">
        <div>
            <div class="inv-label">Tagihan kepada</div>
            <strong>{{ $invoice->subscription->client->company_name }}</strong>
            @if ($invoice->subscription->client->email)
                <div class="inv-meta">{{ $invoice->subscription->client->email }}</div>
            @endif
        </div>
        <div style="text-align: right;">
            <div class="inv-label">Layanan</div>
            <strong>Sewa aplikasi web</strong>
            <div class="inv-meta">{{ $invoice->subscription->application->name }}</div>
            <div class="inv-meta">Periode: {{ $invoice->period_start->format('d/m/Y') }} — {{ $invoice->period_end->format('d/m/Y') }}</div>
        </div>
    </section>

    <table class="inv-table">
        <thead>
            <tr>
                <th>Deskripsi</th>
                <th class="inv-num">Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="inv-num">{{ number_format($item->amount, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="inv-summary">
        <table>
            <tr><td>Subtotal</td><td class="inv-num">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td></tr>
            <tr><td>PPN</td><td class="inv-num">Rp 0 <span class="inv-note-inline">(Non-PPN)</span></td></tr>
            <tr class="inv-total-row"><td><strong>Total tagihan</strong></td><td class="inv-num"><strong>Rp {{ number_format($invoice->amount, 0, ',', '.') }}</strong></td></tr>
            <tr><td>Terbayar</td><td class="inv-num">Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</td></tr>
            <tr><td>Sisa</td><td class="inv-num">Rp {{ number_format($invoice->balanceDue(), 0, ',', '.') }}</td></tr>
        </table>
    </div>

    @if (config('billing.payment.recipient_name') || config('billing.payment.account_number'))
        <section class="inv-payment">
            <div class="inv-label">Pembayaran transfer</div>
            @if (config('billing.payment.bank_name'))
                <div>Bank: <strong>{{ config('billing.payment.bank_name') }}</strong></div>
            @endif
            @if (config('billing.payment.account_number'))
                <div>No. rekening: <strong>{{ config('billing.payment.account_number') }}</strong></div>
            @endif
            @if (config('billing.payment.recipient_name'))
                <div>Atas nama: <strong>{{ config('billing.payment.recipient_name') }}</strong></div>
            @endif
            <div class="inv-meta">Cantumkan nomor invoice pada berita transfer.</div>
        </section>
    @endif

    <footer class="inv-footer">
        <p><strong>Dokumen ini bukan faktur PPN.</strong> Diterbitkan otomatis oleh sistem {{ config('billing.issuer.name', 'Rental Gateway Manager') }}.</p>
        <p>Pengelola layanan sewa aplikasi web dalam kapasitas <strong>perorangan / UMKM mikro</strong>, bukan badan hukum PT/CV kecuali dinyatakan lain secara tertulis.</p>
        <!-- <p class="inv-meta">Dicetak {{ now()->format('d/m/Y H:i') }} WIB · Status: {{ App\Models\Invoice::statuses()[$invoice->status] ?? $invoice->status }}</p> -->
    </footer>
</div>
