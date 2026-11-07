<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\SchoolEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SchoolEventController extends Controller
{
    /**
     * Simpan jadwal hari khusus / kemah / libur baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'type' => 'required|in:event,holiday,dispensation',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'target_type' => 'required|in:all,grade,class',
            'grade_level' => 'nullable|required_if:target_type,grade|in:10,11,12',
            'school_class_id' => 'nullable|required_if:target_type,class|exists:school_classes,id',
            'preserve_streak' => 'nullable',
            'description' => 'nullable|string|max:500',
        ]);

        $preserveStreak = $request->has('preserve_streak') ? (bool) $request->preserve_streak : true;

        SchoolEvent::create([
            'title' => $validated['title'],
            'type' => $validated['type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'target_type' => $validated['target_type'],
            'grade_level' => $validated['target_type'] === 'grade' ? $validated['grade_level'] : null,
            'school_class_id' => $validated['target_type'] === 'class' ? $validated['school_class_id'] : null,
            'preserve_streak' => $preserveStreak,
            'description' => $validated['description'] ?? null,
        ]);

        $targetDesc = 'Semua Siswa';
        if ($validated['target_type'] === 'grade') {
            $targetDesc = "Seluruh Kelas {$validated['grade_level']}";
        } elseif ($validated['target_type'] === 'class') {
            $class = SchoolClass::find($validated['school_class_id']);
            $targetDesc = 'Kelas '.($class?->name ?? '-');
        }

        return redirect()->back()->with('success', "Hari khusus '{$validated['title']}' untuk {$targetDesc} berhasil disimpan. Streak kehadiran siswa akan terlindungi!");
    }

    /**
     * Hapus jadwal hari khusus / kegiatan / libur
     */
    public function destroy(SchoolEvent $schoolEvent): RedirectResponse
    {
        $title = $schoolEvent->title;
        $schoolEvent->delete();

        return redirect()->back()->with('success', "Hari khusus/libur '{$title}' berhasil dihapus.");
    }
}
