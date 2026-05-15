<x-layouts::auth :title="__('Confirm password')">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Space+Grotesk:wght@300;400;500;600&display=swap');

        /* ── Lock the entire page ── */
        html, body {
            height: 100% !important;
            overflow: hidden !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #060d1f !important;
        }

        body > *:not(script):not(style),
        .min-h-screen,
        [class*="min-h"] {
            min-height: unset !important;
            height: 100% !important;
            overflow: hidden !important;
            background: transparent !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        /* ── Full-screen canvas ── */
        .classly-auth-root {
            font-family: 'Outfit', sans-serif;
            position: fixed;
            inset: 0;
            background: #060d1f;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 1rem;
            z-index: 50;
        }

        .classly-blob-a {
            position: fixed;
            top: -15%;
            left: -8%;
            width: 55vw;
            height: 55vw;
            background: radial-gradient(circle, rgba(24,90,219,0.2) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            animation: driftA 18s ease-in-out infinite alternate;
            z-index: 0;
        }

        .classly-blob-b {
            position: fixed;
            bottom: -12%;
            right: -6%;
            width: 42vw;
            height: 42vw;
            background: radial-gradient(circle, rgba(56,189,248,0.11) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            animation: driftB 22s ease-in-out infinite alternate;
            z-index: 0;
        }

        @keyframes driftA {
            from { transform: translate(0,0) scale(1); }
            to   { transform: translate(4vw,3vh) scale(1.08); }
        }

        @keyframes driftB {
            from { transform: translate(0,0) scale(1); }
            to   { transform: translate(-3vw,-4vh) scale(1.06); }
        }

        .classly-auth-grid {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 1;
            background-image:
                linear-gradient(rgba(56,189,248,0.028) 1px, transparent 1px),
                linear-gradient(90deg, rgba(56,189,248,0.028) 1px, transparent 1px);
            background-size: 52px 52px;
            mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 40%, transparent 100%);
        }

        /* Card */
        .classly-card {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 410px;
            background: rgba(8,18,44,0.75);
            border: 1px solid rgba(56,189,248,0.18);
            border-radius: 22px;
            padding: 2.25rem 2.25rem 2rem;
            box-shadow:
                0 0 0 1px rgba(56,189,248,0.06),
                0 8px 40px rgba(0,0,0,0.55),
                0 0 80px rgba(24,90,219,0.08),
                inset 0 1px 0 rgba(255,255,255,0.05);
            backdrop-filter: blur(28px) saturate(1.4);
            -webkit-backdrop-filter: blur(28px) saturate(1.4);
            animation: cardReveal 0.6s cubic-bezier(0.22,1,0.36,1) both;
        }

        .classly-card::before {
            content: '';
            position: absolute;
            top: -1px;
            left: 20%;
            right: 20%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(56,189,248,0.7), rgba(96,165,250,0.5), transparent);
            border-radius: 999px;
        }

        @keyframes cardReveal {
            from { opacity: 0; transform: translateY(18px) scale(0.98); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Branding */
        .classly-brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.3rem;
            margin-bottom: 1.4rem;
            animation: fadeUp 0.5s 0.1s both;
        }

        .classly-logo-ring {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(24,90,219,0.3), rgba(56,189,248,0.15));
            border: 1px solid rgba(56,189,248,0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 18px rgba(56,189,248,0.2), inset 0 1px 0 rgba(255,255,255,0.08);
            margin-bottom: 0.15rem;
            position: relative;
        }

        .classly-logo-ring svg {
            width: 22px;
            height: 22px;
            color: #7dd3fc;
            filter: drop-shadow(0 0 5px rgba(125,211,252,0.6));
        }

        /* Pulsing security ring */
        .classly-logo-ring::after {
            content: '';
            position: absolute;
            inset: -5px;
            border-radius: 50%;
            border: 1px solid rgba(56,189,248,0.18);
            animation: secPulse 3s ease-in-out infinite;
        }

        @keyframes secPulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50%       { transform: scale(1.1); opacity: 0.15; }
        }

        .classly-wordmark {
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            background: linear-gradient(135deg, #e0f2fe, #7dd3fc, #60a5fa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
        }

        .classly-divider-line {
            width: 28px;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(56,189,248,0.45), transparent);
            margin: 0.08rem auto;
        }

        .classly-subword {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.57rem;
            font-weight: 400;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: rgba(148,184,220,0.6);
        }

        /* Security badge */
        .classly-security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.38rem 0.85rem;
            background: rgba(56,189,248,0.06);
            border: 1px solid rgba(56,189,248,0.14);
            border-radius: 999px;
            margin: 0 auto 1.25rem;
            width: fit-content;
            animation: fadeUp 0.5s 0.16s both;
        }

        .classly-security-badge svg {
            width: 12px;
            height: 12px;
            color: #7dd3fc;
        }

        .classly-security-badge span {
            font-size: 0.64rem;
            font-weight: 500;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(125,211,252,0.75);
        }

        /* Heading */
        .classly-heading {
            text-align: center;
            margin-bottom: 1.4rem;
            animation: fadeUp 0.5s 0.22s both;
        }

        .classly-heading h1 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #e0f2fe;
            margin: 0 0 0.28rem;
        }

        .classly-heading p {
            font-size: 0.78rem;
            color: rgba(148,184,220,0.68);
            line-height: 1.55;
            max-width: 290px;
            margin: 0 auto;
        }

        /* Form */
        .classly-form {
            display: flex;
            flex-direction: column;
            gap: 0.9rem;
            animation: fadeUp 0.5s 0.28s both;
        }

        /* Button */
        .classly-btn {
            width: 100%;
            padding: 0.68rem 1.5rem;
            border-radius: 10px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.875rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            color: #fff;
            background: linear-gradient(135deg, #1d4ed8, #2563eb, #3b82f6);
            border: 1px solid rgba(96,165,250,0.4);
            box-shadow: 0 0 18px rgba(37,99,235,0.35), 0 4px 12px rgba(0,0,0,0.3);
            cursor: pointer;
            transition: all 0.22s cubic-bezier(0.22,1,0.36,1);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
        }

        .classly-btn svg {
            width: 14px;
            height: 14px;
            flex-shrink: 0;
            transition: transform 0.2s;
        }

        .classly-btn::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.08), transparent);
            border-radius: inherit;
        }

        .classly-btn:hover {
            background: linear-gradient(135deg, #2563eb, #3b82f6, #60a5fa);
            box-shadow: 0 0 26px rgba(59,130,246,0.5), 0 6px 18px rgba(0,0,0,0.3);
            transform: translateY(-1px);
        }

        .classly-btn:hover svg { transform: translateX(2px); }
        .classly-btn:active { transform: translateY(0); }

        /* Security note */
        .classly-security-note {
            text-align: center;
            margin-top: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            font-size: 0.68rem;
            color: rgba(148,184,220,0.35);
            animation: fadeUp 0.5s 0.38s both;
        }

        .classly-security-note svg {
            width: 10px;
            height: 10px;
            flex-shrink: 0;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(9px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 480px) {
            .classly-card { padding: 1.75rem 1.5rem; }
        }
    </style>

    <div class="classly-auth-root">
        <div class="classly-blob-a"></div>
        <div class="classly-blob-b"></div>
        <div class="classly-auth-grid"></div>

        <div class="classly-card">

            <div class="classly-brand">
                <div class="classly-logo-ring">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <div class="classly-wordmark">CLASSLY</div>
                <div class="classly-divider-line"></div>
                <div class="classly-subword">Professional Academy of the Philippines</div>
            </div>

            <div class="classly-security-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                <span>Secure Area</span>
            </div>

            <div class="classly-heading">
                <h1>{{ __('Confirm Your Password') }}</h1>
                <p>{{ __('For security purposes, please confirm your password before continuing.') }}</p>
            </div>

            <x-auth-session-status class="text-center" :status="session('status')" />

            <form method="POST" action="{{ route('password.confirm.store') }}" class="classly-form">
                @csrf
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Enter your password')"
                    viewable
                />
                <button type="submit" class="classly-btn" data-test="confirm-password-button">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    {{ __('Confirm & Continue') }}
                </button>
            </form>

            <div class="classly-security-note">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <span>This session is encrypted and protected.</span>
            </div>

        </div>
    </div>
</x-layouts::auth>