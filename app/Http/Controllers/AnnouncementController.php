<?php

namespace App\Http\Controllers;

use App\Models\TvAnnouncement;
use App\Models\SchoolClass;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    /**
     * Tampilan Manajemen Pengumuman untuk Guru (Lihat & Ajukan Pengumuman)
     */
    public function index()
    {
        $user = Auth::user();
        $classes = SchoolClass::orderBy('name')->get();

        // Ambil pengumuman yang diajukan oleh guru yang login
        $announcements = TvAnnouncement::with(['user', 'schoolClass', 'approver'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        return view('announcements.index', compact('announcements', 'classes', 'user'));
    }

    /**
     * Simpan Pengajuan Pengumuman Baru oleh Guru (Status Awal: Pending)
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'           => 'required|string|max:150',
            'content'         => 'required|string|max:1000',
            'type'            => 'required|string|in:exam,reminder,info,urgent',
            'school_class_id' => 'nullable|exists:school_classes,id',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after_or_equal:start_date',
        ]);

        $user = Auth::user();

        // Jika diajukan oleh admin langsung, langsung status approved.
        // Jika diajukan oleh guru, statusnya 'pending' (butuh persetujuan admin).
        $status = $user->isAdmin() ? 'approved' : 'pending';
        $approvedBy = $user->isAdmin() ? $user->id : null;

        TvAnnouncement::create([
            'user_id'         => $user->id,
            'school_class_id' => $request->school_class_id ?: ($user->isGuru() ? $user->school_class_id : null),
            'title'           => $request->title,
            'content'         => $request->content,
            'type'            => $request->type,
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
            'status'          => $status,
            'approved_by'     => $approvedBy,
        ]);

        $msg = $user->isAdmin() 
            ? 'Pengumuman berhasil dibuat dan langsung aktif tayang di Mode TV!'
            : 'Pengajuan pengumuman berhasil dikirim! Menunggu persetujuan (approval) dari Administrator sebelum tampil di TV.';

        return back()->with('success', $msg);
    }

    /**
     * Hapus Pengumuman oleh Guru (Khusus milik sendiri)
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $announcement = TvAnnouncement::where('user_id', $user->id)->findOrFail($id);
        $announcement->delete();

        return back()->with('success', 'Pengumuman berhasil dihapus.');
    }

    // ==========================================
    // BAGIAN ADMINISTRATOR (APPROVAL DASHBOARD)
    // ==========================================

    /**
     * Tampilan Persetujuan Pengumuman untuk Admin
     */
    public function adminIndex(Request $request)
    {
        $statusFilter = $request->input('status', 'all');

        $query = TvAnnouncement::with(['user', 'schoolClass', 'approver'])->latest();

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        $announcements = $query->paginate(20);

        // Hitung statistik pengajuan
        $pendingCount = TvAnnouncement::where('status', 'pending')->count();
        $approvedCount = TvAnnouncement::where('status', 'approved')->count();
        $rejectedCount = TvAnnouncement::where('status', 'rejected')->count();

        return view('admin.announcements.index', compact(
            'announcements',
            'pendingCount',
            'approvedCount',
            'rejectedCount',
            'statusFilter'
        ));
    }

    /**
     * Admin Menyetujui Pengumuman (Approve)
     */
    public function approve($id)
    {
        $announcement = TvAnnouncement::findOrFail($id);
        $announcement->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'rejection_reason' => null,
        ]);

        return back()->with('success', "Pengumuman \"{$announcement->title}\" berhasil disetujui dan kini tayang di Layar Mode TV!");
    }

    /**
     * Admin Menolak Pengumuman (Reject)
     */
    public function reject(Request $request, $id)
    {
        $announcement = TvAnnouncement::findOrFail($id);
        $reason = $request->input('rejection_reason', 'Belum memenuhi kriteria penayangan TV.');

        $announcement->update([
            'status'           => 'rejected',
            'approved_by'      => Auth::id(),
            'rejection_reason' => $reason,
        ]);

        return back()->with('warning', "Pengumuman \"{$announcement->title}\" telah ditolak.");
    }

    /**
     * Admin Menghapus Pengumuman
     */
    public function adminDestroy($id)
    {
        $announcement = TvAnnouncement::findOrFail($id);
        $announcement->delete();

        return back()->with('success', 'Pengumuman telah berhasil dihapus dari sistem.');
    }
}
