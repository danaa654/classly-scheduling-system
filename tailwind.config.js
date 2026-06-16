/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],

  // Required for the dark mode toggle to work via class (e.g. <html class="dark">).
  // Without this, Tailwind falls back to the OS media query and the UI toggle does nothing.
  darkMode: 'class',

  theme: {
    extend: {

      fontFamily: {
        // ONE font family across the entire app.
        // Previously: ['Inter', 'Plus Jakarta Sans', ...] — two different fonts competing,
        // whichever loads first "wins", causing page-to-page inconsistency.
        // Plus Jakarta Sans is chosen because its 800–900 weights (font-black, font-extrabold)
        // render crisply for the uppercase tracking labels used ~700 times across the codebase.
        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],

        // JetBrains Mono for EDP codes, emails, timestamps, and grid values.
        mono: ['"JetBrains Mono"', 'ui-monospace', 'SFMono-Regular', 'monospace'],
      },

      fontSize: {
        // Named tokens for the four sub-12px sizes that appear ~800 times
        // across all views as arbitrary [Npx] values (text-[8px], text-[9px], text-[10px]).
        // Naming them here makes the scale intentional and consistent system-wide.
        //
        //  Usage       →  token        old arbitrary class
        //  ─────────────────────────────────────────────────────────
        //  Micro chips →  text-2xs     text-[8px]
        //  Sub-labels  →  text-micro   text-[9px]   (metadata, badges)
        //  Labels      →  text-label   text-[10px]  ← most common (257 uses)
        //  Mid-size    →  text-mid     text-[11px]  (between xs and sm)
        //
        // Tailwind's built-in sizes still apply:
        //   text-xs  = 12px  |  text-sm = 14px  |  text-base = 16px  ...
        '2xs':   ['0.5rem',    { lineHeight: '0.75rem'  }],  // 8px
        'micro': ['0.5625rem', { lineHeight: '0.875rem' }],  // 9px
        'label': ['0.625rem',  { lineHeight: '1rem'     }],  // 10px
        'mid':   ['0.6875rem', { lineHeight: '1rem'     }],  // 11px
      },

    },
  },
  plugins: [],
}