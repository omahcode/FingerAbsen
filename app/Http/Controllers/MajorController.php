<?php
namespace App\Http\Controllers;
use App\Models\Major;
use Illuminate\Http\Request;

class MajorController extends Controller {
    public function index() {
        $majors = Major::all();
        return view('majors.index', compact('majors'));
    }
    public function store(Request $request) {
        $request->validate(['name' => 'required']);
        Major::create(['name' => $request->name]);
        return redirect()->back()->with('success', 'Jurusan ditambahkan');
    }
    public function destroy(Major $major) {
        $major->delete();
        return redirect()->back()->with('success', 'Jurusan dihapus');
    }
}