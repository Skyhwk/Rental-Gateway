{{-- Fragment HTML untuk modal pratinjau (bukan halaman penuh) — layout table/block (html2pdf-safe) --}}
<style>
    .invoice-preview-embed .invoice-a4 {
        width: 100%;
        max-width: 794px;
        min-height: auto;
        margin: 0 auto;
        padding: 40px 45px;
        background: #fff;
        box-shadow: 0 2px 12px rgba(0,0,0,.12);
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
        font-size: 11pt;
        color: #1a1a1a;
        overflow: hidden;
        position: relative;
    }
    .invoice-preview-embed .invoice-a4 > :not(.inv-watermark) {
        position: relative;
        z-index: 1;
    }
    .invoice-preview-embed .inv-watermark {
        left: 50%;
        pointer-events: none;
        position: absolute;
        /* Nilai tetap agar posisi tidak berubah saat elemen di-clone oleh html2pdf. */
        top: 355px;
        transform: translate(-50%, -50%) rotate(-20deg);
        transform-origin: center;
        user-select: none;
        white-space: nowrap;
        z-index: 5;
    }
    .invoice-preview-embed .inv-watermark span {
        background: rgba(255,255,255,.35);
        border: 7px double currentColor;
        border-radius: 10px;
        display: block;
        font-family: Arial, sans-serif;
        font-size: 42px;
        font-weight: 800;
        letter-spacing: 5px;
        line-height: 1;
        opacity: .12;
        padding: 13px 20px 10px 25px;
        text-align: center;
    }
    .invoice-preview-embed .inv-watermark-paid {
        color: #15803d;
    }
    .invoice-preview-embed .inv-watermark-unpaid {
        color: #dc2626;
    }
    .invoice-preview-embed .inv-header {
        display: table;
        width: 100%;
        table-layout: fixed;
        border-bottom: 2px solid #2563eb;
        padding-bottom: 12px;
        margin-bottom: 18px;
    }
    .invoice-preview-embed .inv-brand {
        display: table-cell;
        width: 58%;
        vertical-align: top;
        padding-right: 12px;
    }
    .invoice-preview-embed .inv-brand h1 { font-size: 16pt; margin: 0 0 4px; color: #1e3a8a; }
    .invoice-preview-embed .inv-subtitle { margin: 0 0 6px; font-size: 9pt; color: #475569; }
    .invoice-preview-embed .inv-title-box {
        display: table-cell;
        width: 42%;
        vertical-align: top;
        text-align: right;
    }
    .invoice-preview-embed .inv-title { font-size: 18pt; font-weight: 700; letter-spacing: 2px; color: #2563eb; }
    .invoice-preview-embed .inv-number { font-size: 12pt; font-weight: 600; margin: 4px 0; }
    .invoice-preview-embed .inv-meta { font-size: 9pt; color: #64748b; }
    .invoice-preview-embed .inv-parties {
        display: table;
        width: 100%;
        table-layout: fixed;
        margin-bottom: 20px;
    }
    .invoice-preview-embed .inv-parties > div {
        display: table-cell;
        width: 50%;
        vertical-align: top;
        padding-right: 12px;
    }
    .invoice-preview-embed .inv-parties > div:last-child {
        padding-right: 0;
        padding-left: 12px;
    }
    .invoice-preview-embed .inv-label { font-size: 8pt; text-transform: uppercase; letter-spacing: .5px; color: #64748b; margin-bottom: 4px; }
    .invoice-preview-embed .inv-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; table-layout: fixed; }
    .invoice-preview-embed .inv-table th, .invoice-preview-embed .inv-table td { border: 1px solid #cbd5e1; padding: 8px 10px; }
    .invoice-preview-embed .inv-table th { background: #f1f5f9; text-align: left; font-size: 9pt; }
    .invoice-preview-embed .inv-num { text-align: right; white-space: nowrap; }
    .invoice-preview-embed .inv-summary {
        display: block;
        width: 100%;
        margin-bottom: 20px;
        text-align: right;
    }
    .invoice-preview-embed .inv-summary table {
        min-width: 260px;
        width: auto;
        margin-left: auto;
        border-collapse: collapse;
    }
    .invoice-preview-embed .inv-summary td { padding: 4px 0; }
    .invoice-preview-embed .inv-total-row td { border-top: 1px solid #94a3b8; padding-top: 8px; }
    .invoice-preview-embed .inv-note-inline { font-size: 8pt; color: #64748b; }
    .invoice-preview-embed .inv-payment { background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 14px; margin-bottom: 16px; border-radius: 4px; }
    .invoice-preview-embed .inv-footer { font-size: 8.5pt; color: #475569; border-top: 1px dashed #cbd5e1; padding-top: 12px; margin-top: 12px; }
    .invoice-preview-embed .inv-footer p { margin: 0 0 6px; }
</style>
<div class="invoice-preview-embed">
    @include('billing.invoices._document')
</div>
