<div class="manual-book">

{{-- BAB 1 --}}
<div class="card card-primary card-outline collapsed-card" id="bab1">
    <div class="card-header">
        <h3 class="card-title">1. Apa itu Rental Manager?</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <p>
            <strong>{{ config('app.name') }}</strong> adalah panel untuk mengelola <strong>penyewaan akses aplikasi/website client</strong>
            di server Anda. Dari sini Anda bisa:
        </p>
        <ul>
            <li>Mencatat data <strong>client</strong> (perusahaan pelanggan).</li>
            <li>Mendaftarkan <strong>aplikasi</strong> client (nama, domain, alamat backend di server).</li>
            <li>Mengatur apakah website client <strong>aktif</strong>, <strong>ditangguhkan</strong>, atau <strong>pemeliharaan</strong>.</li>
            <li>Mengelola <strong>langganan</strong>, <strong>invoice</strong>, dan <strong>pembayaran</strong>.</li>
            <li>Memantau apakah backend masih hidup dan gateway sudah sinkron.</li>
        </ul>
        <p class="mb-0 text-muted">
            Yang <em>tidak</em> perlu Anda lakukan di panel: coding, edit file server, atau setting database —
            kecuali tim IT memberi instruksi khusus (misalnya saat setup awal server).
        </p>
    </div>
</div>

