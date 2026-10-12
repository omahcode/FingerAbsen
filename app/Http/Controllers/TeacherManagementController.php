<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SchoolClass;
use App\Models\Major;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TeacherManagementController extends Controller
{
    /**
     * Tampilkan Daftar Akun Guru / Wali Kelas
     */
    public function index()
    {
        $teachers = User::with(['schoolClass', 'major'])
            ->where('role', 'guru')
            ->orderBy('name')
            ->get();

        $classes = SchoolClass::orderBy('name')->get();
        $majors = Major::orderBy('name')->get();

        return view('admin.teachers.index', compact('teachers', 'classes', 'majors'));
    }

    /**
     * Tambah Akun Guru Baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:100',
            'email'           => 'required|email|unique:users,email',
            'password'        => 'required|string|min:6',
            'school_class_id' => 'nullable|exists:school_classes,id',
            'major_id'        => 'nullable|exists:majors,id',
        ]);

        User::create([
            'name'            => $request->name,
            'email'           => $request->email,
            'password'        => Hash::make($request->password),
            'role'            => 'guru',
            'school_class_id' => $request->school_class_id,
            'major_id'        => $request->major_id,
        ]);

        return back()->with('success', "Akun Guru {$request->name} berhasil dibuat!");
    }

    /**
     * Perbarui Data Guru
     */
    public function update(Request $request, $id)
    {
        $teacher = User::where('role', 'guru')->findOrFail($id);

        $request->validate([
            'name'            => 'required|string|max:100',
            'email'           => "required|email|unique:users,email,{$id}",
            'password'        => 'nullable|string|min:6',
            'school_class_id' => 'nullable|exists:school_classes,id',
            'major_id'        => 'nullable|exists:majors,id',
        ]);

        $data = [
            'name'            => $request->name,
            'email'           => $request->email,
            'school_class_id' => $request->school_class_id,
            'major_id'        => $request->major_id,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $teacher->update($data);

        return back()->with('success', "Data Guru {$teacher->name} berhasil diperbarui.");
    }

    /**
     * Hapus Akun Guru
     */
    public function destroy($id)
    {
        $teacher = User::where('role', 'guru')->findOrFail($id);
        $name = $teacher->name;
        $teacher->delete();

        return back()->with('success', "Akun Guru {$name} berhasil dihapus.");
    }
}
