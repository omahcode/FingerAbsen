<?php
namespace App\Http\Controllers;
use App\Models\SchoolClass;
use App\Models\Major;
use Illuminate\Http\Request;

class SchoolClassController extends Controller {
    public function index() {
        $classes = SchoolClass::with('major')->get();
        $majors = Major::all();
        return view('classes.index', compact('classes', 'majors'));
    }
    public function store(Request $request) {
        $request->validate([
            'major_id' => 'required|exists:majors,id',
            'name' => 'required'
        ]);
        SchoolClass::create($request->all());
        return redirect()->back()->with('success', 'Kelas ditambahkan');
    }
    public function destroy($id) {
        $class = SchoolClass::findOrFail($id);
        $name = $class->name;
        $class->delete();
        return redirect()->back()->with('success', "Kelas '{$name}' berhasil dihapus");
    }
}