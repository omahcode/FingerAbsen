<?php

namespace App\Http\Controllers;

use App\Models\Major;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Pastikan hanya Administrator yang dapat mengelola data pengguna
     */
    protected function authorizeAdmin(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Akses terbatas untuk Administrator.');
        }
    }

    /**
     * Tampilkan Halaman Daftar Pengguna (Guru, Kepala Sekolah, Admin)
     */
    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $query = User::with(['schoolClass', 'major']);

        // Filter berdasarkan Role
        $roleFilter = $request->query('role');
        if (! empty($roleFilter) && in_array($roleFilter, ['admin', 'kepala_sekolah', 'guru'])) {
            $query->where('role', $roleFilter);
        }

        // Pencarian Nama / Email
        $search = $request->query('search');
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderByRaw("CASE 
                WHEN role = 'admin' THEN 1 
                WHEN role = 'kepala_sekolah' THEN 2 
                ELSE 3 
            END")
            ->orderBy('name')
            ->get();

        // Hitung statistik pengguna
        $totalUsers = User::count();
        $totalKepsek = User::where('role', 'kepala_sekolah')->count();
        $totalGuru = User::where('role', 'guru')->count();
        $totalAdmin = User::where(function ($q) {
            $q->where('role', 'admin')->orWhereNull('role')->orWhere('role', '');
        })->count();

        $classes = SchoolClass::orderBy('name')->get();
        $majors = Major::orderBy('name')->get();

        return view('admin.users.index', compact(
            'users',
            'classes',
            'majors',
            'roleFilter',
            'search',
            'totalUsers',
            'totalKepsek',
            'totalGuru',
            'totalAdmin'
        ));
    }

    /**
     * Simpan Pengguna Baru
     */
    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,kepala_sekolah,guru',
            'school_class_id' => 'nullable|exists:school_classes,id',
            'major_id' => 'nullable|exists:majors,id',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email login wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar pada pengguna lain.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'role.required' => 'Pilih peran / role pengguna.',
        ]);

        $role = $request->role;
        $isGuru = ($role === 'guru');

        $user = User::create([
            'name' => $request->name,
            'email' => strtolower(trim($request->email)),
            'password' => Hash::make($request->password),
            'role' => $role,
            'school_class_id' => $isGuru ? $request->school_class_id : null,
            'major_id' => $isGuru ? $request->major_id : null,
        ]);

        $roleLabel = match ($role) {
            'kepala_sekolah' => 'Kepala Sekolah',
            'admin' => 'Administrator',
            default => 'Guru / Wali Kelas',
        };

        return back()->with('success', "Akun {$roleLabel} '{$user->name}' berhasil dibuat!");
    }

    /**
     * Perbarui Data Pengguna
     */
    public function update(Request $request, User $user)
    {
        $this->authorizeAdmin();

        $request->validate([
            'name' => 'required|string|max:100',
            'email' => "required|email|max:255|unique:users,email,{$user->id}",
            'role' => 'required|in:admin,kepala_sekolah,guru',
            'password' => 'nullable|string|min:6',
            'school_class_id' => 'nullable|exists:school_classes,id',
            'major_id' => 'nullable|exists:majors,id',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email ini sudah digunakan oleh akun lain.',
            'password.min' => 'Password minimal 6 karakter.',
        ]);

        $role = $request->role;
        $isGuru = ($role === 'guru');

        $data = [
            'name' => $request->name,
            'email' => strtolower(trim($request->email)),
            'role' => $role,
            'school_class_id' => $isGuru ? $request->school_class_id : null,
            'major_id' => $isGuru ? $request->major_id : null,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return back()->with('success', "Data akun '{$user->name}' berhasil diperbarui.");
    }

    /**
     * Reset / Ganti Password Pengguna Secara Cepat
     */
    public function resetPassword(Request $request, User $user)
    {
        $this->authorizeAdmin();

        $request->validate([
            'new_password' => 'required|string|min:6',
        ], [
            'new_password.required' => 'Kata sandi baru wajib diisi.',
            'new_password.min' => 'Kata sandi baru minimal 6 karakter.',
        ]);

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return back()->with('success', "Kata sandi akun '{$user->name}' ({$user->email}) berhasil direset!");
    }

    /**
     * Hapus Akun Pengguna
     */
    public function destroy(User $user)
    {
        $this->authorizeAdmin();

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif login!');
        }

        $name = $user->name;
        $user->delete();

        return back()->with('success', "Akun '{$name}' berhasil dihapus dari sistem.");
    }
}
