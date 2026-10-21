{{-- Memperkecil foto bukti di browser sebelum diunggah, supaya foto HP (3-8 MB) tidak ditolak batas server 2 MB.
     Dipakai di form buat & edit nota. Tanpa library; kalau gagal, foto asli tetap dikirim (selama masih <= 2 MB). --}}
<script>
(function () {
    var BATAS_SERVER = 2 * 1024 * 1024;   // batas validasi di NotaController (max:2048 KB)
    var TARGET_BYTE = 1.6 * 1024 * 1024;  // target hasil, sisakan ruang aman di bawah batas server
    var LEWATI_BAWAH = 1024 * 1024;       // foto <= 1 MB tidak perlu diubah (kualitas dijaga)
    var MAKS_SISI = 1600;                 // sisi terpanjang hasil, cukup untuk membaca struk

    var input = document.querySelector('input[name="bukti_file"]');
    if (!input) return;

    var form = input.form;
    var info = document.createElement('small');
    info.style.display = 'block';
    info.style.marginTop = '4px';
    input.parentNode.appendChild(info);

    var versi = 0;

    function format(byte) {
        return byte >= 1048576
            ? (byte / 1048576).toFixed(1).replace('.', ',') + ' MB'
            : Math.max(1, Math.round(byte / 1024)) + ' KB';
    }

    function tampil(teks, warna) {
        info.textContent = teks;
        info.style.color = warna;
    }

    function kunciTombol(kunci) {
        if (!form) return;
        var tombol = form.querySelectorAll('button[type="submit"]');
        for (var i = 0; i < tombol.length; i++) tombol[i].disabled = kunci;
    }

    function muat(file) {
        return new Promise(function (resolve, reject) {
            var url = URL.createObjectURL(file);
            var img = new Image();
            img.onload = function () { URL.revokeObjectURL(url); resolve(img); };
            img.onerror = function () { URL.revokeObjectURL(url); reject(new Error('tidak bisa dibaca')); };
            img.src = url;
        });
    }

    function keBlob(img, sisi, kualitas) {
        var skala = Math.min(1, sisi / Math.max(img.naturalWidth, img.naturalHeight));
        var w = Math.max(1, Math.round(img.naturalWidth * skala));
        var h = Math.max(1, Math.round(img.naturalHeight * skala));
        var kanvas = document.createElement('canvas');
        kanvas.width = w;
        kanvas.height = h;
        var ctx = kanvas.getContext('2d');
        ctx.fillStyle = '#ffffff';          // PNG transparan jadi putih, bukan hitam
        ctx.fillRect(0, 0, w, h);
        ctx.drawImage(img, 0, 0, w, h);
        return new Promise(function (resolve) { kanvas.toBlob(resolve, 'image/jpeg', kualitas); });
    }

    // Turunkan kualitas dulu, kalau masih besar baru kecilkan dimensi.
    async function kompres(file) {
        var img = await muat(file);
        var sisi = MAKS_SISI;
        var kualitas = 0.82;
        var blob = await keBlob(img, sisi, kualitas);

        while (blob && blob.size > TARGET_BYTE && (kualitas > 0.5 || sisi > 900)) {
            if (kualitas > 0.5) {
                kualitas = Math.round((kualitas - 0.1) * 100) / 100;
            } else {
                sisi = Math.round(sisi * 0.8);
            }
            blob = await keBlob(img, sisi, kualitas);
        }
        return blob;
    }

    function ganti(file) {
        var dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
    }

    input.addEventListener('change', async function () {
        var saya = ++versi;
        kunciTombol(false);   // pilihan baru membatalkan proses sebelumnya, jangan sampai tombol tertinggal terkunci
        var file = input.files && input.files[0];
        if (!file) { tampil('', ''); return; }

        if (file.size <= LEWATI_BAWAH || typeof DataTransfer === 'undefined') {
            if (file.size > BATAS_SERVER) {
                input.value = '';
                tampil('Foto terlalu besar (' + format(file.size) + ', maksimal 2 MB). Pilih foto yang lebih kecil.', 'var(--error-text)');
            } else {
                tampil('Foto siap diunggah (' + format(file.size) + ').', 'var(--success-text)');
            }
            return;
        }

        kunciTombol(true);
        tampil('Memperkecil foto…', 'var(--muted)');

        try {
            var blob = await kompres(file);
            if (saya !== versi) return;   // pengguna sudah memilih file lain

            if (!blob) throw new Error('gagal membuat gambar');

            if (blob.size >= file.size && file.size <= BATAS_SERVER) {
                tampil('Foto siap diunggah (' + format(file.size) + ').', 'var(--success-text)');   // hasil tidak lebih kecil: pakai asli
            } else if (blob.size > BATAS_SERVER) {
                input.value = '';
                tampil('Foto masih terlalu besar setelah diperkecil. Pilih foto lain.', 'var(--error-text)');
            } else {
                var nama = (file.name.replace(/\.[^.]+$/, '') || 'bukti') + '.jpg';
                ganti(new File([blob], nama, { type: 'image/jpeg', lastModified: Date.now() }));
                tampil('Foto diperkecil: ' + format(file.size) + ' → ' + format(blob.size) + '.', 'var(--success-text)');
            }
        } catch (e) {
            if (saya !== versi) return;
            if (file.size > BATAS_SERVER) {
                input.value = '';
                tampil('Foto tidak bisa diproses dan terlalu besar (' + format(file.size) + '). Coba foto lain atau format JPG/PNG.', 'var(--error-text)');
            } else {
                tampil('Foto siap diunggah (' + format(file.size) + ').', 'var(--success-text)');
            }
        } finally {
            if (saya === versi) kunciTombol(false);
        }
    });
})();
</script>
