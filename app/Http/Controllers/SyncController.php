<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\SchoolClass;

class SyncController extends Controller
{
    public function index()
    {
        $devices = Device::all();
        $classes = SchoolClass::with('major')->get();
        return view('sync.index', compact('devices', 'classes'));
    }
}