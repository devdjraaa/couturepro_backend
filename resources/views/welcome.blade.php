<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Gextimo — La gestion d'atelier qui simplifie votre journée</title>
    <meta name="description" content="L'application qui aide les ateliers de couture à gérer clients, commandes, mesures et paiements depuis leur téléphone — même sans Internet." />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary:    #6366F1;
            --primary-dk: #4338CA;
            --accent:     #F59E0B;
            --terra:      #C2410C;
            --ink:        #1C1917;
            --dim:        #57534E;
            --ghost:      #A8A29E;
            --bg:         #FAFAF9;
            --card:       #FFFFFF;
            --edge:       #E7E5E4;
            --success:    #059669;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--ink);
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }
        h1, h2, h3 { font-family: 'Outfit', sans-serif; letter-spacing: -0.02em; }
        a { color: inherit; text-decoration: none; }
        .container { max-width: 1100px; margin: 0 auto; padding: 0 1.25rem; position: relative; }
        svg.icon { width: 1em; height: 1em; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; fill: none; }

        /* HEADER */
        header {
            position: sticky; top: 0; z-index: 50;
            background: rgba(250, 250, 249, 0.85);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--edge);
        }
        .nav { display: flex; align-items: center; justify-content: space-between; padding: 0.9rem 0; }
        .brand { display: flex; align-items: center; gap: 0.6rem; font-weight: 700; font-family: 'Outfit'; font-size: 1.05rem; }
        .brand-icon {
            width: 34px; height: 34px;
            background: var(--primary);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: white;
        }
        .brand-icon svg { width: 18px; height: 18px; }
        .nav-links { display: flex; gap: 1.5rem; font-size: 0.9rem; color: var(--dim); }
        .nav-links a:hover { color: var(--primary); }
        .nav-cta {
            background: var(--primary); color: white;
            padding: 0.55rem 1.1rem; border-radius: 10px;
            font-weight: 600; font-size: 0.9rem;
            transition: transform 0.15s, box-shadow 0.2s;
        }
        .nav-cta:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(99,102,241,0.3); }
        @media (max-width: 720px) { .nav-links { display: none; } }

        /* HERO */
        .hero {
            position: relative;
            padding: 6rem 0 5rem;
            overflow: hidden;
        }
        .hero-bg-shapes { position: absolute; inset: 0; pointer-events: none; z-index: 0; }
        .floater {
            position: absolute;
            border-radius: 50%;
            filter: blur(40px);
            opacity: 0.35;
            animation: float-y 14s ease-in-out infinite;
        }
        .floater.f1 { width: 280px; height: 280px; background: var(--primary);   top: -40px;  left: -60px;  animation-duration: 12s; }
        .floater.f2 { width: 220px; height: 220px; background: var(--accent);    top: 30%;    right: -50px; animation-duration: 16s; animation-delay: -3s; }
        .floater.f3 { width: 180px; height: 180px; background: var(--terra);     bottom: -40px; left: 35%;  animation-duration: 18s; animation-delay: -6s; }
        .floater.f4 { width: 140px; height: 140px; background: #a855f7;          top: 60%;    left: 20%;    animation-duration: 11s; animation-delay: -2s; opacity: 0.25; }
        .floater.f5 { width: 110px; height: 110px; background: #ec4899;          top: 12%;    left: 55%;    animation-duration: 15s; animation-delay: -5s; opacity: 0.2; }

        @keyframes float-y {
            0%, 100% { transform: translateY(0)    translateX(0); }
            33%      { transform: translateY(-30px) translateX(15px); }
            66%      { transform: translateY(20px)  translateX(-15px); }
        }

        .hero-grid {
            position: relative; z-index: 1;
            display: grid;
            grid-template-columns: 1fr;
            text-align: center;
            max-width: 760px;
            margin: 0 auto;
        }
        .badge {
            display: inline-flex; align-items: center; gap: 0.4rem;
            background: rgba(255,255,255,0.7);
            border: 1px solid var(--edge);
            padding: 0.4rem 0.85rem; border-radius: 999px;
            font-size: 0.78rem; color: var(--dim); font-weight: 500;
            margin: 0 auto 1.4rem;
            backdrop-filter: blur(6px);
        }
        .badge .dot { width: 7px; height: 7px; background: var(--success); border-radius: 50%; }
        h1.title {
            font-size: clamp(2.2rem, 5.5vw, 3.6rem);
            font-weight: 800; line-height: 1.05;
            margin-bottom: 1.2rem;
        }
        .grad-text {
            background: linear-gradient(120deg, var(--primary), var(--terra));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .hero-sub { font-size: 1.1rem; color: var(--dim); max-width: 580px; margin: 0 auto 2rem; }
        .cta-row { display: flex; gap: 0.8rem; flex-wrap: wrap; justify-content: center; }
        .btn {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.9rem 1.5rem; border-radius: 12px;
            font-weight: 600; font-size: 0.95rem;
            transition: transform 0.15s, box-shadow 0.2s;
            cursor: pointer; border: none;
        }
        .btn svg { width: 18px; height: 18px; }
        .btn-primary { background: var(--primary); color: white; box-shadow: 0 4px 14px rgba(99,102,241,0.25); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(99,102,241,0.35); }
        .btn-secondary { background: var(--card); color: var(--ink); border: 1px solid var(--edge); }
        .btn-secondary:hover { border-color: var(--primary); color: var(--primary); }

        /* SECTIONS */
        section { padding: 4rem 0; position: relative; }
        .section-title { text-align: center; margin-bottom: 3rem; }
        .section-title h2 {
            font-size: clamp(1.7rem, 3.5vw, 2.4rem);
            font-weight: 700; margin-bottom: 0.6rem;
        }
        .section-title p { color: var(--dim); max-width: 560px; margin: 0 auto; }

        /* FEATURES */
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.2rem;
        }
        .feature-card {
            background: var(--card);
            border: 1px solid var(--edge);
            border-radius: 18px;
            padding: 1.6rem;
            transition: transform 0.2s, box-shadow 0.25s, border-color 0.2s;
        }
        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 28px rgba(28,25,23,0.08);
            border-color: rgba(99,102,241,0.3);
        }
        .feature-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 1rem;
        }
        .feature-icon svg { width: 22px; height: 22px; }
        .feature-card h3 { font-size: 1.05rem; margin-bottom: 0.4rem; font-weight: 600; }
        .feature-card p { color: var(--dim); font-size: 0.92rem; }

        /* HIGHLIGHT (offline) */
        .highlight {
            background: linear-gradient(135deg, var(--primary), var(--primary-dk));
            color: white;
            border-radius: 24px;
            padding: 3rem 2rem;
            text-align: center;
            margin: 2rem 0;
            position: relative;
            overflow: hidden;
        }
        .highlight-icon {
            width: 64px; height: 64px;
            background: rgba(255,255,255,0.18);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.2rem;
        }
        .highlight-icon svg { width: 30px; height: 30px; color: white; }
        .highlight h2 { font-size: 1.9rem; margin-bottom: 0.8rem; }
        .highlight p { font-size: 1.05rem; opacity: 0.9; max-width: 600px; margin: 0 auto 1.5rem; }
        .highlight-stats {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;
            max-width: 520px; margin: 2rem auto 0;
        }
        .highlight-stat-num { font-size: 2.2rem; font-weight: 800; font-family: 'Outfit'; }
        .highlight-stat-lbl { font-size: 0.82rem; opacity: 0.85; }
        @media (max-width: 540px) { .highlight-stats { grid-template-columns: 1fr; } }

        /* ROLES */
        .roles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 1.2rem;
        }
        .role-card {
            background: var(--card);
            border: 1px solid var(--edge);
            border-radius: 16px;
            padding: 1.6rem;
        }
        .role-icon {
            width: 52px; height: 52px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 1rem;
        }
        .role-icon svg { width: 26px; height: 26px; }
        .role-card h3 { font-size: 1.05rem; margin-bottom: 0.3rem; }
        .role-card p { color: var(--dim); font-size: 0.9rem; }

        /* CTA */
        .cta-section {
            text-align: center;
            background: var(--card);
            border: 1px solid var(--edge);
            border-radius: 24px;
            padding: 3.5rem 2rem;
            margin: 2rem 0 4rem;
        }
        .cta-section h2 { font-size: 1.9rem; margin-bottom: 0.8rem; }
        .cta-section p { color: var(--dim); margin-bottom: 1.6rem; }

        /* FOOTER */
        footer {
            border-top: 1px solid var(--edge);
            padding: 2rem 0;
            color: var(--ghost);
            font-size: 0.85rem;
            text-align: center;
        }
        footer a { color: var(--dim); }
        footer a:hover { color: var(--primary); }
        .footer-links { display: flex; justify-content: center; gap: 1.5rem; margin-top: 0.8rem; flex-wrap: wrap; }

        /* ────── RESPONSIVE MOBILE ────── */
        @media (max-width: 768px) {
            .container { padding: 0 1rem; }
            .hero { padding: 4rem 0 3rem; }
            section { padding: 3rem 0; }
            .section-title { margin-bottom: 2rem; }
            .feature-card { padding: 1.4rem; }
            .feature-grid { gap: 1rem; }
            .highlight { padding: 2.5rem 1.5rem; }
            .highlight h2 { font-size: 1.5rem; }
            .cta-section { padding: 2.5rem 1.5rem; }
            .cta-section h2 { font-size: 1.5rem; }
            .floater.f1 { width: 200px; height: 200px; }
            .floater.f2 { width: 160px; height: 160px; }
            .floater.f3 { width: 140px; height: 140px; }
            .floater.f4, .floater.f5 { width: 100px; height: 100px; }
        }

        @media (max-width: 480px) {
            .hero { padding: 3rem 0 2.5rem; }
            h1.title { line-height: 1.1; }
            .hero-sub { font-size: 1rem; }
            .btn { padding: 0.75rem 1.2rem; font-size: 0.9rem; flex: 1; justify-content: center; }
            .cta-row { flex-direction: column; }
            .cta-row .btn { width: 100%; }
            .feature-card { padding: 1.2rem; border-radius: 14px; }
            .feature-icon { width: 42px; height: 42px; }
            .feature-icon svg { width: 20px; height: 20px; }
            .feature-card h3 { font-size: 1rem; }
            .feature-card p { font-size: 0.88rem; }
            .role-card { padding: 1.3rem; }
            .role-icon { width: 46px; height: 46px; }
            .role-icon svg { width: 22px; height: 22px; }
            .highlight { padding: 2rem 1.2rem; border-radius: 18px; }
            .highlight-icon { width: 52px; height: 52px; }
            .highlight-icon svg { width: 24px; height: 24px; }
            .highlight-stat-num { font-size: 1.8rem; }
            .cta-section { padding: 2rem 1.2rem; border-radius: 18px; }
            .nav { padding: 0.7rem 0; }
            .brand { font-size: 0.95rem; }
            .nav-cta { padding: 0.5rem 0.9rem; font-size: 0.85rem; }
            footer { padding: 1.5rem 0; }
            .footer-links { gap: 1rem; }
        }

        /* Réduit l'animation pour les utilisateurs sensibles au mouvement */
        @media (prefers-reduced-motion: reduce) {
            .floater { animation: none; }
        }
    </style>
