<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tvSettings['school_name'] }} - Live Attendance Board</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600;700&family=Plus+Jakarta+Sans:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/js/app.js'])

    <style>
        :root {
            --text: #0f172a;
            --text-muted: #64748b;
            --blue: #2563eb;
            --green: #10b981;
            --purple: #8b5cf6;
            --gold: #f59e0b;
            --red: #ef4444;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; height: 100vh; width: 100vw; overflow: hidden; color: var(--text); background: #f0f4f8; user-select: none; }

        /* THEMES */
        @if($tvSettings['tv_theme'] === 'dark')
            body { --text: #f8fafc; --text-muted: #94a3b8; background: #090d16; }
            .bg-layer { background: radial-gradient(at 0% 0%, #1e1b4b 0px, transparent 50%), radial-gradient(at 100% 100%, #0f172a 0px, transparent 50%), #050811 !important; }
            .s1-wrap, .s2-card, .s2-table-wrap, .board-item, .stat-pill { background: rgba(15, 23, 42, 0.75) !important; color: #f8fafc !important; border-color: rgba(255,255,255,0.08) !important; }
            .s2-table-wrap th { border-color: #1e293b !important; color: #94a3b8 !important; }
            .s2-table-wrap td { border-color: #1e293b !important; }
            .tbl-time-pill { background: #1e293b !important; color: #38bdf8 !important; }
            .s1-clock-wrap { background: #0f172a !important; border: 1px solid #1e293b !important; color: #fff !important; }
            .s1-time { color: #f8fafc !important; }
            .pod-1 { background: linear-gradient(160deg, #064e3b, #022c22) !important; border-color: #059669 !important; color: #fff !important; }
            .pod-2 { background: linear-gradient(160deg, #1e3a8a, #0f172a) !important; border-color: #3b82f6 !important; color: #fff !important; }
            .pod-3 { background: linear-gradient(160deg, #78350f, #451a03) !important; border-color: #d97706 !important; color: #fff !important; }
            .ticker-bar { background: rgba(15, 23, 42, 0.9) !important; border-color: rgba(255,255,255,0.1) !important; color: #e2e8f0 !important; }
        @elseif($tvSettings['tv_theme'] === 'ocean')
            body { background: #0b192c; --text: #f0fdf4; }
            .bg-layer { background: linear-gradient(135deg, #0b192c, #1e3e62, #000000) !important; }
            .s1-wrap, .s2-card, .s2-table-wrap, .board-item, .stat-pill { background: rgba(30, 62, 98, 0.7) !important; color: #f8fafc !important; border-color: rgba(255,255,255,0.1) !important; }
        @elseif($tvSettings['tv_theme'] === 'clean')
            .bg-layer { background: #f8fafc !important; }
        @else
            /* AURORA GRADIENT (DEFAULT) */
            .bg-layer { position: fixed; inset: 0; z-index: 0; background: linear-gradient(-45deg, #e0e7ff, #f0fdf4, #faf5ff, #fefce8, #fce7f3, #e0f2fe); background-size: 600% 600%; animation: bgShift 20s ease infinite; }
        @endif

        @keyframes bgShift { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        .orb { position: fixed; border-radius: 50%; filter: blur(80px); opacity: 0.35; z-index: 0; animation: orbFloat 12s ease-in-out infinite; pointer-events: none; }
        .orb-1 { width: 400px; height: 400px; background: #818cf8; top: -100px; left: -100px; }
        .orb-2 { width: 350px; height: 350px; background: #34d399; bottom: -80px; right: -80px; animation-delay: -4s; }
        .orb-3 { width: 300px; height: 300px; background: #fbbf24; top: 50%; left: 50%; transform: translate(-50%,-50%); animation-delay: -8s; }
        @keyframes orbFloat { 0%, 100% { transform: translate(0,0) scale(1); } 33% { transform: translate(30px,-40px) scale(1.1); } 66% { transform: translate(-20px,20px) scale(0.95); } }

        /* CAROUSEL */
        .carousel { position: relative; width: 100%; height: 100%; z-index: 2; display: flex; flex-direction: column; }
        .progress-wrap { width: 100%; height: 5px; background: rgba(0,0,0,0.06); overflow: hidden; }
        .progress-fill { height: 100%; width: 0%; transition: width 1s linear; border-radius: 0 3px 3px 0; background: linear-gradient(90deg, var(--blue), var(--purple), var(--gold)); background-size: 200% 100%; animation: progGrad 3s ease infinite; }
        @keyframes progGrad { 0% { background-position: 0% 0; } 100% { background-position: 200% 0; } }

        .slides { flex: 1; position: relative; padding: 1rem 2.5rem 3.5rem; overflow: hidden; }
        .slide { position: absolute; inset: 1rem 2.5rem 3.5rem; opacity: 0; transform: translateX(60px) scale(0.97); transition: all 0.8s cubic-bezier(0.23,1,0.32,1); pointer-events: none; display: flex; flex-direction: column; }
        .slide.active { opacity: 1; transform: translateX(0) scale(1); pointer-events: auto; z-index: 10; }
        .slide.exit { opacity: 0; transform: translateX(-60px) scale(0.97); z-index: 9; }

        /* HEADERS */
        .s-header { text-align: center; margin-bottom: 0.8rem; }
        .s-header h2 { font-family: 'Poppins'; font-weight: 800; font-size: 1.8rem; letter-spacing: 1.5px; text-transform: uppercase; display: inline-flex; align-items: center; gap: 10px; }
        .s-header h2 i { font-size: 1.3rem; }
        .s-header p { color: var(--text-muted); font-size: 0.9rem; margin-top: 2px; font-weight: 500; }
        .s-header::after { content: ''; display: block; width: 60px; height: 3px; border-radius: 2px; margin: 6px auto 0; }
        .s2-header h2 { color: var(--blue); } .s2-header::after { background: linear-gradient(90deg, var(--blue), #818cf8); }
        .s3-header h2 { color: var(--green); } .s3-header::after { background: linear-gradient(90deg, var(--green), #6ee7b7); }
        .s4-header h2 { color: var(--purple); } .s4-header::after { background: linear-gradient(90deg, var(--purple), #c4b5fd); }
        .s5-header h2 { color: var(--gold); } .s5-header::after { background: linear-gradient(90deg, var(--gold), #fcd34d); }

        /* S1 WELCOME & STATS */
        .s1-wrap { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; background: rgba(255,255,255,0.6); backdrop-filter: blur(20px); border-radius: 32px; border: 1px solid rgba(255,255,255,0.7); box-shadow: 0 20px 60px rgba(0,0,0,0.05); padding: 1.5rem 2rem; }
        .s1-icon { font-size: 5.5rem; margin-bottom: 0.5rem; background: linear-gradient(135deg, var(--blue), var(--purple)); -webkit-background-clip: text; color: transparent; filter: drop-shadow(0 0 25px rgba(99,102,241,0.4)); animation: iconPulse 3s ease-in-out infinite; }
        @keyframes iconPulse { 0%,100% { transform: scale(1); } 50% { transform: scale(1.06); } }
        .s1-title { font-family: 'Poppins'; font-weight: 900; font-size: 3.5rem; background: linear-gradient(135deg, var(--blue), #6366f1, var(--purple)); -webkit-background-clip: text; color: transparent; line-height: 1.15; max-width: 900px; }
        .s1-sub { font-size: 1.25rem; color: var(--text-muted); font-weight: 500; margin-top: 0.5rem; max-width: 700px; }

        .s1-clock-wrap { margin-top: 1.8rem; display: flex; align-items: center; gap: 2rem; background: white; padding: 0.9rem 2.5rem; border-radius: 100px; box-shadow: 0 8px 30px rgba(0,0,0,0.06); }
        .s1-time { font-family: 'JetBrains Mono'; font-size: 3.2rem; font-weight: 700; letter-spacing: 2px; line-height: 1; color: var(--text); }
        .s1-time .blink { animation: blink 1s step-end infinite; }
        @keyframes blink { 0%,100% { opacity: 1; } 50% { opacity: 0.3; } }
        .s1-date-box { text-align: left; border-left: 2px solid #e2e8f0; padding-left: 1.5rem; }
        .s1-day { font-family: 'Poppins'; font-weight: 700; font-size: 1.2rem; color: var(--blue); }
        .s1-date { font-size: 0.95rem; color: var(--text-muted); font-weight: 500; }

        .stats-strip { display: flex; gap: 1.2rem; margin-top: 1.8rem; justify-content: center; flex-wrap: wrap; }
        .stat-pill { background: white; border: 1px solid rgba(0,0,0,0.05); padding: 0.6rem 1.4rem; border-radius: 50px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); display: flex; align-items: center; gap: 0.75rem; font-family: 'Poppins'; }
        .stat-pill i { font-size: 1.1rem; }
        .stat-pill .stat-val { font-weight: 800; font-size: 1.2rem; font-family: 'JetBrains Mono'; }
        .stat-pill .stat-lbl { font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }

        /* S2 DAILY */
        .s2-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; flex: 1; }
        .s2-top3 { display: flex; flex-direction: column; gap: 0.7rem; justify-content: center; }
        .s2-card { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.2rem; border-radius: 16px; background: white; position: relative; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.04); transition: transform 0.3s; }
        .s2-card:hover { transform: translateY(-3px); }
        .s2-card .accent { position: absolute; left: 0; top: 0; width: 5px; height: 100%; }
        .s2-card.r1 .accent { background: linear-gradient(to bottom, #fbbf24, #f59e0b); }
        .s2-card.r2 .accent { background: linear-gradient(to bottom, #94a3b8, #64748b); }
        .s2-card.r3 .accent { background: linear-gradient(to bottom, #d97706, #b45309); }
        .s2-medal { font-size: 2rem; width: 45px; text-align: center; }
        .s2-card.r1 .s2-medal { color: #fbbf24; filter: drop-shadow(0 0 8px rgba(251,191,36,0.5)); }
        .s2-card.r2 .s2-medal { color: #94a3b8; } .s2-card.r3 .s2-medal { color: #d97706; }
        .s2-info { flex: 1; }
        .s2-name { font-family: 'Poppins'; font-weight: 700; font-size: 1.05rem; }
        .s2-class { color: var(--text-muted); font-size: 0.8rem; font-weight: 500; }
        .s2-time { background: linear-gradient(135deg, #eff6ff, #e0e7ff); color: var(--blue); padding: 0.35rem 0.8rem; border-radius: 50px; font-weight: 700; font-size: 0.9rem; font-family: 'JetBrains Mono'; display: flex; align-items: center; gap: 5px; }

        .s2-table-wrap { background: white; border-radius: 16px; padding: 0.8rem 1.2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.04); display: flex; flex-direction: column; }
        .s2-table-wrap table { width: 100%; border-collapse: collapse; }
        .s2-table-wrap th { font-weight: 600; color: var(--text-muted); text-transform: uppercase; font-size: 0.7rem; letter-spacing: 1px; padding: 0.6rem; text-align: left; border-bottom: 2px solid #f1f5f9; }
        .s2-table-wrap td { padding: 0.5rem 0.6rem; border-bottom: 1px solid #f8fafc; font-size: 0.85rem; }
        .tbl-rank { font-weight: 800; color: var(--blue); font-family: 'JetBrains Mono'; width: 45px; }
        .tbl-name { font-weight: 600; }
        .tbl-time-pill { background: #f1f5f9; padding: 2px 8px; border-radius: 20px; font-weight: 600; font-size: 0.8rem; font-family: 'JetBrains Mono'; color: #475569; }

        /* GAMIFIED BOARD (WEEKLY, MONTHLY, HOF) */
        .board { display: flex; flex-direction: column; flex: 1; gap: 0.4rem; }
        .podium { display: flex; justify-content: center; align-items: flex-end; gap: 1rem; padding: 0.5rem 0; position: relative; }
        .pod-card { border-radius: 20px; text-align: center; position: relative; display: flex; flex-direction: column; align-items: center; transition: transform 0.4s; padding: 0.8rem; }
        .pod-1 { width: 220px; min-height: 290px; background: linear-gradient(160deg, #f0fdf4, #ecfccb); border: 2px solid #86efac; box-shadow: 0 10px 40px rgba(34,197,94,0.12); }
        .pod-2 { width: 195px; min-height: 255px; background: linear-gradient(160deg, #eff6ff, #dbeafe); border: 2px solid #93c5fd; box-shadow: 0 10px 40px rgba(59,130,246,0.08); }
        .pod-3 { width: 195px; min-height: 240px; background: linear-gradient(160deg, #fffbeb, #fef3c7); border: 2px solid #fcd34d; box-shadow: 0 10px 40px rgba(245,158,11,0.08); }
        .pod-crown { position: absolute; top: -26px; left: 50%; transform: translateX(-50%); font-size: 2.2rem; }
        .pod-rank-num { position: absolute; top: 6px; right: 10px; font-weight: 900; font-size: 1.1rem; opacity: 0.15; font-family: 'Poppins'; }
        
        .pod-avatar { width: 55px; height: 55px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.6rem; font-family: 'Poppins'; box-shadow: 0 4px 12px rgba(0,0,0,0.08); background: white; }
        .pod-1 .pod-avatar { width: 65px; height: 65px; font-size: 1.9rem; color: #16a34a; border: 3px solid #86efac; }
        .pod-2 .pod-avatar { color: var(--blue); border: 2px solid #93c5fd; }
        .pod-3 .pod-avatar { color: #d97706; border: 2px solid #fcd34d; }

        .pod-name { font-family: 'Poppins'; font-weight: 700; font-size: 1rem; margin-top: 10px; }
        .pod-class { font-size: 0.75rem; color: var(--text-muted); font-weight: 500; }
        .pod-streak-display { font-weight: 900; font-size: 1.3rem; margin-top: auto; padding-top: 8px; }
        .pod-1 .pod-streak-display { color: #16a34a; }
        .pod-2 .pod-streak-display { color: var(--blue); }
        .pod-3 .pod-streak-display { color: #d97706; }

        .board-list { flex: 1; display: flex; flex-direction: column; gap: 0.35rem; overflow: hidden; }
        .board-item { display: flex; align-items: center; gap: 0.8rem; background: white; border-radius: 14px; padding: 0.45rem 1rem; border: 1px solid #f1f5f9; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
        .bi-rank { font-family: 'JetBrains Mono'; font-weight: 800; font-size: 0.95rem; width: 32px; color: var(--blue); }
        .bi-avatar { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; font-family: 'Poppins'; }
        .bi-info { flex: 1; min-width: 0; }
        .bi-name { font-family: 'Poppins'; font-weight: 700; font-size: 0.88rem; display: flex; align-items: center; gap: 6px; }
        .bi-class-badge { font-size: 0.7rem; font-weight: 600; padding: 1px 6px; border-radius: 4px; background: #f1f5f9; color: #475569; }
        .bi-meta { font-size: 0.72rem; color: var(--text-muted); font-weight: 500; }
        .bi-progress { width: 140px; }
        .bi-bar-bg { height: 6px; border-radius: 3px; background: #e2e8f0; overflow: hidden; }
        .bi-bar-fill { height: 100%; border-radius: 3px; }
        .bi-pts { font-family: 'JetBrains Mono'; font-size: 1rem; font-weight: 700; width: 65px; text-align: right; }

        /* TICKER RUNNING TEXT */
        .ticker-bar { position: fixed; bottom: 0; left: 0; right: 0; height: 36px; background: rgba(255,255,255,0.92); backdrop-filter: blur(10px); border-top: 1px solid rgba(0,0,0,0.06); z-index: 100; display: flex; align-items: center; padding: 0 1rem; box-shadow: 0 -2px 10px rgba(0,0,0,0.03); }
        .ticker-badge { background: linear-gradient(135deg, var(--blue), var(--purple)); color: white; font-size: 0.72rem; font-weight: 800; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 5px; flex-shrink: 0; margin-right: 1rem; }
        .ticker-content { flex: 1; overflow: hidden; white-space: nowrap; font-size: 0.85rem; font-weight: 600; }
        .ticker-text { display: inline-block; padding-left: 100%; animation: tickerMove 25s linear infinite; }
        @keyframes tickerMove { 0% { transform: translate3d(0, 0, 0); } 100% { transform: translate3d(-100%, 0, 0); } }

        /* CONTROLS */
        .nav-arrow { position: fixed; top: 50%; transform: translateY(-50%); z-index: 50; width: 44px; height: 44px; border-radius: 50%; background: white; border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 4px 15px rgba(0,0,0,0.08); display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-muted); opacity: 0; transition: all 0.3s; }
        body:hover .nav-arrow { opacity: 0.7; }
        .nav-arrow:hover { opacity: 1 !important; transform: translateY(-50%) scale(1.1); color: var(--blue); }
        #arrow-prev { left: 1rem; } #arrow-next { right: 1rem; }

        .nav-dots { position: fixed; bottom: 45px; left: 50%; transform: translateX(-50%); display: flex; gap: 8px; z-index: 50; }
        .dot { width: 10px; height: 10px; border-radius: 50%; background: rgba(0,0,0,0.15); cursor: pointer; transition: all 0.3s; }
        .dot.active { width: 28px; border-radius: 10px; background: var(--blue); }

        .btn-settings-tv { position: fixed; bottom: 44px; right: 1.5rem; z-index: 60; width: 36px; height: 36px; border-radius: 50%; background: white; border: 1px solid rgba(0,0,0,0.1); box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; justify-content: center; color: var(--text-muted); text-decoration: none; transition: all 0.3s; }
        .btn-settings-tv:hover { transform: rotate(45deg) scale(1.1); color: var(--blue); }
    </style>
</head>
<body>
    <div class="bg-layer"></div>
    <div class="orb orb-1"></div><div class="orb orb-2"></div><div class="orb orb-3"></div>

    <div class="carousel" id="carousel">
        <div class="progress-wrap"><div class="progress-fill" id="progress-fill"></div></div>
        <div class="slides">

            <!-- SLIDE 1: WELCOME & REALTIME CLOCK + STATS -->
            @if($tvSettings['tv_show_welcome'])
            <div class="slide active" data-slide-name="welcome">
                <div class="s1-wrap">
                    <i class="fas fa-fingerprint s1-icon"></i>
                    <h1 class="s1-title" id="typewriter">{{ $tvSettings['tv_title'] }}</h1>
                    <p class="s1-sub">{{ $tvSettings['tv_subtitle'] }}</p>

                    <div class="s1-clock-wrap">
                        <div class="s1-time" id="clock-time">00<span class="blink">:</span>00<span class="blink">:</span>00</div>
                        <div class="s1-date-box">
                            <div class="s1-day" id="clock-day">Memuat...</div>
                            <div class="s1-date" id="clock-date"></div>
                        </div>
                    </div>

                    <!-- Live Summary Stats -->
                    <div class="stats-strip">
                        <div class="stat-pill">
                            <i class="fas fa-users text-blue-500"></i>
                            <div>
                                <div class="stat-val text-blue-600">{{ $stats['total_students'] }}</div>
                                <div class="stat-lbl">Total Siswa</div>
                            </div>
                        </div>
                        <div class="stat-pill">
                            <i class="fas fa-user-check text-emerald-500"></i>
                            <div>
                                <div class="stat-val text-emerald-600">{{ $stats['present_count'] }}</div>
                                <div class="stat-lbl">Hadir Hari Ini</div>
                            </div>
                        </div>
                        <div class="stat-pill">
                            <i class="fas fa-clock text-indigo-500"></i>
                            <div>
                                <div class="stat-val text-indigo-600">{{ $stats['ontime_count'] }}</div>
                                <div class="stat-lbl">Tepat Waktu</div>
                            </div>
                        </div>
                        <div class="stat-pill">
                            <i class="fas fa-chart-pie text-purple-500"></i>
                            <div>
                                <div class="stat-val text-purple-600">{{ $stats['percentage'] }}%</div>
                                <div class="stat-lbl">Kehadiran</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- SLIDE 2: DAILY TOP 10 (EARLIEST ARRIVALS TODAY) -->
            @if($tvSettings['tv_show_daily'])
            <div class="slide {{ !$tvSettings['tv_show_welcome'] ? 'active' : '' }}" data-slide-name="daily">
                <div class="s-header s2-header">
                    <h2><i class="fas fa-bolt"></i> Top 10 Kehadiran Hari Ini</h2>
                    <p>Siswa yang melakukan tap fingerprint paling awal ({{ $stats['active_date_label'] }})</p>
                </div>
                <div class="s2-grid">
                    <div class="s2-top3" id="daily-top3"></div>
                    <div class="s2-table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Waktu Tap</th>
                                </tr>
                            </thead>
                            <tbody id="daily-table"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- SLIDE 3: WEEKLY LEADERBOARD -->
            @if($tvSettings['tv_show_weekly'])
            <div class="slide" data-slide-name="weekly">
                <div class="s-header s3-header">
                    <h2><i class="fas fa-calendar-week"></i> Top 10 Poin Mingguan</h2>
                    <p>Peringkat kehadiran dan kedisiplinan siswa minggu ini</p>
                </div>
                <div class="board" id="weekly-board"></div>
            </div>
            @endif

            <!-- SLIDE 4: MONTHLY LEADERBOARD -->
            @if($tvSettings['tv_show_monthly'])
            <div class="slide" data-slide-name="monthly">
                <div class="s-header s4-header">
                    <h2><i class="fas fa-chart-line"></i> Top 10 Poin Bulanan</h2>
                    <p>Konsistensi dan ketepatan waktu kehadiran selama bulan ini</p>
                </div>
                <div class="board" id="monthly-board"></div>
            </div>
            @endif

            <!-- SLIDE 5: HALL OF FAME -->
            @if($tvSettings['tv_show_hof'])
            <div class="slide" data-slide-name="hof">
                <div class="s-header s5-header">
                    <h2><i class="fas fa-trophy"></i> Hall of Fame</h2>
                    <p>Akumulasi kehadiran dan rekor kedisiplinan tertinggi sepanjang masa</p>
                </div>
                <div class="board" id="hof-board"></div>
            </div>
            @endif

        </div>

        <!-- Carousel Navigation Controls -->
        <button class="nav-arrow" id="arrow-prev"><i class="fas fa-chevron-left"></i></button>
        <button class="nav-arrow" id="arrow-next"><i class="fas fa-chevron-right"></i></button>
        <div class="nav-dots" id="nav-dots"></div>
    </div>

    <!-- Running Announcement Ticker Bar -->
    @if(!empty($tvSettings['tv_running_text']))
    <div class="ticker-bar">
        <div class="ticker-badge"><i class="fas fa-bullhorn"></i> Info</div>
        <div class="ticker-content">
            <span class="ticker-text">{{ $tvSettings['tv_running_text'] }}</span>
        </div>
    </div>
    @endif

    <!-- Quick Settings Link Button -->
    <a href="{{ route('settings.index') }}" class="btn-settings-tv" title="Buka Pengaturan Mode TV" target="_blank">
        <i class="fas fa-cog text-sm"></i>
    </a>

    <script>
        // ======================== REAL DATA FROM BACKEND ========================
        let dailyData = {!! json_encode($dailyData) !!};
        let weeklyData = {!! json_encode($weeklyData) !!};
        let monthlyData = {!! json_encode($monthlyData) !!};
        let hofData = {!! json_encode($hofData) !!};

        const slideInterval = {{ $tvSettings['tv_slide_interval'] * 1000 }};

        // ======================== RENDER FUNCTIONS ========================
        function renderDaily() {
            const top3El = document.getElementById('daily-top3');
            const tableEl = document.getElementById('daily-table');
            if (!top3El || !tableEl) return;

            if (dailyData.length === 0) {
                top3El.innerHTML = `<div class="s2-card r1"><div class="accent"></div><div class="s2-info"><div class="s2-name text-gray-500">Belum ada tap hari ini</div><div class="s2-class">Tap jari di mesin untuk menjadi yang pertama</div></div></div>`;
                tableEl.innerHTML = `<tr><td colspan="4" class="text-center text-gray-400 py-6">Belum ada data kehadiran pada sesi aktif ini.</td></tr>`;
                return;
            }

            top3El.innerHTML = dailyData.slice(0,3).map((d,i) => {
                const c = ['r1','r2','r3'][i];
                return `<div class="s2-card ${c}">
                    <div class="accent"></div>
                    <div class="s2-medal"><i class="fas fa-medal"></i></div>
                    <div class="s2-info">
                        <div class="s2-name">${d.name}</div>
                        <div class="s2-class">${d.cls}</div>
                    </div>
                    <div class="s2-time"><i class="far fa-clock"></i> ${d.time}</div>
                </div>`;
            }).join('');

            const rest = dailyData.slice(3,10);
            if (rest.length > 0) {
                tableEl.innerHTML = rest.map((d,i) => `<tr>
                    <td class="tbl-rank">#${i+4}</td>
                    <td class="tbl-name">${d.name}</td>
                    <td>${d.cls}</td>
                    <td><span class="tbl-time-pill">${d.time}</span></td>
                </tr>`).join('');
            } else {
                tableEl.innerHTML = `<tr><td colspan="4" class="text-center text-gray-400 py-4">Menunggu siswa berikutnya...</td></tr>`;
            }
        }

        function renderBoard(id, data, color) {
            const el = document.getElementById(id);
            if (!el) return;

            if (!data || data.length === 0) {
                el.innerHTML = `<div class="text-center text-gray-400 py-12 font-medium">Belum ada data akumulasi presensi untuk periode ini.</div>`;
                return;
            }

            const t3 = data.slice(0,3);
            const rest = data.slice(3,10);

            let h = `<div class="podium">`;
            const podOrder = [
                t3[1] ? {...t3[1], r:2, c:'pod-2'} : null,
                t3[0] ? {...t3[0], r:1, c:'pod-1'} : null,
                t3[2] ? {...t3[2], r:3, c:'pod-3'} : null
            ].filter(Boolean);

            podOrder.forEach(d => {
                const avatarContent = d.photo 
                    ? `<img src="${d.photo}" alt="${d.name}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">` 
                    : d.ini;

                h += `<div class="pod-card ${d.c}">
                    ${d.r===1?'<div class="pod-crown">&#x1F451;</div>':''}
                    <div class="pod-rank-num">#${d.r}</div>
                    <div class="pod-avatar">${avatarContent}</div>
                    <div class="pod-name">${d.name.split(' ').slice(0,2).join(' ')}</div>
                    <div class="pod-class">${d.cls}</div>
                    <div class="pod-streak-display">${d.pts} <small style="font-size:0.55em;">pts (${d.streak} Hari)</small></div>
                </div>`;
            });
            h += `</div>`;

            if (rest.length > 0) {
                h += `<div class="board-list">`;
                rest.forEach((d,i) => {
                    const rk = i+4;
                    const maxVal = d.max || 50;
                    const pct = Math.min(100, Math.round((d.pts / maxVal) * 100));
                    const listAvatar = d.photo 
                        ? `<img src="${d.photo}" alt="${d.name}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">` 
                        : d.ini;

                    h += `<div class="board-item">
                        <div class="bi-rank">#${rk}</div>
                        <div class="bi-avatar" style="background:${color}15;color:${color}">${listAvatar}</div>
                        <div class="bi-info">
                            <div class="bi-name">${d.name} <span class="bi-class-badge">${d.cls}</span></div>
                            <div class="bi-meta"><span><i class="fas fa-check-circle text-emerald-500"></i> ${d.days || d.streak} Hari Hadir</span> &bull; <span>Tap: ${d.tap} WIB</span></div>
                        </div>
                        <div class="bi-progress">
                            <div class="bi-bar-bg"><div class="bi-bar-fill" style="width:${pct}%;background:${color};"></div></div>
                        </div>
                        <div class="bi-pts"><strong>${d.pts}</strong><span style="font-size:0.7em;margin-left:2px;color:var(--text-muted)">pt</span></div>
                    </div>`;
                });
                h += `</div>`;
            }

            el.innerHTML = h;
        }

        // ======================== REALTIME CLOCK ========================
        function updateClock() {
            const n = new Date();
            const h = String(n.getHours()).padStart(2,'0');
            const m = String(n.getMinutes()).padStart(2,'0');
            const s = String(n.getSeconds()).padStart(2,'0');
            
            const timeEl = document.getElementById('clock-time');
            if (timeEl) timeEl.innerHTML = `${h}<span class="blink">:</span>${m}<span class="blink">:</span>${s}`;
            
            const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
            const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            
            const dayEl = document.getElementById('clock-day');
            const dateEl = document.getElementById('clock-date');
            if (dayEl) dayEl.textContent = days[n.getDay()];
            if (dateEl) dateEl.textContent = `${n.getDate()} ${months[n.getMonth()]} ${n.getFullYear()}`;
        }

        // ======================== CAROUSEL CONTROLLER ========================
        const allSlides = document.querySelectorAll('.slide');
        const dotsContainer = document.getElementById('nav-dots');
        const progFill = document.getElementById('progress-fill');
        let cur = 0;
        const total = allSlides.length;
        let progTimer, prog = 0, paused = false;

        // Buat dots navigasi dinamis
        if (dotsContainer && total > 1) {
            dotsContainer.innerHTML = Array.from({length: total}).map((_, i) => `<div class="dot ${i===0?'active':''}" data-index="${i}"></div>`).join('');
        }

        function goTo(idx) {
            if (total <= 1) return;
            allSlides[cur].classList.remove('active');
            allSlides[cur].classList.add('exit');
            
            const dots = document.querySelectorAll('.dot');
            if (dots[cur]) dots[cur].classList.remove('active');

            setTimeout(() => document.querySelectorAll('.exit').forEach(e => e.classList.remove('exit')), 800);
            
            cur = ((idx % total) + total) % total;
            allSlides[cur].classList.add('active');
            if (dots[cur]) dots[cur].classList.add('active');
            
            resetProg();
        }

        function next() { goTo(cur + 1); }
        function prev() { goTo(cur - 1); }

        function startProg() {
            if (total <= 1) return;
            prog = 0;
            if (progFill) {
                progFill.style.width = '0%';
                progFill.style.transition = 'none';
                requestAnimationFrame(() => {
                    progFill.style.transition = 'width 1s linear';
                    progTimer = setInterval(() => {
                        if (!paused) {
                            prog += 100 / (slideInterval / 1000);
                            progFill.style.width = prog + '%';
                            if (prog >= 100) next();
                        }
                    }, 1000);
                });
            }
        }

        function resetProg() {
            clearInterval(progTimer);
            startProg();
        }

        document.getElementById('arrow-next')?.addEventListener('click', next);
        document.getElementById('arrow-prev')?.addEventListener('click', prev);
        document.querySelectorAll('.dot').forEach((d,i) => d.addEventListener('click', () => goTo(i)));

        document.getElementById('carousel')?.addEventListener('mouseenter', () => paused = true);
        document.getElementById('carousel')?.addEventListener('mouseleave', () => paused = false);

        document.addEventListener('keydown', e => {
            if (e.key === 'ArrowRight') next();
            if (e.key === 'ArrowLeft') prev();
            if (e.key === ' ' || e.key === 'Spacebar') paused = !paused;
            if (e.key === 'f' || e.key === 'F') {
                if (!document.fullscreenElement) {
                    document.documentElement.requestFullscreen().catch(() => {});
                } else {
                    document.exitFullscreen().catch(() => {});
                }
            }
        });

        // ======================== INITIALIZE ========================
        renderDaily();
        renderBoard('weekly-board', weeklyData, '#10b981');
        renderBoard('monthly-board', monthlyData, '#8b5cf6');
        renderBoard('hof-board', hofData, '#f59e0b');

        setInterval(updateClock, 1000);
        updateClock();
        startProg();

        // Background polling fallback setiap 30 detik agar data selalu sinkron
        setInterval(() => {
            fetch('/attendance/latest?since_id=0')
                .then(r => r.json())
                .then(() => {})
                .catch(() => {});
        }, 30000);
    </script>

    <!-- Realtime WebSockets Listener (Laravel Reverb / Echo) -->
    <script type="module">
        document.addEventListener("DOMContentLoaded", () => {
            if (window.Echo) {
                window.Echo.channel('attendance')
                    .listen('.AttendanceCreated', (e) => {
                        const log = e.log;
                        const studentName = log.student ? log.student.name : ('Siswa ' + (log.device_user_id || ''));
                        const className = (log.student && log.student.school_class) ? log.student.school_class.name : '-';
                        const timeStr = (log.timestamp && log.timestamp.includes(' ')) ? log.timestamp.split(' ')[1].substring(0, 5) : '07:00';

                        // Masukkan data baru ke paling atas
                        dailyData.unshift({
                            name: studentName,
                            cls: className,
                            time: timeStr,
                            ini: studentName.charAt(0).toUpperCase(),
                            is_ontime: true
                        });

                        if (dailyData.length > 10) dailyData.pop();

                        renderDaily();

                        // Efek highlight
                        const pnl = document.getElementById('daily-top3');
                        if (pnl) {
                            pnl.style.transition = 'transform 0.3s, filter 0.3s';
                            pnl.style.transform = 'scale(1.02)';
                            setTimeout(() => {
                                pnl.style.transform = 'scale(1)';
                            }, 500);
                        }
                    });
            }
        });
    </script>
</body>
</html>