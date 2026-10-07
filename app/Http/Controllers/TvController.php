<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use Illuminate\Http\Request;

class TvController extends Controller
{
    public function index()
    {
        // Ambil data hari ini saja (opsional) atau batasi jumlahnya agar tidak berat saat awal load
        $logs = AttendanceLog::with(['student.schoolClass', 'device'])
                ->orderBy('timestamp', 'desc')
                ->limit(50)
                ->get();
                
        return view('tv.index', compact('logs'));
    }
}