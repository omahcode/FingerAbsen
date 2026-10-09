<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RPL Attendance Board</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600;700&family=Plus+Jakarta+Sans:wght@800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --text: #0f172a;
            --text-muted: #64748b;
            --blue: #3b82f6;
            --green: #10b981;
            --purple: #8b5cf6;
            --gold: #f59e0b;
            --red: #ef4444;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; height: 100vh; width: 100vw; overflow: hidden; color: var(--text); background: #f0f4f8; }

        /* BG */
        .bg-layer { position: fixed; inset: 0; z-index: 0; background: linear-gradient(-45deg, #e0e7ff, #f0fdf4, #faf5ff, #fefce8, #fce7f3, #e0f2fe); background-size: 600% 600%; animation: bgShift 20s ease infinite; }
        @keyframes bgShift { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        .orb { position: fixed; border-radius: 50%; filter: blur(80px); opacity: 0.4; z-index: 0; animation: orbFloat 12s ease-in-out infinite; }
        .orb-1 { width: 400px; height: 400px; background: #818cf8; top: -100px; left: -100px; }
        .orb-2 { width: 350px; height: 350px; background: #34d399; bottom: -80px; right: -80px; animation-delay: -4s; }
        .orb-3 { width: 300px; height: 300px; background: #fbbf24; top: 50%; left: 50%; transform: translate(-50%,-50%); animation-delay: -8s; }
        @keyframes orbFloat { 0%, 100% { transform: translate(0,0) scale(1); } 33% { transform: translate(30px,-40px) scale(1.1); } 66% { transform: translate(-20px,20px) scale(0.95); } }
        .particles { position: fixed; inset: 0; z-index: 1; pointer-events: none; }
        .particle { position: absolute; border-radius: 50%; animation: floatP linear infinite; }
        @keyframes floatP { 0% { transform: translateY(100vh) scale(0); opacity: 0; } 10% { opacity: 0.8; } 90% { opacity: 0.3; } 100% { transform: translateY(-100px) scale(1.5); opacity: 0; } }

        /* CAROUSEL */
        .carousel { position: relative; width: 100%; height: 100%; z-index: 2; display: flex; flex-direction: column; }
        .progress-wrap { width: 100%; height: 5px; background: rgba(0,0,0,0.04); overflow: hidden; }
        .progress-fill { height: 100%; width: 0%; transition: width 1s linear; border-radius: 0 3px 3px 0; background: linear-gradient(90deg, var(--blue), var(--purple), var(--gold)); background-size: 200% 100%; animation: progGrad 3s ease infinite; }
        @keyframes progGrad { 0% { background-position: 0% 0; } 100% { background-position: 200% 0; } }
        .slides { flex: 1; position: relative; padding: 1rem 2rem 2.5rem; }
        .slide { position: absolute; inset: 1rem 2rem 2.5rem; opacity: 0; transform: translateX(60px) scale(0.97); transition: all 0.9s cubic-bezier(0.23,1,0.32,1); pointer-events: none; display: flex; flex-direction: column; }
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

        /* S1 WELCOME */
        .s1-wrap { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; background: rgba(255,255,255,0.55); backdrop-filter: blur(20px); border-radius: 32px; border: 1px solid rgba(255,255,255,0.7); box-shadow: 0 20px 60px rgba(0,0,0,0.04); }
        .s1-icon { font-size: 7rem; margin-bottom: 1rem; background: linear-gradient(135deg, var(--blue), var(--purple)); -webkit-background-clip: text; color: transparent; filter: drop-shadow(0 0 30px rgba(99,102,241,0.4)); animation: iconPulse 3s ease-in-out infinite; }
        @keyframes iconPulse { 0%,100% { transform: scale(1); } 50% { transform: scale(1.08); } }
        .s1-title { font-family: 'Poppins'; font-weight: 900; font-size: 4.5rem; background: linear-gradient(135deg, var(--blue), #6366f1, var(--purple)); -webkit-background-clip: text; color: transparent; line-height: 1.1; min-height: 110px; position: relative; overflow: hidden; }
        .s1-title::after { content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent); animation: shimmerMove 3s ease-in-out infinite; }
        @keyframes shimmerMove { 0% { left: -100%; } 100% { left: 200%; } }
        .s1-sub { font-size: 1.4rem; color: var(--text-muted); font-weight: 500; margin-top: 0.8rem; max-width: 600px; }
        .s1-clock-wrap { margin-top: 2.5rem; display: flex; align-items: center; gap: 2rem; background: white; padding: 1.2rem 3rem; border-radius: 100px; box-shadow: 0 8px 30px rgba(0,0,0,0.06); }
        .s1-time { font-family: 'JetBrains Mono'; font-size: 4rem; font-weight: 700; letter-spacing: 3px; line-height: 1; color: var(--text); }
        .s1-time .blink { animation: blink 1s step-end infinite; }
        @keyframes blink { 0%,100% { opacity: 1; } 50% { opacity: 0.3; } }
        .s1-date-box { text-align: left; border-left: 2px solid #e2e8f0; padding-left: 2rem; }
        .s1-day { font-family: 'Poppins'; font-weight: 700; font-size: 1.3rem; color: var(--blue); }
        .s1-date { font-size: 1rem; color: var(--text-muted); font-weight: 500; }

        /* S2 DAILY */
        .s2-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; flex: 1; }
        .s2-top3 { display: flex; flex-direction: column; gap: 0.7rem; justify-content: center; }
        .s2-card { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.2rem; border-radius: 16px; background: white; position: relative; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.04); transition: transform 0.3s; }
        .s2-card:hover { transform: translateY(-3px); }
        .s2-card .accent { position: absolute; left: 0; top: 0; width: 5px; height: 100%; }
        .s2-card.r1 .accent { background: linear-gradient(to bottom, #fbbf24, #f59e0b); }
        .s2-card.r2 .accent { background: linear-gradient(to bottom, #94a3b8, #64748b); }
        .s2-card.r3 .accent { background: linear-gradient(to bottom, #d97706, #b45309); }
        .s2-medal { font-size: 2rem; width: 50px; text-align: center; }
        .s2-card.r1 .s2-medal { color: #fbbf24; filter: drop-shadow(0 0 8px rgba(251,191,36,0.5)); animation: medalGlow 2s ease infinite; }
        .s2-card.r2 .s2-medal { color: #94a3b8; } .s2-card.r3 .s2-medal { color: #d97706; }
        @keyframes medalGlow { 0%,100% { filter: drop-shadow(0 0 8px rgba(251,191,36,0.5)); } 50% { filter: drop-shadow(0 0 18px rgba(251,191,36,0.8)); } }
        .s2-info { flex: 1; }
        .s2-name { font-family: 'Poppins'; font-weight: 700; font-size: 1.1rem; }
        .s2-class { color: var(--text-muted); font-size: 0.8rem; font-weight: 500; }
        .s2-time { background: linear-gradient(135deg, #eff6ff, #e0e7ff); color: var(--blue); padding: 0.3rem 0.7rem; border-radius: 50px; font-weight: 700; font-size: 0.9rem; font-family: 'JetBrains Mono'; display: flex; align-items: center; gap: 5px; }
        .s2-table-wrap { background: white; border-radius: 16px; padding: 0.8rem 1.2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.04); }
        .s2-table-wrap table { width: 100%; border-collapse: collapse; }
        .s2-table-wrap th { font-weight: 600; color: var(--text-muted); text-transform: uppercase; font-size: 0.7rem; letter-spacing: 1px; padding: 0.6rem; text-align: left; border-bottom: 2px solid #f1f5f9; }
        .s2-table-wrap td { padding: 0.5rem 0.6rem; border-bottom: 1px solid #f8fafc; font-size: 0.85rem; }
        .tbl-rank { font-weight: 800; color: var(--blue); font-family: 'JetBrains Mono'; }
        .tbl-name { font-weight: 600; }
        .tbl-time-pill { background: #f1f5f9; padding: 2px 8px; border-radius: 20px; font-weight: 600; font-size: 0.8rem; font-family: 'JetBrains Mono'; color: #475569; }

        /* ============ GAMIFIED BOARD ============ */
        .board { display: flex; flex-direction: column; flex: 1; gap: 0.4rem; }

        /* PODIUM */
        .podium { display: flex; justify-content: center; align-items: flex-end; gap: 1rem; padding: 0.5rem 0; position: relative; }
        .podium-canvas { position: absolute; inset: 0; z-index: 3; pointer-events: none; }
        .pod-card { border-radius: 20px; text-align: center; position: relative; display: flex; flex-direction: column; align-items: center; transition: transform 0.4s; cursor: default; overflow: visible; padding: 0.8rem; padding-top: 1rem; }
        .pod-card:hover { transform: translateY(-4px); }

        .pod-1 { width: 220px; min-height: 310px; z-index: 4; background: linear-gradient(160deg, #f0fdf4, #ecfccb); border: 2px solid #86efac; box-shadow: 0 10px 40px rgba(34,197,94,0.12); }
        .pod-1::before { content: ''; position: absolute; inset: -2px; border-radius: 22px; z-index: -1; background: linear-gradient(135deg, #22c55e, #a3e635, #22c55e); background-size: 200% 200%; animation: borderGlow 3s ease infinite; opacity: 0.5; }
        @keyframes borderGlow { 0%,100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
        .pod-2 { width: 195px; min-height: 270px; background: linear-gradient(160deg, #eff6ff, #dbeafe); border: 2px solid #93c5fd; box-shadow: 0 10px 40px rgba(59,130,246,0.08); }
        .pod-3 { width: 195px; min-height: 255px; background: linear-gradient(160deg, #fffbeb, #fef3c7); border: 2px solid #fcd34d; box-shadow: 0 10px 40px rgba(245,158,11,0.08); }

        .pod-crown { position: absolute; top: -28px; left: 50%; transform: translateX(-50%); font-size: 2.4rem; animation: crownBounce 2s ease infinite; z-index: 10; }
        @keyframes crownBounce { 0%,100% { transform: translateX(-50%) translateY(0); } 50% { transform: translateX(-50%) translateY(-5px); } }
        .pod-rank-num { position: absolute; top: 6px; right: 10px; font-weight: 900; font-size: 1.2rem; opacity: 0.15; font-family: 'Poppins'; z-index: 10; }

        /* Avatar stays on top */
        .pod-avatar { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.8rem; font-family: 'Poppins'; box-shadow: 0 4px 12px rgba(0,0,0,0.08); position: relative; z-index: 6; }
        .pod-1 .pod-avatar { width: 70px; height: 70px; font-size: 2.2rem; background: white; color: #16a34a; border: 3px solid #86efac; }
        .pod-2 .pod-avatar { background: white; color: var(--blue); border: 2px solid #93c5fd; }
        .pod-3 .pod-avatar { background: white; color: #d97706; border: 2px solid #fcd34d; }

        /* Flame container below avatar */
        .pod-flame-wrap { position: relative; width: 100%; height: 80px; display: flex; justify-content: center; align-items: flex-end; z-index: 5; margin-top: -20px; }
        .pod-1 .pod-flame-wrap { height: 90px; }
        .pod-flame-wrap .flame-mount { transform-origin: center bottom; }

        /* Flame glow behind */
        .flame-glow { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); border-radius: 50%; filter: blur(20px); opacity: 0; z-index: 1; pointer-events: none; }
        .flame-glow.active { animation: flameGlowIn 0.8s ease forwards; }
        @keyframes flameGlowIn { 0% { transform: translate(-50%,-50%) scale(0.3); opacity: 0; } 40% { opacity: 0.7; } 100% { transform: translate(-50%,-50%) scale(1); opacity: 0.5; } }

        .pod-name { font-family: 'Poppins'; font-weight: 700; font-size: 1rem; margin-top: 15px; z-index: 6; position: relative; }
        .pod-class { font-size: 0.75rem; color: var(--text-muted); font-weight: 500; z-index: 6; position: relative; margin-bottom: auto; }
        
        .pod-streak-display { font-weight: 900; z-index: 6; position: relative; padding-top: 10px; margin-bottom: 5px; }
        .pod-1 .pod-streak-display { font-size: 1.6rem; color: #16a34a; }
        .pod-2 .pod-streak-display { font-size: 1.4rem; color: var(--blue); }
        .pod-3 .pod-streak-display { font-size: 1.4rem; color: #d97706; }
        .pod-streak-display small { font-weight: 700; font-size: 0.65em; opacity: 0.8; }
        .pod-streak-none { font-size: 0.8rem; font-weight: 600; color: #94a3b8; margin-top: auto; z-index: 6; position: relative; padding-bottom: 10px; }

        /* DIVIDER */
        .board-divider { height: 1px; background: linear-gradient(90deg, transparent, rgba(0,0,0,0.08), transparent); margin: 0.2rem 0; }
        .board-section-title { font-family: 'Poppins'; font-weight: 700; font-size: 0.9rem; color: #475569; display: flex; align-items: center; gap: 8px; padding-left: 0.5rem; }
        .board-section-title .line { width: 35px; height: 3px; border-radius: 2px; }

        /* LIST ITEMS */
        .board-list { flex: 1; display: flex; flex-direction: column; gap: 0.35rem; overflow: hidden; }
        .board-item { display: flex; align-items: center; gap: 0.8rem; background: white; border-radius: 14px; padding: 0.5rem 1rem; border: 1px solid #f1f5f9; box-shadow: 0 2px 8px rgba(0,0,0,0.02); transition: all 0.25s; }
        .board-item:hover { transform: translateX(4px); box-shadow: 0 4px 16px rgba(0,0,0,0.05); }
        .bi-rank { font-weight: 900; color: #d1d5db; width: 30px; font-size: 1rem; font-family: 'JetBrains Mono'; }
        .bi-avatar { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1rem; font-family: 'Poppins'; }
        .bi-info { flex: 0 0 240px; }
        .bi-name { font-family: 'Poppins'; font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; gap: 6px; }
        .bi-class-badge { font-size: 0.65rem; background: #f1f5f9; padding: 1px 6px; border-radius: 4px; font-weight: 600; color: #64748b; }
        .bi-meta { font-size: 0.75rem; color: var(--text-muted); font-weight: 500; margin-top: 1px; display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
        .bi-meta .tap-time { font-family: 'JetBrains Mono'; font-size: 0.7rem; }

        /* Small flame in list */
        .bi-flame-wrap { width: 40px; height: 48px; flex-shrink: 0; display: flex; align-items: flex-end; justify-content: center; }
        .bi-flame-wrap .flame-mount { transform-origin: center bottom; }
        .bi-flame-wrap svg { filter: none !important; }

        .bi-progress { flex: 1; padding: 0 1rem; }
        .bi-bar-bg { width: 100%; height: 6px; background: #f1f5f9; border-radius: 10px; overflow: hidden; }
        .bi-bar-fill { height: 100%; border-radius: 10px; transition: width 1.5s cubic-bezier(0.4,0,0.2,1); width: 0%; }
        .bi-pts { text-align: right; min-width: 70px; }
        .bi-pts strong { font-size: 1.4rem; font-weight: 900; line-height: 1; }
        .bi-pts span { font-size: 0.7rem; color: #94a3b8; font-weight: 600; margin-left: 2px; }

        /* NAV */
        .nav-dots { position: absolute; bottom: 0.8rem; left: 50%; transform: translateX(-50%); display: flex; gap: 6px; z-index: 20; background: rgba(255,255,255,0.7); backdrop-filter: blur(10px); padding: 5px 14px; border-radius: 30px; border: 1px solid rgba(255,255,255,0.6); box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .dot { width: 10px; height: 10px; border-radius: 50%; background: #cbd5e1; cursor: pointer; transition: all 0.4s; }
        .dot.active { width: 28px; border-radius: 20px; }
        .dot[data-index="0"].active { background: linear-gradient(90deg, var(--blue), var(--purple)); }
        .dot[data-index="1"].active { background: var(--blue); }
        .dot[data-index="2"].active { background: var(--green); }
        .dot[data-index="3"].active { background: var(--purple); }
        .dot[data-index="4"].active { background: var(--gold); }
        .nav-arrow { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.85); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.5); width: 42px; height: 42px; border-radius: 50%; font-size: 0.9rem; cursor: pointer; z-index: 20; box-shadow: 0 4px 15px rgba(0,0,0,0.06); color: #475569; opacity: 0; transition: all 0.3s; display: flex; align-items: center; justify-content: center; }
        .carousel:hover .nav-arrow { opacity: 1; }
        .nav-arrow:hover { background: white; color: var(--blue); transform: translateY(-50%) scale(1.08); }
        #arrow-prev { left: 0.5rem; } #arrow-next { right: 0.5rem; }

        .fade-up { opacity: 0; transform: translateY(15px); transition: all 0.5s ease; }
        .slide.active .fade-up { opacity: 1; transform: translateY(0); }
        .d1 { transition-delay: 0.08s; } .d2 { transition-delay: 0.15s; } .d3 { transition-delay: 0.22s; }

        /* ============================================================
           FLAME ANIMATION KEYFRAMES (ALL 5 STAGES)
           ============================================================ */

        /* STAGE 1 (RED) — Smooth organic sway */
        .flame-s1 svg { filter: drop-shadow(0 8px 24px rgba(255,60,0,0.6)) drop-shadow(0 2px 8px rgba(255,170,0,0.8)); transform-origin: 50% 90%; animation: s1Sway 2.8s ease-in-out infinite alternate; }
        @keyframes s1Sway { 0% { transform: rotate(-1.5deg) scaleX(0.98) skewX(-1deg); } 50% { transform: rotate(2deg) scaleX(1.02) skewX(1.2deg); } 100% { transform: rotate(-1deg) scaleX(1.01) skewX(-0.8deg); } }
        .flame-s1 .fl-outer { animation: s1FlkOut 1.4s ease-in-out infinite alternate; transform-origin: 50% 85%; }
        .flame-s1 .fl-mid { animation: s1FlkMid 1.1s ease-in-out infinite alternate-reverse; transform-origin: 50% 88%; }
        .flame-s1 .fl-core { animation: s1PulseCore 0.85s ease-in-out infinite alternate; transform-origin: 50% 90%; }
        @keyframes s1FlkOut { 0% { transform: scale(1) translateY(0); } 100% { transform: scale(1.025,0.98) translateY(-2px); } }
        @keyframes s1FlkMid { 0% { transform: scale(0.98,1.02) skewX(-1.5deg); } 100% { transform: scale(1.02,0.97) skewX(2deg) translateY(-3px); } }
        @keyframes s1PulseCore { 0% { transform: scale(0.96) translateY(1px); opacity: 0.92; } 100% { transform: scale(1.06) translateY(-2px); opacity: 1; } }
        .flame-s1 .fl-lick1 { animation: s1Lick1 1.7s cubic-bezier(0.4,0,0.6,1) infinite; transform-origin: center bottom; }
        .flame-s1 .fl-lick2 { animation: s1Lick2 2.1s cubic-bezier(0.4,0,0.6,1) 0.6s infinite; transform-origin: center bottom; }
        .flame-s1 .fl-lick3 { animation: s1Lick3 1.9s cubic-bezier(0.4,0,0.6,1) 1.1s infinite; transform-origin: center bottom; }
        @keyframes s1Lick1 { 0% { transform: translate(0,0) scale(0.85); opacity: 0; } 25% { opacity: 0.95; } 75% { transform: translate(6px,-24px) scale(0.55); opacity: 0.6; } 100% { transform: translate(12px,-42px) scale(0.2); opacity: 0; } }
        @keyframes s1Lick2 { 0% { transform: translate(0,0) scale(0.9); opacity: 0; } 30% { opacity: 1; } 70% { transform: translate(-7px,-28px) scale(0.5); opacity: 0.5; } 100% { transform: translate(-14px,-48px) scale(0.15); opacity: 0; } }
        @keyframes s1Lick3 { 0% { transform: translate(0,0) scale(0.7); opacity: 0; } 35% { opacity: 0.9; } 80% { transform: translate(4px,-32px) scale(0.4); opacity: 0.4; } 100% { transform: translate(8px,-52px) scale(0.1); opacity: 0; } }

        /* STAGE 2 (ORANGE) — Same structure, softer */
        .flame-s2 svg { filter: drop-shadow(0 8px 24px rgba(255,120,0,0.55)) drop-shadow(0 2px 8px rgba(255,175,0,0.75)); transform-origin: 50% 90%; animation: s2Sway 3s ease-in-out infinite alternate; }
        @keyframes s2Sway { 0% { transform: rotate(-1.5deg) scaleX(0.99) skewX(-1deg); } 50% { transform: rotate(1.8deg) scaleX(1.01) skewX(1deg); } 100% { transform: rotate(-1deg) scaleX(1) skewX(-0.8deg); } }
        .flame-s2 .fl-outer { animation: s1FlkOut 1.4s ease-in-out infinite alternate; transform-origin: 50% 85%; }
        .flame-s2 .fl-mid { animation: s1FlkMid 1.1s ease-in-out infinite alternate-reverse; transform-origin: 50% 88%; }
        .flame-s2 .fl-core { animation: s1PulseCore 0.85s ease-in-out infinite alternate; transform-origin: 50% 90%; }
        .flame-s2 .fl-lick1 { animation: s1Lick1 1.8s cubic-bezier(0.4,0,0.6,1) infinite; transform-origin: center bottom; }
        .flame-s2 .fl-lick2 { animation: s1Lick2 2.2s cubic-bezier(0.4,0,0.6,1) 0.6s infinite; transform-origin: center bottom; }
        .flame-s2 .fl-lick3 { animation: s1Lick3 1.9s cubic-bezier(0.4,0,0.6,1) 1.2s infinite; transform-origin: center bottom; }

        /* STAGE 3 (GOLD) — Faster flicker, more energy */
        .flame-s3 svg { filter: drop-shadow(0 6px 20px rgba(255,215,0,0.75)) drop-shadow(0 12px 36px rgba(255,193,7,0.5)) drop-shadow(0 0 10px rgba(255,255,255,0.8)); transform-origin: 50% 90%; animation: s3Sway 2.2s ease-in-out infinite alternate; }
        @keyframes s3Sway { 0% { transform: rotate(-2.2deg) scale(0.98,1.01) skewX(-1.5deg); } 50% { transform: rotate(2.4deg) scale(1.02,0.98) skewX(1.8deg); } 100% { transform: rotate(-1.5deg) scale(0.99,1.01) skewX(-1deg); } }
        .flame-s3 .fl-outer { animation: s3FlkOut 0.95s ease-in-out infinite alternate; transform-origin: 50% 85%; }
        .flame-s3 .fl-mid { animation: s3FlkMid 0.75s ease-in-out infinite alternate-reverse; transform-origin: 50% 88%; }
        .flame-s3 .fl-core { animation: s3PulseCore 0.55s ease-in-out infinite alternate; transform-origin: 50% 90%; }
        @keyframes s3FlkOut { 0% { transform: scale(1) translateY(0); } 100% { transform: scale(1.025,0.975) translateY(-3px); } }
        @keyframes s3FlkMid { 0% { transform: scale(0.975,1.02) skewX(-1.8deg); } 100% { transform: scale(1.025,0.97) skewX(2.2deg) translateY(-3.5px); } }
        @keyframes s3PulseCore { 0% { transform: scale(0.94) translateY(1.5px); opacity: 0.92; } 100% { transform: scale(1.08) translateY(-2.5px); opacity: 1; } }
        .flame-s3 .fl-lick1 { animation: s1Lick1 1.6s cubic-bezier(0.4,0,0.6,1) infinite; transform-origin: center bottom; }
        .flame-s3 .fl-lick2 { animation: s1Lick2 1.9s cubic-bezier(0.4,0,0.6,1) 0.5s infinite; transform-origin: center bottom; }
        .flame-s3 .fl-lick3 { animation: s1Lick3 1.7s cubic-bezier(0.4,0,0.6,1) 1s infinite; transform-origin: center bottom; }

        /* STAGE 4 (BLUE) — Extremely aggressive */
        .flame-s4 svg { filter: drop-shadow(0 0 20px rgba(0,180,255,0.85)) drop-shadow(0 0 45px rgba(0,100,255,0.55)); transform-origin: 50% 90%; animation: s4Rage 0.35s infinite alternate ease-in-out; }
        @keyframes s4Rage { 0% { transform: scale(1,1) rotate(0deg) skewX(0deg); } 25% { transform: scale(1.05,0.96) rotate(-2deg) skewX(-2deg); } 50% { transform: scale(0.97,1.06) rotate(1.5deg) skewX(2deg); } 75% { transform: scale(1.04,0.98) rotate(-1deg) skewX(-1.5deg); } 100% { transform: scale(1.01,1.03) rotate(2deg) skewX(1deg); } }
        .flame-s4 .fl-outer { animation: s4LickOut 0.25s infinite alternate ease-in-out; transform-origin: 50% 90%; }
        .flame-s4 .fl-mid { animation: s4LickMid 0.2s infinite alternate-reverse ease-in-out; transform-origin: 50% 90%; }
        .flame-s4 .fl-inner { animation: s4InnerP 0.15s infinite alternate ease-in-out; transform-origin: 50% 90%; }
        .flame-s4 .fl-core { animation: s4CoreHF 0.12s infinite alternate ease-in-out; transform-origin: 50% 90%; }
        @keyframes s4LickOut { 0% { transform: scale(1,1) skewX(0deg); } 100% { transform: scale(1.04,1.1) skewX(-3deg); } }
        @keyframes s4LickMid { 0% { transform: scale(1,1) skewX(2deg); } 100% { transform: scale(0.96,1.14) skewX(-3.5deg); } }
        @keyframes s4InnerP { 0% { transform: scale(1,1); opacity: 0.85; } 100% { transform: scale(1.12,1.22); opacity: 1; } }
        @keyframes s4CoreHF { 0% { transform: scale(0.92); opacity: 0.9; } 100% { transform: scale(1.15); opacity: 1; } }
        .flame-s4 .fl-wild-l { animation: s4WildL 0.4s infinite ease-out; transform-origin: 25% 60%; }
        .flame-s4 .fl-wild-r { animation: s4WildR 0.35s infinite ease-out 0.1s; transform-origin: 75% 60%; }
        @keyframes s4WildL { 0% { transform: translateY(0) scale(0.8) rotate(0deg); opacity: 0.9; } 100% { transform: translateY(-45px) scale(0.1) rotate(-15deg); opacity: 0; } }
        @keyframes s4WildR { 0% { transform: translateY(0) scale(0.8) rotate(0deg); opacity: 0.9; } 100% { transform: translateY(-50px) scale(0.1) rotate(18deg); opacity: 0; } }

        /* STAGE 5 (PURPLE) — Insane */
        .flame-s5 svg { filter: drop-shadow(0 0 25px rgba(185,30,255,0.95)) drop-shadow(0 0 60px rgba(110,0,255,0.7)); transform-origin: 50% 90%; animation: s5Rage 0.28s infinite alternate ease-in-out; }
        @keyframes s5Rage { 0% { transform: scale(1,1) rotate(0deg) skewX(0deg); } 20% { transform: scale(1.08,0.94) rotate(-3deg) skewX(-2.5deg); } 45% { transform: scale(0.95,1.09) rotate(2deg) skewX(2.5deg); } 70% { transform: scale(1.06,0.96) rotate(-2deg) skewX(-2deg); } 100% { transform: scale(1.02,1.05) rotate(2.5deg) skewX(1.5deg); } }
        .flame-s5 .fl-outer { animation: s5LickOut 0.22s infinite alternate ease-in-out; transform-origin: 50% 90%; }
        .flame-s5 .fl-mid { animation: s5LickMid 0.18s infinite alternate-reverse ease-in-out; transform-origin: 50% 90%; }
        .flame-s5 .fl-inner { animation: s5InnerP 0.14s infinite alternate ease-in-out; transform-origin: 50% 90%; }
        .flame-s5 .fl-core { animation: s5CoreUP 0.09s infinite alternate ease-in-out; transform-origin: 50% 90%; }
        @keyframes s5LickOut { 0% { transform: scale(1,1) skewX(0deg); } 100% { transform: scale(1.06,1.15) skewX(-4deg); } }
        @keyframes s5LickMid { 0% { transform: scale(1,1) skewX(2.5deg); } 100% { transform: scale(0.94,1.18) skewX(-4.5deg); } }
        @keyframes s5InnerP { 0% { transform: scale(1,1); opacity: 0.85; } 100% { transform: scale(1.18,1.28); opacity: 1; } }
        @keyframes s5CoreUP { 0% { transform: scale(0.88); opacity: 0.9; } 100% { transform: scale(1.25); opacity: 1; } }
        .flame-s5 .fl-wild-l { animation: s5WildL 0.32s infinite ease-out; transform-origin: 20% 65%; }
        .flame-s5 .fl-wild-r { animation: s5WildR 0.28s infinite ease-out 0.08s; transform-origin: 80% 65%; }
        @keyframes s5WildL { 0% { transform: translateY(0) scale(0.8) rotate(0); opacity: 0.95; } 100% { transform: translateY(-55px) scale(0.1) rotate(-20deg); opacity: 0; } }
        @keyframes s5WildR { 0% { transform: translateY(0) scale(0.8) rotate(0); opacity: 0.95; } 100% { transform: translateY(-60px) scale(0.1) rotate(22deg); opacity: 0; } }

        /* Flame entrance pop */
        .flame-entrance { animation: flameEntPop 0.8s cubic-bezier(0.2,0.85,0.25,1) forwards; }
        @keyframes flameEntPop { 0% { transform: scale(0,0) translateY(30px); opacity: 0; } 38% { transform: scale(0.72,1.48) translateY(-20px); opacity: 1; } 62% { transform: scale(1.34,0.72) translateY(6px); } 82% { transform: scale(0.92,1.1) translateY(-4px); } 100% { transform: scale(1,1) translateY(0); opacity: 1; } }

        /* Streak text badge (for no-flame) */
        .streak-none { font-size: 0.75rem; color: #94a3b8; font-weight: 500; }
    /* MODERN SOFT PLAYFUL THEME OVERRIDES */
        @import url('https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap');
        
        :root {
            --blue: #7dd3fc;
            --green: #86efac;
            --purple: #d8b4fe;
            --gold: #fde047;
            --text: #334155;
        }
        
        body { 
            font-family: 'Nunito', 'Poppins', sans-serif; 
            background: #f8fafc;
            color: var(--text);
            cursor: auto;
        }
        
        a, button, .s2-card, .board-item, .pod-card, .dot {
            cursor: pointer !important;
        }

        .bg-layer { background: radial-gradient(circle at top left, #fdf4ff 0%, #f0fdf4 50%, #eff6ff 100%); background-size: 200% 200%; }
        
        .orb-1 { background: #e879f9; border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%; opacity: 0.15; filter: blur(60px); }
        .orb-2 { background: #38bdf8; border-radius: 50% 50% 30% 70% / 50% 30% 70% 50%; opacity: 0.15; filter: blur(60px); }
        .orb-3 { background: #4ade80; border-radius: 60% 40% 50% 50% / 30% 70% 30% 70%; opacity: 0.15; filter: blur(60px); }
        
        .s1-wrap { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(20px); border: 2px solid rgba(255,255,255,0.8); box-shadow: 0 20px 40px rgba(148, 163, 184, 0.15); border-radius: 40px; transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .s1-wrap:hover { transform: scale(1.02); }
        
        .s1-clock-wrap { border: none; box-shadow: 0 10px 30px rgba(148, 163, 184, 0.15); background: white; border-radius: 30px; }
        .s1-icon { filter: drop-shadow(0 10px 15px rgba(125, 211, 252, 0.6)); background: linear-gradient(135deg, #38bdf8, #818cf8); -webkit-text-fill-color: transparent; }
        .s1-title { -webkit-text-fill-color: var(--text); -webkit-text-stroke: 0; text-shadow: none; background: none; }
        
        .s2-card { border: none; box-shadow: 0 10px 25px rgba(148, 163, 184, 0.1); border-radius: 24px; margin-bottom: 0.6rem; transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); background: white; }
        .s2-card:hover { transform: translateY(-5px) scale(1.03); box-shadow: 0 15px 35px rgba(148, 163, 184, 0.2); }
        .s2-card .accent { width: 8px; border: none; background: linear-gradient(to bottom, #7dd3fc, #38bdf8) !important; border-radius: 24px 0 0 24px; }
        .s2-time { border: none; color: #0284c7; background: #e0f2fe; box-shadow: none; border-radius: 12px; }
        
        .s2-table-wrap { border: none; box-shadow: 0 15px 35px rgba(148, 163, 184, 0.1); border-radius: 32px; background: rgba(255,255,255,0.8); backdrop-filter: blur(10px); }
        .s2-table-wrap th { border-bottom: 2px solid #f1f5f9; color: var(--text-muted); font-weight: 700; }
        .s2-table-wrap td { border-bottom: 1px solid #f8fafc; color: var(--text); }
        
        .pod-card { border: none !important; box-shadow: 0 15px 35px rgba(148, 163, 184, 0.15) !important; transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); border-radius: 32px !important; }
        .pod-card:hover { transform: translateY(-10px) scale(1.03); box-shadow: 0 20px 40px rgba(148, 163, 184, 0.25) !important; }
        .pod-1 { background: linear-gradient(145deg, #dcfce7, #bbf7d0) !important; }
        .pod-2 { background: linear-gradient(145deg, #e0f2fe, #bae6fd) !important; }
        .pod-3 { background: linear-gradient(145deg, #f3e8ff, #e9d5ff) !important; color: var(--text); }
        .pod-3 .pod-name, .pod-3 .pod-class, .pod-3 .pod-streak-none { color: var(--text) !important; }
        
        .pod-avatar { border: 4px solid white !important; box-shadow: 0 8px 20px rgba(148, 163, 184, 0.2) !important; background: white !important; color: var(--text) !important; }
        
        .board-item { border: 2px solid transparent; box-shadow: 0 8px 20px rgba(148, 163, 184, 0.08); border-radius: 20px; margin-bottom: 0.5rem; transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); background: white; }
        .board-item:hover { transform: translateX(5px) scale(1.02); box-shadow: 0 12px 25px rgba(148, 163, 184, 0.15); border-color: rgba(255,255,255,0.5); }
        
        .nav-dots { border: none; box-shadow: 0 10px 25px rgba(148, 163, 184, 0.15); background: rgba(255,255,255,0.8); backdrop-filter: blur(10px); padding: 8px 16px; border-radius: 30px; }
        .dot { border: none; background: #cbd5e1; }
        .dot.active { background: #38bdf8 !important; border: none; }
        
        .nav-arrow { border: none; box-shadow: 0 10px 25px rgba(148, 163, 184, 0.15); color: #64748b; background: white; opacity: 1; }
        .nav-arrow:hover { background: white; transform: translateY(-50%) scale(1.1); box-shadow: 0 15px 35px rgba(148, 163, 184, 0.2); color: #38bdf8; }
        
        h2 { text-shadow: none; color: var(--text) !important; -webkit-text-stroke: 0; }
        .s-header::after { background: #38bdf8 !important; height: 4px; border-radius: 4px; }
        
        .tbl-time-pill { border: none; box-shadow: none; background: #e0f2fe; color: #0284c7; font-weight: 700; padding: 4px 10px; border-radius: 12px; }
        .bi-class-badge { border: none; background: #f1f5f9; color: #64748b; padding: 3px 8px; border-radius: 8px; }
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/js/app.js'])
</head>
<body>
    <div class="bg-layer"></div>
    <div class="orb orb-1"></div><div class="orb orb-2"></div><div class="orb orb-3"></div>
    <div class="particles" id="particles"></div>

    <div class="carousel" id="carousel">
        <div class="progress-wrap"><div class="progress-fill" id="progress-fill"></div></div>
        <div class="slides">
            <!-- S1 WELCOME -->
            <div class="slide active" id="slide-0">
                <div class="s1-wrap fade-up">
                    <i class="fas fa-fingerprint s1-icon"></i>
                    <h1 class="s1-title" id="typewriter"></h1>
                    <p class="s1-sub fade-up d2">Sistem Informasi Absensi Cerdas Berbasis Fingerprint</p>
                    <div class="s1-clock-wrap fade-up d3">
                        <div class="s1-time" id="clock-time">00<span class="blink">:</span>00<span class="blink">:</span>00</div>
                        <div class="s1-date-box"><div class="s1-day" id="clock-day">Memuat...</div><div class="s1-date" id="clock-date"></div></div>
                    </div>
                </div>
            </div>
            <!-- S2 DAILY -->
            <div class="slide" id="slide-1">
                <div class="s-header s2-header fade-up"><h2><i class="fas fa-bolt"></i> Top 10 Kehadiran Hari Ini</h2><p>Siswa yang tap fingerprint paling awal hari ini</p></div>
                <div class="s2-grid"><div class="s2-top3 fade-up d1" id="daily-top3"></div><div class="s2-table-wrap fade-up d2"><table><thead><tr><th>Rank</th><th>Nama Siswa</th><th>Kelas</th><th>Tap</th></tr></thead><tbody id="daily-table"></tbody></table></div></div>
            </div>
            <!-- S3 WEEKLY -->
            <div class="slide" id="slide-2">
                <div class="s-header s3-header fade-up"><h2><i class="fas fa-calendar-week"></i> Top 10 Poin Mingguan</h2><p>Peringkat akumulasi poin kehadiran minggu ini</p></div>
                <div class="board" id="weekly-board"></div>
            </div>
            <!-- S4 MONTHLY -->
            <div class="slide" id="slide-3">
                <div class="s-header s4-header fade-up"><h2><i class="fas fa-chart-line"></i> Top 10 Poin Bulanan</h2><p>Konsistensi kehadiran selama bulan ini</p></div>
                <div class="board" id="monthly-board"></div>
            </div>
            <!-- S5 HOF -->
            <div class="slide" id="slide-4">
                <div class="s-header s5-header fade-up"><h2><i class="fas fa-trophy"></i> Hall of Fame</h2><p>Legenda dengan streak kehadiran terpanjang sepanjang masa</p></div>
                <div class="board" id="hof-board"></div>
            </div>
        </div>
        <button class="nav-arrow" id="arrow-prev"><i class="fas fa-chevron-left"></i></button>
        <button class="nav-arrow" id="arrow-next"><i class="fas fa-chevron-right"></i></button>
        <div class="nav-dots"><div class="dot active" data-index="0"></div><div class="dot" data-index="1"></div><div class="dot" data-index="2"></div><div class="dot" data-index="3"></div><div class="dot" data-index="4"></div></div>
    </div>

<script>
// ======================== DATA ========================
let dailyData = [
    @foreach($logs as $log)
    { 
        name: "{{ $log->student->name ?? 'Belum terdaftar' }}", 
        cls: "{{ $log->student->schoolClass->name ?? '-' }}", 
        time: "{{ \Carbon\Carbon::parse($log->timestamp)->format('H:i') }}" 
    },
    @endforeach
];

function mkBoard(m) {
    return [
        { name:"Ibrahim Nasrullah", ini:"I", cls:"XII RPL 1", pts:15*m, max:15*m, streak:155, tap:"06:35" },
        { name:"Wahyu Hidayat", ini:"W", cls:"X RPL 1", pts:13*m, max:15*m, streak:102, tap:"06:36" },
        { name:"Dimas Arya Putra", ini:"D", cls:"XII RPL 1", pts:12*m, max:15*m, streak:55, tap:"06:40" },
        { name:"Muhammad Fikri", ini:"M", cls:"X RPL 2", pts:10*m, max:15*m, streak:12, tap:"06:45" },
        { name:"Rizky Firmansyah", ini:"R", cls:"XI RPL 1", pts:9*m, max:15*m, streak:5, tap:"06:46" },
        { name:"Nabila Syahrani", ini:"N", cls:"XI RPL 2", pts:8*m, max:15*m, streak:0, tap:"06:48" },
        { name:"Aditya Pratama", ini:"A", cls:"XII RPL 2", pts:7*m, max:15*m, streak:3, tap:"06:50" },
        { name:"Citra Kirana", ini:"C", cls:"X RPL 1", pts:6*m, max:15*m, streak:0, tap:"06:52" },
        { name:"Bagas Putra", ini:"B", cls:"XI RPL 1", pts:5*m, max:15*m, streak:0, tap:"06:55" },
        { name:"Diana Putri", ini:"D", cls:"XII RPL 1", pts:4*m, max:15*m, streak:0, tap:"06:58" }
    ];
}
const wData = mkBoard(1), mData = mkBoard(4);
const hData = mkBoard(1).map(d => ({...d, pts: d.streak > 0 ? d.streak*10 : 5, max: 1550}));

// ======================== STREAK STAGE ========================
function getStage(days) {
    if (days >= 150) return 5;
    if (days >= 100) return 4;
    if (days >= 50) return 3;
    if (days >= 10) return 2;
    if (days >= 3) return 1;
    return 0;
}

// ======================== FLAME SVG GENERATOR ========================
function flameSVG(stage, size) {
    if (stage === 0) return '';
    const w = size === 'big' ? 140 : 40;
    const h = size === 'big' ? 165 : 48;
    const vb = (stage >= 4) ? '0 0 200 240' : '0 0 170 200';
    const cls = `flame-s${stage}`;
    const grads = getGradients(stage);
    const paths = getPaths(stage);
    return `<div class="flame-mount ${size==='big'?'flame-entrance':''}" style="width:${w}px;height:${h}px;">
        <div class="${cls}" style="width:100%;height:100%;">
            <svg viewBox="${vb}" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:100%;height:100%;overflow:visible;">${grads}${paths}</svg>
        </div>
    </div>`;
}

function getGradients(s) {
    if (s === 1) return `<defs>
        <linearGradient id="s1og" x1="85" y1="190" x2="85" y2="10" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#d61f00"/><stop offset="28%" stop-color="#ff3b00"/><stop offset="70%" stop-color="#ff7a00"/><stop offset="100%" stop-color="#ff9900"/></linearGradient>
        <linearGradient id="s1mg" x1="85" y1="185" x2="85" y2="35" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#ff6200"/><stop offset="40%" stop-color="#ff9d00"/><stop offset="85%" stop-color="#ffc400"/><stop offset="100%" stop-color="#ffe853"/></linearGradient>
        <linearGradient id="s1ig" x1="85" y1="180" x2="85" y2="80" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#ffb300"/><stop offset="30%" stop-color="#ffe066"/><stop offset="70%" stop-color="#fffbe6"/><stop offset="100%" stop-color="#fff"/></linearGradient>
        <linearGradient id="s1lg" x1="0" y1="1" x2="0" y2="0"><stop offset="0%" stop-color="#ff5500"/><stop offset="50%" stop-color="#ffb700"/><stop offset="100%" stop-color="#fff5b8"/></linearGradient>
        <filter id="s1LayerFilter" x="-15%" y="-15%" width="130%" height="130%"><feDropShadow dx="0" dy="4" stdDeviation="4.5" flood-color="#a62200" flood-opacity="0.38"/></filter></defs>`;
    if (s === 2) return `<defs>
        <linearGradient id="s2og" x1="85" y1="190" x2="85" y2="10" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#e65c00"/><stop offset="35%" stop-color="#ff7300"/><stop offset="70%" stop-color="#ff9100"/><stop offset="100%" stop-color="#ffa600"/></linearGradient>
        <linearGradient id="s2mg" x1="85" y1="185" x2="85" y2="35" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#ff8c00"/><stop offset="40%" stop-color="#ffa800"/><stop offset="85%" stop-color="#ffc400"/><stop offset="100%" stop-color="#ffd54f"/></linearGradient>
        <linearGradient id="s2ig" x1="85" y1="180" x2="85" y2="80" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#ffa000"/><stop offset="30%" stop-color="#ffca28"/><stop offset="70%" stop-color="#fff8b5"/><stop offset="100%" stop-color="#fff"/></linearGradient>
        <linearGradient id="s2lg" x1="0" y1="1" x2="0" y2="0"><stop offset="0%" stop-color="#ff7b00"/><stop offset="50%" stop-color="#ffb700"/><stop offset="100%" stop-color="#ffecb3"/></linearGradient>
        <filter id="s2LayerFilter" x="-15%" y="-15%" width="130%" height="130%"><feDropShadow dx="0" dy="4" stdDeviation="4.5" flood-color="#a84700" flood-opacity="0.38"/></filter></defs>`;
    if (s === 3) return `<defs>
        <linearGradient id="s3og" x1="85" y1="190" x2="85" y2="10" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#e69b00"/><stop offset="25%" stop-color="#ffb700"/><stop offset="60%" stop-color="#ffd200"/><stop offset="90%" stop-color="#ffea00"/><stop offset="100%" stop-color="#fff59d"/></linearGradient>
        <linearGradient id="s3mg" x1="85" y1="185" x2="85" y2="35" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#ffb700"/><stop offset="35%" stop-color="#ffdb1a"/><stop offset="70%" stop-color="#fff04d"/><stop offset="100%" stop-color="#ffffb3"/></linearGradient>
        <linearGradient id="s3ig" x1="85" y1="180" x2="85" y2="80" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#ffd54f"/><stop offset="30%" stop-color="#fff59d"/><stop offset="70%" stop-color="#fff"/><stop offset="100%" stop-color="#fff"/></linearGradient>
        <linearGradient id="s3lg" x1="0" y1="1" x2="0" y2="0"><stop offset="0%" stop-color="#ffa000"/><stop offset="50%" stop-color="#ffd700"/><stop offset="100%" stop-color="#fff"/></linearGradient>
        <filter id="s3LayerFilter" x="-15%" y="-15%" width="130%" height="130%"><feDropShadow dx="0" dy="4" stdDeviation="4.5" flood-color="#a66d00" flood-opacity="0.38"/></filter></defs>`;
    if (s === 4) return `<defs>
        <linearGradient id="s4og" x1="85" y1="190" x2="85" y2="10" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#001845"/><stop offset="25%" stop-color="#0038a8"/><stop offset="60%" stop-color="#0077ff"/><stop offset="90%" stop-color="#00d4ff"/><stop offset="100%" stop-color="#99f6ff"/></linearGradient>
        <linearGradient id="s4mg" x1="85" y1="185" x2="85" y2="35" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#0044ff"/><stop offset="45%" stop-color="#00b4d8"/><stop offset="85%" stop-color="#48cae4"/><stop offset="100%" stop-color="#caf0f8"/></linearGradient>
        <linearGradient id="s4ig" x1="85" y1="180" x2="85" y2="80" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#0096c7"/><stop offset="30%" stop-color="#90e0ef"/><stop offset="70%" stop-color="#fff"/><stop offset="100%" stop-color="#fff"/></linearGradient>
        <linearGradient id="s4lg" x1="0" y1="1" x2="0" y2="0"><stop offset="0%" stop-color="#0066ff"/><stop offset="50%" stop-color="#00d4ff"/><stop offset="100%" stop-color="#fff"/></linearGradient>
        <filter id="s4LayerFilter" x="-15%" y="-15%" width="130%" height="130%"><feDropShadow dx="0" dy="4" stdDeviation="4.5" flood-color="#0033a8" flood-opacity="0.38"/></filter></defs>`;
    if (s === 5) return `<defs>
        <linearGradient id="s5og" x1="85" y1="190" x2="85" y2="10" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#1f003d"/><stop offset="25%" stop-color="#4d008f"/><stop offset="60%" stop-color="#9d00ff"/><stop offset="90%" stop-color="#dc52ff"/><stop offset="100%" stop-color="#f8b8ff"/></linearGradient>
        <linearGradient id="s5mg" x1="85" y1="185" x2="85" y2="35" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#5500aa"/><stop offset="45%" stop-color="#ba1fff"/><stop offset="85%" stop-color="#e275ff"/><stop offset="100%" stop-color="#fdd9ff"/></linearGradient>
        <linearGradient id="s5ig" x1="85" y1="180" x2="85" y2="80" gradientUnits="userSpaceOnUse"><stop offset="0%" stop-color="#9300ff"/><stop offset="30%" stop-color="#ea9eff"/><stop offset="70%" stop-color="#fff"/><stop offset="100%" stop-color="#fff"/></linearGradient>
        <linearGradient id="s5lg" x1="0" y1="1" x2="0" y2="0"><stop offset="0%" stop-color="#7e00ff"/><stop offset="50%" stop-color="#dc52ff"/><stop offset="100%" stop-color="#fff"/></linearGradient>
        <filter id="s5LayerFilter" x="-15%" y="-15%" width="130%" height="130%"><feDropShadow dx="0" dy="4" stdDeviation="4.5" flood-color="#4a008f" flood-opacity="0.38"/></filter></defs>`;
    return '';
}

function getPaths(s) {
    const p = `s${s}`;
    let highlightColor = '#d48800'; // Default gold highlight
    if(s===1) highlightColor = '#d61f00';
    if(s===2) highlightColor = '#e65c00';
    if(s===4) highlightColor = '#0038a8';
    if(s===5) highlightColor = '#4d008f';

    return `
    <g class="fl-lick1"><path d="M78 58 C72 46, 68 34, 82 18 C86 28, 92 37, 85 54 C82 58, 79 60, 78 58 Z" fill="url(#${p}lg)" opacity="0.95"/></g>
    <g class="fl-lick2"><path d="M112 65 C118 52, 126 42, 115 25 C108 36, 105 45, 109 59 C110 63, 112 66, 112 65 Z" fill="url(#${p}lg)" opacity="0.98"/></g>
    <g class="fl-lick3"><path d="M92 45 C88 32, 96 22, 93 10 C100 19, 102 30, 96 40 C94 43, 93 45, 92 45 Z" fill="url(#${p}lg)" opacity="0.9"/></g>

    <path class="fl-outer" filter="url(#${p}LayerFilter)" d="M92 12 C102 36, 128 42, 138 68 C148 94, 162 110, 156 140 C150 166, 132 188, 106 198 C98 201, 86 201, 78 198 C52 188, 34 166, 28 140 C22 112, 36 94, 46 68 C54 52, 66 48, 62 34 C72 44, 80 58, 84 70 C88 50, 84 28, 92 12 Z" fill="url(#${p}og)" />
    <path class="fl-outer" opacity="0.48" d="M132 74 C140 88, 152 104, 146 124 C140 144, 128 164, 108 175 C122 164, 134 142, 132 122 C130 102, 118 90, 132 74 Z" fill="${highlightColor}" />

    <path class="fl-mid" filter="url(#${p}LayerFilter)" d="M93 48 C104 66, 122 76, 130 96 C138 118, 134 142, 120 160 C111 171, 101 178, 92 179 C83 178, 73 171, 64 160 C50 142, 46 118, 54 96 C61 78, 74 70, 72 56 C78 65, 84 76, 85 87 C91 72, 86 59, 93 48 Z" fill="url(#${p}mg)" />

    <path class="fl-core" d="M92 90 C99 104, 112 114, 114 130 C116 146, 107 162, 92 164 C77 162, 68 146, 70 130 C72 116, 84 106, 87 97 C89 104, 91 111, 92 116 C94 107, 91 97, 92 90 Z" fill="url(#${p}ig)" />

    <circle cx="62" cy="122" r="2.4" fill="#ffffff" opacity="0.95"/>
    <circle cx="124" cy="116" r="2.0" fill="#ffffff" opacity="0.95"/>
    <circle cx="92" cy="74" r="2.2" fill="#ffffff" opacity="1"/>
    <circle cx="106" cy="144" r="1.6" fill="#fff9c4" opacity="0.9"/>
    `;
}

function getGlowColor(s) {
    return ['','rgba(255,85,0,0.4)','rgba(255,140,0,0.35)','rgba(255,215,0,0.45)','rgba(0,180,255,0.5)','rgba(185,30,255,0.5)'][s] || '';
}

const PALETTES = {
    1: ['#FF9600','#FFC800','#FF4B4B','#58CC02','#1CB0F6','#FFFFFF'],
    2: ['#FF8C00','#FF9F00','#FFB300','#FFCA28','#FFE082','#FFFFFF'],
    3: ['#FFFFFF','#FFFF8D','#FFEA00','#FFD700','#FFC400','#FFA000'],
    4: ['#ffffff','#d7f8ff','#00f0ff','#00aaff','#0066ff','#3a86ff'],
    5: ['#ffffff','#fae8ff','#ea7aff','#c233ff','#9900ff','#e040fb']
};

// ======================== PARTICLE ENGINE ========================
class Particle {
    constructor(x, y, mode, palette) {
        this.x = x; this.y = y; this.mode = mode;
        this.color = palette[Math.floor(Math.random()*palette.length)];
        const a = Math.random()*Math.PI*2;
        if (mode === 'burst') {
            const sp = 3+Math.random()*7; this.vx = Math.cos(a)*sp; this.vy = Math.sin(a)*sp-1.5;
            this.r = 2+Math.random()*3.5; this.alpha = 1; this.decay = 0.025+Math.random()*0.025;
        } else if (mode === 'streak') {
            const sp = 6+Math.random()*10; this.vx = Math.cos(a)*sp; this.vy = Math.sin(a)*sp;
            this.alpha = 1; this.decay = 0.035+Math.random()*0.03;
        } else if (mode === 'star') {
            const sp = 2+Math.random()*4; this.vx = Math.cos(a)*sp; this.vy = Math.sin(a)*sp-1;
            this.sz = 5+Math.random()*7; this.alpha = 1; this.rot = Math.random()*360;
            this.rotS = (Math.random()-0.5)*16; this.decay = 0.02+Math.random()*0.02;
        } else { // ember
            this.x += (Math.random()-0.5)*50; this.vx = (Math.random()-0.5)*2.5;
            this.vy = -3-Math.random()*4; this.r = 1.5+Math.random()*2.5;
            this.alpha = 0.9; this.decay = 0.02+Math.random()*0.02;
        }
    }
    update() {
        this.x += this.vx; this.y += this.vy; this.alpha -= this.decay;
        if (this.mode==='burst') { this.vx*=0.95; this.vy*=0.95; this.vy+=0.08; }
        else if (this.mode==='streak') { this.vx*=0.94; this.vy*=0.94; }
        else if (this.mode==='star') { this.rot+=this.rotS; this.vy+=0.05; }
        else { this.vx+=(Math.random()-0.5)*0.4; }
    }
    draw(c) {
        if (this.alpha<=0) return;
        c.save(); c.globalAlpha=Math.max(0,this.alpha); c.fillStyle=this.color;
        if (this.mode==='streak') {
            c.strokeStyle=this.color; c.lineWidth=2; c.shadowColor=this.color; c.shadowBlur=8;
            c.beginPath(); c.moveTo(this.x,this.y); c.lineTo(this.x-this.vx*2.5,this.y-this.vy*2.5); c.stroke();
        } else if (this.mode==='star') {
            c.translate(this.x,this.y); c.rotate(this.rot*Math.PI/180); c.shadowColor=this.color; c.shadowBlur=10;
            c.beginPath();
            for(let i=0;i<4;i++){c.lineTo(Math.cos((18+i*90)*Math.PI/180)*this.sz,-Math.sin((18+i*90)*Math.PI/180)*this.sz);c.lineTo(Math.cos((63+i*90)*Math.PI/180)*(this.sz*0.28),-Math.sin((63+i*90)*Math.PI/180)*(this.sz*0.28));}
            c.closePath(); c.fill();
        } else {
            c.beginPath(); c.arc(this.x,this.y,this.r||2,0,Math.PI*2); c.fill();
        }
        c.restore();
    }
}

const slideParticles = {}; // keyed by slideId
const slideCanvases = {};
const slideAnimFrames = {};

function initSlideCanvas(slideId) {
    const cv = document.querySelector(`#${slideId} .podium-canvas`);
    if (!cv) return;
    slideCanvases[slideId] = cv;
    slideParticles[slideId] = [];
    const rect = cv.parentElement.getBoundingClientRect();
    cv.width = rect.width; cv.height = rect.height;
}

function emitBurst(slideId, x, y, count, modes, stage) {
    if (!slideParticles[slideId]) slideParticles[slideId] = [];
    const pal = PALETTES[stage] || PALETTES[1];
    for (let i=0; i<count; i++) {
        const m = modes[Math.floor(Math.random()*modes.length)];
        slideParticles[slideId].push(new Particle(x,y,m,pal));
    }
}

function startParticleLoop(slideId) {
    if (slideAnimFrames[slideId]) cancelAnimationFrame(slideAnimFrames[slideId]);
    const cv = slideCanvases[slideId]; if (!cv) return;
    const ctx = cv.getContext('2d');
    function loop() {
        ctx.clearRect(0,0,cv.width,cv.height);
        const ps = slideParticles[slideId];
        // Continuous ember spawn from top-3 flames
        const podium = cv.parentElement.querySelector('.podium');
        if (podium) {
            podium.querySelectorAll('.pod-flame-wrap[data-stage]').forEach(fw => {
                const st = +fw.dataset.stage;
                if (st >= 3 && Math.random() < 0.3) {
                    const r = fw.getBoundingClientRect();
                    const pr = cv.getBoundingClientRect();
                    const cx = r.left - pr.left + r.width/2;
                    const cy = r.top - pr.top + r.height*0.3;
                    ps.push(new Particle(cx,cy,'ember',PALETTES[st]||PALETTES[3]));
                }
            });
        }
        for (let i=ps.length-1;i>=0;i--) { ps[i].update(); ps[i].draw(ctx); if(ps[i].alpha<=0) ps.splice(i,1); }
        slideAnimFrames[slideId] = requestAnimationFrame(loop);
    }
    loop();
}

function stopParticleLoop(slideId) {
    if (slideAnimFrames[slideId]) { cancelAnimationFrame(slideAnimFrames[slideId]); slideAnimFrames[slideId]=null; }
    if (slideParticles[slideId]) slideParticles[slideId]=[];
    const cv = slideCanvases[slideId]; if(cv) cv.getContext('2d').clearRect(0,0,cv.width,cv.height);
}

// ======================== RENDER ========================
function renderDaily() {
    document.getElementById('daily-top3').innerHTML = dailyData.slice(0,3).map((d,i) => {
        const c = ['r1','r2','r3'][i];
        return `<div class="s2-card ${c}"><div class="accent"></div><div class="s2-medal"><i class="fas fa-medal"></i></div><div class="s2-info"><div class="s2-name">${d.name}</div><div class="s2-class">${d.cls}</div></div><div class="s2-time"><i class="far fa-clock"></i> ${d.time}</div></div>`;
    }).join('');
    document.getElementById('daily-table').innerHTML = dailyData.slice(3,10).map((d,i) => `<tr><td class="tbl-rank">#${i+4}</td><td class="tbl-name">${d.name}</td><td>${d.cls}</td><td><span class="tbl-time-pill">${d.time}</span></td></tr>`).join('');
}

function renderBoard(id, data, color, slideId) {
    const el = document.getElementById(id);
    const t3 = data.slice(0,3), rest = data.slice(3,10);
    const pod = [{...t3[1],r:2,c:'pod-2'},{...t3[0],r:1,c:'pod-1'},{...t3[2],r:3,c:'pod-3'}];

    let h = `<div class="podium fade-up d1"><canvas class="podium-canvas"></canvas>`;
    pod.forEach(d => {
        const st = getStage(d.streak);
        const flameHTML = flameSVG(st, 'big');
        const glowCol = getGlowColor(st);
        const streakText = st > 0 ? `${d.streak}x Streak` : `${d.streak > 0 ? d.streak : 1} Hari`;
        h += `<div class="pod-card ${d.c}">
            ${d.r===1?'<div class="pod-crown">&#x1F451;</div>':''}
            <div class="pod-rank-num">#${d.r}</div>
            <div class="pod-avatar">${d.ini}</div>
            <div class="pod-flame-wrap" data-stage="${st}">
                ${glowCol ? `<div class="flame-glow" style="width:140px;height:140px;background:radial-gradient(circle,${glowCol} 0%,transparent 70%);"></div>` : ''}
                ${flameHTML}
            </div>
            <div class="pod-name">${d.name.split(' ').slice(0,2).join(' ')}</div>
            <div class="pod-class">${d.cls}</div>
            ${st > 0 ? `<div class="pod-streak-display"><span class="counter" data-target="${d.streak}">0</span> <small>x Streak</small></div>` : `<div class="pod-streak-none">Belum Streak</div>`}
        </div>`;
    });
    h += `</div>`;
    h += `<div class="board-divider fade-up d2"></div>`;
    h += `<div class="board-section-title fade-up d2">Peringkat lainnya <div class="line" style="background:${color}"></div></div>`;
    h += `<div class="board-list">`;
    rest.forEach((d,i) => {
        const rk = i+4;
        const pct = Math.round((d.pts/d.max)*100);
        const st = getStage(d.streak);
        const smallFlame = flameSVG(st, 'small');
        const skText = st > 0 ? `${d.streak}x Streak` : `${d.streak > 0 ? d.streak : 1} Hari (Belum Streak)`;
        h += `<div class="board-item fade-up d${Math.min(3+i,5)}">
            <div class="bi-rank">#${rk}</div>
            ${st > 0 ? `<div class="bi-flame-wrap">${smallFlame}</div>` : `<div class="bi-avatar" style="background:${color}15;color:${color}">${d.ini}</div>`}
            <div class="bi-info">
                <div class="bi-name">${d.name} <span class="bi-class-badge">${d.cls}</span></div>
                <div class="bi-meta"><span class="${st>0?'':'streak-none'}">${st>0?'<i class="fas fa-fire" style="color:'+(['','#ff3b00','#ff8c00','#ffd700','#00aaff','#c233ff'][st])+'"></i> ':''} ${skText}</span> &bull; <span class="tap-time">Tap: ${d.tap} WIB</span></div>
            </div>
            <div class="bi-progress"><div class="bi-bar-bg"><div class="bi-bar-fill slide-anim" data-width="${pct}%" style="background:linear-gradient(90deg,${color},${color}88)"></div></div></div>
            <div class="bi-pts"><strong><span class="counter" data-target="${d.pts}">0</span></strong><span>pt</span></div>
        </div>`;
    });
    h += `</div>`;
    el.innerHTML = h;
}

// ======================== PARTICLES ========================
function initBgParticles() {
    const c = document.getElementById('particles');
    const cols = ['rgba(99,102,241,0.3)','rgba(16,185,129,0.3)','rgba(245,158,11,0.25)','rgba(139,92,246,0.25)','rgba(59,130,246,0.2)'];
    for(let i=0;i<20;i++){const p=document.createElement('div');p.classList.add('particle');const sz=Math.random()*6+3;p.style.cssText=`left:${Math.random()*100}vw;width:${sz}px;height:${sz}px;background:${cols[i%cols.length]};box-shadow:0 0 ${sz*2}px ${cols[i%cols.length]};animation-duration:${Math.random()*12+10}s;animation-delay:${Math.random()*8}s;`;c.appendChild(p);}
}

// ======================== CLOCK ========================
function updateClock() {
    const n=new Date(),h=String(n.getHours()).padStart(2,'0'),m=String(n.getMinutes()).padStart(2,'0'),s=String(n.getSeconds()).padStart(2,'0');
    document.getElementById('clock-time').innerHTML=`${h}<span class="blink">:</span>${m}<span class="blink">:</span>${s}`;
    const days=['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    const months=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    document.getElementById('clock-day').textContent=days[n.getDay()];
    document.getElementById('clock-date').textContent=`${n.getDate()} ${months[n.getMonth()]} ${n.getFullYear()}`;
}

// ======================== CAROUSEL ========================
let cur=0;const total=5,interval=60000;let progTimer,prog=0,paused=false;
const allSlides=document.querySelectorAll('.slide'),allDots=document.querySelectorAll('.dot'),progFill=document.getElementById('progress-fill');

function triggerAnims(idx) {
    const sl=allSlides[idx];
    if(idx===0){const txt="SELAMAT DATANG DI RPL",el=document.getElementById('typewriter');el.textContent='';let i=0;if(el._tw)clearInterval(el._tw);el._tw=setInterval(()=>{if(i<txt.length){el.textContent+=txt[i];i++;}else clearInterval(el._tw);},80);}
    sl.querySelectorAll('.counter').forEach(c=>{c.textContent='0';const t=+c.dataset.target,step=t/60;let v=0;const iv=setInterval(()=>{v+=step;if(v>=t){c.textContent=t;clearInterval(iv);}else c.textContent=Math.floor(v);},25);});
    sl.querySelectorAll('.slide-anim').forEach(f=>{f.style.width='0';setTimeout(()=>f.style.width=f.dataset.width,250);});

    // Activate flame glows
    sl.querySelectorAll('.flame-glow').forEach(g=>{g.classList.remove('active');void g.offsetWidth;g.classList.add('active');});
    // Re-trigger flame entrance
    sl.querySelectorAll('.flame-entrance').forEach(f=>{f.classList.remove('flame-entrance');void f.offsetWidth;f.classList.add('flame-entrance');});

    // Start particle systems for this slide
    const slideId = `slide-${idx}`;
    if (idx >= 2) {
        initSlideCanvas(slideId);
        startParticleLoop(slideId);
        // Initial burst for podium flames
        setTimeout(() => {
            const cv = slideCanvases[slideId]; if(!cv) return;
            const pr = cv.getBoundingClientRect();
            cv.parentElement.querySelectorAll('.pod-flame-wrap[data-stage]').forEach(fw => {
                const st = +fw.dataset.stage; if(st<1) return;
                const r = fw.getBoundingClientRect();
                const cx = r.left-pr.left+r.width/2, cy = r.top-pr.top+r.height*0.4;
                emitBurst(slideId, cx, cy, 12+st*4, ['burst','star','streak'], st);
            });
        }, 300);
    }
}

function goTo(idx) {
    // Stop particles on old slide
    stopParticleLoop(`slide-${cur}`);
    allSlides[cur].classList.remove('active');allSlides[cur].classList.add('exit');allDots[cur].classList.remove('active');
    setTimeout(()=>document.querySelectorAll('.exit').forEach(e=>e.classList.remove('exit')),900);
    cur=((idx%total)+total)%total;
    allSlides[cur].classList.add('active');allDots[cur].classList.add('active');
    resetProg();triggerAnims(cur);
}
function next(){goTo(cur+1);}function prev(){goTo(cur-1);}
function startProg(){prog=0;progFill.style.width='0%';progFill.style.transition='none';requestAnimationFrame(()=>{progFill.style.transition='width 1s linear';progTimer=setInterval(()=>{if(!paused){prog+=100/(interval/1000);progFill.style.width=prog+'%';if(prog>=100)next();}},1000);});}
function resetProg(){clearInterval(progTimer);startProg();}

document.getElementById('arrow-next').addEventListener('click',next);
document.getElementById('arrow-prev').addEventListener('click',prev);
document.querySelectorAll('.dot').forEach((d,i)=>d.addEventListener('click',()=>{if(cur!==i)goTo(i);}));
document.getElementById('carousel').addEventListener('mouseenter',()=>paused=true);
document.getElementById('carousel').addEventListener('mouseleave',()=>paused=false);
document.addEventListener('keydown',e=>{if(e.key==='ArrowRight')next();if(e.key==='ArrowLeft')prev();});
let tx=0;document.getElementById('carousel').addEventListener('touchstart',e=>tx=e.changedTouches[0].screenX);
document.getElementById('carousel').addEventListener('touchend',e=>{const dx=e.changedTouches[0].screenX-tx;if(dx<-50)next();if(dx>50)prev();});

// ======================== INIT ========================
renderDaily();
renderBoard('weekly-board', wData, '#10b981', 'slide-2');
renderBoard('monthly-board', mData, '#8b5cf6', 'slide-3');
renderBoard('hof-board', hData, '#f59e0b', 'slide-4');
initBgParticles();
setInterval(updateClock,1000); updateClock();
triggerAnims(0); startProg();
// --- PLAYFUL INTERACTIVE CLICK EFFECT ---
document.addEventListener('click', (e) => {
    const c = document.createElement('div');
    c.style.position = 'fixed'; c.style.left = e.clientX + 'px'; c.style.top = e.clientY + 'px';
    c.style.pointerEvents = 'none'; c.style.zIndex = '9999';
    document.body.appendChild(c);
    for(let i=0; i<8; i++) {
        const p = document.createElement('div');
        p.style.position = 'absolute';
        p.style.width = '12px'; p.style.height = '12px';
        p.style.background = ['#7dd3fc','#86efac','#d8b4fe','#fde047','#fbcfe8'][Math.floor(Math.random()*5)];
        p.style.borderRadius = Math.random()>0.5 ? '50%' : '4px';
        const a = Math.random() * Math.PI * 2;
        const v = 30 + Math.random() * 50;
        p.style.transition = 'all 0.6s cubic-bezier(0.2, 0.8, 0.2, 1)';
        p.style.transform = `translate(-50%, -50%) scale(1)`;
        c.appendChild(p);
        setTimeout(() => {
            p.style.transform = `translate(calc(-50% + ${Math.cos(a)*v}px), calc(-50% + ${Math.sin(a)*v}px)) scale(0) rotate(${Math.random()*360}deg)`;
            p.style.opacity = '0';
        }, 10);
    }
    setTimeout(() => c.remove(), 600);
});
</script>

<script type="module">
    document.addEventListener("DOMContentLoaded", () => {
        if(window.Echo) {
            window.Echo.channel('attendance')
                .listen('.AttendanceCreated', (e) => {
                    const log = e.log;
                    const studentName = log.student ? log.student.name : 'Belum terdaftar';
                    const className = (log.student && log.student.school_class) ? log.student.school_class.name : '-';
                    const timeStr = log.timestamp.split(' ')[1].substring(0, 5); // Ambil jam:menit
                    
                    // Masukkan data baru ke paling atas array
                    dailyData.unshift({
                        name: studentName,
                        cls: className,
                        time: timeStr
                    });

                    // Jika lebih dari 10, buang yang paling bawah
                    if(dailyData.length > 10) {
                        dailyData.pop();
                    }

                    // Render ulang papan daily-board (id list nya 'list-slide-1' di kode asli)
                    renderDaily();
                    
                    // Efek flash sederhana di board saat ada data masuk
                    const pnl = document.getElementById('daily-top3');
                    if(pnl) {
                        pnl.style.transition = 'transform 0.3s, box-shadow 0.3s';
                        pnl.style.transform = 'scale(1.02)';
                        pnl.style.boxShadow = '0 0 30px rgba(59, 130, 246, 0.5)';
                        setTimeout(() => {
                            pnl.style.transform = 'scale(1)';
                            pnl.style.boxShadow = 'none';
                        }, 500);
                    }
                });
        }
    });
</script>
</body>
</html>