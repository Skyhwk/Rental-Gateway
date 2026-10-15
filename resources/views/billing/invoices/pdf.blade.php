<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page { size: A4; margin: 12mm; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            font-size: 11pt;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
            background: #e9ecef;
        }
        .invoice-a4 {
            width: 210mm;
            min-height: 297mm;
            margin: 12px auto;
            padding: 14mm 16mm;
            background: #fff;
            box-shadow: 0 2px 12px rgba(0,0,0,.12);
        }
        .inv-header { display: table; width: 100%; table-layout: fixed; border-bottom: 2px solid #2563eb; padding-bottom: 12px; margin-bottom: 18px; }
        .inv-brand { display: table-cell; width: 58%; vertical-align: top; padding-right: 12px; }
        .inv-brand h1 { font-size: 16pt; margin: 0 0 4px; color: #1e3a8a; }
        .inv-subtitle { margin: 0 0 6px; font-size: 9pt; color: #475569; }
        .inv-title-box { display: table-cell; width: 42%; vertical-align: top; text-align: right; }
        .inv-title { font-size: 18pt; font-weight: 700; letter-spacing: 2px; color: #2563eb; }
        .inv-number { font-size: 12pt; font-weight: 600; margin: 4px 0; }
        .inv-meta { font-size: 9pt; color: #64748b; }
        .inv-parties { display: table; width: 100%; table-layout: fixed; margin-bottom: 20px; }
        .inv-parties > div { display: table-cell; width: 50%; vertical-align: top; }
        .inv-label { font-size: 8pt; text-transform: uppercase; letter-spacing: .5px; color: #64748b; margin-bottom: 4px; }
        .inv-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .inv-table th, .inv-table td { border: 1px solid #cbd5e1; padding: 8px 10px; }
        .inv-table th { background: #f1f5f9; text-align: left; font-size: 9pt; }
        .inv-num { text-align: right; white-space: nowrap; }
        .inv-summary { display: block; width: 100%; text-align: right; margin-bottom: 20px; }
        .inv-summary table { min-width: 260px; margin-left: auto; }
        .inv-summary td { padding: 4px 0; }
        .inv-total-row td { border-top: 1px solid #94a3b8; padding-top: 8px; }
        .inv-note-inline { font-size: 8pt; color: #64748b; }
        .inv-payment { background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 14px; margin-bottom: 16px; border-radius: 4px; }
        .inv-footer { font-size: 8.5pt; color: #475569; border-top: 1px dashed #cbd5e1; padding-top: 12px; margin-top: auto; }
        .inv-footer p { margin: 0 0 6px; }
        .no-print { text-align: center; padding: 12px; background: #fff; border-bottom: 1px solid #dee2e6; }
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .invoice-a4 { margin: 0; box-shadow: none; width: auto; min-height: auto; }
        }
    </style>
</head>
<body>
    @if (empty($forDownload) && ! request()->boolean('embed'))
        <div class="no-print">
            <button type="button" onclick="window.print()">Cetak / Simpan sebagai PDF (A4)</button>
            <a href="{{ route('billing.invoices.show', $invoice) }}">Kembali</a>
        </div>
    @endif

    @include('billing.invoices._document')
</body>
</html>
