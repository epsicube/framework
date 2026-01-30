<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Epsicube — Modular Ecosystem</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet"/>

    <style>
        :root {
            /* Light Mode */
            --bg-color: #ffffff;
            --card-bg: #f9f9f8;
            --border-color: #e5e5e3;
            --text-main: #1a1a18;
            --text-muted: #666662;
            --accent-orange: #f53003;
            --accent-purple: #a855f7;
            --accent-green: #10b981;
            --glow-opacity: 0.08;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                /* Dark Mode */
                --bg-color: #050505;
                --card-bg: #0f0f0e;
                --border-color: #262624;
                --text-main: #fcfcfc;
                --text-muted: #888883;
                --glow-opacity: 0.15;
            }
        }

        * { box-sizing: border-box; }

        body {
            background-color: var(--bg-color);
            background-image: radial-gradient(circle at 50% -10%, rgba(245, 48, 3, var(--glow-opacity)), transparent 40%);
            color: var(--text-main);
            font-family: 'Instrument Sans', sans-serif;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            padding: 40px 24px;
            overflow-x: hidden;
            transition: background-color 0.3s ease;
        }

        header {
            width: 100%;
            max-width: 1100px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 80px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--border-color);
        }

        .logo {
            font-size: 1.1rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .logo span { color: var(--accent-orange); }

        .header-links {
            display: flex;
            gap: 32px;
            align-items: center;
        }

        .header-link {
            text-decoration: none;
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 600;
            transition: color 0.2s;
        }

        .header-link:hover { color: var(--text-main); }

        .btn-admin {
            text-decoration: none;
            color: var(--bg-color);
            background: var(--text-main);
            font-size: 0.8rem;
            font-weight: 700;
            padding: 8px 18px;
            border-radius: 6px;
            transition: opacity 0.2s;
        }

        .btn-admin:hover { opacity: 0.9; }

        main { width: 100%; max-width: 1100px; }

        .hero {
            display: grid;
            grid-template-columns: 1.3fr 0.7fr;
            gap: 60px;
            align-items: center;
            margin-bottom: 100px;
        }

        .hero h1 {
            font-size: clamp(3rem, 7vw, 4.8rem);
            margin: 0 0 20px 0;
            line-height: 0.9;
            letter-spacing: -0.05em;
            font-weight: 700;
        }

        .hero p {
            font-size: 1.25rem;
            color: var(--text-muted);
            line-height: 1.6;
            margin: 0;
            max-width: 550px;
        }

        /* Gestion des logos selon le mode */
        .logo-light { display: block; }
        .logo-dark { display: none; }

        @media (prefers-color-scheme: dark) {
            .logo-light { display: none; }
            .logo-dark { display: block; }
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 24px;
        }

        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 40px;
            border-radius: 24px;
            text-decoration: none;
            color: inherit;
            transition: all 0.5s cubic-bezier(0.2, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .card.wide { grid-column: span 3; }
        .card.full {
            grid-column: span 6;
            flex-direction: row;
            align-items: center;
            gap: 40px;
        }

        .card:hover {
            transform: translateY(-6px);
            background-color: var(--card-bg);
        }

        .card.admin-card:hover {
            border-color: var(--accent-orange);
            box-shadow: 0 20px 50px -10px rgba(245, 48, 3, var(--glow-opacity));
        }

        .card.docs-card:hover {
            border-color: var(--accent-purple);
            box-shadow: 0 20px 50px -10px rgba(168, 85, 247, var(--glow-opacity));
        }

        .card.dev-card:hover {
            border-color: var(--accent-green);
            box-shadow: 0 20px 50px -10px rgba(16, 185, 129, var(--glow-opacity));
        }

        .icon-box {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .card:hover .icon-box { border-color: inherit; }

        .card.full .icon-box { margin-bottom: 0; width: 64px; height: 64px; }

        .card h3 { font-size: 1.5rem; margin: 0 0 12px 0; font-weight: 700; letter-spacing: -0.02em; }
        .card p { font-size: 1rem; color: var(--text-muted); margin: 0; line-height: 1.6; }

        .badge {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 12px;
            color: var(--text-muted);
            display: block;
        }

        .hero-img {
            display: flex;
            justify-content: flex-end;
            filter: drop-shadow(0 20px 40px rgba(245, 48, 3, 0.1));
        }

        .hero-img svg { width: 220px; height: 220px; }

        @media (max-width: 900px) {
            .hero { grid-template-columns: 1fr; text-align: center; gap: 40px; }
            .hero-img { display: none; }
            .card.wide, .card.full { grid-column: span 6; flex-direction: column; text-align: left; }
            header { flex-direction: column; gap: 30px; text-align: center; margin-bottom: 60px; }
        }
    </style>
</head>
<body>

<header>
    <div class="logo">EPSICUBE<span>.</span></div>
    <div class="header-links">
        <a href="https://epsicube.dev" class="header-link">Docs</a>
        <a href="https://github.com/epsicube/epsicube" class="header-link">GitHub</a>
        @if(\Epsicube\Support\Facades\Modules::isEnabled('core::administration'))
            <a href="{{\Filament\Facades\Filament::getPanel('epsicube-administration')->getUrl()}}" class="btn-admin">Administration</a>
        @endif
    </div>
</header>

<main>
    <section class="hero">
        <div>
            <h1>Scale faster.<br>Stay modular.</h1>
            <p>Epsicube is the foundational layer for your Laravel applications. Built for clean, decoupled, and ultra-scalable architecture.</p>
        </div>
        <div class="hero-img">
            <div class="logo-light">@include('partials.logo')</div>
            <div class="logo-dark">@include('partials.logo-white')</div>
        </div>
    </section>

    <div class="grid">
        @if(\Epsicube\Support\Facades\Modules::isEnabled('core::administration'))
            <a href="{{\Filament\Facades\Filament::getPanel('epsicube-administration')->getUrl()}}" class="card full admin-card">
                <div class="icon-box" style="color: var(--accent-orange);">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M21 9H3"/><path d="M9 21V9"/></svg>
                </div>
                <div>
                    <span class="badge" style="color: var(--accent-orange)">Internal Tool</span>
                    <h3>Administration</h3>
                    <p>Live-manage your ecosystem, toggle modules, and configure your core options through a unified interface.</p>
                </div>
            </a>
        @endif

        <a href="https://epsicube.dev/usage/overview/" class="card wide docs-card">
            <div class="icon-box" style="color: var(--accent-purple);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M8 7h6"/><path d="M8 11h8"/></svg>
            </div>
            <span class="badge">Resources</span>
            <h3>Explore Concepts</h3>
            <p>Understand the lifecycle of modules, OptionsDefinitions, and master your integrations.</p>
        </a>

        <a href="https://epsicube.dev/writing-module/" class="card wide dev-card">
            <div class="icon-box" style="color: var(--accent-green);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15h6"/><path d="M12 12v6"/></svg>
            </div>
            <span class="badge">Development</span>
            <h3>Build a Module</h3>
            <p>Scaffold a new module using the CLI and start extending your application core logic safely.</p>
        </a>
    </div>
</main>

<footer style="margin-top: 100px; padding-bottom: 60px; color: var(--text-muted); font-size: 0.85rem; text-align: center; width: 100%; border-top: 1px solid var(--border-color); padding-top: 40px;">
    &copy; {{ date('Y') }} Epsicube &bull; Designed for modular Laravel architectures.
</footer>

</body>
</html>
