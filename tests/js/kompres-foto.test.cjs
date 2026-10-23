const { JSDOM } = require('jsdom');
const fs = require('fs');
const assert = require('assert');

const sumber = fs.readFileSync(require('path').join(__dirname, '../../resources/views/nota/_kompres-foto.blade.php'), 'utf8');
const js = sumber.match(/<script>([\s\S]*?)<\/script>/)[1];
new Function(js); // cek sintaks

const tunda = (ms) => new Promise((r) => setTimeout(r, ms));
const MB = 1024 * 1024;

// opsi: ukuran piksel asli foto, fungsi ukuran blob hasil, apakah gambar gagal dimuat
function buatLingkungan({ lebar = 4000, tinggi = 3000, ukuranBlob, gagalMuat = false } = {}) {
  const dom = new JSDOM(`<form><div class="field"><input type="file" name="bukti_file"><button type="submit">Simpan</button></div></form>`, { runScripts: 'outside-only' });
  const w = dom.window;
  w.URL.createObjectURL = () => 'blob:x';
  w.URL.revokeObjectURL = () => {};
  w.Image = class {
    constructor() { this.naturalWidth = lebar; this.naturalHeight = tinggi; }
    set src(_) { setTimeout(() => (gagalMuat ? this.onerror() : this.onload()), 0); }
  };
  w.HTMLCanvasElement.prototype.getContext = () => ({ fillRect() {}, drawImage() {}, fillStyle: '' });
  w.HTMLCanvasElement.prototype.toBlob = function (cb, tipe, kualitas) {
    const ukuran = Math.round(ukuranBlob(this.width, this.height, kualitas));
    cb(new w.Blob([new Uint8Array(ukuran)], { type: tipe }));
  };
  const input = w.document.querySelector('input');
  const tombol = w.document.querySelector('button');
  let berkas = [];
  Object.defineProperty(input, 'files', { get: () => berkas, set: (v) => { berkas = v; }, configurable: true });
  w.DataTransfer = class { constructor() { const arr = []; this.items = { add: (f) => arr.push(f) }; this.files = arr; } };
  w.eval(js);
  const pilih = (file) => { berkas = [file]; input.dispatchEvent(new w.Event('change')); };
  const info = () => input.parentNode.querySelector('small').textContent;
  return { w, input, tombol, pilih, info, berkas: () => berkas, buatFile: (nama, ukuran, tipe = 'image/jpeg') => new w.File([new Uint8Array(ukuran)], nama, { type: tipe }) };
}

(async () => {
  // A. Foto HP 5 MB: terus diperkecil sampai di bawah target 1,6 MB, nama jadi .jpg, tombol dikunci lalu dibuka
  {
    const e = buatLingkungan({ ukuranBlob: (w, h, q) => w * h * q * 1.2 });
    const asli = e.buatFile('foto.png', 5 * MB, 'image/png');
    e.pilih(asli);
    assert.strictEqual(e.tombol.disabled, true, 'tombol harus terkunci selama memproses');
    await tunda(80);
    const hasil = e.berkas()[0];
    assert.strictEqual(e.tombol.disabled, false, 'tombol harus terbuka lagi');
    assert.strictEqual(hasil.name, 'foto.jpg');
    assert.strictEqual(hasil.type, 'image/jpeg');
    assert.ok(hasil.size <= 1.6 * MB && hasil.size < asli.size, 'ukuran hasil ' + hasil.size);
    assert.ok(e.info().includes('→'), e.info());
    console.log('A ok  :', e.info());
  }
  // B. Foto kecil (500 KB): tidak diubah
  {
    const e = buatLingkungan({ ukuranBlob: () => 1 });
    const asli = e.buatFile('kecil.jpg', 500 * 1024);
    e.pilih(asli);
    await tunda(30);
    assert.strictEqual(e.berkas()[0], asli);
    assert.ok(e.info().includes('siap diunggah'));
    console.log('B ok  :', e.info());
  }
  // C. Gambar tidak bisa dibaca (mis. HEIC di desktop) dan > 2 MB: dikosongkan + pesan jelas
  {
    const e = buatLingkungan({ gagalMuat: true, ukuranBlob: () => 1 });
    e.pilih(e.buatFile('heic.jpg', 3 * MB));
    await tunda(50);
    assert.ok(e.info().includes('terlalu besar'), e.info());
    assert.strictEqual(e.tombol.disabled, false);
    console.log('C ok  :', e.info());
  }
  // D. Gagal dibaca tapi sudah <= 2 MB: foto asli tetap boleh dikirim
  {
    const e = buatLingkungan({ gagalMuat: true, ukuranBlob: () => 1 });
    const asli = e.buatFile('aneh.jpg', 1.5 * MB);
    e.pilih(asli);
    await tunda(50);
    assert.strictEqual(e.berkas()[0], asli);
    assert.ok(e.info().includes('siap diunggah'));
    console.log('D ok  :', e.info());
  }
  // E. Hasil tetap > 2 MB walau sudah diperkecil maksimal: ditolak dengan pesan, tidak mengirim file besar
  {
    const e = buatLingkungan({ ukuranBlob: () => 3 * MB });
    const asli = e.buatFile('raksasa.jpg', 6 * MB);
    e.pilih(asli);
    await tunda(120);
    assert.ok(e.info().includes('masih terlalu besar'), e.info());
    assert.strictEqual(e.tombol.disabled, false);
    console.log('E ok  :', e.info());
  }
  // F. Pilih file kedua saat file pertama masih diproses: hanya hasil file terakhir yang berlaku
  {
    const e = buatLingkungan({ ukuranBlob: (w, h, q) => w * h * q * 1.2 });
    e.pilih(e.buatFile('pertama.jpg', 5 * MB));
    const kedua = e.buatFile('kedua.jpg', 300 * 1024);
    e.pilih(kedua);
    await tunda(120);
    assert.strictEqual(e.berkas()[0], kedua);
    assert.ok(e.info().includes('siap diunggah'));
    assert.strictEqual(e.tombol.disabled, false);
    console.log('F ok  :', e.info());
  }
  console.log('\nSemua skenario JS lolos');
})().catch((err) => { console.error('GAGAL:', err.message); process.exit(1); });
