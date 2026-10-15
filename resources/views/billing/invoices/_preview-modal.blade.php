@push('styles')
<style>
    #invoicePreviewModal .modal-dialog {
        max-width: 1180px;
    }
    #invoicePreviewModal .modal-content {
        border: 0;
        border-radius: .5rem;
        overflow: hidden;
    }
    #invoicePreviewModal .modal-body {
        background: #dfe3e8;
        min-height: 70vh;
        padding: 12px;
        position: relative;
    }
    #invoicePreviewContent {
        display: none;
        max-height: 72vh;
        overflow: auto;
    }
    #invoicePreviewLoading {
        left: 50%;
        position: absolute;
        top: 50%;
        transform: translate(-50%, -50%);
        z-index: 1;
    }
    @media (max-width: 767.98px) {
        #invoicePreviewModal .modal-dialog {
            margin: .5rem;
        }
        #invoicePreviewContent {
            max-height: 68vh;
        }
    }
</style>
@endpush

<div class="modal fade" id="invoicePreviewModal" tabindex="-1" role="dialog"
    aria-labelledby="invoicePreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title font-weight-bold" id="invoicePreviewModalLabel">
                        <i class="fas fa-file-invoice text-primary mr-2"></i>Pratinjau invoice
                    </h5>
                    <small class="text-muted" id="invoicePreviewNumber"></small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="invoicePreviewLoading" class="text-center text-muted">
                    <div class="spinner-border text-primary mb-2" role="status"></div>
                    <div>Memuat invoice...</div>
                </div>
                <div id="invoicePreviewContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Tutup</button>
                <!-- <a href="#" class="btn btn-outline-secondary" id="invoicePreviewNewTab" target="_blank" rel="noopener">
                    <i class="fas fa-external-link-alt mr-1"></i> Buka tab baru
                </a> -->
                <!-- <button type="button" class="btn btn-outline-secondary" id="invoicePreviewDownload" disabled>
                    <i class="fas fa-file-pdf mr-1"></i> Unduh PDF
                </button> -->
                <button type="button" class="btn btn-primary" id="invoicePreviewPrint" disabled>
                    <i class="fas fa-print mr-1"></i> Cetak
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
(function () {
    var $modal = $('#invoicePreviewModal');
    var $content = $('#invoicePreviewContent');
    var $loading = $('#invoicePreviewLoading');
    var $download = $('#invoicePreviewDownload');
    var $print = $('#invoicePreviewPrint');
    var previewPageUrl = '';
    var invoiceFileName = 'invoice.pdf';

    function resetLoading() {
        $loading.show().html(
            '<div class="spinner-border text-primary mb-2" role="status"></div>' +
            '<div>Memuat invoice...</div>'
        );
    }

    function setActionsEnabled(enabled) {
        $download.prop('disabled', !enabled);
        $print.prop('disabled', !enabled);
    }

    function sanitizeFileName(name) {
        var safe = (name || 'invoice').replace(/[^\w\-]+/g, '_');
        return safe + '.pdf';
    }

    var PDF_PAGE_WIDTH_PX = 794;

    /** Clone pratinjau — harus punya lebar penuh & tidak di luar viewport (html2canvas) */
    function buildPdfSourceElement() {
        var $source = $content.find('.invoice-preview-embed');
        if (!$source.length) {
            return null;
        }

        var $clone = $source.clone(true, true);
        $clone.find('.invoice-a4').css({
            boxShadow: 'none',
            margin: '0',
            width: PDF_PAGE_WIDTH_PX + 'px',
            maxWidth: PDF_PAGE_WIDTH_PX + 'px'
        });

        var $wrap = $('<div id="invoicePdfExportWrap">').css({
            position: 'fixed',
            left: '0',
            top: '0',
            width: PDF_PAGE_WIDTH_PX + 'px',
            background: '#fff',
            zIndex: '99999',
            opacity: '0.01',
            pointerEvents: 'none',
            overflow: 'hidden'
        }).append($clone);

        $('body').append($wrap);

        return {
            node: $clone.get(0),
            cleanup: function () {
                $wrap.remove();
            }
        };
    }

    function generatePdf() {
        if (typeof html2pdf === 'undefined') {
            alert('Pustaka PDF belum dimuat. Muat ulang halaman atau gunakan Buka tab baru.');
            return;
        }

        var built = buildPdfSourceElement();
        if (!built) {
            alert('Invoice belum siap. Tunggu pratinjau selesai dimuat.');
            return;
        }

        var $btn = $download;
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

        var opt = {
            margin: [5, 5, 5, 5],
            filename: invoiceFileName,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: {
                scale: 2,
                useCORS: true,
                logging: false,
                scrollX: 0,
                scrollY: -window.scrollY,
                width: PDF_PAGE_WIDTH_PX,
                windowWidth: PDF_PAGE_WIDTH_PX
            },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
            pagebreak: { mode: ['css', 'legacy'] }
        };

        window.requestAnimationFrame(function () {
            html2pdf().set(opt).from(built.node).save().then(function () {
                built.cleanup();
                $btn.prop('disabled', false).html(originalHtml);
            }).catch(function () {
                built.cleanup();
                $btn.prop('disabled', false).html(originalHtml);
                alert('Gagal membuat PDF. Coba Cetak atau Buka tab baru.');
            });
        });
    }

    $(document).on('click', '.btn-invoice-preview', function () {
        var $button = $(this);
        var previewUrl = $button.attr('data-preview-url');
        previewPageUrl = $button.attr('data-page-url') || previewUrl;

        if (!previewUrl) {
            return;
        }

        var invNum = $button.attr('data-invoice-number') || '';
        $('#invoicePreviewNumber').text(invNum);
        invoiceFileName = sanitizeFileName(invNum);
        $('#invoicePreviewNewTab').attr('href', previewPageUrl);

        resetLoading();
        $content.hide().empty();
        setActionsEnabled(false);
        $modal.modal('show');

        if (previewUrl.indexOf('http') === 0) {
            try {
                var parsed = new URL(previewUrl, window.location.origin);
                previewUrl = parsed.pathname + parsed.search;
            } catch (e) {
                // keep original
            }
        }

        $.ajax({
            url: previewUrl,
            method: 'GET',
            dataType: 'html',
            cache: false,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        }).done(function (html) {
            if (!html || String(html).trim() === '') {
                $loading.html('<div class="text-danger">Invoice kosong. Coba buka tab baru.</div>');
                return;
            }
            $loading.hide();
            $content.html(html).show();
            setActionsEnabled(true);
        }).fail(function (xhr) {
            var msg = 'Gagal memuat invoice.';
            if (xhr.status === 401 || xhr.status === 419) {
                msg = 'Sesi login habis. Muat ulang halaman lalu coba lagi.';
            } else if (xhr.status === 403) {
                msg = 'Anda tidak punya akses ke invoice ini.';
            } else if (xhr.status === 404) {
                msg = 'Invoice tidak ditemukan.';
            }
            $loading.html('<div class="text-danger">' + msg + ' <a href="' + previewPageUrl + '" target="_blank" rel="noopener">Buka tab baru</a></div>');
        });
    });

    $download.on('click', function () {
        generatePdf();
    });

    $print.on('click', function () {
        if (!previewPageUrl) {
            return;
        }
        var printWindow = window.open(previewPageUrl, '_blank');
        if (printWindow) {
            printWindow.focus();
        }
    });

    $modal.on('hidden.bs.modal', function () {
        $content.hide().empty();
        previewPageUrl = '';
        invoiceFileName = 'invoice.pdf';
        resetLoading();
        setActionsEnabled(false);
        $('#invoicePdfExportWrap').remove();
    });
})();
</script>
@endpush
