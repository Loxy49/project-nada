<?php

namespace App\Http\Controllers;

use App\Models\Pengaturan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PengaturanController extends Controller
{
    // Daftar semua template nota
    public function index()
    {
        $templates = Pengaturan::orderByDesc('is_default')->orderBy('nama')->get();

        return view('pengaturan.index', compact('templates'));
    }

    public function create()
    {
        return view('pengaturan.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('logo-nota', 'public');
        }

        $template = Pengaturan::create($validated);

        // Kalau ini template pertama, atau admin centang "jadikan default"
        if ($request->boolean('is_default') || Pengaturan::count() === 1) {
            $template->jadikanDefault();
        }

        return redirect()->route('pengaturan.index')->with('success', 'Template nota baru berhasil dibuat.');
    }

    public function edit(Pengaturan $pengaturan)
    {
        return view('pengaturan.edit', ['template' => $pengaturan]);
    }

    public function update(Request $request, Pengaturan $pengaturan)
    {
        $validated = $this->validateRequest($request);

        if ($request->hasFile('logo')) {
            if ($pengaturan->logo) {
                Storage::disk('public')->delete($pengaturan->logo);
            }
            $validated['logo'] = $request->file('logo')->store('logo-nota', 'public');
        }

        $pengaturan->update($validated);

        if ($request->boolean('is_default')) {
            $pengaturan->jadikanDefault();
        }

        return redirect()->route('pengaturan.index')->with('success', 'Template nota berhasil diperbarui.');
    }

    public function destroy(Pengaturan $pengaturan)
    {
        if (Pengaturan::count() <= 1) {
            return back()->with('error', 'Minimal harus ada satu template nota.');
        }

        if ($pengaturan->logo) {
            Storage::disk('public')->delete($pengaturan->logo);
        }

        $wasDefault = $pengaturan->is_default;
        $pengaturan->delete();

        if ($wasDefault) {
            Pengaturan::first()?->jadikanDefault();
        }

        return back()->with('success', 'Template nota berhasil dihapus.');
    }

    // Jadikan template ini default lewat tombol di daftar (tanpa buka form edit)
    public function jadikanDefault(Pengaturan $pengaturan)
    {
        $pengaturan->jadikanDefault();

        return back()->with('success', "\"{$pengaturan->nama}\" kini menjadi template default.");
    }

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'nama' => 'required|string|max:100',
            'nama_toko' => 'required|string|max:100',
            'alamat' => 'nullable|string|max:255',
            'no_cs' => 'nullable|string|max:50',
            'teks_footer' => 'nullable|string|max:255',
            'gaya_default' => 'required|in:formal,struk',
            'orientasi' => 'required|in:potret,lanskap',
            'logo' => 'nullable|image|max:1024',
        ]);
    }
}