</head>
<body>

{{-- ─── HEADER ─── --}}
<header>
    <div class="container nav">
        <div class="brand">
            <div class="brand-icon">
                <svg viewBox="0 0 24 24" class="icon"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/></svg>
            </div>
            <span>Gextimo</span>
        </div>
        <nav class="nav-links">
            <a href="#fonctionnalites">Fonctionnalités</a>
            <a href="#offline">Mode hors-ligne</a>
            <a href="#equipe">Pour qui ?</a>
        </nav>
        <a href="#telecharger" class="nav-cta">Commencer</a>
    </div>
</header>

{{-- ─── HERO ─── --}}
<section class="hero">
    {{-- Floaters opaques en background --}}
    <div class="hero-bg-shapes">
        <div class="floater f1"></div>
        <div class="floater f2"></div>
        <div class="floater f3"></div>
        <div class="floater f4"></div>
        <div class="floater f5"></div>
    </div>

    <div class="container">
        <div class="hero-grid">
            <div class="badge"><span class="dot"></span> Disponible sur Android et Web</div>
            <h1 class="title">L'atelier de couture de demain — <span class="grad-text">tient dans votre poche</span>.</h1>
            <p class="hero-sub">Gextimo accompagne les couturiers africains au quotidien : clients, commandes, mesures, paiements, équipe. Tout au même endroit, même quand vous êtes hors-ligne.</p>
            <div class="cta-row">
                <a href="#telecharger" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" class="icon"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                    Télécharger l'app
                </a>
                <a href="#fonctionnalites" class="btn btn-secondary">Voir les fonctionnalités</a>
            </div>
        </div>
    </div>
