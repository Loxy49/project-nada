<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

// Kelola akun oleh admin. Registrasi publik ditutup; akun dibuat di sini, dan dinonaktifkan (bukan dihapus).
// Rutenya dijaga middleware can:admin di routes/web.php.
class UserController extends Controller
{
    public function index()
    {
        $users = User::orderByDesc('aktif')->orderBy('name')->get();

        return view('pengguna.index', compact('users'));
    }

    public function create()
    {
        return view('pengguna.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['karyawan', 'admin'])],
            'departemen' => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'departemen' => $data['departemen'] ?? null,
            'password' => $data['password'], // di-hash otomatis oleh cast 'hashed' di model User
            'aktif' => true,
        ]);

        return redirect()->route('pengguna.index')
            ->with('success', "Akun {$data['name']} dibuat. Sampaikan password sementaranya langsung ke yang bersangkutan dan minta diganti lewat menu Profil.");
    }

    public function edit(User $user)
    {
        return view('pengguna.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['karyawan', 'admin'])],
            'departemen' => ['nullable', 'string', 'max:100'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $aktif = $request->boolean('aktif');

        // Cegah admin mengunci dirinya sendiri (jadi selalu ada minimal satu admin aktif).
        if ($user->is(Auth::user()) && ($data['role'] !== 'admin' || ! $aktif)) {
            return back()->withInput()->withErrors([
                'role' => 'Anda tidak dapat menurunkan peran atau menonaktifkan akun Anda sendiri. Minta bantuan admin lain.',
            ]);
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'departemen' => $data['departemen'] ?? null,
            'aktif' => $aktif,
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return redirect()->route('pengguna.index')->with('success', "Akun {$user->name} diperbarui.");
    }
}
