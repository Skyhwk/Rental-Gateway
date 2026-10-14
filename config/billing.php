<?php

return [
    'auto_activate_on_payment_verified' => env('BILLING_AUTO_ACTIVATE_ON_PAYMENT', true),
    'auto_suspend_on_grace_expired' => env('BILLING_AUTO_SUSPEND', true),
    /** Jam suspend otomatis (timezone APP_TIMEZONE), format HH:MM */
    'suspend_at_time' => env('BILLING_SUSPEND_AT_TIME', '09:00'),
    /** Jam scheduler menjalankan cek suspend (sedikit setelah suspend_at_time) */
    'suspend_run_at' => env('BILLING_SUSPEND_RUN_AT', '09:05'),
    /** Buat invoice otomatis saat today >= jatuh_tempo - N hari (7 = H-7, 1 = H-1). */
    'invoice_generate_days_before_due' => (int) env('BILLING_INVOICE_GENERATE_DAYS_BEFORE', 7),
    /** Format nomor: {prefix}-{6 hex huruf besar}, contoh INV-A3F2B1 */
    'invoice_number_prefix' => env('BILLING_INVOICE_PREFIX', 'INV'),
    'payment_proof_disk' => env('BILLING_PAYMENT_PROOF_DISK', 'local'),
    'payment_proof_max_kb' => (int) env('PAYMENT_PROOF_MAX_KB', 5120),

    'issuer' => [
        'name' => env('BILLING_ISSUER_NAME', env('APP_NAME', 'Rental Gateway Manager')),
        'subtitle' => env('BILLING_ISSUER_SUBTITLE', 'Pengelola sewa aplikasi web — perorangan / UMKM'),
        'address' => env('BILLING_ISSUER_ADDRESS', ''),
        'email' => env('BILLING_ISSUER_EMAIL', ''),
        'phone' => env('BILLING_ISSUER_PHONE', ''),
    ],
    'payment' => [
        'recipient_name' => env('BILLING_PAYMENT_RECIPIENT', ''),
        'bank_name' => env('BILLING_BANK_NAME', ''),
        'account_number' => env('BILLING_BANK_ACCOUNT', ''),
    ],
];