</section>

{{-- ─── FONCTIONNALITÉS ─── --}}
<section id="fonctionnalites">
    <div class="container">
        <div class="section-title">
            <h2>Tout ce dont vous avez besoin</h2>
            <p>Pensé pour le quotidien d'un atelier de couture en Afrique de l'Ouest.</p>
        </div>
        <div class="feature-grid">

            <div class="feature-card">
                <div class="feature-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
                    <svg viewBox="0 0 24 24" class="icon"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <h3>Vos clients organisés</h3>
                <p>Profils avec photo, téléphone, statut VIP. Recherche instantanée. Anti-doublon automatique.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background: rgba(245,158,11,0.12); color: var(--accent);">
                    <svg viewBox="0 0 24 24" class="icon"><path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4L5.3 2.7a2.41 2.41 0 0 1 3.4 0Z"/><path d="m14.5 12.5 2-2"/><path d="m11.5 9.5 2-2"/><path d="m8.5 6.5 2-2"/><path d="m17.5 15.5 2-2"/></svg>
                </div>
                <h3>Mesures par vêtement</h3>
                <p>Carnets de mesures personnalisés selon le modèle (boubou, robe, pantalon…). Export PDF en un clic.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background: rgba(194,65,12,0.12); color: var(--terra);">
                    <svg viewBox="0 0 24 24" class="icon"><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M9 14l2 2 4-4"/></svg>
                </div>
                <h3>Commandes maîtrisées</h3>
                <p>Statuts en temps réel, photos du tissu, dates de livraison, alertes en cas de retard.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background: rgba(5,150,105,0.12); color: var(--success);">
                    <svg viewBox="0 0 24 24" class="icon"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                </div>
                <h3>Paiements & caisse</h3>
                <p>Encaissez par espèces, mobile money ou carte. Suivi des avances, soldes, et exports comptables.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background: rgba(34,197,94,0.12); color: #22c55e;">
                    <svg viewBox="0 0 24 24" class="icon"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                </div>
                <h3>WhatsApp intégré</h3>
                <p>Envoyez factures, rappels de livraison J-2, et confirmations directement depuis l'app.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background: rgba(168,85,247,0.12); color: #a855f7;">
                    <svg viewBox="0 0 24 24" class="icon"><circle cx="13.5" cy="6.5" r=".5"/><circle cx="17.5" cy="10.5" r=".5"/><circle cx="8.5" cy="7.5" r=".5"/><circle cx="6.5" cy="12.5" r=".5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>
                </div>
                <h3>Catalogue de modèles</h3>
                <p>Vos vêtements types (templates) avec photos, mesures requises et tarifs. Réutilisable à l'infini.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background: rgba(14,165,233,0.12); color: #0ea5e9;">
                    <svg viewBox="0 0 24 24" class="icon"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                </div>
                <h3>Fidélité & points</h3>
                <p>Vos clients fidèles gagnent des points. Échangez-les contre des jours d'abonnement gratuits.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background: rgba(236,72,153,0.12); color: #ec4899;">
                    <svg viewBox="0 0 24 24" class="icon"><path d="M3 21V8l9-5 9 5v13"/><path d="M9 22V12h6v10"/></svg>
                </div>
                <h3>Plusieurs ateliers</h3>
                <p>Gérez jusqu'à 7 ateliers depuis le même compte avec une vue consolidée.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background: rgba(220,38,38,0.12); color: #dc2626;">
                    <svg viewBox="0 0 24 24" class="icon"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <h3>Sécurité & rôles</h3>
                <p>Gérant, assistant, lecteur — chacun ses droits. Vos employés ne voient que ce que vous décidez.</p>
            </div>

        </div>
    </div>
