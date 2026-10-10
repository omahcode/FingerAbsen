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

        .att-countdown-fill {
            height: 100%;
            width: 100%;
            background: linear-gradient(90deg, #10b981, #38bdf8, #818cf8);
            border-radius: 0 3px 3px 0;
        }
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
                    <h2><i class="fas fa-bolt"></i> Top 10 Kehadiran Hari Ini</h2>
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

    <!-- Realtime Attendance Success Overlay Popup -->
    <div id="attendance-overlay">
        <div class="att-card">
            <button type="button" class="att-close-btn" id="att-close-btn" title="Tutup">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="att-header-badge">
                <i class="fas fa-check-circle"></i> Absensi Berhasil
            </div>

            <div class="att-icon-wrap">
                <i class="fas fa-fingerprint"></i>
            </div>

            <div class="att-greeting">
                Halo <span class="att-name-highlight" id="att-name">Siswa</span>,<br>
                Kamu sudah Absen pukul <span class="att-time-pill" id="att-time">--:-- WIB</span>
            </div>

            <div class="att-sub-info">
                <div class="att-tag"><i class="fas fa-graduation-cap text-sky-400"></i> <span id="att-class">Kelas</span></div>
                <div class="att-tag"><i class="fas fa-shield-alt text-emerald-400"></i> Terverifikasi Mesin</div>
            </div>

            <div class="att-quote-card">
                <div class="att-quote-text" id="att-quote">"Semangat belajar hari ini! Sukses dimulai dari langkah kecil dan disiplin setiap pagi."</div>
                <div class="att-quote-footer">
                    <i class="fas fa-sparkles"></i> Quote Motivasi Hari Ini
                </div>
            </div>

            <div class="att-countdown-bar">
                <div class="att-countdown-fill" id="att-countdown-fill"></div>
            </div>
        </div>
    </div>

    <!-- Floating Quick Actions (Dashboard, Fullscreen, Settings) -->
    <div class="tv-floating-tools">
        <button type="button" id="btn-test-popup" class="btn-tv-action" title="Uji Coba Tampilan Pop-up Absen">
            <i class="fas fa-bell"></i>
        </button>
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
        // ======================== REAL DATA FROM BACKEND ========================
        let dailyData = {!! json_encode($dailyData) !!};
        let weeklyData = {!! json_encode($weeklyData) !!};
        let monthlyData = {!! json_encode($monthlyData) !!};
        let hofData = {!! json_encode($hofData) !!};

        const slideInterval = {{ $tvSettings['tv_slide_interval'] * 1000 }};

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

        function goToDailySlide() {
            const slides = Array.from(document.querySelectorAll('.slide'));
            const dailyIdx = slides.findIndex(s => s.dataset.slideName === 'daily');
            if (dailyIdx !== -1) {
                goTo(dailyIdx);
            }
        }

        function showAttendanceOverlay(studentName, timeStr, className) {
            if (!overlayEl) return;

            // Abaikan jika user adalah admin
            const lowerName = (studentName || '').toLowerCase();
            if (lowerName.includes('admin')) {
                return;
            }

            // Isi konten
            if (attNameEl) attNameEl.textContent = studentName || 'Siswa';
            if (attTimeEl) attTimeEl.textContent = (timeStr || '07:00') + ' WIB';
            if (attClassEl) attClassEl.textContent = className || 'Umum';
            if (attQuoteEl) attQuoteEl.textContent = `"${getRandomQuote()}"`;

            // Langsung pindah ke Slide 2 (Kehadiran Hari Ini) saat ada yang absen
            goToDailySlide();

            // Reset countdown bar
            if (attCountdownFill) {
                attCountdownFill.style.transition = 'none';
                attCountdownFill.style.width = '100%';
            }

            // Tampilkan modal
            overlayEl.classList.add('active');
            playChime();

            // Jalankan countdown bar
            setTimeout(() => {
                if (attCountdownFill) {
                    attCountdownFill.style.transition = 'width 6s linear';
                    attCountdownFill.style.width = '0%';
                }
            }, 50);

            // Bersihkan timer lama jika ada
            clearTimeout(overlayTimer);

            // Auto dismiss setelah 6 detik
            overlayTimer = setTimeout(() => {
                hideAttendanceOverlay();
            }, 6000);
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

        // Test Pop-up Button (Simulasi tap absen & langsung beralih ke slide 2)
        document.getElementById('btn-test-popup')?.addEventListener('click', () => {
            const sampleNames = ['Mohamad Jibril', 'Achmad Dzaky', 'Kayla Aqila', 'Rafa Abrar', 'Sheren Angelica'];
            const sampleClasses = ['XI RPL 1', 'XI RPL 2', 'X PPLG 1', 'XII RPL 1'];
            const rName = sampleNames[Math.floor(Math.random() * sampleNames.length)];
            const rClass = sampleClasses[Math.floor(Math.random() * sampleClasses.length)];
            const n = new Date();
            const timeStr = String(n.getHours()).padStart(2,'0') + ':' + String(n.getMinutes()).padStart(2,'0');
            
            // Masukkan data test ke daily top 10
            dailyData.unshift({
                name: rName,
                cls: rClass,
                time: timeStr,
                ini: rName.charAt(0).toUpperCase(),
                is_ontime: true
            });
            if (dailyData.length > 10) dailyData.pop();
            renderDaily();

            showAttendanceOverlay(rName, timeStr, rClass);
            updateLiveStats(timeStr);
        });

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
            const dailyDateEl = document.getElementById('daily-active-date');
            
            if (dayEl) dayEl.textContent = days[n.getDay()];
            if (dateEl) dateEl.textContent = `${n.getDate()} ${months[n.getMonth()]} ${n.getFullYear()}`;
            if (dailyDateEl) dailyDateEl.textContent = `${days[n.getDay()]}, ${String(n.getDate()).padStart(2,'0')} ${months[n.getMonth()]} ${n.getFullYear()}`;
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
            if (e.key === ' ' || e.key === 'Spacebar') paused = !paused;
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

        // Background polling fallback setiap 10 detik agar data selalu sinkron
        let maxTvLogId = 0;
        fetch('/attendance/latest?since_id=0')
            .then(r => r.json())
            .then(data => {
                if (data && data.max_id) maxTvLogId = data.max_id;
            })
            .catch(() => {});

        setInterval(() => {
            if (maxTvLogId > 0) {
                fetch(`/attendance/latest?since_id=${maxTvLogId}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'success' && data.logs && data.logs.length > 0) {
                            data.logs.forEach(log => {
                                // Abaikan absen pulang (status_code == 1)
                                if (log.status_code == 1 || log.status_code === '1') {
                                    return;
                                }

                                const studentName = log.student ? log.student.name : ('Siswa ' . (log.device_user_id || ''));
                                
                                // Abaikan jika user adalah admin
                                const lowerName = (studentName || '').toLowerCase();
                                if (lowerName.includes('admin') || (log.student && log.student.privilege > 0)) {
                                    return;
                                }

                                const className = (log.student && log.student.school_class) ? log.student.school_class.name : '-';
                                const timeStr = (log.timestamp && log.timestamp.includes(' ')) ? log.timestamp.split(' ')[1].substring(0, 5) : '07:00';

                                dailyData.unshift({
                                    name: studentName,
                                    cls: className,
                                    time: timeStr,
                                    ini: studentName.charAt(0).toUpperCase(),
                                    is_ontime: true
                                });
                                if (dailyData.length > 10) dailyData.pop();
                                renderDaily();

                                showAttendanceOverlay(studentName, timeStr, className);
                                updateLiveStats(timeStr);
                            });
                            maxTvLogId = data.max_id;
                        }
                    })
                    .catch(() => {});
            }
        }, 10000);
    </script>

    <!-- Realtime WebSockets Listener (Laravel Reverb / Echo) -->
    <script type="module">
        document.addEventListener("DOMContentLoaded", () => {
            if (window.Echo) {
                window.Echo.channel('attendance')
                    .listen('.AttendanceCreated', (e) => {
                        const log = e.log;

                        // HANYA proses absen masuk, abaikan jika absen pulang (status_code == 1)
                        if (log.status_code == 1 || log.status_code === '1') {
                            return;
                        }

                        const studentName = log.student ? log.student.name : ('Siswa ' + (log.device_user_id || ''));
                        
                        // Abaikan jika user adalah admin
                        const lowerName = (studentName || '').toLowerCase();
                        if (lowerName.includes('admin') || (log.student && log.student.privilege > 0)) {
                            return;
                        }

                        const className = (log.student && log.student.school_class) ? log.student.school_class.name : ((log.student && log.student.schoolClass) ? log.student.schoolClass.name : '-');
                        const timeStr = (log.timestamp && log.timestamp.includes(' ')) ? log.timestamp.split(' ')[1].substring(0, 5) : '07:00';

                        // Masukkan data baru ke paling atas daftar Top 10 Hari Ini
                        dailyData.unshift({
                            name: studentName,
                            cls: className,
                            time: timeStr,
                            ini: studentName.charAt(0).toUpperCase(),
                            is_ontime: true
                        });

                        if (dailyData.length > 10) dailyData.pop();

                        renderDaily();

                        // Tampilkan modal overlay absensi & langsung alihkan slide ke slide 2 (Kehadiran Hari Ini)
                        if (typeof showAttendanceOverlay === 'function') {
                            showAttendanceOverlay(studentName, timeStr, className);
                        }

                        // Update live summary stats di Slide 1
                        if (typeof updateLiveStats === 'function') {
                            updateLiveStats(timeStr);
                        }

                        // Update maxTvLogId agar polling tidak menduplikasi
                        if (log.id && typeof maxTvLogId !== 'undefined' && log.id > maxTvLogId) {
                            maxTvLogId = log.id;
                        }

                        // Efek highlight panel
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