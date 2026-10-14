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
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
    @vite(['resources/js/app.js'])

    <style>
        :root {
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --blue: #38bdf8;
            --green: #34d399;
            --purple: #c084fc;
            --gold: #fbbf24;
            --red: #f87171;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            height: 100vh; 
            width: 100vw; 
            overflow: hidden; 
            color: var(--text); 
            background: #070a14; 
            user-select: none; 
        }

        /* PERMANENT DARK TV THEME (HIGH CONTRAST & MAXIMUM LEGIBILITY) */
        .bg-layer { 
            position: fixed; 
            inset: 0; 
            z-index: 0; 
            background: radial-gradient(circle at 10% 20%, #1e1b4b 0%, transparent 45%), 
                        radial-gradient(circle at 90% 80%, #0c4a6e 0%, transparent 45%), 
                        radial-gradient(circle at 50% 50%, #0f172a 0%, #030712 100%); 
        }

        .orb { position: fixed; border-radius: 50%; filter: blur(90px); opacity: 0.3; z-index: 0; animation: orbFloat 14s ease-in-out infinite; pointer-events: none; }
        .orb-1 { width: 480px; height: 480px; background: #6366f1; top: -120px; left: -120px; }
        .orb-2 { width: 420px; height: 420px; background: #06b6d4; bottom: -100px; right: -100px; animation-delay: -5s; }
        .orb-3 { width: 380px; height: 380px; background: #f59e0b; top: 40%; left: 50%; transform: translate(-50%,-50%); animation-delay: -9s; opacity: 0.2; }
        @keyframes orbFloat { 0%, 100% { transform: translate(0,0) scale(1); } 33% { transform: translate(35px,-45px) scale(1.1); } 66% { transform: translate(-25px,25px) scale(0.95); } }

        /* CAROUSEL */
        .carousel { position: relative; width: 100%; height: 100%; z-index: 2; display: flex; flex-direction: column; }
        .progress-wrap { width: 100%; height: 6px; background: rgba(255,255,255,0.08); overflow: hidden; }
        .progress-fill { height: 100%; width: 0%; transition: width 1s linear; border-radius: 0 4px 4px 0; background: linear-gradient(90deg, #38bdf8, #818cf8, #fbbf24); background-size: 200% 100%; animation: progGrad 3s ease infinite; box-shadow: 0 0 12px rgba(56,189,248,0.6); }
        @keyframes progGrad { 0% { background-position: 0% 0; } 100% { background-position: 200% 0; } }

        .slides { flex: 1; position: relative; padding: 1.2rem 3rem 4rem; overflow: hidden; }
        .slide { position: absolute; inset: 1.2rem 3rem 4rem; opacity: 0; transform: translateX(70px) scale(0.97); transition: all 0.8s cubic-bezier(0.23,1,0.32,1); pointer-events: none; display: flex; flex-direction: column; }
        .slide.active { opacity: 1; transform: translateX(0) scale(1); pointer-events: auto; z-index: 10; }
        .slide.exit { opacity: 0; transform: translateX(-70px) scale(0.97); z-index: 9; }

        /* HEADERS */
        .s-header { text-align: center; margin-bottom: 1.2rem; }
        .s-header h2 { font-family: 'Poppins'; font-weight: 900; font-size: 2.4rem; letter-spacing: 1.5px; text-transform: uppercase; display: inline-flex; align-items: center; gap: 12px; }
        .s-header h2 i { font-size: 1.8rem; }
        .s-header p { color: var(--text-muted); font-size: 1.15rem; margin-top: 4px; font-weight: 500; }
        .s-header::after { content: ''; display: block; width: 80px; height: 4px; border-radius: 2px; margin: 8px auto 0; }
        .s2-header h2 { color: #38bdf8; text-shadow: 0 0 25px rgba(56,189,248,0.4); } .s2-header::after { background: linear-gradient(90deg, #38bdf8, #818cf8); }
        .s3-header h2 { color: #34d399; text-shadow: 0 0 25px rgba(52,211,153,0.4); } .s3-header::after { background: linear-gradient(90deg, #34d399, #6ee7b7); }
        .s4-header h2 { color: #c084fc; text-shadow: 0 0 25px rgba(192,132,252,0.4); } .s4-header::after { background: linear-gradient(90deg, #c084fc, #e879f9); }
        .s5-header h2 { color: #fbbf24; text-shadow: 0 0 25px rgba(251,191,36,0.4); } .s5-header::after { background: linear-gradient(90deg, #fbbf24, #fde047); }
        .s6-header h2 { color: #f59e0b; text-shadow: 0 0 25px rgba(245,158,11,0.4); } .s6-header::after { background: linear-gradient(90deg, #f59e0b, #fbbf24); }

        /* S6 ANNOUNCEMENTS GRID & CARDS */
        .ann-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 1.5rem; flex: 1; align-items: stretch; overflow: hidden; padding: 0.5rem 0; }
        .ann-card { background: rgba(15, 23, 42, 0.88); border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 24px; padding: 1.6rem; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 10px 30px rgba(0,0,0,0.35); position: relative; overflow: hidden; }
        .ann-card::before { content: ''; position: absolute; top: 0; left: 0; width: 6px; height: 100%; }
        .ann-card.type-exam::before { background: #f59e0b; }
        .ann-card.type-reminder::before { background: #a855f7; }
        .ann-card.type-urgent::before { background: #ef4444; }
        .ann-card.type-info::before { background: #38bdf8; }
        .ann-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.9rem; }
        .ann-badge { font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; padding: 5px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 6px; }
        .ann-badge.badge-exam { background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4); }
        .ann-badge.badge-reminder { background: rgba(168, 85, 247, 0.2); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.4); }
        .ann-badge.badge-urgent { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4); }
        .ann-badge.badge-info { background: rgba(56, 189, 248, 0.2); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.4); }
        .ann-class { font-size: 0.85rem; font-weight: 700; color: #94a3b8; background: rgba(255,255,255,0.06); padding: 4px 10px; border-radius: 12px; }
        .ann-title { font-family: 'Poppins', sans-serif; font-size: 1.45rem; font-weight: 800; color: #ffffff; line-height: 1.3; margin-bottom: 0.6rem; }
        .ann-desc { font-size: 1.05rem; color: #cbd5e1; line-height: 1.5; flex: 1; word-break: break-word; }
        .ann-footer { margin-top: 1.2rem; padding-top: 0.8rem; border-top: 1px solid rgba(255,255,255,0.08); display: flex; align-items: center; justify-content: space-between; font-size: 0.88rem; color: #94a3b8; font-weight: 600; }
        .ann-author { display: flex; align-items: center; gap: 6px; color: #e2e8f0; }
        .ann-date { display: flex; align-items: center; gap: 6px; }

        body.theme-light .s6-header h2 { color: #d97706; }
        body.theme-light .ann-card { background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 6px 20px rgba(0,0,0,0.06); }
        body.theme-light .ann-title { color: #0f172a; }
        body.theme-light .ann-desc { color: #334155; }
        body.theme-light .ann-footer { border-top-color: #f1f5f9; color: #64748b; }
        body.theme-light .ann-author { color: #0f172a; }
        body.theme-light .ann-class { background: #f1f5f9; color: #475569; }

        /* S1 WELCOME & STATS (BIGGER & HIGH CONTRAST) */
        .s1-wrap { 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            text-align: center; 
            background: rgba(15, 23, 42, 0.75); 
            backdrop-filter: blur(25px); 
            border-radius: 36px; 
            border: 1px solid rgba(255,255,255,0.12); 
            box-shadow: 0 25px 80px rgba(0,0,0,0.6); 
            padding: 2rem 3rem; 
        }
        .s1-icon { font-size: 6.5rem; margin-bottom: 0.8rem; background: linear-gradient(135deg, #38bdf8, #818cf8, #c084fc); -webkit-background-clip: text; color: transparent; filter: drop-shadow(0 0 35px rgba(56,189,248,0.5)); animation: iconPulse 3s ease-in-out infinite; }
        @keyframes iconPulse { 0%,100% { transform: scale(1); } 50% { transform: scale(1.06); } }
        .s1-title { font-family: 'Poppins'; font-weight: 900; font-size: 4.2rem; background: linear-gradient(135deg, #ffffff, #e0e7ff, #bae6fd); -webkit-background-clip: text; color: transparent; line-height: 1.15; max-width: 1100px; text-shadow: 0 0 40px rgba(255,255,255,0.2); }
        .s1-sub { font-size: 1.55rem; color: #94a3b8; font-weight: 500; margin-top: 0.8rem; max-width: 850px; }

        .s1-clock-wrap { 
            margin-top: 2rem; 
            display: flex; 
            align-items: center; 
            gap: 2.5rem; 
            background: rgba(15, 23, 42, 0.95); 
            border: 2px solid rgba(56, 189, 248, 0.35); 
            padding: 1.1rem 3.2rem; 
            border-radius: 100px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.5), 0 0 30px rgba(56,189,248,0.15); 
        }
        .s1-time { font-family: 'JetBrains Mono'; font-size: 4.4rem; font-weight: 700; letter-spacing: 3px; line-height: 1; color: #f8fafc; text-shadow: 0 0 25px rgba(56,189,248,0.4); }
        .s1-time .blink { animation: blink 1s step-end infinite; }
        @keyframes blink { 0%,100% { opacity: 1; } 50% { opacity: 0.3; } }
        .s1-date-box { text-align: left; border-left: 3px solid rgba(56, 189, 248, 0.35); padding-left: 2rem; }
        .s1-day { font-family: 'Poppins'; font-weight: 800; font-size: 1.55rem; color: #38bdf8; }
        .s1-date { font-size: 1.2rem; color: #cbd5e1; font-weight: 500; }

        .stats-strip { display: flex; gap: 1.5rem; margin-top: 2.2rem; justify-content: center; flex-wrap: wrap; }
        .stat-pill { 
            background: rgba(15, 23, 42, 0.9); 
            border: 1px solid rgba(255,255,255,0.12); 
            padding: 0.85rem 1.8rem; 
            border-radius: 50px; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.3); 
            display: flex; 
            align-items: center; 
            gap: 1.1rem; 
            font-family: 'Poppins'; 
        }
        .stat-pill i { font-size: 1.5rem; }
        .stat-pill .stat-val { font-weight: 900; font-size: 1.7rem; font-family: 'JetBrains Mono'; }
        .stat-pill .stat-lbl { font-size: 0.95rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }

        /* S2 DAILY CARDS & TABLE (LARGE & LEGIBLE) */
        .s2-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.8rem; flex: 1; align-items: stretch; }
        .s2-top3 { display: flex; flex-direction: column; gap: 1rem; justify-content: center; }
        .s2-card { 
            display: flex; 
            align-items: center; 
            gap: 1.4rem; 
            padding: 1.3rem 1.6rem; 
            border-radius: 20px; 
            background: rgba(15, 23, 42, 0.88); 
            border: 1px solid rgba(255,255,255,0.1); 
            position: relative; 
            overflow: hidden; 
            box-shadow: 0 8px 30px rgba(0,0,0,0.35); 
            transition: transform 0.3s; 
        }
        .s2-card:hover { transform: translateY(-3px); }
        .s2-card .accent { position: absolute; left: 0; top: 0; width: 6px; height: 100%; }
        .s2-card.r1 .accent { background: linear-gradient(to bottom, #fbbf24, #f59e0b); }
        .s2-card.r2 .accent { background: linear-gradient(to bottom, #94a3b8, #64748b); }
        .s2-card.r3 .accent { background: linear-gradient(to bottom, #d97706, #b45309); }
        .s2-medal { font-size: 2.8rem; width: 55px; text-align: center; flex-shrink: 0; }
        .s2-card.r1 .s2-medal { color: #fbbf24; filter: drop-shadow(0 0 12px rgba(251,191,36,0.6)); }
        .s2-card.r2 .s2-medal { color: #cbd5e1; filter: drop-shadow(0 0 8px rgba(203,213,225,0.4)); } 
        .s2-card.r3 .s2-medal { color: #f59e0b; filter: drop-shadow(0 0 8px rgba(245,158,11,0.4)); }
        .s2-info { flex: 1; min-width: 0; }
        .s2-name { font-family: 'Poppins'; font-weight: 800; font-size: 1.45rem; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .s2-class { color: #94a3b8; font-size: 1.15rem; font-weight: 600; margin-top: 2px; }
        .s2-time { 
            background: rgba(56, 189, 248, 0.15); 
            border: 1px solid rgba(56, 189, 248, 0.35); 
            color: #38bdf8; 
            padding: 0.5rem 1.1rem; 
            border-radius: 50px; 
            font-weight: 800; 
            font-size: 1.25rem; 
            font-family: 'JetBrains Mono'; 
            display: flex; 
            align-items: center; 
            gap: 7px; 
            flex-shrink: 0; 
        }

        .s2-table-wrap { 
            background: rgba(15, 23, 42, 0.88); 
            border: 1px solid rgba(255,255,255,0.1); 
            border-radius: 20px; 
            padding: 1.2rem 1.6rem; 
            box-shadow: 0 8px 30px rgba(0,0,0,0.35); 
            display: flex; 
            flex-direction: column; 
        }
        .s2-table-wrap table { width: 100%; border-collapse: collapse; }
        .s2-table-wrap th { font-weight: 700; color: #94a3b8; text-transform: uppercase; font-size: 0.95rem; letter-spacing: 1px; padding: 0.8rem 0.6rem; text-align: left; border-bottom: 2px solid rgba(255,255,255,0.08); }
        .s2-table-wrap td { padding: 0.75rem 0.6rem; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 1.15rem; color: #f8fafc; }
        .tbl-rank { font-weight: 900; color: #38bdf8; font-family: 'JetBrains Mono'; font-size: 1.25rem; width: 60px; }
        .tbl-name { font-weight: 700; color: #ffffff; font-size: 1.25rem; }
        .tbl-time-pill { background: rgba(56, 189, 248, 0.15); border: 1px solid rgba(56, 189, 248, 0.3); padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 1.15rem; font-family: 'JetBrains Mono'; color: #38bdf8; }

        /* GAMIFIED BOARD (WEEKLY, MONTHLY, HOF) */
        .board { display: flex; flex-direction: column; flex: 1; gap: 0.8rem; }
        .podium { display: flex; justify-content: center; align-items: flex-end; gap: 1.4rem; padding: 0.5rem 0; position: relative; }
        .pod-card { border-radius: 24px; text-align: center; position: relative; display: flex; flex-direction: column; align-items: center; transition: transform 0.4s; padding: 1.2rem; }
        .pod-1 { width: 270px; min-height: 330px; background: linear-gradient(160deg, #064e3b, #022c22); border: 2px solid #059669; color: #fff; box-shadow: 0 12px 50px rgba(5,150,105,0.3); }
        .pod-2 { width: 240px; min-height: 290px; background: linear-gradient(160deg, #1e3a8a, #0f172a); border: 2px solid #3b82f6; color: #fff; box-shadow: 0 12px 50px rgba(59,130,246,0.25); }
        .pod-3 { width: 240px; min-height: 270px; background: linear-gradient(160deg, #78350f, #451a03); border: 2px solid #d97706; color: #fff; box-shadow: 0 12px 50px rgba(217,119,6,0.25); }
        .pod-crown { position: absolute; top: -32px; left: 50%; transform: translateX(-50%); font-size: 2.8rem; filter: drop-shadow(0 0 12px rgba(251,191,36,0.8)); }
        .pod-rank-num { position: absolute; top: 10px; right: 14px; font-weight: 900; font-size: 1.4rem; opacity: 0.25; font-family: 'Poppins'; }
        
        .pod-avatar { width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 2rem; font-family: 'Poppins'; box-shadow: 0 6px 18px rgba(0,0,0,0.4); background: #0f172a; }
        .pod-1 .pod-avatar { width: 85px; height: 85px; font-size: 2.4rem; color: #34d399; border: 3px solid #059669; }
        .pod-2 .pod-avatar { color: #60a5fa; border: 3px solid #3b82f6; }
        .pod-3 .pod-avatar { color: #fbbf24; border: 3px solid #d97706; }

        .pod-name { font-family: 'Poppins'; font-weight: 800; font-size: 1.35rem; margin-top: 14px; color: #ffffff; line-height: 1.2; }
        .pod-class { font-size: 1.05rem; color: #cbd5e1; font-weight: 600; margin-top: 2px; }
        .pod-streak-display { font-weight: 900; font-size: 1.6rem; margin-top: auto; padding-top: 10px; font-family: 'JetBrains Mono'; }
        .pod-1 .pod-streak-display { color: #34d399; text-shadow: 0 0 15px rgba(52,211,153,0.5); }
        .pod-2 .pod-streak-display { color: #60a5fa; text-shadow: 0 0 15px rgba(96,165,250,0.5); }
        .pod-3 .pod-streak-display { color: #fbbf24; text-shadow: 0 0 15px rgba(251,191,36,0.5); }

        .board-list { flex: 1; display: flex; flex-direction: column; gap: 0.5rem; overflow: hidden; }
        .board-item { display: flex; align-items: center; gap: 1.1rem; background: rgba(15, 23, 42, 0.85); border-radius: 16px; padding: 0.65rem 1.4rem; border: 1px solid rgba(255,255,255,0.08); box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .bi-rank { font-family: 'JetBrains Mono'; font-weight: 900; font-size: 1.25rem; width: 45px; color: #38bdf8; }
        .bi-avatar { width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.15rem; font-family: 'Poppins'; }
        .bi-info { flex: 1; min-width: 0; }
        .bi-name { font-family: 'Poppins'; font-weight: 800; font-size: 1.2rem; display: flex; align-items: center; gap: 8px; color: #ffffff; }
        .bi-class-badge { font-size: 0.95rem; font-weight: 700; padding: 2px 8px; border-radius: 6px; background: #1e293b; color: #94a3b8; }
        .bi-meta { font-size: 0.95rem; color: #94a3b8; font-weight: 500; margin-top: 2px; }
        .bi-progress { width: 180px; }
        .bi-bar-bg { height: 8px; border-radius: 4px; background: #1e293b; overflow: hidden; }
        .bi-bar-fill { height: 100%; border-radius: 4px; }
        .bi-pts { font-family: 'JetBrains Mono'; font-size: 1.35rem; font-weight: 800; width: 85px; text-align: right; color: #ffffff; }

        /* TICKER RUNNING TEXT (LARGER & BRIGHTER) */
        .ticker-bar { 
            position: fixed; 
            bottom: 0; 
            left: 0; 
            right: 0; 
            height: 44px; 
            background: rgba(15, 23, 42, 0.96); 
            backdrop-filter: blur(15px); 
            border-top: 1px solid rgba(255,255,255,0.12); 
            z-index: 100; 
            display: flex; 
            align-items: center; 
            padding: 0 1.5rem; 
            box-shadow: 0 -4px 20px rgba(0,0,0,0.5); 
        }
        .ticker-badge { background: linear-gradient(135deg, #38bdf8, #818cf8); color: #0f172a; font-size: 0.95rem; font-weight: 900; padding: 4px 14px; border-radius: 20px; text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 6px; flex-shrink: 0; margin-right: 1.2rem; }
        .ticker-content { flex: 1; overflow: hidden; white-space: nowrap; font-size: 1.15rem; font-weight: 700; }
        .ticker-text { display: inline-block; padding-left: 100%; animation: tickerMove 28s linear infinite; color: #f8fafc; }
        @keyframes tickerMove { 0% { transform: translate3d(0, 0, 0); } 100% { transform: translate3d(-100%, 0, 0); } }

        /* CONTROLS */
        .nav-arrow { position: fixed; top: 50%; transform: translateY(-50%); z-index: 50; width: 52px; height: 52px; border-radius: 50%; background: rgba(15, 23, 42, 0.9); border: 1px solid rgba(255,255,255,0.15); box-shadow: 0 6px 25px rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; cursor: pointer; color: #94a3b8; opacity: 0; transition: all 0.3s; font-size: 1.2rem; }
        body:hover .nav-arrow { opacity: 0.8; }
        .nav-arrow:hover { opacity: 1 !important; transform: translateY(-50%) scale(1.1); color: #38bdf8; }
        #arrow-prev { left: 1.2rem; } #arrow-next { right: 1.2rem; }

        .nav-dots { position: fixed; bottom: 54px; left: 50%; transform: translateX(-50%); display: flex; gap: 10px; z-index: 50; }
        .dot { width: 12px; height: 12px; border-radius: 50%; background: rgba(255,255,255,0.25); cursor: pointer; transition: all 0.3s; }
        .dot.active { width: 34px; border-radius: 10px; background: #38bdf8; box-shadow: 0 0 12px rgba(56,189,248,0.7); }

        .tv-floating-tools { position: fixed; bottom: 52px; right: 1.2rem; z-index: 60; display: flex; gap: 10px; align-items: center; }
        .btn-tv-action { width: 42px; height: 42px; border-radius: 50%; background: rgba(15, 23, 42, 0.9); border: 1px solid rgba(255,255,255,0.15); box-shadow: 0 6px 20px rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; color: #94a3b8; text-decoration: none; cursor: pointer; transition: all 0.3s; font-size: 1.1rem; }
        .btn-tv-action:hover { transform: scale(1.1); color: #38bdf8; }

        /* ==================== MOBILE & TABLET RESPONSIVENESS ==================== */
        @media (max-width: 900px) {
            .slides { padding: 0.75rem 1rem 3.5rem; }
            .slide { inset: 0.75rem 1rem 3.5rem; overflow-y: auto; -webkit-overflow-scrolling: touch; }
            .s-header { margin-bottom: 0.5rem; }
            .s-header h2 { font-size: 1.25rem; letter-spacing: 0.5px; }
            .s-header h2 i { font-size: 1.1rem; }
            .s-header p { font-size: 0.8rem; }
            
            /* Slide 1 Mobile */
            .s1-wrap { padding: 1.2rem 1rem; border-radius: 20px; }
            .s1-icon { font-size: 3.5rem; margin-bottom: 0.25rem; }
            .s1-title { font-size: 1.85rem; line-height: 1.2; }
            .s1-sub { font-size: 0.95rem; }
            .s1-clock-wrap { margin-top: 1rem; padding: 0.75rem 1.2rem; gap: 1rem; border-radius: 16px; width: 90%; max-width: 320px; justify-content: center; }
            .s1-time { font-size: 2.2rem; }
            .s1-date-box { border-left: none; border-top: 1px solid #e2e8f0; padding-left: 0; padding-top: 0.5rem; text-align: center; width: 100%; }
            .s1-day { font-size: 1rem; }
            .s1-date { font-size: 0.82rem; }
            .stats-strip { margin-top: 1rem; gap: 0.5rem; display: grid; grid-template-columns: 1fr 1fr; width: 100%; max-width: 400px; }
            .stat-pill { padding: 0.5rem 0.75rem; border-radius: 12px; gap: 0.5rem; }
            .stat-pill i { font-size: 1rem; }
            .stat-pill .stat-val { font-size: 1rem; }
            .stat-pill .stat-lbl { font-size: 0.68rem; }

            /* Slide 2 Mobile Daily */
            .s2-grid { grid-template-columns: 1fr; gap: 0.75rem; overflow-y: auto; }
            .s2-card { padding: 0.6rem 0.8rem; gap: 0.6rem; border-radius: 12px; }
            .s2-medal { font-size: 1.4rem; width: 30px; }
            .s2-name { font-size: 0.9rem; }
            .s2-class { font-size: 0.75rem; }
            .s2-time { font-size: 0.78rem; padding: 0.25rem 0.5rem; }
            .s2-table-wrap { padding: 0.6rem 0.75rem; border-radius: 12px; }
            .s2-table-wrap th { font-size: 0.65rem; padding: 0.4rem; }
            .s2-table-wrap td { font-size: 0.78rem; padding: 0.4rem; }

            /* Slide 3,4,5 Mobile Podiums */
            .podium { gap: 0.4rem; width: 100%; padding: 0.25rem 0; }
            .pod-card { padding: 0.5rem 0.3rem; border-radius: 14px; }
            .pod-1 { width: 35%; min-height: 180px; }
            .pod-2 { width: 31%; min-height: 155px; }
            .pod-3 { width: 31%; min-height: 145px; }
            .pod-crown { font-size: 1.3rem; top: -16px; }
            .pod-rank-num { font-size: 0.85rem; top: 4px; right: 6px; }
            .pod-avatar { width: 42px; height: 42px; font-size: 1.2rem; }
            .pod-1 .pod-avatar { width: 50px; height: 50px; font-size: 1.4rem; }
            .pod-name { font-size: 0.75rem; margin-top: 6px; line-height: 1.2; text-align: center; }
            .pod-class { font-size: 0.65rem; }
            .pod-streak-display { font-size: 0.9rem; padding-top: 4px; }

            /* Board List Mobile */
            .board-list { gap: 0.3rem; }
            .board-item { padding: 0.4rem 0.6rem; gap: 0.5rem; border-radius: 10px; }
            .bi-rank { font-size: 0.82rem; width: 26px; }
            .bi-avatar { width: 28px; height: 28px; font-size: 0.8rem; }
            .bi-name { font-size: 0.8rem; }
            .bi-meta { font-size: 0.68rem; }
            .bi-progress { display: none; }
            .bi-pts { font-size: 0.88rem; width: auto; }

            .nav-arrow { display: none; }
            .nav-dots { bottom: 40px; }
            .ticker-bar { height: 32px; font-size: 0.78rem; padding: 0 0.5rem; }
            .ticker-badge { font-size: 0.65rem; padding: 2px 7px; margin-right: 0.5rem; }
            .tv-floating-tools { bottom: 38px; right: 0.5rem; gap: 5px; }
            .btn-tv-action { width: 30px; height: 30px; font-size: 0.75rem; }
        }

        /* ==================== ATTENDANCE REALTIME OVERLAY MODAL ==================== */
        #attendance-overlay {
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: rgba(3, 7, 18, 0.82);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            padding: 1.5rem;
        }

        #attendance-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .att-card {
            background: linear-gradient(150deg, rgba(15, 23, 42, 0.98), rgba(30, 41, 59, 0.96));
            border: 2px solid rgba(56, 189, 248, 0.5);
            border-radius: 36px;
            padding: 2.6rem 3.2rem;
            max-width: 750px;
            width: 100%;
            box-shadow: 0 25px 80px -10px rgba(0, 0, 0, 0.9), 0 0 60px rgba(56, 189, 248, 0.35);
            text-align: center;
            position: relative;
            overflow: hidden;
            transform: scale(0.85) translateY(25px);
            transition: transform 0.45s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        #attendance-overlay.active .att-card {
            transform: scale(1) translateY(0);
        }

        .att-close-btn {
            position: absolute;
            top: 1.4rem;
            right: 1.4rem;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1.2rem;
            z-index: 10;
        }
        .att-close-btn:hover {
            background: rgba(239, 68, 68, 0.25);
            color: #f87171;
            transform: scale(1.1);
        }

        .att-header-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.25), rgba(56, 189, 248, 0.25));
            border: 1.5px solid rgba(52, 211, 153, 0.6);
            color: #34d399;
            padding: 8px 24px;
            border-radius: 50px;
            font-size: 1.05rem;
            font-weight: 900;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 1.4rem;
            animation: pulseGlow 2s infinite alternate;
        }

        @keyframes pulseGlow {
            0% { box-shadow: 0 0 10px rgba(52, 211, 153, 0.2); }
            100% { box-shadow: 0 0 25px rgba(52, 211, 153, 0.5); }
        }

        .att-icon-wrap {
            width: 100px;
            height: 100px;
            margin: 0 auto 1.4rem;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981, #06b6d4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            box-shadow: 0 10px 35px rgba(16, 185, 129, 0.5);
            animation: iconPop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes iconPop {
            0% { transform: scale(0) rotate(-45deg); opacity: 0; }
            100% { transform: scale(1) rotate(0deg); opacity: 1; }
        }

        .att-greeting {
            font-family: 'Poppins', sans-serif;
            font-size: 2.2rem;
            font-weight: 800;
            line-height: 1.35;
            color: #f8fafc;
            margin-bottom: 0.8rem;
        }

        .att-name-highlight {
            background: linear-gradient(135deg, #38bdf8, #818cf8, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
            font-weight: 900;
            font-size: 2.5rem;
        }

        .att-time-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(56, 189, 248, 0.2);
            border: 1px solid rgba(56, 189, 248, 0.45);
            color: #38bdf8;
            padding: 4px 16px;
            border-radius: 24px;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 800;
            font-size: 1.6rem;
            text-shadow: 0 0 15px rgba(56,189,248,0.5);
        }

        .att-sub-info {
            display: flex;
            justify-content: center;
            gap: 14px;
            margin-bottom: 1.4rem;
            flex-wrap: wrap;
        }

        .att-tag {
            font-size: 1.15rem;
            font-weight: 700;
            padding: 6px 18px;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.08);
            color: #e2e8f0;
            border: 1px solid rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .att-quote-card {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(217, 119, 6, 0.08));
            border: 1px solid rgba(245, 158, 11, 0.4);
            border-radius: 24px;
            padding: 1.3rem 1.8rem;
            position: relative;
            text-align: center;
            margin-top: 0.8rem;
            box-shadow: 0 6px 25px rgba(0,0,0,0.25);
        }

        .att-quote-text {
            font-family: 'Inter', sans-serif;
            font-size: 1.25rem;
            font-weight: 600;
            font-style: italic;
            color: #fef08a;
            line-height: 1.55;
        }

        .att-quote-footer {
            margin-top: 0.65rem;
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: 1px;
            color: #f59e0b;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .att-countdown-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: rgba(255, 255, 255, 0.1);
        }

        /* THEME OVERLAY PULANG */
        #attendance-overlay.overlay-pulang .att-card {
            border-color: rgba(168, 85, 247, 0.8) !important;
            box-shadow: 0 25px 80px rgba(0,0,0,0.85), 0 0 60px rgba(168, 85, 247, 0.4) !important;
        }
        #attendance-overlay.overlay-pulang .att-header-badge {
            background: linear-gradient(135deg, rgba(168, 85, 247, 0.25), rgba(99, 102, 241, 0.25)) !important;
            border-color: rgba(168, 85, 247, 0.5) !important;
            color: #d8b4fe !important;
        }
        #attendance-overlay.overlay-pulang .att-icon-wrap {
            background: linear-gradient(135deg, #a855f7, #6366f1) !important;
            box-shadow: 0 10px 35px rgba(168, 85, 247, 0.5) !important;
        }
        #attendance-overlay.overlay-pulang .att-time-pill {
            background: rgba(168, 85, 247, 0.2) !important;
            border-color: rgba(168, 85, 247, 0.5) !important;
            color: #d8b4fe !important;
            text-shadow: 0 0 15px rgba(168, 85, 247, 0.5) !important;
        }
        #attendance-overlay.overlay-pulang .att-quote-card {
            background: linear-gradient(135deg, rgba(168, 85, 247, 0.18), rgba(99, 102, 241, 0.1)) !important;
            border-color: rgba(168, 85, 247, 0.4) !important;
        }
        #attendance-overlay.overlay-pulang .att-quote-text {
            color: #f3e8ff !important;
        }
        #attendance-overlay.overlay-pulang .att-quote-footer {
            color: #c084fc !important;
        }

        /* NEW ATTENDANCE HIGHLIGHT & AVATAR */
        @keyframes cardPulseGlow {
            0%, 100% { box-shadow: 0 0 20px rgba(56, 189, 248, 0.4); border-color: rgba(56, 189, 248, 0.8); }
            50% { box-shadow: 0 0 45px rgba(52, 211, 153, 0.7); border-color: rgba(52, 211, 153, 0.9); }
        }
        .s2-card.just-attended {
            animation: cardPulseGlow 1.8s ease-in-out infinite !important;
            border-width: 2px !important;
        }
        .new-pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.68rem;
            font-weight: 900;
            letter-spacing: 0.5px;
            background: linear-gradient(135deg, #10b981, #06b6d4);
            color: #ffffff;
            vertical-align: middle;
            margin-left: 6px;
            animation: pulse 1s infinite;
        }
        .s2-avatar-wrap {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid rgba(56, 189, 248, 0.5);
            flex-shrink: 0;
        }
        .s2-avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* REALTIME SIMULATION MODAL ON TV */
        .tv-sim-modal {
            position: fixed;
            inset: 0;
            z-index: 99998;
            background: rgba(3, 7, 18, 0.82);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            padding: 1.5rem;
        }
        .tv-sim-modal.active {
            opacity: 1;
            pointer-events: auto;
        }
        .tv-sim-box {
            background: linear-gradient(150deg, #0f172a, #1e293b);
            border: 2px solid rgba(99, 102, 241, 0.5);
            border-radius: 28px;
            padding: 2rem 2.4rem;
            max-width: 560px;
            width: 100%;
            box-shadow: 0 25px 70px rgba(0,0,0,0.8), 0 0 50px rgba(99, 102, 241, 0.3);
            position: relative;
            transform: scale(0.92);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .tv-sim-modal.active .tv-sim-box {
            transform: scale(1);
        }
        .tv-sim-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.2rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .tv-sim-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .tv-sim-close {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            color: #94a3b8;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .tv-sim-close:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
        }
        .tv-sim-desc {
            color: #94a3b8;
            font-size: 0.95rem;
            margin-bottom: 1.2rem;
            line-height: 1.45;
        }
        .tv-sim-group {
            margin-bottom: 1.1rem;
            text-align: left;
        }
        .tv-sim-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #cbd5e1;
            margin-bottom: 0.5rem;
        }
        .tv-sim-select {
            width: 100%;
            background: rgba(15, 23, 42, 0.9);
            border: 1.5px solid rgba(255,255,255,0.15);
            border-radius: 14px;
            padding: 0.75rem 1rem;
            color: #f8fafc;
            font-size: 0.95rem;
            font-family: inherit;
            outline: none;
        }
        .tv-sim-select:focus {
            border-color: #6366f1;
        }
        .tv-sim-time-presets {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }
        .tv-time-btn {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            color: #cbd5e1;
            padding: 8px 10px;
            border-radius: 12px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .tv-time-btn:hover, .tv-time-btn.active {
            background: rgba(99, 102, 241, 0.35);
            border-color: #818cf8;
            color: #ffffff;
        }
        .btn-run-sim {
            width: 100%;
            background: linear-gradient(135deg, #4f46e5, #06b6d4);
            border: none;
            color: #ffffff;
            font-size: 1.05rem;
            font-weight: 800;
            padding: 0.95rem 1.4rem;
            border-radius: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 10px 30px rgba(79, 70, 229, 0.4);
            transition: all 0.25s;
            margin-top: 1.2rem;
        }
        .btn-run-sim:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 40px rgba(79, 70, 229, 0.6);
        }
        .tv-sim-footer {
            margin-top: 1.2rem;
            text-align: center;
            padding-top: 0.8rem;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        .tv-sim-link {
            color: #38bdf8;
            font-size: 0.88rem;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }
        .tv-sim-link:hover {
            color: #7dd3fc;
            text-decoration: underline;
        }

        /* ==================== LIGHT MODE STYLING ==================== */
        body.theme-light {
            --text: #0f172a;
            --text-muted: #64748b;
            background: #f1f5f9;
            color: #0f172a;
        }

        body.theme-light .bg-layer {
            background: radial-gradient(circle at 10% 15%, #e0e7ff 0%, transparent 50%),
                        radial-gradient(circle at 85% 80%, #bae6fd 0%, transparent 50%),
                        radial-gradient(circle at 50% 50%, #f8fafc 0%, #e2e8f0 100%);
        }

        body.theme-light .orb { opacity: 0.18; }
        body.theme-light .orb-1 { background: #818cf8; }
        body.theme-light .orb-2 { background: #38bdf8; }
        body.theme-light .orb-3 { background: #fbbf24; }

        body.theme-light .progress-wrap {
            background: rgba(0, 0, 0, 0.08);
        }
        body.theme-light .progress-fill {
            background: linear-gradient(90deg, #0284c7, #6366f1, #d97706);
            box-shadow: 0 0 10px rgba(2, 132, 199, 0.4);
        }

        body.theme-light .s-header h2 {
            color: #0f172a;
            text-shadow: none;
        }
        body.theme-light .s-header p {
            color: #475569;
        }
        body.theme-light .s2-header h2 { color: #0284c7; }
        body.theme-light .s3-header h2 { color: #059669; }
        body.theme-light .s4-header h2 { color: #7c3aed; }
        body.theme-light .s5-header h2 { color: #d97706; }

        body.theme-light .s1-wrap {
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.08);
        }
        body.theme-light .s1-title {
            background: linear-gradient(135deg, #0f172a, #1e293b, #334155);
            -webkit-background-clip: text;
            color: transparent;
            text-shadow: none;
        }
        body.theme-light .s1-sub {
            color: #475569;
        }
        body.theme-light .s1-clock-wrap {
            background: #ffffff;
            border: 2px solid #38bdf8;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }
        body.theme-light .s1-time {
            color: #0f172a;
            text-shadow: none;
        }
        body.theme-light .s1-date-box {
            border-left: 3px solid #38bdf8;
        }
        body.theme-light .s1-day {
            color: #0284c7;
        }
        body.theme-light .s1-date {
            color: #334155;
        }
        body.theme-light .stat-pill {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            color: #0f172a;
        }
        body.theme-light .stat-pill .stat-lbl {
            color: #64748b;
        }
        body.theme-light .stat-pill .stat-val {
            color: #0f172a;
        }

        body.theme-light .s2-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
        }
        body.theme-light .s2-name {
            color: #0f172a !important;
        }
        body.theme-light .s2-class {
            color: #64748b;
        }
        body.theme-light .s2-time {
            background: #e0f2fe;
            border: 1px solid #7dd3fc;
            color: #0284c7;
        }
        body.theme-light .s2-table-wrap {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
        }
        body.theme-light .s2-table-wrap th {
            color: #64748b;
            border-bottom: 2px solid #f1f5f9;
        }
        body.theme-light .s2-table-wrap td {
            color: #0f172a;
            border-bottom: 1px solid #f8fafc;
        }
        body.theme-light .tbl-name {
            color: #0f172a;
        }
        body.theme-light .tbl-time-pill {
            background: #e0f2fe;
            border: 1px solid #7dd3fc;
            color: #0284c7;
        }

        body.theme-light .pod-1 {
            background: linear-gradient(160deg, #ecfdf5, #d1fae5);
            border: 2px solid #10b981;
            color: #064e3b;
            box-shadow: 0 12px 35px rgba(16, 185, 129, 0.2);
        }
        body.theme-light .pod-2 {
            background: linear-gradient(160deg, #eff6ff, #dbeafe);
            border: 2px solid #3b82f6;
            color: #1e3a8a;
            box-shadow: 0 12px 35px rgba(59, 130, 246, 0.18);
        }
        body.theme-light .pod-3 {
            background: linear-gradient(160deg, #fffbeb, #fef3c7);
            border: 2px solid #f59e0b;
            color: #78350f;
            box-shadow: 0 12px 35px rgba(245, 158, 11, 0.18);
        }
        body.theme-light .pod-avatar {
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        body.theme-light .pod-name {
            color: #0f172a;
        }
        body.theme-light .pod-class {
            color: #475569;
        }
        body.theme-light .pod-streak-display small {
            color: #64748b !important;
        }
        body.theme-light .pod-1 .pod-streak-display {
            color: #059669;
            text-shadow: none;
        }
        body.theme-light .pod-2 .pod-streak-display {
            color: #2563eb;
            text-shadow: none;
        }
        body.theme-light .pod-3 .pod-streak-display {
            color: #d97706;
            text-shadow: none;
        }

        body.theme-light .board-item {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        }
        body.theme-light .bi-name {
            color: #0f172a;
        }
        body.theme-light .bi-class-badge {
            background: #f1f5f9;
            color: #475569;
        }
        body.theme-light .bi-meta {
            color: #64748b;
        }
        body.theme-light .bi-pts {
            color: #0f172a;
        }
        body.theme-light .bi-bar-bg {
            background: #e2e8f0;
        }

        body.theme-light .ticker-bar {
            background: rgba(255, 255, 255, 0.96);
            border-top: 1px solid #e2e8f0;
            box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.06);
        }
        body.theme-light .ticker-text {
            color: #0f172a;
        }
        body.theme-light .ticker-badge {
            background: linear-gradient(135deg, #0284c7, #6366f1);
            color: #ffffff;
        }

        body.theme-light .nav-arrow {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #e2e8f0;
            color: #475569;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }
        body.theme-light .nav-arrow:hover {
            color: #0284c7;
        }
        body.theme-light .dot {
            background: rgba(0, 0, 0, 0.15);
        }
        body.theme-light .dot.active {
            background: #0284c7;
            box-shadow: 0 0 10px rgba(2, 132, 199, 0.5);
        }

        body.theme-light .btn-tv-action {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #e2e8f0;
            color: #475569;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }
        body.theme-light .btn-tv-action:hover {
            color: #0284c7;
            background: #ffffff;
        }

        body.theme-light #attendance-overlay {
            background: rgba(15, 23, 42, 0.65);
        }
        body.theme-light .att-card {
            background: #ffffff;
            border: 2px solid #38bdf8;
            box-shadow: 0 25px 70px rgba(15, 23, 42, 0.25);
        }
        body.theme-light .att-greeting {
            color: #0f172a;
        }
        body.theme-light .att-close-btn {
            background: rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(0, 0, 0, 0.1);
            color: #475569;
        }
        body.theme-light .att-close-btn:hover {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }
        body.theme-light .att-tag {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #e2e8f0;
        }
        body.theme-light .att-quote-card {
            background: #fffbeb;
            border: 1px solid #fde68a;
        }
        body.theme-light .att-quote-text {
            color: #92400e;
        }
        body.theme-light .att-quote-footer {
            color: #b45309;
        }
    </style>
</head>
<body class="{{ ($tvSettings['tv_theme'] ?? 'dark') === 'light' ? 'theme-light' : 'theme-dark' }}" id="tv-body">
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
                        <div class="s1-time" id="clock-time">00<span class="blink">:</span>00<span class="blink">:</span>00 <span style="font-size:0.42em;letter-spacing:1px;color:#38bdf8;font-weight:800;vertical-align:middle;margin-left:4px;">WIB</span></div>
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
                                <div class="stat-val text-blue-600" id="stat-total">{{ $stats['total_students'] }}</div>
                                <div class="stat-lbl">Total Siswa</div>
                            </div>
                        </div>
                        <div class="stat-pill">
                            <i class="fas fa-user-check text-emerald-500"></i>
                            <div>
                                <div class="stat-val text-emerald-600" id="stat-present">{{ $stats['present_count'] }}</div>
                                <div class="stat-lbl">Hadir Hari Ini</div>
                            </div>
                        </div>
                        <div class="stat-pill">
                            <i class="fas fa-clock text-indigo-500"></i>
                            <div>
                                <div class="stat-val text-indigo-600" id="stat-ontime">{{ $stats['ontime_count'] }}</div>
                                <div class="stat-lbl">Tepat Waktu</div>
                            </div>
                        </div>
                        <div class="stat-pill">
                            <i class="fas fa-chart-pie text-purple-500"></i>
                            <div>
                                <div class="stat-val text-purple-600" id="stat-pct">{{ $stats['percentage'] }}%</div>
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
                    <h2>
                        @if(!empty($tvSettings['is_weekend_mode']))
                            <i class="fas fa-umbrella-beach text-amber-400"></i> Rekap Juara Kehadiran Terakhir
                        @else
                            <i class="fas fa-bolt"></i> Top 10 Kehadiran Hari Ini
                        @endif
                    </h2>
                    <p>Siswa yang melakukan tap fingerprint paling awal (<span id="daily-active-date">{{ $stats['active_date_label'] }}</span>)</p>
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

            <!-- SLIDE 6: PENGUMUMAN & AGENDA SEKOLAH (APPROVED DARI GURU) -->
            @if(isset($activeAnnouncements) && $activeAnnouncements->isNotEmpty())
            <div class="slide" data-slide-name="announcements">
                <div class="s-header s6-header">
                    <h2><i class="fas fa-bullhorn"></i> Pengumuman & Agenda Guru</h2>
                    <p>Informasi jadwal ulangan, reminder tugas, dan agenda penting sekolah</p>
                </div>
                <div class="ann-grid">
                    @foreach($activeAnnouncements->take(6) as $ann)
                    @php $badge = $ann->type_badge; @endphp
                    <div class="ann-card type-{{ $ann->type }}">
                        <div class="ann-top">
                            <span class="ann-badge badge-{{ $ann->type }}">
                                <i class="fas {{ $badge['icon'] }}"></i> {{ $ann->type_label }}
                            </span>
                            <span class="ann-class">
                                <i class="fas fa-chalkboard mr-1 text-xs"></i> {{ $ann->schoolClass ? $ann->schoolClass->name : 'Semua Siswa' }}
                            </span>
                        </div>
                        <h3 class="ann-title">{{ $ann->title }}</h3>
                        <p class="ann-desc">{{ $ann->content }}</p>
                        <div class="ann-footer">
                            <div class="ann-author">
                                <i class="fas fa-user-tie text-amber-400"></i> {{ $ann->user->name ?? 'Dewan Guru' }}
                            </div>
                            <div class="ann-date">
                                <i class="fas fa-calendar-check text-blue-400"></i> s.d {{ \Carbon\Carbon::parse($ann->end_date)->translatedFormat('d M Y') }}
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
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
        <div class="ticker-badge" style="{{ !empty($tvSettings['is_weekend_mode']) ? 'background: linear-gradient(135deg, #f59e0b, #ec4899); color: #ffffff;' : '' }}">
            @if(!empty($tvSettings['is_weekend_mode']))
                <i class="fas fa-umbrella-beach"></i> Akhir Pekan
            @else
                <i class="fas fa-bullhorn"></i> Info
            @endif
        </div>
        <div class="ticker-content">
            <span class="ticker-text">{{ $tvSettings['tv_running_text'] }}</span>
        </div>
    </div>
    @endif

    <!-- Realtime Attendance Success Overlay Popup -->
    <div id="attendance-overlay">
        <div class="att-card">
            <button type="button" class="att-close-btn" id="att-close-btn" title="Tutup">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="att-header-badge" id="att-header-badge">
                <i class="fas fa-check-circle" id="att-badge-icon"></i> <span id="att-badge-text">Absensi Berhasil</span>
            </div>

            <div class="att-icon-wrap" id="att-icon-wrap">
                <i class="fas fa-fingerprint" id="att-finger-icon"></i>
                <img id="att-photo" src="" alt="Foto Siswa" style="display:none; width:100%; height:100%; object-fit:cover; border-radius:50%;">
            </div>

            <div class="att-greeting" id="att-greeting">
                <span id="att-salutation">Halo</span> <span class="att-name-highlight" id="att-name">Siswa</span>,<br>
                <span id="att-status-verb">Kamu sudah Absen</span> pukul <span class="att-time-pill" id="att-time">--:-- WIB</span>
            </div>

            <div class="att-sub-info">
                <div class="att-tag"><i class="fas fa-graduation-cap text-sky-400"></i> <span id="att-class">Kelas</span></div>
                <div class="att-tag" id="att-status-tag"><i class="fas fa-shield-alt text-emerald-400"></i> Terverifikasi Mesin</div>
            </div>

            <div class="att-quote-card" id="att-quote-card">
                <div class="att-quote-text" id="att-quote">"Semangat belajar hari ini! Sukses dimulai dari langkah kecil dan disiplin setiap pagi."</div>
                <div class="att-quote-footer" id="att-quote-footer">
                    <i class="fas fa-sparkles"></i> Quote Motivasi Hari Ini
                </div>
            </div>

            <div class="att-countdown-bar">
                <div class="att-countdown-fill" id="att-countdown-fill"></div>
            </div>
        </div>
    </div>

    <!-- Realtime Attendance Simulation Drawer / Modal on TV -->
    <div id="tv-sim-modal" class="tv-sim-modal">
        <div class="tv-sim-box">
            <div class="tv-sim-header">
                <div class="tv-sim-title">
                    <i class="fas fa-flask text-amber-400"></i> Simulator Absensi Realtime TV
                </div>
                <button type="button" class="tv-sim-close" id="btn-close-sim-modal" title="Tutup">&times;</button>
            </div>
            <div class="tv-sim-body">
                <p class="tv-sim-desc">
                    Uji coba notifikasi pop-up absen Masuk & Pulang dengan animasi visual dan efek suara realtime.
                </p>

                <div class="tv-sim-group">
                    <label>Jenis Presensi:</label>
                    <div class="tv-sim-time-presets" style="grid-template-columns: 1fr 1fr; margin-bottom: 0.8rem;">
                        <button type="button" class="tv-status-btn active" data-status="0"><i class="fas fa-sign-in-alt text-emerald-400"></i> Absen Masuk</button>
                        <button type="button" class="tv-status-btn" data-status="1"><i class="fas fa-walking text-amber-400"></i> Absen Pulang</button>
                    </div>
                </div>

                <div class="tv-sim-group">
                    <label>Pilih Siswa untuk Simulasi:</label>
                    <select id="tv-sim-student-select" class="tv-sim-select">
                        <option value="random">🎲 Siswa Acak (Random)</option>
                        @foreach($allStudents ?? [] as $st)
                            <option value="{{ $st->id }}">{{ $st->name }} ({{ $st->schoolClass->name ?? '-' }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="tv-sim-group">
                    <label>Preset Waktu Kehadiran:</label>
                    <div class="tv-sim-time-presets" style="grid-template-columns: repeat(4, 1fr);">
                        <button type="button" class="tv-time-btn active" data-time="now"><i class="fas fa-bolt"></i> Sekarang</button>
                        <button type="button" class="tv-time-btn" data-time="06:15"><i class="far fa-clock"></i> 06:15</button>
                        <button type="button" class="tv-time-btn" data-time="06:45"><i class="fas fa-running"></i> 06:45</button>
                        <button type="button" class="tv-time-btn" data-time="14:15"><i class="fas fa-home"></i> 14:15</button>
                    </div>
                </div>

                <button type="button" id="btn-run-tv-sim" class="btn-run-sim">
                    <i class="fas fa-paper-plane"></i> Simulasikan Tap Absen Sekarang
                </button>

                <div class="tv-sim-footer">
                    <a href="/attendance/simulate" target="_blank" class="tv-sim-link">
                        <i class="fas fa-external-link-alt"></i> Buka Halaman Simulator Lengkap
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Quick Actions (Dashboard, Fullscreen, Settings, Simulation) -->
    <div class="tv-floating-tools">
        <button type="button" id="btn-open-sim-modal" class="btn-tv-action" title="Buka Panel Uji Coba / Simulasi Absen" style="background: rgba(99, 102, 241, 0.4); border-color: rgba(99, 102, 241, 0.6); color: #a5b4fc;">
            <i class="fas fa-flask"></i>
        </button>
        <button type="button" id="btn-theme-tv" class="btn-tv-action" title="Ganti Mode Gelap / Terang (Dark / Light)">
            <i class="fas {{ ($tvSettings['tv_theme'] ?? 'dark') === 'light' ? 'fa-moon' : 'fa-sun' }}" id="tv-theme-icon"></i>
        </button>
        <button type="button" id="btn-test-popup" class="btn-tv-action" title="Uji Coba Cepat Pop-up Absen Masuk (1-Klik)">
            <i class="fas fa-sign-in-alt text-emerald-400"></i>
        </button>
        <button type="button" id="btn-test-pulang" class="btn-tv-action" title="Uji Coba Cepat Pop-up Absen Pulang (1-Klik)">
            <i class="fas fa-walking text-amber-400"></i>
        </button>
        <a href="{{ route('monitoring.kepulangan') }}" class="btn-tv-action" title="Buka Monitoring Kepulangan Siswa" target="_blank" style="color: #38bdf8; border-color: rgba(56, 189, 248, 0.4);">
            <i class="fas fa-door-open"></i>
        </a>
        <a href="{{ route('attendance.index') }}" class="btn-tv-action" title="Kembali ke Dashboard">
            <i class="fas fa-th-large"></i>
        </a>
        <button type="button" id="btn-fullscreen-tv" class="btn-tv-action" title="Layar Penuh">
            <i class="fas fa-expand"></i>
        </button>
        <a href="{{ route('settings.index') }}" class="btn-tv-action" title="Buka Pengaturan Mode TV" target="_blank">
            <i class="fas fa-cog"></i>
        </a>
    </div>

    <script>
        // ======================== QUICK THEME TOGGLE (DARK / LIGHT) ========================
        const tvBody = document.getElementById('tv-body');
        const btnThemeTv = document.getElementById('btn-theme-tv');
        const themeIcon = document.getElementById('tv-theme-icon');

        function updateThemeDisplay(theme) {
            if (theme === 'light') {
                tvBody.classList.remove('theme-dark');
                tvBody.classList.add('theme-light');
                if (themeIcon) {
                    themeIcon.className = 'fas fa-moon';
                }
            } else {
                tvBody.classList.remove('theme-light');
                tvBody.classList.add('theme-dark');
                if (themeIcon) {
                    themeIcon.className = 'fas fa-sun';
                }
            }
        }

        const savedTheme = localStorage.getItem('tv_display_theme');
        if (savedTheme) {
            updateThemeDisplay(savedTheme);
        }

        btnThemeTv?.addEventListener('click', () => {
            const isCurrentlyLight = tvBody.classList.contains('theme-light');
            const nextTheme = isCurrentlyLight ? 'dark' : 'light';
            updateThemeDisplay(nextTheme);
            localStorage.setItem('tv_display_theme', nextTheme);
        });

        // ======================== REAL DATA FROM BACKEND ========================
        let dailyData = {!! json_encode($dailyData) !!};
        let weeklyData = {!! json_encode($weeklyData) !!};
        let monthlyData = {!! json_encode($monthlyData) !!};
        let hofData = {!! json_encode($hofData) !!};

        // Simpan cache ke localStorage agar saat refresh (biasa / hard) data tidak pernah hilang
        try {
            if (dailyData && dailyData.length > 0) {
                localStorage.setItem('tv_daily_cache', JSON.stringify(dailyData));
            } else {
                const cachedDaily = localStorage.getItem('tv_daily_cache');
                if (cachedDaily) {
                    const parsed = JSON.parse(cachedDaily);
                    if (Array.isArray(parsed) && parsed.length > 0) {
                        dailyData = parsed;
                    }
                }
            }

            if (weeklyData && weeklyData.length > 0) {
                localStorage.setItem('tv_weekly_cache', JSON.stringify(weeklyData));
            } else {
                const cachedWeekly = localStorage.getItem('tv_weekly_cache');
                if (cachedWeekly) {
                    const parsed = JSON.parse(cachedWeekly);
                    if (Array.isArray(parsed) && parsed.length > 0) {
                        weeklyData = parsed;
                    }
                }
            }
        } catch(e) {}

        // Urutkan siswa berdasarkan waktu kedatangan paling awal (ascending) agar peringkat #1 adalah yang datang paling pagi
        function sortDailyData() {
            dailyData.sort((a, b) => {
                const timeA = (a.time || '99:99').trim();
                const timeB = (b.time || '99:99').trim();
                if (timeA !== timeB) {
                    return timeA.localeCompare(timeB);
                }
                const stampA = a.timestamp || '';
                const stampB = b.timestamp || '';
                return stampA.localeCompare(stampB);
            });
        }
        sortDailyData();

        // ======================== MOTIVATIONAL QUOTES ========================
        const motivationalQuotes = [
            "Disiplin adalah jembatan antara cita-cita dan pencapaian nyata.",
            "Masa depan adalah milik mereka yang mempersiapkan diri mulai hari ini.",
            "Setiap langkah kecil dan konsisten membawamu lebih dekat ke impian besarmu.",
            "Sukses dimulai dari kebiasaan baik dan kedisiplinan setiap pagi.",
            "Jangan menunggu kesempatan datang, ciptakan kesempatan terbaikmu sendiri.",
            "Orang sukses tidak pernah berhenti belajar dan terus memperbaiki diri.",
            "Kerja keras dan ketekunan hari ini akan berbuah manis di masa depan.",
            "Konsistensi adalah kunci utama yang mengubah hal biasa menjadi luar biasa.",
            "Waktu adalah aset paling berharga. Manfaatkan setiap detiknya untuk berkembang.",
            "Jadilah pribadi yang lebih berprestasi dan lebih baik dari dirimu yang kemarin.",
            "Pendidikan adalah paspor terbaik untuk meraih masa depan yang gemilang.",
            "Fokuslah pada proses dan tujuanmu, jangan biarkan rasa malas menghalangi langkahmu.",
            "Keberanian untuk memulai pagi ini adalah awal dari setiap kesuksesan besar.",
            "Ketekunan mengalahkan bakat ketika bakat tidak bekerja keras.",
            "Hari baru adalah peluang baru. Berikan usaha terbaikmu hari ini!",
            "Keringat hari ini adalah kejayaanmu di masa depan.",
            "Bermimpilah setinggi langit, dan berjuanglah dengan segenap hatimu.",
            "Karakter dan integritas terbentuk dari apa yang kamu lakukan setiap hari."
        ];

        function getRandomQuote() {
            return motivationalQuotes[Math.floor(Math.random() * motivationalQuotes.length)];
        }

        // ======================== MELODIC CHIME SOUND ========================
        let audioCtx = null;
        function getAudioContext() {
            if (!audioCtx) {
                const AudioCtxClass = window.AudioContext || window.webkitAudioContext;
                if (AudioCtxClass) audioCtx = new AudioCtxClass();
            }
            if (audioCtx && audioCtx.state === 'suspended') {
                audioCtx.resume().catch(() => {});
            }
            return audioCtx;
        }

        // Unlock audio on first user gesture
        ['click', 'touchstart', 'keydown'].forEach(evt => {
            window.addEventListener(evt, () => getAudioContext(), { once: true });
        });

        function playChime() {
            try {
                const ctx = getAudioContext();
                if (!ctx) return;
                
                const playTone = (freq, start, duration) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(freq, ctx.currentTime + start);
                    
                    gain.gain.setValueAtTime(0, ctx.currentTime + start);
                    gain.gain.linearRampToValueAtTime(0.2, ctx.currentTime + start + 0.04);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + start + duration);
                    
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(ctx.currentTime + start);
                    osc.stop(ctx.currentTime + start + duration);
                };

                // Melodic chime (E5 -> A5)
                playTone(659.25, 0, 0.35);
                playTone(880.00, 0.12, 0.55);
            } catch(e) {}
        }

        function playChimePulang() {
            try {
                const ctx = getAudioContext();
                if (!ctx) return;
                
                const playTone = (freq, start, duration) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(freq, ctx.currentTime + start);
                    
                    gain.gain.setValueAtTime(0, ctx.currentTime + start);
                    gain.gain.linearRampToValueAtTime(0.25, ctx.currentTime + start + 0.04);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + start + duration);
                    
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(ctx.currentTime + start);
                    osc.stop(ctx.currentTime + start + duration);
                };

                // Warm descending chime (A5 -> F#5) for pulang
                playTone(880.00, 0, 0.35);
                playTone(739.99, 0.15, 0.6);
            } catch(e) {}
        }

        const farewellQuotes = [
            "Terima kasih atas semangat belajar hari ini! Selamat pulang dan hati-hati di perjalanan.",
            "Hari ini kamu telah berusaha dengan hebat! Istirahatlah dengan baik dan sampai jumpa besok.",
            "Pulang dengan selamat, salam hangat untuk orang tua dan keluarga di rumah!",
            "Ilmu yang dipelajari hari ini adalah investasi masa depanmu. Hati-hati di jalan sampai rumah!",
            "Selesaikan hari ini dengan rasa syukur dan persiapkan energi baru untuk hari esok!"
        ];
        function getRandomFarewellQuote() {
            return farewellQuotes[Math.floor(Math.random() * farewellQuotes.length)];
        }

        // ======================== LIVE STATS REALTIME CONTROLLER ========================
        let statTotal = {{ $stats['total_students'] ?? 0 }};
        let statPresent = {{ $stats['present_count'] ?? 0 }};
        let statOntime = {{ $stats['ontime_count'] ?? 0 }};
        const checkinEndTarget = '{{ $checkinEnd ?? "06:30" }}';

        function updateLiveStats(timeStr) {
            statPresent++;
            const isOntime = (timeStr || '07:00') <= checkinEndTarget;
            if (isOntime) statOntime++;
            
            const pEl = document.getElementById('stat-present');
            const oEl = document.getElementById('stat-ontime');
            const pctEl = document.getElementById('stat-pct');
            
            if (pEl) pEl.textContent = statPresent;
            if (oEl) oEl.textContent = statOntime;
            if (pctEl && statTotal > 0) {
                const pct = Math.min(100, Math.round((statPresent / statTotal) * 1000) / 10);
                pctEl.textContent = pct + '%';
            }
        }

        // ======================== ATTENDANCE POPUP OVERLAY CONTROLLER ========================
        let overlayTimer = null;
        const overlayEl = document.getElementById('attendance-overlay');
        const attNameEl = document.getElementById('att-name');
        const attTimeEl = document.getElementById('att-time');
        const attClassEl = document.getElementById('att-class');
        const attQuoteEl = document.getElementById('att-quote');
        const attCountdownFill = document.getElementById('att-countdown-fill');

        let isAttendancePaused = false;
        let attendancePauseTimer = null;

        function goToDailySlide() {
            const slides = Array.from(document.querySelectorAll('.slide'));
            const dailyIdx = slides.findIndex(s => s.dataset.slideName === 'daily');
            if (dailyIdx !== -1) {
                goTo(dailyIdx);
            }
        }

        function showAttendanceOverlay(studentName, timeStr, className, photoUrl, type = 'masuk') {
            if (!overlayEl) return;

            // Abaikan jika user adalah admin
            const lowerName = (studentName || '').toLowerCase();
            if (lowerName.includes('admin')) {
                return;
            }

            const isPulang = (type === 'pulang');

            // Sesuaikan tampilan overlay Masuk vs Pulang
            const badgeIcon = document.getElementById('att-badge-icon');
            const badgeText = document.getElementById('att-badge-text');
            const salutationEl = document.getElementById('att-salutation');
            const statusVerbEl = document.getElementById('att-status-verb');
            const statusTagEl = document.getElementById('att-status-tag');
            const fingerIcon = document.getElementById('att-finger-icon');
            const quoteFooterEl = document.getElementById('att-quote-footer');

            if (isPulang) {
                overlayEl.classList.add('overlay-pulang');
                if (badgeIcon) badgeIcon.className = 'fas fa-walking';
                if (badgeText) badgeText.textContent = 'Absen Pulang Berhasil';
                if (salutationEl) salutationEl.textContent = 'Sampai Jumpa,';
                if (statusVerbEl) statusVerbEl.textContent = 'Kamu sudah Absen Pulang';
                if (statusTagEl) statusTagEl.innerHTML = '<i class="fas fa-check-circle text-indigo-400"></i> Status: Sudah Pulang';
                if (fingerIcon) fingerIcon.className = 'fas fa-door-open';
                if (attQuoteEl) attQuoteEl.textContent = `"${getRandomFarewellQuote()}"`;
                if (quoteFooterEl) quoteFooterEl.innerHTML = '<i class="fas fa-heart text-pink-400"></i> Selamat Beristirahat & Hati-Hati di Jalan';
            } else {
                overlayEl.classList.remove('overlay-pulang');
                if (badgeIcon) badgeIcon.className = 'fas fa-check-circle';
                if (badgeText) badgeText.textContent = 'Absensi Masuk Berhasil';
                if (salutationEl) salutationEl.textContent = 'Halo';
                if (statusVerbEl) statusVerbEl.textContent = 'Kamu sudah Absen';
                if (statusTagEl) statusTagEl.innerHTML = '<i class="fas fa-shield-alt text-emerald-400"></i> Terverifikasi Mesin';
                if (fingerIcon) fingerIcon.className = 'fas fa-fingerprint';
                if (attQuoteEl) attQuoteEl.textContent = `"${getRandomQuote()}"`;
                if (quoteFooterEl) quoteFooterEl.innerHTML = '<i class="fas fa-sparkles"></i> Quote Motivasi Hari Ini';
            }

            // Isi konten
            if (attNameEl) attNameEl.textContent = studentName || 'Siswa';
            if (attTimeEl) attTimeEl.textContent = (timeStr || '07:00') + ' WIB';
            if (attClassEl) attClassEl.textContent = className || 'Umum';

            // Tampilkan foto siswa atau ikon sidik jari mesin
            const attPhoto = document.getElementById('att-photo');
            if (attPhoto && fingerIcon) {
                if (photoUrl) {
                    attPhoto.src = photoUrl;
                    attPhoto.style.display = 'block';
                    fingerIcon.style.display = 'none';
                } else {
                    attPhoto.style.display = 'none';
                    fingerIcon.style.display = 'block';
                }
            }

            if (!isPulang) {
                // Otomatis langsung pindah ke Tampilan ke-2 (Slide 2: Kehadiran Hari Ini)
                goToDailySlide();

                // Tahan carousel pada Slide 2 selama 8.5 detik agar semua orang melihat siswa yang baru absen
                isAttendancePaused = true;
                clearTimeout(attendancePauseTimer);
                attendancePauseTimer = setTimeout(() => {
                    isAttendancePaused = false;
                    resetProg();
                }, 8500);
            }

            // Reset countdown bar
            if (attCountdownFill) {
                attCountdownFill.style.transition = 'none';
                attCountdownFill.style.width = '100%';
            }

            // Tampilkan modal popup
            overlayEl.classList.add('active');
            if (isPulang) {
                playChimePulang();
            } else {
                playChime();
            }

            // Jalankan countdown bar animasi 6.5 detik
            setTimeout(() => {
                if (attCountdownFill) {
                    attCountdownFill.style.transition = 'width 6.5s linear';
                    attCountdownFill.style.width = '0%';
                }
            }, 50);

            // Bersihkan timer lama jika ada
            clearTimeout(overlayTimer);

            // Auto dismiss modal setelah 6.5 detik
            overlayTimer = setTimeout(() => {
                hideAttendanceOverlay();
            }, 6500);
        }

        function hideAttendanceOverlay() {
            if (overlayEl) {
                overlayEl.classList.remove('active');
            }
            clearTimeout(overlayTimer);
        }

        document.getElementById('att-close-btn')?.addEventListener('click', hideAttendanceOverlay);
        overlayEl?.addEventListener('click', (e) => {
            if (e.target === overlayEl) hideAttendanceOverlay();
        });

        // ======================== INTERACTIVE SIMULATION CONTROLLERS ========================
        const tvSimModal = document.getElementById('tv-sim-modal');
        const btnOpenSimModal = document.getElementById('btn-open-sim-modal');
        const btnCloseSimModal = document.getElementById('btn-close-sim-modal');
        const btnRunTvSim = document.getElementById('btn-run-tv-sim');
        let selectedSimPresetTime = 'now';

        btnOpenSimModal?.addEventListener('click', () => {
            tvSimModal?.classList.add('active');
        });

        btnCloseSimModal?.addEventListener('click', () => {
            tvSimModal?.classList.remove('active');
        });

        tvSimModal?.addEventListener('click', (e) => {
            if (e.target === tvSimModal) tvSimModal.classList.remove('active');
        });

        // Preset Time Selector in TV Modal
        document.querySelectorAll('.tv-time-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tv-time-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                selectedSimPresetTime = btn.dataset.time || 'now';
            });
        });

        // Preset Status Selector (Masuk vs Pulang) in TV Modal
        let selectedSimStatus = 0;
        document.querySelectorAll('.tv-status-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tv-status-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                selectedSimStatus = parseInt(btn.dataset.status || '0');
            });
        });

        // Run simulation from TV Modal
        btnRunTvSim?.addEventListener('click', async () => {
            const studentId = document.getElementById('tv-sim-student-select')?.value || 'random';
            let timeVal = null;
            if (selectedSimPresetTime !== 'now') {
                timeVal = selectedSimPresetTime;
            }

            btnRunTvSim.disabled = true;
            btnRunTvSim.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses Absensi...';

            try {
                const res = await fetch("/attendance/simulate", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        student_id: studentId,
                        time: timeVal,
                        status_code: selectedSimStatus
                    })
                });

                const data = await res.json();
                if (data.status === 'success' && data.log) {
                    tvSimModal?.classList.remove('active');
                    processNewAttendanceLog(data.log);
                }
            } catch(e) {
                // Fallback simulation
                runLocalFallbackSimulation(selectedSimStatus);
            } finally {
                btnRunTvSim.disabled = false;
                btnRunTvSim.innerHTML = '<i class="fas fa-paper-plane"></i> Simulasikan Tap Absen Sekarang';
            }
        });

        // Quick 1-Click Simulation Button for Masuk (#btn-test-popup)
        document.getElementById('btn-test-popup')?.addEventListener('click', async () => {
            const btn = document.getElementById('btn-test-popup');
            if (btn) btn.style.transform = 'scale(0.9)';
            setTimeout(() => { if (btn) btn.style.transform = ''; }, 200);

            try {
                const res = await fetch("/attendance/simulate", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        student_id: 'random',
                        status_code: 0
                    })
                });

                const data = await res.json();
                if (data.status === 'success' && data.log) {
                    processNewAttendanceLog(data.log);
                } else {
                    runLocalFallbackSimulation(0);
                }
            } catch(err) {
                runLocalFallbackSimulation(0);
            }
        });

        // Quick 1-Click Simulation Button for Pulang (#btn-test-pulang)
        document.getElementById('btn-test-pulang')?.addEventListener('click', async () => {
            const btn = document.getElementById('btn-test-pulang');
            if (btn) btn.style.transform = 'scale(0.9)';
            setTimeout(() => { if (btn) btn.style.transform = ''; }, 200);

            try {
                const res = await fetch("/attendance/simulate", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        student_id: 'random',
                        status_code: 1,
                        time: '14:15'
                    })
                });

                const data = await res.json();
                if (data.status === 'success' && data.log) {
                    processNewAttendanceLog(data.log);
                } else {
                    runLocalFallbackSimulation(1);
                }
            } catch(err) {
                runLocalFallbackSimulation(1);
            }
        });

        function runLocalFallbackSimulation(statusCode = 0) {
            const sampleNames = ['Mohamad Jibril', 'Achmad Dzaky', 'Kayla Aqila', 'Rafa Abrar', 'Sheren Angelica', 'Aulia Zahra'];
            const sampleClasses = ['XI RPL 1', 'XI RPL 2', 'X PPLG 1', 'XII RPL 1'];
            const rName = sampleNames[Math.floor(Math.random() * sampleNames.length)];
            const rClass = sampleClasses[Math.floor(Math.random() * sampleClasses.length)];
            const n = new Date();
            const nowWibParts = new Intl.DateTimeFormat('id-ID', {
                timeZone: 'Asia/Jakarta',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            }).formatToParts(n);
            const hWib = nowWibParts.find(p => p.type === 'hour')?.value || String(n.getHours()).padStart(2,'0');
            const mWib = nowWibParts.find(p => p.type === 'minute')?.value || String(n.getMinutes()).padStart(2,'0');
            const timeStr = statusCode === 1 ? '14:15' : `${hWib}:${mWib}`;
            
            if (statusCode === 1) {
                showAttendanceOverlay(rName, timeStr, rClass, null, 'pulang');
                return;
            }

            const existingIdx = dailyData.findIndex(d => (d.name || '').toLowerCase() === rName.toLowerCase());
            let finalTime = timeStr;
            if (existingIdx !== -1) {
                const existing = dailyData[existingIdx];
                if (existing.time && existing.time <= timeStr) {
                    finalTime = existing.time;
                }
                dailyData.splice(existingIdx, 1);
            }

            dailyData.forEach(d => d.is_just_attended = false);

            dailyData.push({
                name: rName,
                cls: rClass,
                time: finalTime,
                ini: rName.charAt(0).toUpperCase(),
                photo: null,
                is_ontime: true,
                is_just_attended: true
            });

            sortDailyData();
            if (dailyData.length > 10) dailyData = dailyData.slice(0, 10);
            try { localStorage.setItem('tv_daily_cache', JSON.stringify(dailyData)); } catch(e) {}
            renderDaily();

            showAttendanceOverlay(rName, timeStr, rClass, null, 'masuk');
            updateLiveStats(timeStr);
        }

        window.testAttendancePopup = showAttendanceOverlay;

        // ======================== RENDER FUNCTIONS ========================
        function renderDaily() {
            const top3El = document.getElementById('daily-top3');
            const tableEl = document.getElementById('daily-table');
            if (!top3El || !tableEl) return;

            if (dailyData.length === 0) {
                top3El.innerHTML = `<div class="s2-card r1" style="justify-content:center;text-align:center;padding:1.8rem 1.2rem;"><div class="accent"></div><div class="s2-info"><div class="s2-name" style="font-size:1.35rem;color:var(--text);margin-bottom:6px;"><i class="fas fa-fingerprint text-sky-400"></i> Belum ada presensi siswa hari ini</div><div class="s2-class" style="font-size:1.05rem;color:var(--text-muted);">Silakan tap sidik jari pada mesin untuk menjadi yang pertama</div></div></div>`;
                tableEl.innerHTML = `<tr><td colspan="4" class="text-center py-10" style="color:var(--text-muted);font-weight:600;font-size:1.15rem;"><i class="far fa-clock mr-2"></i> Menunggu kehadiran siswa pada hari ini...</td></tr>`;
                return;
            }

            top3El.innerHTML = dailyData.slice(0,3).map((d,i) => {
                const c = ['r1','r2','r3'][i];
                const isJustAttended = d.is_just_attended ? 'just-attended' : '';
                const avatarContent = d.photo 
                    ? `<div class="s2-avatar-wrap"><img src="${d.photo}" alt="${d.name}" class="s2-avatar-img"></div>`
                    : `<div class="s2-medal"><i class="fas fa-medal"></i></div>`;

                return `<div class="s2-card ${c} ${isJustAttended}">
                    <div class="accent"></div>
                    ${avatarContent}
                    <div class="s2-info">
                        <div class="s2-name">
                            ${d.name}
                            ${d.is_just_attended ? '<span class="new-pill">BARU</span>' : ''}
                        </div>
                        <div class="s2-class">${d.cls}</div>
                    </div>
                    <div class="s2-time"><i class="far fa-clock"></i> ${d.time} WIB</div>
                </div>`;
            }).join('');

            const rest = dailyData.slice(3,10);
            if (rest.length > 0) {
                tableEl.innerHTML = rest.map((d,i) => `<tr>
                    <td class="tbl-rank">#${i+4}</td>
                    <td class="tbl-name">${d.name} ${d.is_just_attended ? '<span class="new-pill" style="font-size:0.6rem;padding:1px 6px;">BARU</span>' : ''}</td>
                    <td>${d.cls}</td>
                    <td><span class="tbl-time-pill">${d.time} WIB</span></td>
                </tr>`).join('');
            } else {
                tableEl.innerHTML = `<tr><td colspan="4" class="text-center text-gray-400 py-6 text-base">Menunggu siswa berikutnya...</td></tr>`;
            }
        }

        function renderBoard(id, data, color) {
            const el = document.getElementById(id);
            if (!el) return;

            if (!data || data.length === 0) {
                el.innerHTML = `<div class="text-center text-gray-400 py-16 font-medium text-lg">Belum ada data akumulasi presensi untuk periode ini.</div>`;
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
                    <div class="pod-streak-display">${d.pts} <small style="font-size:0.6em;color:#cbd5e1;">pts (${d.streak} Hari)</small></div>
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
                        <div class="bi-avatar" style="background:${color}25;color:${color}">${listAvatar}</div>
                        <div class="bi-info">
                            <div class="bi-name">${d.name} <span class="bi-class-badge">${d.cls}</span></div>
                            <div class="bi-meta"><span><i class="fas fa-check-circle text-emerald-400"></i> ${d.days || d.streak} Hari Hadir</span> &bull; <span>Tap: ${d.tap} WIB</span></div>
                        </div>
                        <div class="bi-progress">
                            <div class="bi-bar-bg"><div class="bi-bar-fill" style="width:${pct}%;background:${color};"></div></div>
                        </div>
                        <div class="bi-pts"><strong>${d.pts}</strong><span style="font-size:0.75em;margin-left:3px;color:#94a3b8">pt</span></div>
                    </div>`;
                });
                h += `</div>`;
            }

            el.innerHTML = h;
        }

        // ======================== REALTIME CLOCK (WIB / ASIA/JAKARTA) ========================
        function updateClock() {
            const n = new Date();
            const timeFormatter = new Intl.DateTimeFormat('id-ID', {
                timeZone: 'Asia/Jakarta',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });
            const parts = timeFormatter.formatToParts(n);
            const h = parts.find(p => p.type === 'hour')?.value || String(n.getHours()).padStart(2,'0');
            const m = parts.find(p => p.type === 'minute')?.value || String(n.getMinutes()).padStart(2,'0');
            const s = parts.find(p => p.type === 'second')?.value || String(n.getSeconds()).padStart(2,'0');
            
            const timeEl = document.getElementById('clock-time');
            if (timeEl) timeEl.innerHTML = `${h}<span class="blink">:</span>${m}<span class="blink">:</span>${s} <span style="font-size:0.42em;letter-spacing:1px;color:#38bdf8;font-weight:800;vertical-align:middle;margin-left:4px;">WIB</span>`;
            
            const dayFormatter = new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Jakarta', weekday: 'long' });
            const dateOnlyFormatter = new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Jakarta', day: 'numeric', month: 'long', year: 'numeric' });
            
            const dayEl = document.getElementById('clock-day');
            const dateEl = document.getElementById('clock-date');
            
            if (dayEl) dayEl.textContent = dayFormatter.format(n);
            if (dateEl) dateEl.textContent = dateOnlyFormatter.format(n);
        }

        // ======================== ROBUST CAROUSEL CONTROLLER ========================
        const allSlides = Array.from(document.querySelectorAll('.slide'));
        const dotsContainer = document.getElementById('nav-dots');
        const progFill = document.getElementById('progress-fill');
        let cur = 0;
        const total = allSlides.length;
        let progTimer = null;
        let isManualPaused = false;
        let slideStartTime = Date.now();
        let lastProgressPct = 0;

        const rawInterval = {{ (int) ($tvSettings['tv_slide_interval'] ?? 30) }};
        const slideInterval = Math.max(3, rawInterval) * 1000;

        // Inisialisasi slide awal yang benar
        if (allSlides.length > 0) {
            allSlides.forEach((s, idx) => {
                if (idx === 0) {
                    s.classList.add('active');
                    s.classList.remove('exit');
                } else {
                    s.classList.remove('active', 'exit');
                }
            });
        }

        // Buat dots navigasi dinamis
        if (dotsContainer && total > 1) {
            dotsContainer.innerHTML = Array.from({length: total}).map((_, i) => `<div class="dot ${i===0?'active':''}" data-index="${i}"></div>`).join('');
        }

        function goTo(idx) {
            if (total <= 1) return;
            const nextIdx = ((idx % total) + total) % total;
            if (nextIdx === cur) {
                resetProg();
                return;
            }

            allSlides.forEach((s, i) => {
                if (i === cur) {
                    s.classList.remove('active');
                    s.classList.add('exit');
                } else if (i === nextIdx) {
                    s.classList.remove('exit');
                    s.classList.add('active');
                } else {
                    s.classList.remove('active', 'exit');
                }
            });

            const dots = document.querySelectorAll('.dot');
            dots.forEach((d, i) => {
                if (i === nextIdx) d.classList.add('active');
                else d.classList.remove('active');
            });

            cur = nextIdx;
            setTimeout(() => {
                document.querySelectorAll('.slide.exit').forEach(e => e.classList.remove('exit'));
            }, 850);

            resetProg();
        }

        function next() { goTo(cur + 1); }
        function prev() { goTo(cur - 1); }

        function startProg() {
            if (total <= 1) return;
            clearInterval(progTimer);
            slideStartTime = Date.now();
            lastProgressPct = 0;

            if (progFill) {
                progFill.style.transition = 'none';
                progFill.style.width = '0%';
            }

            progTimer = setInterval(() => {
                if (isManualPaused || isAttendancePaused) {
                    slideStartTime = Date.now() - (lastProgressPct / 100 * slideInterval);
                    return;
                }

                const elapsed = Date.now() - slideStartTime;
                const pct = Math.min(100, (elapsed / slideInterval) * 100);
                lastProgressPct = pct;

                if (progFill) {
                    progFill.style.transition = 'width 0.2s linear';
                    progFill.style.width = pct + '%';
                }

                if (elapsed >= slideInterval) {
                    next();
                }
            }, 200);
        }

        function resetProg() {
            clearInterval(progTimer);
            lastProgressPct = 0;
            if (progFill) {
                progFill.style.transition = 'none';
                progFill.style.width = '0%';
            }
            startProg();
        }

        document.getElementById('arrow-next')?.addEventListener('click', next);
        document.getElementById('arrow-prev')?.addEventListener('click', prev);
        document.querySelectorAll('.dot').forEach((d,i) => d.addEventListener('click', () => goTo(i)));

        // Fullscreen Toggle Button & Auto-Fullscreen Logic
        const autoFullscreen = {{ $tvSettings['tv_auto_fullscreen'] ? 'true' : 'false' }};

        function enterFullscreen() {
            if (!document.fullscreenElement) {
                const el = document.documentElement;
                if (el.requestFullscreen) {
                    el.requestFullscreen().catch(() => {});
                } else if (el.webkitRequestFullscreen) {
                    el.webkitRequestFullscreen();
                } else if (el.msRequestFullscreen) {
                    el.msRequestFullscreen();
                }
            }
        }

        function exitFullscreen() {
            if (document.fullscreenElement) {
                if (document.exitFullscreen) {
                    document.exitFullscreen().catch(() => {});
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
            }
        }

        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                enterFullscreen();
            } else {
                exitFullscreen();
            }
        }

        document.getElementById('btn-fullscreen-tv')?.addEventListener('click', toggleFullscreen);

        // Auto Fullscreen saat halaman dibuka
        if (autoFullscreen) {
            setTimeout(() => {
                enterFullscreen();
            }, 300);

            const onFirstUserGesture = () => {
                enterFullscreen();
                window.removeEventListener('click', onFirstUserGesture);
                window.removeEventListener('touchstart', onFirstUserGesture);
                window.removeEventListener('keydown', onFirstUserGesture);
            };

            window.addEventListener('click', onFirstUserGesture, { once: true });
            window.addEventListener('touchstart', onFirstUserGesture, { once: true });
            window.addEventListener('keydown', onFirstUserGesture, { once: true });
        }

        // Mobile Touch Gestures (Swipe Left / Right)
        let touchStartX = 0;
        let touchEndX = 0;
        const carouselEl = document.getElementById('carousel');

        if (carouselEl) {
            carouselEl.addEventListener('touchstart', e => {
                touchStartX = e.changedTouches[0].screenX;
            }, { passive: true });

            carouselEl.addEventListener('touchend', e => {
                touchEndX = e.changedTouches[0].screenX;
                handleSwipe();
            }, { passive: true });
        }

        function handleSwipe() {
            const swipeThreshold = 50;
            if (touchEndX < touchStartX - swipeThreshold) {
                next(); // Geser ke kiri -> Slide selanjutnya
            } else if (touchEndX > touchStartX + swipeThreshold) {
                prev(); // Geser ke kanan -> Slide sebelumnya
            }
        }

        document.addEventListener('keydown', e => {
            if (e.key === 'ArrowRight') next();
            if (e.key === 'ArrowLeft') prev();
            if (e.key === ' ' || e.key === 'Spacebar') isManualPaused = !isManualPaused;
            if (e.key === 'f' || e.key === 'F') {
                toggleFullscreen();
            }
            if (e.key === 'Escape') {
                hideAttendanceOverlay();
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

        // ======================== ULTRA-FAST REALTIME POLLING (1.5 SECONDS) & PROCESSOR ========================
        let maxTvLogId = {{ $maxLogId ?? 0 }};
        const seenLogIds = new Set();
        let isFetchingLogs = false;

        function processNewAttendanceLog(log) {
            if (!log) return;
            if (log.id && seenLogIds.has(log.id)) return;
            if (log.id) {
                seenLogIds.add(log.id);
                if (log.id > maxTvLogId) maxTvLogId = log.id;
            }

            const student = log.student;
            const studentName = student ? student.name : ('Siswa ' + (log.device_user_id || ''));

            // Abaikan jika user adalah admin
            const lowerName = (studentName || '').toLowerCase();
            if (lowerName.includes('admin') || (student && student.privilege > 0)) {
                return;
            }

            const className = (student && student.school_class) 
                ? student.school_class.name 
                : ((student && student.schoolClass) ? student.schoolClass.name : '-');

            const timeStr = (log.timestamp && log.timestamp.includes(' ')) 
                ? log.timestamp.split(' ')[1].substring(0, 5) 
                : '07:00';

            const photoUrl = student?.photo_url || (student?.photo ? ('/storage/' + student.photo) : null);

            // JIKA ABSEN PULANG (status_code == 1), TAMPILKAN NOTIFIKASI POP-UP PULANG
            const isPulang = (log.status_code == 1 || log.status_code === '1');
            if (isPulang) {
                showAttendanceOverlay(studentName, timeStr, className, photoUrl, 'pulang');
                return;
            }

            // Perbarui dailyData: Cek apakah siswa sudah ada
            const existingIdx = dailyData.findIndex(d => (d.name || '').toLowerCase() === studentName.toLowerCase());
            let finalTime = timeStr;
            let finalTimestamp = log.timestamp || null;

            if (existingIdx !== -1) {
                const existing = dailyData[existingIdx];
                // Pertahankan waktu kedatangan paling awal jika siswa sudah tap sebelumnya
                if (existing.time && existing.time <= timeStr) {
                    finalTime = existing.time;
                    finalTimestamp = existing.timestamp || finalTimestamp;
                }
                dailyData.splice(existingIdx, 1);
            }

            // Tandai siswa yang baru absen dengan badge 'BARU'
            dailyData.forEach(d => d.is_just_attended = false);

            // Tambahkan data siswa
            dailyData.push({
                name: studentName,
                cls: className,
                time: finalTime,
                timestamp: finalTimestamp,
                ini: studentName.charAt(0).toUpperCase(),
                photo: photoUrl,
                is_ontime: true,
                is_just_attended: true
            });

            // Urutkan siswa: yang datang paling awal waktunya jadi peringkat pertama (#1)
            sortDailyData();

            // Batasi top 10
            if (dailyData.length > 10) {
                dailyData = dailyData.slice(0, 10);
            }

            try { localStorage.setItem('tv_daily_cache', JSON.stringify(dailyData)); } catch(e) {}

            // Render ulang tampilan Slide 2
            renderDaily();

            // Tampilkan popup modal & geser otomatis ke Slide 2
            showAttendanceOverlay(studentName, timeStr, className, photoUrl);
            updateLiveStats(timeStr);

            // Efek highlight panel Top 3
            const pnl = document.getElementById('daily-top3');
            if (pnl) {
                pnl.style.transition = 'transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1)';
                pnl.style.transform = 'scale(1.03)';
                setTimeout(() => {
                    pnl.style.transform = 'scale(1)';
                }, 600);
            }
        }
        window.processNewAttendanceLog = processNewAttendanceLog;

        async function pollRealtimeAttendance() {
            if (isFetchingLogs) return;
            isFetchingLogs = true;
            try {
                const res = await fetch(`/attendance/latest?since_id=${maxTvLogId}`);
                const data = await res.json();
                if (data.status === 'success' && data.logs && data.logs.length > 0) {
                    data.logs.forEach(log => {
                        processNewAttendanceLog(log);
                    });
                    if (data.max_id && data.max_id > maxTvLogId) {
                        maxTvLogId = data.max_id;
                    }
                }
            } catch(e) {
                // Silent retry on network hiccups
            } finally {
                isFetchingLogs = false;
            }
        }

        // Jalankan polling setiap 1.5 detik
        setInterval(pollRealtimeAttendance, 1500);
    </script>

    <!-- Realtime WebSockets Listener (Laravel Reverb / Echo) -->
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            if (typeof Echo !== 'undefined' && !window.Echo) {
                try {
                    window.Pusher = Pusher;
                    window.Echo = new Echo({
                        broadcaster: 'reverb',
                        key: '{{ env('VITE_REVERB_APP_KEY', 'app_key') }}',
                        wsHost: '{{ env('VITE_REVERB_HOST', '127.0.0.1') }}',
                        wsPort: {{ env('VITE_REVERB_PORT', 8080) }},
                        wssPort: {{ env('VITE_REVERB_PORT', 8080) }},
                        forceTLS: false,
                        enabledTransports: ['ws', 'wss'],
                    });
                } catch(e) {}
            }

            if (window.Echo) {
                window.Echo.channel('attendance')
                    .listen('.AttendanceCreated', (e) => {
                        if (typeof window.processNewAttendanceLog === 'function') {
                            window.processNewAttendanceLog(e.log);
                        }
                    });
            }
        });
    </script>
</body>
</html>