{{-- BAB 2 --}}
<div class="card card-primary card-outline collapsed-card" id="bab2">
    <div class="card-header">
        <h3 class="card-title">2. Login &amp; peran pengguna</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <h4>Cara login</h4>
        <ol>
            <li>Buka alamat panel yang diberikan tim IT (contoh: <code>https://panel.perusahaan Anda.id</code>).</li>
            <li>Masukkan <strong>email</strong> dan <strong>kata sandi</strong> Anda.</li>
            <li>Klik login. Jika lupa password, hubungi Super Admin — pendaftaran mandiri biasanya dinonaktifkan.</li>
        </ol>
        <h4>Peran (role) umum</h4>
        <table class="table table-sm table-bordered">
            <thead><tr><th>Peran</th><th>Bisa apa?</th></tr></thead>
            <tbody>
                <tr><td><strong>Super Admin</strong></td><td>Semua menu, termasuk pengguna (jika ada).</td></tr>
                <tr><td><strong>Administrator</strong></td><td>Client, aplikasi, langganan, gateway, monitoring — tanpa verifikasi pembayaran finance.</td></tr>
                <tr><td><strong>Finance</strong></td><td>Lihat client/aplikasi, kelola invoice &amp; verifikasi pembayaran.</td></tr>
                <tr><td><strong>Viewer</strong></td><td>Hanya melihat data, tidak mengubah.</td></tr>
            </tbody>
        </table>
        <p class="mb-0">Nama peran Anda tampil di sidebar kiri bawah nama login. Jika menu tidak muncul, berarti role Anda tidak punya izin — minta Admin menyesuaikan.</p>
    </div>
</div>

{{-- BAB 3 --}}
<div class="card card-primary card-outline collapsed-card" id="bab3">
    <div class="card-header">
        <h3 class="card-title">3. Dasbor — membaca ringkasan</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <p>Menu <strong>Dasbor</strong> adalah halaman pertama setelah login. Kotak angka memberi gambaran cepat:</p>
        <ul>
            <li>Jumlah <strong>client</strong> dan <strong>aplikasi</strong>.</li>
            <li>Berapa aplikasi <strong>aktif</strong> vs <strong>ditangguhkan</strong>.</li>
            <li>Health backend: berapa yang <strong>OK</strong> vs <strong>down</strong>.</li>
            <li>Invoice <strong>jatuh tempo</strong> (overdue) jika ada.</li>
        </ul>
        <p class="mb-0">Gunakan dasbor setiap pagi untuk cek apakah ada tagihan menumpuk atau aplikasi bermasalah sebelum client menelpon.</p>
    </div>
</div>

{{-- BAB 4 --}}
<div class="card card-success card-outline" id="bab4">
    <div class="card-header">
        <h3 class="card-title">4. Alur lengkap: client baru sampai online</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <p>Ini urutan kerja standar saat ada <strong>pelanggan baru</strong>. Tim IT biasanya menyiapkan backend (port aplikasi); Anda mengisi panel.</p>
        <div class="flow-box">
            <p class="mb-1"><span class="step-num">1</span> <strong>Client</strong> → Buat data perusahaan (nama, PIC, email, telepon).</p>
            <p class="mb-1"><span class="step-num">2</span> <strong>Aplikasi</strong> → Daftarkan aplikasi: pilih client, isi domain (contoh <code>db.client.com</code>), host/port backend dari IT (contoh <code>127.0.0.1:450</code>).</p>
            <p class="mb-1"><span class="step-num">3</span> <strong>Sinkronkan gateway</strong> → Di detail aplikasi, klik <em>Sinkronkan</em> agar domain di server Apache mengikuti status.</p>
            <p class="mb-1"><span class="step-num">4</span> <strong>Langganan</strong> → Buat langganan: pilih client, aplikasi, paket (bulanan/tahunan), isi <strong>nominal tagihan (Rp)</strong>, tanggal mulai, toleransi hari.</p>
            <p class="mb-1"><span class="step-num">5</span> Centang <strong>Buat invoice awal otomatis</strong> jika tagihan pertama langsung dikirim.</p>
            <p class="mb-1"><span class="step-num">6</span> <strong>Finance</strong> → Client bayar → catat pembayaran di invoice → <strong>Verifikasi</strong> → aplikasi bisa otomatis <strong>Aktif</strong> jika sebelumnya suspend.</p>
            <p class="mb-0"><span class="step-num">7</span> Pantau <strong>Monitoring</strong> &amp; badge <strong>Health backend</strong> di detail aplikasi.</p>
        </div>
        <p class="mb-0 text-muted"><strong>Catatan:</strong> Domain harus sudah diarahkan (DNS/tunnel) ke server oleh IT — panel tidak mengganti DNS di registrar.</p>
    </div>
</div>

{{-- BAB 5 --}}
<div class="card card-primary card-outline collapsed-card" id="bab5">
    <div class="card-header">
        <h3 class="card-title">5. Menu Client</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <h4>Menambah client</h4>
        <ol>
            <li>Klik menu <strong>Client</strong> → tombol tambah / buat baru.</li>
            <li>Isi <strong>nama perusahaan</strong>, nama PIC, email, telepon, alamat jika ada.</li>
            <li>Status client: pilih <strong>Aktif</strong> untuk pelanggan yang masih berlangganan.</li>
            <li>Simpan.</li>
        </ol>
        <h4>Mengubah atau melihat detail</h4>
        <p>Klik nama client di daftar → lihat aplikasi terkait → dari sana bisa loncat ke aplikasi masing-masing.</p>
        <p class="mb-0">Jangan hapus client yang masih punya aplikasi/langganan aktif tanpa koordinasi IT.</p>
    </div>
</div>

{{-- BAB 6 --}}
<div class="card card-primary card-outline collapsed-card" id="bab6">
    <div class="card-header">
        <h3 class="card-title">6. Menu Aplikasi (domain, backend, gateway)</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <h4>Field penting saat buat aplikasi</h4>
        <table class="table table-sm table-bordered">
            <tr><th>Field</th><th>Artinya (bahasa awam)</th></tr>
            <tr><td>Nama aplikasi</td><td>Label internal, misalnya &quot;AKSES DATABASE&quot;.</td></tr>
            <tr><td>Client</td><td>Pemilik aplikasi.</td></tr>
            <tr><td>Domain utama</td><td>Alamat website yang diketik user di browser.</td></tr>
            <tr><td>Domain lain (alias)</td><td>Domain alternatif ke aplikasi yang sama.</td></tr>
            <tr><td>Backend (host:port)</td><td>Alamat program asli di server — biasanya diberikan IT, jangan asal ubah.</td></tr>
            <tr><td>Gateway node</td><td>Server pintu masuk — biasanya pilih default.</td></tr>
        </table>
        <h4>Setelah aplikasi dibuat</h4>
        <ol>
            <li>Buka <strong>detail aplikasi</strong>.</li>
            <li>Periksa bagian <strong>Routing &amp; Backend</strong> sudah benar.</li>
            <li>Klik <strong>Sinkronkan</strong> (jika tombol ada) supaya konfigurasi gateway ter-update.</li>
            <li>Lihat badge <strong>Sinkronisasi</strong>: idealnya <em>Sinkron</em> (hijau).</li>
        </ol>
        <p class="mb-0"><strong>Health backend</strong> mengecek program di belakang (port backend), bukan domain publik. Jika &quot;Backend down&quot; padahal website bisa dibuka, laporkan ke IT — bisa jadi salah path pengecekan.</p>
    </div>
</div>

{{-- BAB 7 --}}
<div class="card card-warning card-outline collapsed-card" id="bab7">
    <div class="card-header">
        <h3 class="card-title">7. Menangguhkan &amp; mengaktifkan akses</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <p>Di detail aplikasi, bagian <strong>Kontrol akses</strong>:</p>
        <table class="table table-sm table-bordered">
            <tr><th>Status</th><th>Dampak ke pengunjung website</th></tr>
            <tr><td><strong>Aktif</strong></td><td>Website normal, lalu lintas diteruskan ke backend.</td></tr>
            <tr><td><strong>Ditangguhkan</strong></td><td>Halaman &quot;akses ditangguhkan&quot; — client tidak bisa pakai aplikasi.</td></tr>
            <tr><td><strong>Pemeliharaan</strong></td><td>Halaman maintenance (sedang perbaikan).</td></tr>
        </table>
        <h4>Langkah manual suspend (misalnya tunggakan)</h4>
        <ol>
            <li>Pilih status <strong>Ditangguhkan</strong>.</li>
            <li>Isi <strong>alasan</strong> (wajib), contoh: &quot;Invoice INV-ABC123 belum lunas&quot;.</li>
            <li>Klik <strong>Terapkan status &amp; sync gateway</strong>.</li>
            <li>Pastikan <strong>Gateway (applied)</strong> ikut berubah dan sinkronisasi sukses.</li>
        </ol>
        <h4>Suspend otomatis (tagihan)</h4>
        <p>
            Sistem bisa otomatis menangguhkan aplikasi jika invoice masih belum lunas setelah
            <strong>jatuh tempo + hari toleransi</strong> (diisi saat buat langganan).
            Proses ini berjalan otomatis di server (jadwal malam); Anda tidak perlu klik apa pun.
        </p>
        <p class="mb-0">Mengaktifkan kembali: setelah pembayaran diverifikasi, aplikasi sering otomatis <strong>Aktif</strong> lagi. Jika tidak, ubah manual ke Aktif dengan alasan &quot;Pembayaran sudah diverifikasi&quot;.</p>
    </div>
</div>

{{-- BAB 8 --}}
<div class="card card-primary card-outline collapsed-card" id="bab8">
    <div class="card-header">
        <h3 class="card-title">8. Langganan &amp; nominal tagihan</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <p>Menu <strong>Langganan</strong> menghubungkan <em>satu aplikasi client</em> dengan aturan bayar.</p>
        <h4>Saat buat langganan</h4>
        <ul>
            <li><strong>Client</strong> dan <strong>Aplikasi</strong> harus cocok (aplikasi milik client itu).</li>
            <li><strong>Paket</strong> menentukan interval tagihan: bulanan atau tahunan — bukan harga wajib dari paket.</li>
            <li><strong>Nominal tagihan (Rp)</strong> — isi langsung jumlah yang akan muncul di invoice (mis. 500000).</li>
            <li><strong>Mulai</strong> — tanggal mulai periode langganan.</li>
            <li><strong>Toleransi (hari)</strong> — grace period setelah jatuh tempo sebelum auto-suspend (contoh: 3 hari).</li>
            <li><strong>Status langganan</strong> — biasanya Aktif untuk pelanggan bayar.</li>
        </ul>
        <p class="mb-0">Setelah langganan ada, buka <strong>Detail</strong> untuk melihat daftar invoice terkait.</p>
    </div>
</div>

{{-- BAB 9 --}}
<div class="card card-primary card-outline collapsed-card" id="bab9">
    <div class="card-header">
        <h3 class="card-title">9. Invoice otomatis &amp; manual</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <h4>Nomor invoice</h4>
        <p>Format: <code>INV-XXXXXX</code> (6 huruf/angka acak). Contoh: <code>INV-A3F2B1</code>.</p>
        <h4>Kapan invoice dibuat otomatis?</h4>
        <ul>
            <li>Saat buat langganan dengan centang <strong>Buat invoice awal otomatis</strong>.</li>
            <li>Secara berkala oleh sistem <strong>sebelum jatuh tempo</strong> (standar H-7 hari; bisa diubah IT menjadi H-1 untuk tagihan dadakan).</li>
        </ul>
        <p>Invoice baru otomatis berstatus <strong>Belum dibayar (unpaid)</strong>. Sistem tidak membuat invoice berikutnya jika masih ada tagihan terbuka (belum lunas).</p>
        <h4>Invoice manual</h4>
        <p>Dari <strong>Detail langganan</strong> → tombol <strong>Buat invoice baru</strong> — gunakan jika perlu tagihan extra di luar jadwal.</p>
        <h4>Menu Invoice (rekap)</h4>
        <p>Kotak hijau/kuning = jumlah sudah bayar vs belum. Unduh/cetak PDF per invoice, export CSV. Kirim email — segera hadir.</p>
        <p class="mb-0"><strong>Status invoice:</strong> Belum dibayar → Sebagian → Lunas / Jatuh tempo. Catat bayar di menu <strong>Pembayaran</strong>, bukan di Invoice.</p>
    </div>
</div>

{{-- BAB 10 --}}
<div class="card card-primary card-outline collapsed-card" id="bab10">
    <div class="card-header">
        <h3 class="card-title">10. Pembayaran &amp; verifikasi</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <h4>Menu Pembayaran</h4>
        <p>Semua pencatatan dan verifikasi ada di menu <strong>Pembayaran</strong> (sidebar TAGIHAN).</p>
        <h4>Langkah staff (catat bukti bayar)</h4>
        <ol>
            <li><strong>Pembayaran</strong> → <em>Catat pembayaran</em> (atau dari daftar invoice belum lunas).</li>
            <li>Pilih invoice, isi nominal, tanggal, metode, referensi, unggah bukti.</li>
            <li>Simpan — status: <strong>Menunggu verifikasi</strong>.</li>
        </ol>
        <h4>Langkah finance (verifikasi)</h4>
        <ol>
            <li><strong>Pembayaran</strong> → filter menunggu verifikasi.</li>
            <li>Cocokkan bukti dengan rekening.</li>
            <li><strong>Verifikasi</strong> atau <strong>Tolak</strong>.</li>
        </ol>
        <h4>Langganan — hapus invoice dobel</h4>
        <p>Di detail langganan, tombol <strong>Hapus</strong> pada invoice (hanya jika belum ada pembayaran terverifikasi). PDF invoice via tombol <strong>PDF</strong>. Ubah nominal/toleransi lewat <strong>Ubah langganan</strong>.</p>
        <p>Setelah verifikasi dan nominal mencukupi:</p>
        <ul>
            <li>Invoice menjadi <strong>Lunas</strong>.</li>
            <li>Aplikasi client yang ditangguhkan karena tunggakan dapat <strong>otomatis Aktif</strong> kembali (jika fitur ini diaktifkan di server).</li>
        </ul>
        <p class="mb-0">Pembayaran sebagian: invoice status <strong>Sebagian</strong> — suspend otomatis tetap bisa terjadi jika setelah toleransi masih ada sisa tagihan.</p>
    </div>
</div>

{{-- BAB 11 --}}
<div class="card card-primary card-outline collapsed-card" id="bab11">
    <div class="card-header">
        <h3 class="card-title">11. Monitoring kesehatan sistem</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <p>Menu <strong>Monitoring</strong> menampilkan kondisi server secara umum:</p>
        <ul>
            <li>Apache, tunnel (cloudflared), Gateway Agent hidup atau tidak.</li>
            <li>Beban CPU/RAM (informasi untuk IT).</li>
            <li>Ringkasan health aplikasi (up/down).</li>
        </ul>
        <p>Tombol <strong>Jalankan health check</strong> memperbarui status backend semua aplikasi. Pengecekan juga berjalan otomatis setiap beberapa menit.</p>
        <p class="mb-0">Di daftar/detail aplikasi, badge <strong>Backend OK / down</strong> membantu membedakan &quot;website ditutup sengaja (suspend)&quot; vs &quot;program di belakang mati&quot;.</p>
    </div>
</div>

{{-- BAB 12 --}}
<div class="card card-primary card-outline collapsed-card" id="bab12">
    <div class="card-header">
        <h3 class="card-title">12. Menu Gateway (sinkronisasi)</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <p><strong>Gateway</strong> adalah &quot;pintu&quot; yang menerima pengunjung dari internet lalu meneruskan ke backend client — atau menampilkan halaman suspend.</p>
        <h4>Yang perlu Anda ketahui</h4>
        <ul>
            <li>Setelah ubah status akses aplikasi, selalu pastikan <strong>sinkronisasi sukses</strong>.</li>
            <li>Menu <strong>Gateway</strong> (untuk admin): cek agent jalan, restart agent jika IT mengizinkan.</li>
            <li>Jika di detail aplikasi muncul peringatan gateway/kuning, screenshot dan kirim ke IT — biasanya masalah konfigurasi server, bukan salah input Anda.</li>
        </ul>
        <p class="mb-0"><strong>Retry Sync</strong> dipakai jika sync gagal (badge merah/gagal) setelah masalah server diperbaiki.</p>
    </div>
</div>

{{-- BAB 13 --}}
<div class="card card-secondary card-outline collapsed-card" id="bab13">
    <div class="card-header">
        <h3 class="card-title">13. Glosarium istilah</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Backend</dt>
            <dd class="col-sm-9">Program asli client yang berjalan di port tertentu di server.</dd>
            <dt class="col-sm-3">Gateway</dt>
            <dd class="col-sm-9">Lapisan di depan backend yang mengatur domain dan suspend.</dd>
            <dt class="col-sm-3">Sinkron</dt>
            <dd class="col-sm-9">Pengaturan di panel sudah sama dengan yang diterapkan di server.</dd>
            <dt class="col-sm-3">Langganan</dt>
            <dd class="col-sm-9">Kontrak bayar per aplikasi (nominal + interval).</dd>
            <dt class="col-sm-3">Jatuh tempo</dt>
            <dd class="col-sm-9">Tanggal deadline pembayaran invoice.</dd>
            <dt class="col-sm-3">Toleransi</dt>
            <dd class="col-sm-9">Hari extra setelah jatuh tempo sebelum akses otomatis ditangguhkan.</dd>
            <dt class="col-sm-3">H-7 / H-1</dt>
            <dd class="col-sm-9">Invoice otomatis dibuat 7 atau 1 hari sebelum jatuh tempo.</dd>
        </dl>
    </div>
</div>

{{-- BAB 14 --}}
<div class="card card-danger card-outline collapsed-card" id="bab14">
    <div class="card-header">
        <h3 class="card-title">14. FAQ &amp; masalah umum</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <h4>Client sudah bayar tapi masih suspend?</h4>
        <p>Pastikan finance sudah klik <strong>Verifikasi</strong>, bukan hanya upload bukti. Cek invoice status <strong>Lunas</strong>. Jika sudah lunas tapi masih suspend, ubah manual ke Aktif + sync gateway.</p>
        <h4>Suspend di panel tapi website masih bisa dibuka?</h4>
        <p>Domain mungkin belum lewat gateway (tunnel salah). Laporkan ke IT dengan screenshot peringatan kuning di detail aplikasi.</p>
        <h4>Backend down padahal website normal?</h4>
        <p>Health cek ke port backend, bukan domain publik. Lapor IT jika probe URL di detail aplikasi salah.</p>
        <h4>Invoice otomatis tidak muncul?</h4>
        <p>Cek: masih ada invoice belum lunas? Tanggal langganan (<code>next billing</code>) sudah diisi? Tunggu jadwal H-7 atau minta IT jalankan proses billing manual.</p>
        <h4>Tidak bisa simpan / menu hilang?</h4>
        <p>Role Anda mungkin Viewer — hubungi Super Admin. Atau sesi login expired — login ulang.</p>
        <p class="mb-0"><strong>Butuh bantuan teknis server?</strong> Siapkan: nama client, nama aplikasi, domain, screenshot halaman detail aplikasi + invoice terkait.</p>
    </div>
</div>

</div>
