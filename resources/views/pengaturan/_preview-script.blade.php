<script>
(function () {
    const namaInput = document.getElementById('input-nama-toko');
    const alamatInput = document.getElementById('input-alamat');
    const noCsInput = document.getElementById('input-no-cs');
    const footerInput = document.getElementById('input-footer');
    const gayaSelect = document.getElementById('input-gaya');
    const orientasiSelect = document.getElementById('input-orientasi');
    const logoInput = document.getElementById('input-logo');
    const existingLogoUrl = @json($existingLogoUrl ?? null);

    function buildPreviewHtml(dataUrlLogo) {
        const namaToko = namaInput.value || 'Nama Toko';
        const alamat = alamatInput.value || '';
        const noCs = noCsInput.value || '';
        const footer = footerInput.value || 'Terima kasih.';
        const gaya = gayaSelect.value;
        const orientasi = orientasiSelect.value;
        const logoTag = dataUrlLogo
            ? `<img src="${dataUrlLogo}" alt="Logo" style="width:100%; height:100%; object-fit:cover; border-radius:inherit;">`
            : '<svg viewBox="0 0 24 24" width="60%" height="60%" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2.5h9l2.5 2.5v16.5l-3-1.6-3 1.6-3-1.6-3 1.6V2.5z"/><path d="M8.3 7.5h7.2M8.3 11.5h7.2M8.3 15.5H13"/></svg>';
        const now = new Date();
        const bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        const tanggal = `${String(now.getDate()).padStart(2,'0')} ${bulan[now.getMonth()]} ${now.getFullYear()}`;

        const itemsFormal = `
            <tr><td colspan="2">Alat tulis kantor</td><td>2</td><td style="text-align:right;">Rp50.000</td></tr>
            <tr><td colspan="2">Parkir</td><td>1</td><td style="text-align:right;">Rp5.000</td></tr>`;
        const itemsStruk = `
            <div class="baris-struk"><span class="k">1. Alat tulis kantor<div style="font-size:11px;color:#a3a3a3;">2 x Rp25.000</div></span><span class="v">Rp50.000</span></div>
            <div class="baris-struk"><span class="k">2. Parkir</span><span class="v">Rp5.000</span></div>`;

        return `<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">
        <title>Preview - ${namaToko}</title>
        <style>
            * { box-sizing: border-box; }
            body { font-family: -apple-system, 'Segoe UI', sans-serif; background:#f7f6f2; color:#4b5563; margin:0; padding:24px; }
            .toolbar-preview { max-width:720px; margin:0 auto 14px; display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
            .toolbar-preview .kembali { display:inline-flex; align-items:center; gap:6px; color:#5a7a62; text-decoration:none; font-size:14px; font-weight:500; background:none; border:none; cursor:pointer; padding:0; }
            .badge-preview { background:#FBF3D9; color:#8a6d1a; font-size:12.5px; padding:6px 12px; border-radius:8px; }
            .kertas { background:#fff; margin:0 auto; border-radius:10px; border:1px solid #e5e4e2; box-shadow:0 1px 3px rgba(0,0,0,.06); }
            #formal.kertas { max-width:${orientasi === 'lanskap' ? '980px' : '720px'}; padding:40px 48px; }
            #formal .kop { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #4b5563; padding-bottom:14px; margin-bottom:18px; }
            #formal .kop .brand { font-size:20px; font-weight:700; display:flex; align-items:center; gap:8px; }
            #formal .kop .logo-box { width:26px; height:26px; border-radius:6px; overflow:hidden; display:flex; align-items:center; justify-content:center; background:#f7f6f2; font-size:16px; }
            #formal .kop .sub { font-size:12px; color:#6b7280; margin-top:2px; }
            #formal .kop .meta { text-align:right; font-size:13px; }
            #formal h2.judul { text-align:center; text-transform:uppercase; letter-spacing:.5px; font-size:17px; margin:0 0 18px; }
            #formal table.rincian { width:100%; border-collapse:collapse; font-size:14px; margin-bottom:4px; }
            #formal table.rincian td { padding:7px 4px; vertical-align:top; }
            #formal .nominal-box { background:#f7f6f2; border-radius:8px; padding:14px 16px; margin:16px 0; display:flex; justify-content:space-between; align-items:center; }
            #formal .nominal-box .angka { font-size:22px; font-weight:700; }
            #formal .ttd { display:flex; justify-content:space-between; margin-top:48px; }
            #formal .ttd .kotak { width:45%; text-align:center; font-size:13px; }
            #formal .ttd .garis { margin-top:56px; border-top:1px solid #4b5563; padding-top:6px; font-weight:600; }
            #formal .footer-note { margin-top:32px; font-size:11px; color:#a3a3a3; text-align:center; }
            #struk.kertas { max-width:${orientasi === 'lanskap' ? '600px' : '380px'}; padding:30px 26px; border-radius:20px; }
            #struk .icon-box { width:44px; height:44px; margin:0 auto 14px; background:#E7F0E8; color:#5a7a62; border-radius:13px; display:flex; align-items:center; justify-content:center; overflow:hidden; font-size:20px; }
            #struk .company-name { font-size:15.5px; font-weight:700; text-align:center; }
            #struk .company-sub { font-size:11.5px; color:#6b7280; text-align:center; line-height:1.5; }
            #struk .divider { border:none; border-top:1px dashed #a3a3a3; margin:12px 0; }
            #struk .judul { text-align:center; font-weight:700; font-size:14.5px; margin-bottom:16px; }
            #struk .baris-struk { display:flex; justify-content:space-between; align-items:flex-start; font-size:13.5px; padding:6px 0; }
            #struk .baris-struk .k { color:#6b7280; }
            #struk .baris-struk .v { font-weight:500; text-align:right; white-space:nowrap; }
            #struk .footer-note { margin-top:4px; font-size:11.5px; color:#a3a3a3; text-align:center; line-height:1.6; }
            @media print {
                @page { size: A4 ${orientasi === 'lanskap' ? 'landscape' : 'portrait'}; margin: 10mm; }
                body { background:#fff; padding:0; }
                .toolbar-preview { display:none !important; }
            }
        </style></head><body>
            <div class="toolbar-preview">
                <button type="button" class="kembali" onclick="history.back()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="m15 18-6-6 6-6"/></svg>
                    Kembali
                </button>
                <div class="badge-preview">Orientasi: ${orientasi === 'lanskap' ? 'Landscape' : 'Portrait'}</div>
            </div>
            ${gaya === 'formal' ? `
            <div id="formal" class="kertas">
                <div class="kop">
                    <div>
                        <div class="brand"><span class="logo-box">${logoTag}</span> ${namaToko}</div>
                        <div class="sub">Bukti Pengeluaran Kas Kecil</div>
                    </div>
                    <div class="meta">
                        <div>No: PREVIEW-0001</div>
                        <div>${tanggal}</div>
                    </div>
                </div>
                <h2 class="judul">Contoh Nota</h2>
                <table class="rincian">${itemsFormal}</table>
                <div class="nominal-box"><span>Total Nominal</span><span class="angka">Rp55.000</span></div>
                <div class="ttd">
                    <div class="kotak"><div>Kasir,</div><div class="garis">Nama Kasir</div></div>
                    <div class="kotak"><div>Mengetahui,</div><div class="garis">Nama Atasan</div></div>
                </div>
                <div class="footer-note">${footer}</div>
            </div>` : `
            <div id="struk" class="kertas">
                <div class="icon-box">${logoTag}</div>
                <div class="company-name">${namaToko}</div>
                ${alamat ? `<div class="company-sub">${alamat}</div>` : ''}
                ${noCs ? `<div class="company-sub">CS: ${noCs}</div>` : ''}
                <hr class="divider">
                <div class="judul">Bukti nota pengeluaran</div>
                <div style="text-align:center; font-size:11.5px; color:#6b7280; margin-top:-8px; margin-bottom:8px;">No. Transaksi: PREVIEW-0001</div>
                <hr class="divider">
                ${itemsStruk}
                <hr class="divider">
                <div class="baris-struk total" style="font-weight:700;"><span class="k">Total</span><span class="v" style="font-size:16.5px;">Rp55.000</span></div>
                <hr class="divider">
                <div class="footer-note">${footer}</div>
            </div>`}
        </body></html>`;
    }

    function showPreview(dataUrlLogo) {
        const html = buildPreviewHtml(dataUrlLogo);
        const blob = new Blob([html], { type: 'text/html' });
        const url = URL.createObjectURL(blob);
        window.location.href = url;
    }

    function openPreview() {
        if (logoInput && logoInput.files && logoInput.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                showPreview(e.target.result);
            };
            reader.readAsDataURL(logoInput.files[0]);
        } else {
            showPreview(existingLogoUrl);
        }
    }

    document.getElementById('btn-preview').addEventListener('click', openPreview);
})();
</script>
