<x-layouts::auth :title="__('Reset password')">
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

        /* Card — tighter padding to prevent scroll */
        .classly-card {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 440px;
            background: rgba(8,18,44,0.75);
            border: 1px solid rgba(56,189,248,0.18);
            border-radius: 22px;
            padding: 1.75rem 2.25rem 1.75rem;
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

        /* Branding — tighter */
        .classly-brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            margin-bottom: 1.1rem;
            animation: fadeUp 0.5s 0.1s both;
        }

        .classly-logo-ring {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(24,90,219,0.3), rgba(56,189,248,0.15));
            border: 1px solid rgba(56,189,248,0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 16px rgba(56,189,248,0.2), inset 0 1px 0 rgba(255,255,255,0.08);
            margin-bottom: 0.1rem;
        }

        .classly-logo-ring svg {
            width: 20px;
            height: 20px;
            color: #7dd3fc;
            filter: drop-shadow(0 0 5px rgba(125,211,252,0.6));
        }

        .classly-wordmark {
            font-size: 1.3rem;
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
            font-size: 0.56rem;
            font-weight: 400;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: rgba(148,184,220,0.6);
        }

        /* Heading */
        .classly-heading {
            text-align: center;
            margin-bottom: 1.1rem;
            animation: fadeUp 0.5s 0.18s both;
        }

        .classly-heading h1 {
            font-size: 1.15rem;
            font-weight: 600;
            color: #e0f2fe;
            margin: 0 0 0.25rem;
        }

        .classly-heading p {
            font-size: 0.76rem;
            color: rgba(148,184,220,0.68);
            line-height: 1.5;
            max-width: 300px;
            margin: 0 auto;
        }

        /* Form */
        .classly-form {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            animation: fadeUp 0.5s 0.26s both;
        }

        /* ── Strength indicator ── */
        .classly-strength-wrap {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
        }

        .classly-strength-bar-track {
            display: flex;
            gap: 4px;
            height: 3px;
        }

        .classly-strength-seg {
            flex: 1;
            border-radius: 99px;
            background: rgba(255,255,255,0.07);
            transition: background 0.35s cubic-bezier(0.22,1,0.36,1), box-shadow 0.35s;
        }

        .classly-strength-seg.weak   { background: #f87171; box-shadow: 0 0 5px rgba(248,113,113,0.5); }
        .classly-strength-seg.medium { background: #fbbf24; box-shadow: 0 0 5px rgba(251,191,36,0.5); }
        .classly-strength-seg.strong { background: #34d399; box-shadow: 0 0 5px rgba(52,211,153,0.5); }

        .classly-strength-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .classly-strength-label {
            font-size: 0.65rem;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: rgba(148,184,220,0.38);
            transition: color 0.3s;
        }

        .classly-strength-label.weak   { color: #f87171; }
        .classly-strength-label.medium { color: #fbbf24; }
        .classly-strength-label.strong { color: #34d399; }

        /* Requirements — compact 2-col grid */
        .classly-req-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.22rem 0.6rem;
            padding: 0.6rem 0.85rem;
            background: rgba(8,22,58,0.5);
            border: 1px solid rgba(56,189,248,0.1);
            border-radius: 9px;
        }

        .classly-req-item {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.67rem;
            color: rgba(148,184,220,0.45);
            transition: color 0.22s;
        }

        .classly-req-item.met { color: #34d399; }

        .classly-req-dot {
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: rgba(148,184,220,0.22);
            flex-shrink: 0;
            transition: background 0.22s, box-shadow 0.22s;
        }

        .classly-req-item.met .classly-req-dot {
            background: #34d399;
            box-shadow: 0 0 4px rgba(52,211,153,0.6);
        }

        /* Button */
        .classly-btn {
            width: 100%;
            padding: 0.65rem 1.5rem;
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
            margin-top: 0.1rem;
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

        .classly-btn:active { transform: translateY(0); }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(9px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Very small screens: shrink further */
        @media (max-height: 720px) {
            .classly-card { padding: 1.4rem 2rem; }
            .classly-brand { margin-bottom: 0.75rem; }
            .classly-heading { margin-bottom: 0.75rem; }
            .classly-logo-ring { width: 38px; height: 38px; }
            .classly-logo-ring svg { width: 17px; height: 17px; }
            .classly-req-list { padding: 0.45rem 0.75rem; }
        }

        @media (max-width: 480px) {
            .classly-card { padding: 1.5rem 1.25rem; }
            .classly-req-list { grid-template-columns: 1fr; }
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

            <div class="classly-heading">
                <h1>{{ __('Reset Your Password') }}</h1>
                <p>{{ __('Create a new secure password for your account.') }}</p>
            </div>

            <x-auth-session-status class="text-center" :status="session('status')" />

            <form method="POST" action="{{ route('password.update') }}" class="classly-form" id="classly-reset-form">
                @csrf
                <input type="hidden" name="token" value="{{ request()->route('token') }}">

                <flux:input
                    name="email"
                    value="{{ request('email') }}"
                    :label="__('Email')"
                    type="email"
                    required
                    autocomplete="email"
                />

                {{-- Password + strength --}}
                <div class="classly-strength-wrap">
                    <flux:input
                        id="classly-pw-field"
                        name="password"
                        :label="__('New Password')"
                        type="password"
                        required
                        autocomplete="new-password"
                        :placeholder="__('New password')"
                        viewable
                    />

                    <div>
                        <div class="classly-strength-bar-track">
                            <div class="classly-strength-seg" id="cly-s1"></div>
                            <div class="classly-strength-seg" id="cly-s2"></div>
                            <div class="classly-strength-seg" id="cly-s3"></div>
                        </div>
                        <div class="classly-strength-meta" style="margin-top:0.28rem;">
                            <span class="classly-strength-label" id="cly-label">Password strength</span>
                        </div>
                    </div>

                    <div class="classly-req-list">
                        <div class="classly-req-item" id="req-length"><div class="classly-req-dot"></div>8+ characters</div>
                        <div class="classly-req-item" id="req-upper"><div class="classly-req-dot"></div>Uppercase letter</div>
                        <div class="classly-req-item" id="req-lower"><div class="classly-req-dot"></div>Lowercase letter</div>
                        <div class="classly-req-item" id="req-number"><div class="classly-req-dot"></div>Number</div>
                        <div class="classly-req-item" id="req-special"><div class="classly-req-dot"></div>Special character</div>
                    </div>
                </div>

                <flux:input
                    name="password_confirmation"
                    :label="__('Confirm Password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Confirm password')"
                    viewable
                />

                <button type="submit" class="classly-btn" data-test="reset-password-button">
                    {{ __('Reset Password') }}
                </button>
            </form>

        </div>
    </div>

    <script>
    (function () {
        function init() {
            var input = document.querySelector('#classly-pw-field input[type="password"]')
                     || document.querySelector('input[name="password"]');
            if (!input) return;

            var s1 = document.getElementById('cly-s1');
            var s2 = document.getElementById('cly-s2');
            var s3 = document.getElementById('cly-s3');
            var lbl = document.getElementById('cly-label');

            var reqs = {
                length:  document.getElementById('req-length'),
                upper:   document.getElementById('req-upper'),
                lower:   document.getElementById('req-lower'),
                number:  document.getElementById('req-number'),
                special: document.getElementById('req-special')
            };

            function toggle(el, met) {
                met ? el.classList.add('met') : el.classList.remove('met');
            }

            input.addEventListener('input', function () {
                var v = this.value;
                var hasLen = v.length >= 8;
                var hasUp  = /[A-Z]/.test(v);
                var hasLo  = /[a-z]/.test(v);
                var hasNum = /\d/.test(v);
                var hasSp  = /[^A-Za-z0-9]/.test(v);

                toggle(reqs.length,  hasLen);
                toggle(reqs.upper,   hasUp);
                toggle(reqs.lower,   hasLo);
                toggle(reqs.number,  hasNum);
                toggle(reqs.special, hasSp);

                var score = [hasLen, hasUp, hasLo, hasNum, hasSp].filter(Boolean).length;

                [s1, s2, s3].forEach(function(s) { s.className = 'classly-strength-seg'; });
                lbl.className = 'classly-strength-label';

                if (!v.length) { lbl.textContent = 'Password strength'; return; }

                if (score <= 2) {
                    s1.classList.add('weak');
                    lbl.classList.add('weak');
                    lbl.textContent = 'Weak';
                } else if (score <= 3) {
                    s1.classList.add('medium'); s2.classList.add('medium');
                    lbl.classList.add('medium');
                    lbl.textContent = 'Medium';
                } else {
                    s1.classList.add('strong'); s2.classList.add('strong'); s3.classList.add('strong');
                    lbl.classList.add('strong');
                    lbl.textContent = 'Strong';
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() { init(); setTimeout(init, 300); });
        } else {
            init(); setTimeout(init, 300);
        }
    })();
    </script>
</x-layouts::auth>