</section>

{{-- ─── OFFLINE ─── --}}
<section id="offline">
    <div class="container">
        <div class="highlight">
            <div class="highlight-icon">
                <svg viewBox="0 0 24 24" class="icon"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>
            </div>
            <h2>L'application qui marche sans Internet</h2>
            <p>Gextimo fonctionne <strong>30 jours sans connexion</strong>. Vos données sont stockées sur votre téléphone et se synchronisent automatiquement dès que la connexion revient.</p>
            <div class="highlight-stats">
                <div>
                    <div class="highlight-stat-num">30 j</div>
                    <div class="highlight-stat-lbl">d'autonomie offline</div>
                </div>
                <div>
                    <div class="highlight-stat-num">100%</div>
                    <div class="highlight-stat-lbl">de vos données protégées</div>
                </div>
                <div>
                    <div class="highlight-stat-num">Auto</div>
                    <div class="highlight-stat-lbl">synchro au retour Wi-Fi</div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ─── ROLES ─── --}}
<section id="equipe">
    <div class="container">
        <div class="section-title">
            <h2>Pour vous et votre équipe</h2>
            <p>Chaque rôle a ses droits, vous gardez le contrôle.</p>
        </div>
        <div class="roles-grid">
            <div class="role-card">
                <div class="role-icon" style="background: rgba(245,158,11,0.12); color: var(--accent);">
                    <svg viewBox="0 0 24 24" class="icon"><polygon points="12 2 19 9 16 21 8 21 5 9 12 2"/></svg>
                </div>
                <h3>Le Gérant</h3>
                <p>Vous, ou la personne qui dirige l'atelier. Accès complet à tout : clients, finances, équipe, abonnement.</p>
            </div>
            <div class="role-card">
                <div class="role-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
                    <svg viewBox="0 0 24 24" class="icon"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/></svg>
                </div>
                <h3>L'Assistant</h3>
                <p>Crée et modifie clients, commandes, mesures. Pas d'accès aux finances ni aux paramètres sensibles.</p>
            </div>
            <div class="role-card">
                <div class="role-icon" style="background: rgba(168,85,247,0.12); color: #a855f7;">
                    <svg viewBox="0 0 24 24" class="icon"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </div>
                <h3>Le Membre</h3>
                <p>Consultation seule. Idéal pour les apprentis ou la consultation en boutique.</p>
            </div>
        </div>
    </div>
</section>

{{-- ─── CTA ─── --}}
<section id="telecharger">
    <div class="container">
        <div class="cta-section">
            <h2>Prêt à moderniser votre atelier ?</h2>
            <p>14 jours d'essai gratuit, niveau Premium inclus. Aucun engagement.</p>
            <a href="#" class="btn btn-primary">Démarrer maintenant</a>
        </div>
    </div>
</section>

{{-- ─── FOOTER ─── --}}
<footer>
    <div class="container">
        <div>© {{ date('Y') }} Gextimo — Conçu en Afrique, pour l'Afrique.</div>
        <div class="footer-links">
            <a href="#fonctionnalites">Fonctionnalités</a>
            <a href="#offline">Mode hors-ligne</a>
            <a href="#equipe">Rôles</a>
            <a href="mailto:contact.gextimo@novafriq.africa">Nous contacter</a>
        </div>
    </div>
</footer>

</body>
</html>
