<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CouturePro API</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:      #111113;
            --surface: #18181b;
            --border:  #27272a;
            --text:    #e4e4e7;
            --muted:   #71717a;
            --accent:  #a78bfa;
            --green:   #4ade80;
            --radius:  8px;
            --mono:    'Menlo', 'Consolas', 'Courier New', monospace;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            min-height: 100vh;
            padding: 64px 24px 80px;
            display: flex;
            justify-content: center;
        }

        .page { width: 100%; max-width: 760px; }

        /* ── Header ── */
        header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 48px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 36px;
            height: 36px;
            color: var(--accent);
            flex-shrink: 0;
        }

        .brand-name {
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: -.2px;
            color: var(--text);
        }

        .brand-sub {
            font-size: .75rem;
            color: var(--muted);
            margin-top: 1px;
        }

        .status {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: .75rem;
            color: var(--green);
            padding-top: 4px;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--green);
            animation: blink 3s ease-in-out infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50%       { opacity: .35; }
        }

        /* ── Section ── */
        section { margin-bottom: 40px; }

        .section-label {
            font-size: .7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--muted);
            margin-bottom: 12px;
        }

        /* ── Base URL ── */
        .base-url-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 14px 18px;
            font-family: var(--mono);
            font-size: .85rem;
            color: var(--accent);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .base-url-box .label {
            color: var(--muted);
            font-size: .75rem;
            flex-shrink: 0;
        }

        /* ── Resources table ── */
        .resource-table {
            width: 100%;
            border-collapse: collapse;
        }

        .resource-table thead th {
            text-align: left;
            font-size: .7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: var(--muted);
            padding: 0 12px 10px 12px;
            border-bottom: 1px solid var(--border);
        }

        .resource-table thead th:first-child { padding-left: 0; }

        .resource-table tbody tr {
            border-bottom: 1px solid var(--border);
        }

        .resource-table tbody tr:last-child { border-bottom: none; }

        .resource-table td {
            padding: 12px;
            vertical-align: top;
        }

        .resource-table td:first-child {
            padding-left: 0;
            width: 28px;
            color: var(--muted);
        }

        .res-name {
            font-weight: 600;
            font-size: .85rem;
            color: var(--text);
        }

        .res-desc {
            font-size: .78rem;
            color: var(--muted);
            margin-top: 2px;
        }

        .endpoints-col {
            font-family: var(--mono);
            font-size: .75rem;
            color: var(--muted);
            line-height: 1.9;
        }

        .endpoints-col .m {
            display: inline-block;
            width: 36px;
            color: var(--accent);
            opacity: .7;
        }

        /* ── Auth note ── */
        .auth-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 14px 18px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: .82rem;
        }

        .auth-box svg { flex-shrink: 0; margin-top: 1px; color: var(--muted); }

        .auth-box strong { color: var(--text); }

        .auth-box code {
            font-family: var(--mono);
            font-size: .78rem;
            color: var(--accent);
        }

        /* ── Curl example ── */
        .code-block {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 18px 20px;
            font-family: var(--mono);
            font-size: .78rem;
            line-height: 1.8;
            overflow-x: auto;
            color: #a1a1aa;
        }

        .code-block .cmd  { color: #e4e4e7; }
        .code-block .flag { color: var(--accent); opacity: .8; }
        .code-block .val  { color: #86efac; }
        .code-block .cmt  { color: #52525b; }

        /* ── Footer ── */
        footer {
            margin-top: 64px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            font-size: .72rem;
            color: var(--muted);
            border-top: 1px solid var(--border);
            padding-top: 20px;
        }

        .meta { display: flex; gap: 16px; flex-wrap: wrap; }
    </style>
</head>
<body>
<div class="page">

    <!-- Header -->
    <header>
        <div class="brand">
            <svg class="brand-icon" viewBox="0 0 36 36" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <!-- needle -->
                <line x1="10" y1="26" x2="26" y2="10"/>
                <path d="M26 10 c1-2 3-2 3 0 s-1 3-3 3"/>
                <ellipse cx="11.5" cy="24.5" rx="2" ry="1.2" transform="rotate(-45 11.5 24.5)"/>
                <!-- thread arc -->
                <path d="M14 28 Q20 20 28 14" stroke-dasharray="2 3" opacity=".5"/>
            </svg>
            <div>
                <div class="brand-name">CouturePro</div>
                <div class="brand-sub">REST API — v1.0.0</div>
            </div>
        </div>
        <div class="status">
            <div class="status-dot"></div>
            Opérationnel
        </div>
    </header>

    <!-- Base URL -->
    <section>
        <div class="section-label">Base URL</div>
        <div class="base-url-box">
            <span class="label">HTTPS</span>
            {{ config('app.url') }}/api
        </div>
    </section>

    <!-- Authentication -->
    <section>
        <div class="section-label">Authentification</div>
        <div class="auth-box">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            <div>
                <strong>Bearer Token</strong> — Laravel Sanctum.<br>
                Toutes les routes sauf <code>/auth/*</code> et <code>/webhooks/*</code> requièrent
                <code>Authorization: Bearer {token}</code>.
            </div>
        </div>
    </section>

    <!-- Resources -->
    <section>
        <div class="section-label">Ressources</div>
        <table class="resource-table">
            <thead>
                <tr>
                    <th></th>
                    <th>Ressource</th>
                    <th>Endpoints</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                        </svg>
                    </td>
                    <td>
                        <div class="res-name">Auth</div>
                        <div class="res-desc">Inscription, OTP, connexion, récupération de compte</div>
                    </td>
                    <td class="endpoints-col">
                        <span class="m">POST</span>/auth/inscription<br>
                        <span class="m">POST</span>/auth/verifier-otp<br>
                        <span class="m">POST</span>/auth/login<br>
                        <span class="m">GET</span>/auth/me
                    </td>
                </tr>
                <tr>
                    <td>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </td>
                    <td>
                        <div class="res-name">Clients</div>
                        <div class="res-desc">CRUD, archivage, quotas par abonnement</div>
                    </td>
                    <td class="endpoints-col">
                        <span class="m">GET</span>/clients<br>
                        <span class="m">POST</span>/clients<br>
                        <span class="m">PUT</span>/clients/{id}<br>
                        <span class="m">DELETE</span>/clients/{id}
                    </td>
                </tr>
                <tr>
                    <td>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                    </td>
                    <td>
                        <div class="res-name">Commandes</div>
                        <div class="res-desc">Suivi des commandes par statut</div>
                    </td>
                    <td class="endpoints-col">
                        <span class="m">GET</span>/commandes<br>
                        <span class="m">POST</span>/commandes<br>
                        <span class="m">PUT</span>/commandes/{id}<br>
                        <span class="m">DELETE</span>/commandes/{id}
                    </td>
                </tr>
                <tr>
                    <td>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                        </svg>
                    </td>
                    <td>
                        <div class="res-name">Mesures</div>
                        <div class="res-desc">Mensurations par client et type de vêtement</div>
                    </td>
                    <td class="endpoints-col">
                        <span class="m">GET</span>/clients/{id}/mesures<br>
                        <span class="m">POST</span>/mesures<br>
                        <span class="m">PUT</span>/mesures/{id}<br>
                        <span class="m">DELETE</span>/mesures/{id}
                    </td>
                </tr>
                <tr>
                    <td>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
                        </svg>
                    </td>
                    <td>
                        <div class="res-name">Sync</div>
                        <div class="res-desc">Synchronisation offline-first, batch de 20 opérations max</div>
                    </td>
                    <td class="endpoints-col">
                        <span class="m">POST</span>/sync/push<br>
                        <span class="m">GET</span>/sync/pull
                    </td>
                </tr>
                <tr>
                    <td>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </td>
                    <td>
                        <div class="res-name">Paiements</div>
                        <div class="res-desc">Abonnements via FedaPay (XOF/FCFA), webhooks</div>
                    </td>
                    <td class="endpoints-col">
                        <span class="m">POST</span>/paiements/initier<br>
                        <span class="m">GET</span>/paiements/{id}/status<br>
                        <span class="m">POST</span>/webhooks/{provider}
                    </td>
                </tr>
            </tbody>
        </table>
    </section>

    <!-- Quick start -->
    <section>
        <div class="section-label">Exemple</div>
        <div class="code-block">
<span class="cmt"># Connexion</span>
<span class="cmd">curl</span> <span class="flag">-s -X</span> POST {{ config('app.url') }}/api/auth/login \
     <span class="flag">-H</span> <span class="val">"Content-Type: application/json"</span> \
     <span class="flag">-d</span> <span class="val">'{"telephone":"771234567","password":"secret"}'</span>

<span class="cmt"># Récupérer les clients</span>
<span class="cmd">curl</span> <span class="flag">-s</span> {{ config('app.url') }}/api/clients \
     <span class="flag">-H</span> <span class="val">"Authorization: Bearer {token}"</span>
        </div>
    </section>

    <footer>
        <span>CouturePro &mdash; Afrique de l'Ouest &mdash; XOF/FCFA</span>
        <div class="meta">
            <span>Laravel {{ app()->version() }}</span>
            <span>PHP {{ PHP_VERSION }}</span>
            <span>{{ config('app.env') }}</span>
        </div>
    </footer>

</div>
</body>
</html>
