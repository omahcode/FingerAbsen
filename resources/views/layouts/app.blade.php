<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sekolah Fingerprint Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @vite(['resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-800 font-sans">
    <nav class="bg-blue-700 p-4 text-white shadow">
        <div class="container mx-auto font-bold text-xl flex justify-between items-center">
            <span>Sekolah Fingerprint Monitoring</span>
            <div class="flex space-x-4">
                <a href="{{ route('devices.index') }}" class="text-sm font-normal hover:underline">Mesin</a>
                <a href="{{ route('majors.index') }}" class="text-sm font-normal hover:underline">Jurusan</a>
                <a href="{{ route('classes.index') }}" class="text-sm font-normal hover:underline">Kelas</a>
                <a href="{{ route('students.index') }}" class="text-sm font-normal hover:underline">Siswa</a>
                <a href="{{ route('attendance.index') }}" class="text-sm font-normal hover:underline">Absensi</a>
                <a href="{{ route('sync.index') }}" class="text-sm font-normal hover:underline text-yellow-300">Sinkronisasi</a>
            </div>
        </div>
    </nav>
    <main class="container mx-auto mt-8 px-4">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 relative">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 relative">
                {{ session('error') }}
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>