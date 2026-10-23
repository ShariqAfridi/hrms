<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'HRMS Portal') - AxeCorp HRMS</title>
    @include('partials.security-lockdown')
    <!-- Global Session Expiry & CSRF Protection Interceptor -->
    <script>
        (function() {
            // Intercept standard fetch requests
            if (window.fetch) {
                const _originalFetch = window.fetch;
                window.fetch = async function(...args) {
                    try {
                        const response = await _originalFetch.apply(this, args);
                        if (response && response.status === 419) {
                            window.location.href = "{{ route('login') }}?expired=1";
                        }
                        return response;
                    } catch (err) {
                        throw err;
                    }
                };
            }
        })();
    </script>
    <!-- Immediate Theme Initialization (Default Dark, Persistent) -->
    <script>
        window.getHrmsTheme = function() {
            try {
                return localStorage.getItem('hrms_theme') || 'dark';
            } catch(e) {
                return 'dark';
            }
        };
        window.applyHrmsTheme = function(theme) {
            try {
                var isLight = (theme === 'light');
                if (isLight) {
                    document.documentElement.classList.add('theme-light', 'light');
                    document.documentElement.classList.remove('dark');
                    if (document.body) {
                        document.body.classList.add('theme-light', 'light');
                        document.body.classList.remove('dark');
                    }
                } else {
                    document.documentElement.classList.add('dark');
                    document.documentElement.classList.remove('theme-light', 'light');
                    if (document.body) {
                        document.body.classList.add('dark');
                        document.body.classList.remove('theme-light', 'light');
                    }
                }
            } catch(e) {}
        };

        // Run immediately before DOM paint
        window.applyHrmsTheme(window.getHrmsTheme());
    </script>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        },
                        secondary: {
                            50: '#f5f3ff',
                            100: '#ede9fe',
                            200: '#ddd6fe',
                            300: '#c4b5fd',
                            400: '#a78bfa',
                            500: '#8b5cf6',
                            600: '#7c3aed',
                            700: '#6d28d9',
                            800: '#5b21b6',
                            900: '#4c1d95',
                        },
                        slateSecondary: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
                        heading: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
                    }
                }
            }
        };
    </script>
    <!-- FontAwesome 6 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Flatpickr Date Picker CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap');

        /* Universal Inter Font Typography Across HRMS */
        html,
        body,
        h1, h2, h3, h4, h5, h6,
        p, span, div, a, button, input, select, textarea, label, table, th, td, blockquote, li, ul, ol {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
        }

        /* Preserve Monospace Font for Code, Clocks & Hashes */
        .font-mono,
        code,
        kbd,
        pre,
        samp {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace !important;
        }

        /* Preserve FontAwesome Icons */
        i[class*="fa-"],
        .fa,
        .fa-solid,
        .fa-regular,
        .fa-brands,
        .fa-duotone {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands", sans-serif !important;
        }

        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-active: #3730a3;
            --primary-light: #eef2ff;
            --primary-border: #c7d2fe;
            --primary-ring: rgba(79, 70, 229, 0.20);
            --primary-glow: rgba(79, 70, 229, 0.30);

            --secondary-accent: #6366f1;
            --secondary-accent-hover: #4f46e5;
            --secondary-accent-light: #f5f3ff;
            --secondary-accent-border: #ddd6fe;

            --secondary-neutral: #475569;
            --secondary-neutral-bg: #f1f5f9;
            --secondary-neutral-border: #cbd5e1;
            --secondary-neutral-hover: #e2e8f0;
        }

        [x-cloak] { display: none !important; }

        /* Custom Dark Theme Scrollbars */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #020617;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 9999px;
            border: 1px solid #020617;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #4f46e5;
        }
        * {
            scrollbar-width: thin;
            scrollbar-color: #334155 #020617;
        }

        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* =========================================================
           DATE & TIME PICKER ENHANCEMENTS (GLOBAL THEMED ICONS)
           ========================================================= */
        input[type="date"],
        input[type="time"] {
            color-scheme: dark;
            cursor: pointer;
        }

        /* Time Picker Themed Clock Icon (Indigo #818cf8 / #6366f1) */
        input[type="time"]::-webkit-calendar-picker-indicator {
            display: inline-block;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23818cf8' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'%3E%3C/circle%3E%3Cpolyline points='12 6 12 12 16 14'%3E%3C/polyline%3E%3C/svg%3E") !important;
            background-size: 16px 16px !important;
            background-repeat: no-repeat !important;
            background-position: center !important;
            width: 22px !important;
            height: 22px !important;
            cursor: pointer !important;
            opacity: 0.9 !important;
            border-radius: 6px !important;
            margin-left: 4px !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        input[type="time"]::-webkit-calendar-picker-indicator:hover {
            opacity: 1 !important;
            transform: scale(1.15) !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23a5b4fc' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'%3E%3C/circle%3E%3Cpolyline points='12 6 12 12 16 14'%3E%3C/polyline%3E%3C/svg%3E") !important;
            filter: drop-shadow(0 0 6px rgba(129, 140, 248, 0.8)) !important;
            background-color: rgba(99, 102, 241, 0.18) !important;
        }

        /* Native Date Picker Themed Calendar Icon (Indigo #818cf8 / #6366f1) */
        input[type="date"]::-webkit-calendar-picker-indicator {
            display: inline-block;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23818cf8' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'%3E%3C/rect%3E%3Cline x1='16' y1='2' x2='16' y2='6'%3E%3C/line%3E%3Cline x1='8' y1='2' x2='8' y2='6'%3E%3C/line%3E%3Cline x1='3' y1='10' x2='21' y2='10'%3E%3C/line%3E%3C/svg%3E") !important;
            background-size: 16px 16px !important;
            background-repeat: no-repeat !important;
            background-position: center !important;
            width: 22px !important;
            height: 22px !important;
            cursor: pointer !important;
            opacity: 0.9 !important;
            border-radius: 6px !important;
            margin-left: 4px !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        input[type="date"]::-webkit-calendar-picker-indicator:hover {
            opacity: 1 !important;
            transform: scale(1.15) !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23a5b4fc' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'%3E%3C/rect%3E%3Cline x1='16' y1='2' x2='16' y2='6'%3E%3C/line%3E%3Cline x1='8' y1='2' x2='8' y2='6'%3E%3C/line%3E%3Cline x1='3' y1='10' x2='21' y2='10'%3E%3C/line%3E%3C/svg%3E") !important;
            filter: drop-shadow(0 0 6px rgba(129, 140, 248, 0.8)) !important;
            background-color: rgba(99, 102, 241, 0.18) !important;
        }

        /* Light Theme Overrides */
        html.theme-light input[type="time"],
        body.theme-light input[type="time"],
        html.theme-light input[type="date"],
        body.theme-light input[type="date"] {
            color-scheme: light;
        }
        html.theme-light input[type="time"]::-webkit-calendar-picker-indicator,
        body.theme-light input[type="time"]::-webkit-calendar-picker-indicator {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234f46e5' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'%3E%3C/circle%3E%3Cpolyline points='12 6 12 12 16 14'%3E%3C/polyline%3E%3C/svg%3E") !important;
            opacity: 0.85 !important;
        }
        html.theme-light input[type="time"]::-webkit-calendar-picker-indicator:hover,
        body.theme-light input[type="time"]::-webkit-calendar-picker-indicator:hover {
            opacity: 1 !important;
            filter: drop-shadow(0 0 4px rgba(79, 70, 229, 0.5)) !important;
            background-color: rgba(79, 70, 229, 0.1) !important;
        }
        html.theme-light input[type="date"]::-webkit-calendar-picker-indicator,
        body.theme-light input[type="date"]::-webkit-calendar-picker-indicator {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234f46e5' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'%3E%3C/rect%3E%3Cline x1='16' y1='2' x2='16' y2='6'%3E%3C/line%3E%3Cline x1='8' y1='2' x2='8' y2='6'%3E%3C/line%3E%3Cline x1='3' y1='10' x2='21' y2='10'%3E%3C/line%3E%3C/svg%3E") !important;
            opacity: 0.85 !important;
        }
        html.theme-light input[type="date"]::-webkit-calendar-picker-indicator:hover,
        body.theme-light input[type="date"]::-webkit-calendar-picker-indicator:hover {
            opacity: 1 !important;
            filter: drop-shadow(0 0 4px rgba(79, 70, 229, 0.5)) !important;
            background-color: rgba(79, 70, 229, 0.1) !important;
        }

        /* Flatpickr Premium Dark Theme Customization */
        .flatpickr-wrapper {
            display: block !important;
            width: 100% !important;
            position: relative !important;
        }
        .flatpickr-calendar.static {
            position: absolute !important;
            top: calc(100% + 4px) !important;
            left: 0 !important;
            z-index: 1050 !important;
        }
        .flatpickr-calendar {
            background: #0f172a !important;
            border: 1px solid #334155 !important;
            border-radius: 16px !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 0 1px rgba(99, 102, 241, 0.15) !important;
            font-family: 'Inter', sans-serif !important;
            overflow: hidden;
            padding: 6px 4px;
        }
        .flatpickr-calendar.arrowTop:before,
        .flatpickr-calendar.arrowTop:after {
            border-bottom-color: #0f172a !important;
        }
        .flatpickr-calendar.arrowBottom:before,
        .flatpickr-calendar.arrowBottom:after {
            border-top-color: #0f172a !important;
        }
        .flatpickr-months {
            padding: 8px 6px 4px 6px;
            background: #0f172a !important;
            border-bottom: 1px solid #1e293b;
        }
        .flatpickr-current-month {
            font-size: 14px !important;
            font-weight: 700 !important;
            color: #f8fafc !important;
            padding-top: 4px !important;
        }
        .flatpickr-current-month .flatpickr-monthDropdown-months {
            background: #0f172a !important;
            font-weight: 700 !important;
            color: #ffffff !important;
            border-radius: 8px;
            padding: 2px 6px;
        }
        .flatpickr-current-month input.cur-year {
            font-weight: 700 !important;
            color: #a5b4fc !important;
        }
        .flatpickr-months .flatpickr-prev-month,
        .flatpickr-months .flatpickr-next-month {
            color: #94a3b8 !important;
            fill: #94a3b8 !important;
            padding: 6px;
            border-radius: 8px;
            transition: all 0.15s ease;
        }
        .flatpickr-months .flatpickr-prev-month:hover,
        .flatpickr-months .flatpickr-next-month:hover {
            color: #ffffff !important;
            fill: #ffffff !important;
            background: #1e293b;
        }
        span.flatpickr-weekday {
            color: #64748b !important;
            font-weight: 700 !important;
            font-size: 11px !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .flatpickr-day {
            color: #cbd5e1 !important;
            border-radius: 10px !important;
            font-weight: 600 !important;
            font-size: 13px !important;
            height: 36px !important;
            line-height: 36px !important;
            margin: 2px 0 !important;
            transition: all 0.15s ease;
        }
        .flatpickr-day:hover {
            background: #1e293b !important;
            color: #ffffff !important;
            border-color: #475569 !important;
        }
        .flatpickr-day.today {
            border-color: #6366f1 !important;
            color: #a5b4fc !important;
            font-weight: 800 !important;
        }
        .flatpickr-day.selected,
        .flatpickr-day.selected:hover {
            background: #4f46e5 !important;
            color: #ffffff !important;
            border-color: #4f46e5 !important;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
            font-weight: 800 !important;
        }
        .flatpickr-day.flatpickr-disabled,
        .flatpickr-day.prevMonthDay,
        .flatpickr-day.nextMonthDay {
            color: #334155 !important;
        }

        /* Light Theme Flatpickr overrides */
        html.theme-light .flatpickr-calendar,
        body.theme-light .flatpickr-calendar {
            background: #ffffff !important;
            border-color: #e2e8f0 !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
        }
        html.theme-light .flatpickr-calendar.arrowTop:before,
        html.theme-light .flatpickr-calendar.arrowTop:after {
            border-bottom-color: #ffffff !important;
        }
        html.theme-light .flatpickr-months {
            background: #ffffff !important;
            border-bottom-color: #f1f5f9;
        }
        html.theme-light .flatpickr-current-month {
            color: #0f172a !important;
        }
        html.theme-light .flatpickr-current-month .flatpickr-monthDropdown-months {
            background: #ffffff !important;
            color: #0f172a !important;
        }
        html.theme-light .flatpickr-day {
            color: #334155 !important;
        }
        html.theme-light .flatpickr-day:hover {
            background: #f1f5f9 !important;
            color: #0f172a !important;
        }

        /* ==========================================================================
           AXECORP EXECUTIVE LIGHT DESIGN SYSTEM - "ROYAL SAPPHIRE & CRISP PORCELAIN"
           High-end B2B Enterprise SaaS UI (WCAG AAA contrast, multi-tier elevation)
           ========================================================================== */

        /* Design System Tokens for Light Mode */
        html.theme-light,
        body.theme-light {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-active: #3730a3;
            --primary-light: #eef2ff;
            --primary-border: #c7d2fe;
            --primary-ring: rgba(79, 70, 229, 0.16);
            --primary-glow: rgba(79, 70, 229, 0.25);
            
            --bg-canvas: #f8fafc;
            --surface-card: #ffffff;
            --surface-inset: #f1f5f9;
            --surface-hover: #f8fafc;
            --border-card: #e2e8f0;
            --border-subtle: #f1f5f9;
            --border-focus: #4f46e5;
            
            --text-heading: #0f172a;
            --text-body: #1e293b;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --text-placeholder: #94a3b8;
            
            color-scheme: light;
        }

        /* 1. Global Canvas & Ambient Atmosphere (Crisp Modern Slate & Soft Blue Glow) */
        html.theme-light,
        html.theme-light body,
        body.theme-light {
            background-color: #f8fafc !important;
            background-image: 
                radial-gradient(1200px circle at 15% -5%, rgba(14, 165, 233, 0.08) 0%, rgba(56, 189, 248, 0.02) 45%, transparent 70%),
                radial-gradient(1100px circle at 85% 10%, rgba(59, 130, 246, 0.07) 0%, rgba(99, 102, 241, 0.02) 35%, transparent 60%),
                radial-gradient(1000px circle at 50% 100%, rgba(186, 230, 254, 0.22) 0%, transparent 60%),
                linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%) !important;
            background-attachment: fixed !important;
            color: #0f172a !important;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* 2. Precision Minimalist Scrollbars */
        html.theme-light ::-webkit-scrollbar-track,
        body.theme-light ::-webkit-scrollbar-track {
            background: #f1f5f9 !important;
        }
        html.theme-light ::-webkit-scrollbar-thumb,
        body.theme-light ::-webkit-scrollbar-thumb {
            background: #cbd5e1 !important;
            border-radius: 9999px !important;
            border: 2px solid #f1f5f9 !important;
        }
        html.theme-light ::-webkit-scrollbar-thumb:hover,
        body.theme-light ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8 !important;
        }
        html.theme-light *,
        body.theme-light * {
            scrollbar-color: #cbd5e1 #f1f5f9;
        }

        /* 3. Architectural Sidebar (Pristine White with Subtle Edge Depth) */
        html.theme-light aside,
        .theme-light aside {
            background: #ffffff !important;
            border-right: 1px solid #e2e8f0 !important;
            box-shadow: 4px 0 24px -4px rgba(15, 23, 42, 0.04), 1px 0 3px 0 rgba(15, 23, 42, 0.02) !important;
        }
        html.theme-light aside [class*="bg-slate-950"],
        .theme-light aside [class*="bg-slate-950"] {
            background-color: transparent !important;
            border-color: #f1f5f9 !important;
        }
        html.theme-light aside [class*="border-slate-800"],
        .theme-light aside [class*="border-slate-800"] {
            border-color: #f1f5f9 !important;
        }

        /* Sidebar Logo Area */
        html.theme-light aside div.h-16,
        .theme-light aside div.h-16 {
            background: #ffffff !important;
            border-bottom: 1px solid #f1f5f9 !important;
        }
        html.theme-light aside a.group > div:first-child,
        .theme-light aside a.group > div:first-child {
            background: #ffffff !important;
            border: 1px solid #e0e7ff !important;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.08) !important;
        }
        html.theme-light aside a.group:hover > div:first-child,
        .theme-light aside a.group:hover > div:first-child {
            border-color: #c7d2fe !important;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.15) !important;
            transform: scale(1.03);
        }
        html.theme-light aside a.group span,
        .theme-light aside a.group span {
            background: linear-gradient(135deg, #0f172a 0%, #3730a3 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            font-weight: 800 !important;
            letter-spacing: -0.02em !important;
        }

        /* Sidebar Navigation Links */
        html.theme-light aside nav a,
        .theme-light aside nav a {
            color: #475569 !important;
            font-weight: 600 !important;
            border: 1px solid transparent !important;
            border-radius: 0.75rem !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        /* Inactive Nav Micro-Icon Squircle Tiles - Vibrant Jewel Palette */
        html.theme-light aside nav a:not([class*="/20"]) div.w-8,
        .theme-light aside nav a:not([class*="/20"]) div.w-8 {
            border-radius: 0.65rem !important;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05) !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        
        /* Dashboard Icon Tile */
        html.theme-light aside nav a[href*="dashboard"]:not([class*="/20"]) div.w-8,
        .theme-light aside nav a[href*="dashboard"]:not([class*="/20"]) div.w-8 {
            background: #eef2ff !important;
            border: 1px solid #c7d2fe !important;
            color: #4f46e5 !important;
        }
        html.theme-light aside nav a[href*="dashboard"]:not([class*="/20"]) div.w-8 i,
        .theme-light aside nav a[href*="dashboard"]:not([class*="/20"]) div.w-8 i {
            color: #4f46e5 !important;
        }

        /* Companies Icon Tile */
        html.theme-light aside nav a[href*="companies"]:not([class*="/20"]) div.w-8,
        .theme-light aside nav a[href*="companies"]:not([class*="/20"]) div.w-8 {
            background: #e0f2fe !important;
            border: 1px solid #bae6fd !important;
            color: #0284c7 !important;
        }
        html.theme-light aside nav a[href*="companies"]:not([class*="/20"]) div.w-8 i,
        .theme-light aside nav a[href*="companies"]:not([class*="/20"]) div.w-8 i {
            color: #0284c7 !important;
        }

        /* Employees Icon Tile */
        html.theme-light aside nav a[href*="employees"]:not([class*="/20"]) div.w-8,
        .theme-light aside nav a[href*="employees"]:not([class*="/20"]) div.w-8 {
            background: #ecfdf5 !important;
            border: 1px solid #a7f3d0 !important;
            color: #059669 !important;
        }
        html.theme-light aside nav a[href*="employees"]:not([class*="/20"]) div.w-8 i,
        .theme-light aside nav a[href*="employees"]:not([class*="/20"]) div.w-8 i {
            color: #059669 !important;
        }

        /* Teams Icon Tile */
        html.theme-light aside nav a[href*="teams"]:not([class*="/20"]) div.w-8,
        .theme-light aside nav a[href*="teams"]:not([class*="/20"]) div.w-8 {
            background: #f5f3ff !important;
            border: 1px solid #ddd6fe !important;
            color: #7c3aed !important;
        }
        html.theme-light aside nav a[href*="teams"]:not([class*="/20"]) div.w-8 i,
        .theme-light aside nav a[href*="teams"]:not([class*="/20"]) div.w-8 i {
            color: #7c3aed !important;
        }

        /* Attendance Icon Tile */
        html.theme-light aside nav a[href*="attendance"]:not([class*="/20"]) div.w-8,
        .theme-light aside nav a[href*="attendance"]:not([class*="/20"]) div.w-8 {
            background: #fffbeb !important;
            border: 1px solid #fde68a !important;
            color: #d97706 !important;
        }
        html.theme-light aside nav a[href*="attendance"]:not([class*="/20"]) div.w-8 i,
        .theme-light aside nav a[href*="attendance"]:not([class*="/20"]) div.w-8 i {
            color: #d97706 !important;
        }

        /* Leaves Icon Tile */
        html.theme-light aside nav a[href*="leaves"]:not([class*="/20"]) div.w-8,
        .theme-light aside nav a[href*="leaves"]:not([class*="/20"]) div.w-8 {
            background: #fff1f2 !important;
            border: 1px solid #fecdd3 !important;
            color: #e11d48 !important;
        }
        html.theme-light aside nav a[href*="leaves"]:not([class*="/20"]) div.w-8 i,
        .theme-light aside nav a[href*="leaves"]:not([class*="/20"]) div.w-8 i {
            color: #e11d48 !important;
        }

        /* Projects Icon Tile */
        html.theme-light aside nav a[href*="projects"]:not([class*="/20"]) div.w-8,
        .theme-light aside nav a[href*="projects"]:not([class*="/20"]) div.w-8 {
            background: #eff6ff !important;
            border: 1px solid #bfdbfe !important;
            color: #2563eb !important;
        }
        html.theme-light aside nav a[href*="projects"]:not([class*="/20"]) div.w-8 i,
        .theme-light aside nav a[href*="projects"]:not([class*="/20"]) div.w-8 i {
            color: #2563eb !important;
        }

        /* Payroll Icon Tile */
        html.theme-light aside nav a[href*="payroll"]:not([class*="/20"]) div.w-8,
        .theme-light aside nav a[href*="payroll"]:not([class*="/20"]) div.w-8 {
            background: #f0fdf4 !important;
            border: 1px solid #bbf7d0 !important;
            color: #16a34a !important;
        }
        html.theme-light aside nav a[href*="payroll"]:not([class*="/20"]) div.w-8 i,
        .theme-light aside nav a[href*="payroll"]:not([class*="/20"]) div.w-8 i {
            color: #16a34a !important;
        }

        /* Settings Icon Tile */
        html.theme-light aside nav a[href*="settings"]:not([class*="/20"]) div.w-8,
        .theme-light aside nav a[href*="settings"]:not([class*="/20"]) div.w-8 {
            background: #f1f5f9 !important;
            border: 1px solid #cbd5e1 !important;
            color: #475569 !important;
        }
        html.theme-light aside nav a[href*="settings"]:not([class*="/20"]) div.w-8 i,
        .theme-light aside nav a[href*="settings"]:not([class*="/20"]) div.w-8 i {
            color: #475569 !important;
        }

        /* Inactive Nav Link Hover */
        html.theme-light aside nav a:not([class*="/20"]):hover,
        .theme-light aside nav a:not([class*="/20"]):hover {
            background: #f8faff !important;
            border-color: #e0e7ff !important;
            color: #0f172a !important;
            transform: translateX(2px) !important;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04) !important;
        }
        html.theme-light aside nav a:not([class*="/20"]):hover div.w-8,
        .theme-light aside nav a:not([class*="/20"]):hover div.w-8 {
            transform: scale(1.08) !important;
            box-shadow: 0 3px 8px rgba(79, 70, 229, 0.16) !important;
        }

        /* Active Navigation Link - High-End Royal Sapphire State */
        html.theme-light aside nav a[class*="/20"],
        .theme-light aside nav a[class*="/20"] {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%) !important;
            border: 1px solid #3730a3 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35) !important;
        }
        html.theme-light aside nav a[class*="/20"] span,
        .theme-light aside nav a[class*="/20"] span {
            color: #ffffff !important;
            font-weight: 700 !important;
        }
        html.theme-light aside nav a[class*="/20"] div.w-8,
        .theme-light aside nav a[class*="/20"] div.w-8 {
            background: rgba(255, 255, 255, 0.22) !important;
            border: 1px solid rgba(255, 255, 255, 0.35) !important;
            color: #ffffff !important;
            box-shadow: none !important;
        }
        html.theme-light aside nav a[class*="/20"] div.w-8 i,
        .theme-light aside nav a[class*="/20"] div.w-8 i {
            color: #ffffff !important;
        }

        /* Sidebar User Profile Card */
        html.theme-light aside div.border-t.bg-slate-950\/60,
        .theme-light aside div.border-t.bg-slate-950\/60 {
            background: #ffffff !important;
            border-top: 1px solid #e2e8f0 !important;
            box-shadow: 0 -3px 10px rgba(15, 23, 42, 0.02) !important;
        }
        html.theme-light aside div.border-t.bg-slate-950\/60 a.group,
        .theme-light aside div.border-t.bg-slate-950\/60 a.group {
            background: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03) !important;
            border-radius: 1rem !important;
        }
        html.theme-light aside div.border-t.bg-slate-950\/60 a.group:hover,
        .theme-light aside div.border-t.bg-slate-950\/60 a.group:hover {
            background: #ffffff !important;
            border-color: #cbd5e1 !important;
            box-shadow: 0 3px 8px rgba(15, 23, 42, 0.06) !important;
            transform: translateY(-1px) !important;
        }
        html.theme-light aside div.border-t.bg-slate-950\/60 p.text-white,
        .theme-light aside div.border-t.bg-slate-950\/60 p.text-white {
            color: #0f172a !important;
            font-weight: 700 !important;
        }
        html.theme-light aside div.border-t.bg-slate-950\/60 p.text-indigo-400,
        .theme-light aside div.border-t.bg-slate-950\/60 p.text-indigo-400 {
            color: #4f46e5 !important;
            font-weight: 600 !important;
        }
        html.theme-light aside div.border-t.bg-slate-950\/60 .text-amber-400,
        .theme-light aside div.border-t.bg-slate-950\/60 .text-amber-400 {
            color: #d97706 !important;
            font-weight: 700 !important;
        }
        html.theme-light aside div.border-t.bg-slate-950\/60 button[title="Logout"],
        .theme-light aside div.border-t.bg-slate-950\/60 button[title="Logout"] {
            color: #64748b !important;
            border-radius: 0.75rem !important;
        }
        html.theme-light aside div.border-t.bg-slate-950\/60 button[title="Logout"]:hover,
        .theme-light aside div.border-t.bg-slate-950\/60 button[title="Logout"]:hover {
            color: #e11d48 !important;
            background-color: #fff1f2 !important;
        }

        /* 4. Top Header & Navbar (Frosted Porcelain Glass) */
        html.theme-light header,
        .theme-light header {
            background: rgba(255, 255, 255, 0.88) !important;
            backdrop-filter: blur(16px) !important;
            -webkit-backdrop-filter: blur(16px) !important;
            border-bottom: 1px solid #e2e8f0 !important;
            box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.03) !important;
        }
        html.theme-light header button[class*="bg-slate-900"],
        .theme-light header button[class*="bg-slate-900"] {
            background-color: #ffffff !important;
            border-color: #cbd5e1 !important;
            color: #475569 !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03) !important;
        }
        html.theme-light header button[class*="bg-slate-900"]:hover,
        .theme-light header button[class*="bg-slate-900"]:hover {
            border-color: #94a3b8 !important;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.05) !important;
        }
        html.theme-light header kbd,
        .theme-light header kbd {
            background-color: #f1f5f9 !important;
            border-color: #e2e8f0 !important;
            color: #475569 !important;
            font-weight: 600 !important;
        }

        /* Header Copilot Button */
        html.theme-light header button[class*="from-indigo-600"],
        .theme-light header button[class*="from-indigo-600"] {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%) !important;
            color: #ffffff !important;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.25) !important;
        }

        /* Karachi Clock & Date in Header */
        html.theme-light header .font-mono,
        .theme-light header .font-mono {
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            color: #0f172a !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04) !important;
        }
        html.theme-light header .font-mono span.bg-indigo-500\/20,
        .theme-light header .font-mono span.bg-indigo-500\/20 {
            background-color: #eef2ff !important;
            color: #4338ca !important;
            border: 1px solid #c7d2fe !important;
        }
        html.theme-light header .font-mono .text-slate-400,
        .theme-light header .font-mono .text-slate-400 {
            color: #64748b !important;
        }

        /* Header Dropdown Menu (Notifications Hub) */
        html.theme-light header .relative > div.shadow-2xl,
        .theme-light header .relative > div.shadow-2xl {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 20px 40px -10px rgba(15, 23, 42, 0.12), 0 0 0 1px rgba(0, 0, 0, 0.04) !important;
            color: #0f172a !important;
        }
        html.theme-light header .relative > div.shadow-2xl > div.border-b,
        .theme-light header .relative > div.shadow-2xl > div.border-b {
            background: #f8fafc !important;
            border-bottom: 1px solid #e2e8f0 !important;
            color: #0f172a !important;
        }
        html.theme-light header .relative > div.shadow-2xl > div.border-t,
        .theme-light header .relative > div.shadow-2xl > div.border-t {
            background: #f8fafc !important;
            border-top: 1px solid #e2e8f0 !important;
        }
        html.theme-light header .relative > div.shadow-2xl [class*="divide-slate"],
        .theme-light header .relative > div.shadow-2xl [class*="divide-slate"] {
            border-color: #f1f5f9 !important;
        }

        /* 5. Welcome Hero Banner & Page Banners (Executive Luminous Sapphire Card) */
        html.theme-light [class*="from-indigo-900"],
        html.theme-light [class*="from-blue-900"],
        html.theme-light [class*="from-slate-900"][class*="via-indigo-950"],
        html.theme-light [class*="from-slate-900"][class*="via-slate-900"],
        html.theme-light [class*="from-slate-950"][class*="via-slate-900"],
        html.theme-light [class*="from-slate-950"][class*="to-indigo-950"],
        html.theme-light [class*="from-slate-950"][class*="via-indigo-950"],
        html.theme-light .squad-banner-card,
        .theme-light [class*="from-indigo-900"],
        .theme-light [class*="from-blue-900"],
        .theme-light [class*="from-slate-900"][class*="via-indigo-950"],
        .theme-light [class*="from-slate-900"][class*="via-slate-900"],
        .theme-light [class*="from-slate-950"][class*="via-slate-900"],
        .theme-light [class*="from-slate-950"][class*="to-indigo-950"],
        .theme-light [class*="from-slate-950"][class*="via-indigo-950"],
        .theme-light .squad-banner-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8faff 45%, #eef2ff 100%) !important;
            border: 1px solid #c7d2fe !important;
            box-shadow: 0 10px 30px -5px rgba(79, 70, 229, 0.08), 0 4px 6px -2px rgba(15, 23, 42, 0.02) !important;
            color: #0f172a !important;
        }
        html.theme-light [class*="from-indigo-900"] h1,
        html.theme-light [class*="from-indigo-900"] h2,
        html.theme-light [class*="from-indigo-900"] h3,
        html.theme-light [class*="from-blue-900"] h1,
        html.theme-light [class*="from-blue-900"] h2,
        html.theme-light [class*="from-blue-900"] h3,
        html.theme-light [class*="via-indigo-950"] h1,
        html.theme-light [class*="via-indigo-950"] h2,
        html.theme-light [class*="via-indigo-950"] h3,
        html.theme-light [class*="from-slate-950"] h1,
        html.theme-light [class*="from-slate-950"] h2,
        html.theme-light [class*="from-slate-950"] h3,
        html.theme-light .squad-banner-card h1,
        html.theme-light .squad-banner-card h2,
        html.theme-light .squad-banner-card h3,
        .theme-light [class*="from-indigo-900"] h1,
        .theme-light [class*="from-indigo-900"] h2,
        .theme-light [class*="from-indigo-900"] h3,
        .theme-light [class*="from-blue-900"] h1,
        .theme-light [class*="from-blue-900"] h2,
        .theme-light [class*="from-blue-900"] h3,
        .theme-light [class*="via-indigo-950"] h1,
        .theme-light [class*="via-indigo-950"] h2,
        .theme-light [class*="via-indigo-950"] h3,
        .theme-light [class*="from-slate-950"] h1,
        .theme-light [class*="from-slate-950"] h2,
        .theme-light [class*="from-slate-950"] h3,
        .theme-light .squad-banner-card h1,
        .theme-light .squad-banner-card h2,
        .theme-light .squad-banner-card h3 {
            color: #0f172a !important;
            font-weight: 800 !important;
            letter-spacing: -0.025em !important;
        }
        html.theme-light [class*="from-indigo-900"] p,
        html.theme-light [class*="from-blue-900"] p,
        html.theme-light [class*="via-indigo-950"] p,
        html.theme-light [class*="from-slate-950"] p,
        html.theme-light .squad-banner-card p,
        .theme-light [class*="from-indigo-900"] p,
        .theme-light [class*="from-blue-900"] p,
        .theme-light [class*="via-indigo-950"] p,
        .theme-light [class*="from-slate-950"] p,
        .theme-light .squad-banner-card p {
            color: #475569 !important;
            font-weight: 500 !important;
        }

        /* Banner Status Pill Badge ("System Operational") */
        html.theme-light [class*="from-indigo-900"] [class*="bg-indigo-500/10"],
        html.theme-light [class*="from-blue-900"] [class*="bg-blue-500/10"],
        html.theme-light [class*="via-indigo-950"] [class*="bg-indigo-500/10"],
        .theme-light [class*="from-indigo-900"] [class*="bg-indigo-500/10"],
        .theme-light [class*="from-blue-900"] [class*="bg-blue-500/10"],
        .theme-light [class*="via-indigo-950"] [class*="bg-indigo-500/10"] {
            background-color: #ecfdf5 !important;
            border: 1px solid #a7f3d0 !important;
            color: #047857 !important;
            box-shadow: 0 1px 2px rgba(16, 185, 129, 0.05) !important;
        }
        html.theme-light [class*="from-indigo-900"] [class*="bg-indigo-500/10"] span,
        .theme-light [class*="from-indigo-900"] [class*="bg-indigo-500/10"] span {
            color: #047857 !important;
            font-weight: 700 !important;
        }

        /* Squad Banner Pill Badge ("My Squad", "Squad Leader Portal") */
        html.theme-light [class*="from-slate-950"] [class*="bg-amber-500/20"],
        html.theme-light .squad-banner-card [class*="bg-amber-500/20"],
        .theme-light [class*="from-slate-950"] [class*="bg-amber-500/20"],
        .theme-light .squad-banner-card [class*="bg-amber-500/20"] {
            background-color: #fef3c7 !important;
            border: 1px solid #fde68a !important;
            color: #b45309 !important;
            font-weight: 800 !important;
        }

        /* Squad Icon Box */
        html.theme-light [class*="from-slate-950"] .w-12.h-12[class*="bg-amber-500/10"],
        html.theme-light .squad-banner-card .w-12.h-12[class*="bg-amber-500/10"],
        .theme-light [class*="from-slate-950"] .w-12.h-12[class*="bg-amber-500/10"],
        .theme-light .squad-banner-card .w-12.h-12[class*="bg-amber-500/10"] {
            background-color: #fffbeb !important;
            border: 1px solid #fde68a !important;
            color: #d97706 !important;
            box-shadow: 0 2px 6px rgba(245, 158, 11, 0.12) !important;
        }

        /* Squad Leader Pill Card */
        html.theme-light [class*="from-slate-950"] [class*="bg-slate-950/70"],
        html.theme-light [class*="from-slate-950"] [class*="bg-slate-950/80"],
        html.theme-light .squad-banner-card [class*="bg-slate-950/70"],
        html.theme-light .squad-banner-card [class*="bg-slate-950/80"],
        .theme-light [class*="from-slate-950"] [class*="bg-slate-950/70"],
        .theme-light [class*="from-slate-950"] [class*="bg-slate-950/80"],
        .theme-light .squad-banner-card [class*="bg-slate-950/70"],
        .theme-light .squad-banner-card [class*="bg-slate-950/80"] {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05) !important;
            border-radius: 1rem !important;
        }
        html.theme-light [class*="from-slate-950"] [class*="bg-slate-950/70"] p.text-white,
        html.theme-light .squad-banner-card [class*="bg-slate-950/70"] p.text-white,
        .theme-light [class*="from-slate-950"] [class*="bg-slate-950/70"] p.text-white,
        .theme-light .squad-banner-card [class*="bg-slate-950/70"] p.text-white {
            color: #0f172a !important;
            font-weight: 700 !important;
        }
        html.theme-light [class*="from-slate-950"] [class*="bg-slate-950/70"] p.text-slate-400,
        html.theme-light .squad-banner-card [class*="bg-slate-950/70"] p.text-slate-400,
        .theme-light [class*="from-slate-950"] [class*="bg-slate-950/70"] p.text-slate-400,
        .theme-light .squad-banner-card [class*="bg-slate-950/70"] p.text-slate-400 {
            color: #64748b !important;
        }

        /* Squad Button ("View Squad Hub") */
        html.theme-light [class*="from-slate-950"] a[class*="bg-slate-800"],
        html.theme-light .squad-banner-card a[class*="bg-slate-800"],
        .theme-light [class*="from-slate-950"] a[class*="bg-slate-800"],
        .theme-light .squad-banner-card a[class*="bg-slate-800"] {
            background: #0f172a !important;
            color: #ffffff !important;
            border: 1px solid #0f172a !important;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.15) !important;
            font-weight: 700 !important;
            transition: all 0.2s ease !important;
        }
        html.theme-light [class*="from-slate-950"] a[class*="bg-slate-800"]:hover,
        html.theme-light .squad-banner-card a[class*="bg-slate-800"]:hover,
        .theme-light [class*="from-slate-950"] a[class*="bg-slate-800"]:hover,
        .theme-light .squad-banner-card a[class*="bg-slate-800"]:hover {
            background: #334155 !important;
            border-color: #334155 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.22) !important;
            transform: translateY(-1px) !important;
        }
        html.theme-light [class*="from-slate-950"] a[class*="bg-slate-800"] i,
        html.theme-light .squad-banner-card a[class*="bg-slate-800"] i,
        .theme-light [class*="from-slate-950"] a[class*="bg-slate-800"] i,
        .theme-light .squad-banner-card a[class*="bg-slate-800"] i {
            color: #ffffff !important;
        }

        /* Attendance Oversight / Clock-In Card inside Hero Banner */
        html.theme-light [class*="from-indigo-900"] [class*="bg-slate-950"],
        .theme-light [class*="from-indigo-900"] [class*="bg-slate-950"] {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 6px 20px -2px rgba(15, 23, 42, 0.06), 0 2px 6px -1px rgba(15, 23, 42, 0.03) !important;
            border-radius: 1.5rem !important;
        }
        html.theme-light [class*="from-indigo-900"] [class*="bg-slate-950"] p,
        .theme-light [class*="from-indigo-900"] [class*="bg-slate-950"] p {
            color: #475569 !important;
        }
        html.theme-light [class*="from-indigo-900"] [class*="bg-slate-950"] p.text-white,
        .theme-light [class*="from-indigo-900"] [class*="bg-slate-950"] p.text-white {
            color: #0f172a !important;
            font-weight: 700 !important;
        }
        html.theme-light [class*="from-indigo-900"] [class*="bg-slate-950"] span.text-slate-300,
        .theme-light [class*="from-indigo-900"] [class*="bg-slate-950"] span.text-slate-300 {
            color: #334155 !important;
            font-weight: 600 !important;
        }
        html.theme-light [class*="from-indigo-900"] [class*="bg-slate-950"] span.text-slate-500,
        .theme-light [class*="from-indigo-900"] [class*="bg-slate-950"] span.text-slate-500 {
            color: #64748b !important;
        }
        html.theme-light [class*="from-indigo-900"] [class*="bg-slate-950"] a[class*="from-indigo-600"],
        .theme-light [class*="from-indigo-900"] [class*="bg-slate-950"] a[class*="from-indigo-600"] {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%) !important;
            color: #ffffff !important;
            border: 1px solid #3730a3 !important;
            box-shadow: 0 3px 10px rgba(79, 70, 229, 0.3) !important;
            font-weight: 700 !important;
        }
        html.theme-light [class*="from-indigo-900"] [class*="bg-slate-950"] a[class*="from-indigo-600"] i,
        .theme-light [class*="from-indigo-900"] [class*="bg-slate-950"] a[class*="from-indigo-600"] i {
            color: #ffffff !important;
        }
        html.theme-light [class*="from-indigo-900"] [class*="bg-slate-950"] a[class*="from-indigo-600"]:hover,
        .theme-light [class*="from-indigo-900"] [class*="bg-slate-950"] a[class*="from-indigo-600"]:hover {
            background: linear-gradient(135deg, #4338ca 0%, #3730a3 100%) !important;
            box-shadow: 0 6px 18px rgba(79, 70, 229, 0.4) !important;
            transform: translateY(-1px) !important;
        }

        /* 6. Universal Card Surfaces & Containers */
        html.theme-light [class*="bg-slate-950"]:not(header):not(header *):not(aside):not(aside *):not([class*="from-indigo-900"] *):not([class*="from-slate-950"] *):not(.squad-banner-card *):not([class*="company-card-"]),
        html.theme-light [class*="bg-slate-900"]:not(header):not(header *):not(aside):not(aside *):not([class*="from-indigo-900"] *):not([class*="from-slate-950"] *):not(.squad-banner-card *):not([class*="company-card-"]),
        .theme-light [class*="bg-slate-950"]:not(header):not(header *):not(aside):not(aside *):not([class*="from-indigo-900"] *):not([class*="from-slate-950"] *):not(.squad-banner-card *):not([class*="company-card-"]),
        .theme-light [class*="bg-slate-900"]:not(header):not(header *):not(aside):not(aside *):not([class*="from-indigo-900"] *):not([class*="from-slate-950"] *):not(.squad-banner-card *):not([class*="company-card-"]) {
            background-color: #ffffff !important;
            border-color: #dbe2ea !important;
            box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.04), 0 4px 14px -2px rgba(99, 102, 241, 0.04) !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        /* Large Container Cards (rounded-3xl) with Ambient Depth & Subtle Shading */
        html.theme-light .rounded-3xl[class*="bg-slate-950"]:not([class*="company-card-"]),
        html.theme-light .rounded-3xl[class*="bg-slate-900"]:not([class*="company-card-"]),
        .theme-light .rounded-3xl[class*="bg-slate-950"]:not([class*="company-card-"]),
        .theme-light .rounded-3xl[class*="bg-slate-900"]:not([class*="company-card-"]) {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%) !important;
            border: 1px solid #dbe2ea !important;
            box-shadow: 0 4px 20px -2px rgba(99, 102, 241, 0.06), 0 2px 6px -1px rgba(15, 23, 42, 0.03) !important;
        }

        /* Nested Insets & Information Chips (Soft Tinted Contrast - Never Blinding White) */
        html.theme-light .rounded-3xl [class*="bg-slate-900/40"],
        html.theme-light .rounded-3xl [class*="bg-slate-900/60"],
        html.theme-light .rounded-3xl [class*="bg-slate-900/80"],
        .theme-light .rounded-3xl [class*="bg-slate-900/40"],
        .theme-light .rounded-3xl [class*="bg-slate-900/60"],
        .theme-light .rounded-3xl [class*="bg-slate-900/80"] {
            background-color: #f1f5f9 !important;
            border-color: #e2e8f0 !important;
        }

        /* Interactive Card Hover Lift */
        html.theme-light [class*="bg-slate-950"].group:hover,
        html.theme-light [class*="bg-slate-900"].group:hover,
        .theme-light [class*="bg-slate-950"].group:hover,
        .theme-light [class*="bg-slate-900"].group:hover {
            border-color: #cbd5e1 !important;
            box-shadow: 0 14px 30px -4px rgba(79, 70, 229, 0.12), 0 4px 10px -2px rgba(15, 23, 42, 0.04) !important;
            transform: translateY(-2px) !important;
        }

        /* 6B. Dedicated Vibrant Saturated Company Entity Cards */
        html.theme-light .company-card-indigo,
        .theme-light .company-card-indigo {
            background: linear-gradient(165deg, #eff2fe 0%, #e0e7ff 35%, #ffffff 85%) !important;
            border: 2px solid #a5b4fc !important;
            box-shadow: 0 12px 28px -5px rgba(99, 102, 241, 0.20), 0 4px 10px -2px rgba(99, 102, 241, 0.10) !important;
        }
        html.theme-light .company-card-sky,
        .theme-light .company-card-sky {
            background: linear-gradient(165deg, #f0f9ff 0%, #e0f2fe 35%, #ffffff 85%) !important;
            border: 2px solid #7dd3fc !important;
            box-shadow: 0 12px 28px -5px rgba(14, 165, 233, 0.20), 0 4px 10px -2px rgba(14, 165, 233, 0.10) !important;
        }
        html.theme-light .company-card-purple,
        .theme-light .company-card-purple {
            background: linear-gradient(165deg, #faf5ff 0%, #f3e8ff 35%, #ffffff 85%) !important;
            border: 2px solid #d8b4fe !important;
            box-shadow: 0 12px 28px -5px rgba(168, 85, 247, 0.20), 0 4px 10px -2px rgba(168, 85, 247, 0.10) !important;
        }
        html.theme-light .company-card-emerald,
        .theme-light .company-card-emerald {
            background: linear-gradient(165deg, #f0fdf4 0%, #dcfce7 35%, #ffffff 85%) !important;
            border: 2px solid #86efac !important;
            box-shadow: 0 12px 28px -5px rgba(16, 185, 129, 0.20), 0 4px 10px -2px rgba(16, 185, 129, 0.10) !important;
        }
        html.theme-light .company-card-amber,
        .theme-light .company-card-amber {
            background: linear-gradient(165deg, #fffbeb 0%, #fef3c7 35%, #ffffff 85%) !important;
            border: 2px solid #fcd34d !important;
            box-shadow: 0 12px 28px -5px rgba(245, 158, 11, 0.20), 0 4px 10px -2px rgba(245, 158, 11, 0.10) !important;
        }

        /* Company Card Hover States */
        html.theme-light .company-card-indigo:hover,
        .theme-light .company-card-indigo:hover {
            border-color: #6366f1 !important;
            box-shadow: 0 20px 35px -5px rgba(99, 102, 241, 0.30) !important;
            transform: translateY(-4px) !important;
        }
        html.theme-light .company-card-sky:hover,
        .theme-light .company-card-sky:hover {
            border-color: #0284c7 !important;
            box-shadow: 0 20px 35px -5px rgba(14, 165, 233, 0.30) !important;
            transform: translateY(-4px) !important;
        }
        html.theme-light .company-card-purple:hover,
        .theme-light .company-card-purple:hover {
            border-color: #a855f7 !important;
            box-shadow: 0 20px 35px -5px rgba(168, 85, 247, 0.30) !important;
            transform: translateY(-4px) !important;
        }
        html.theme-light .company-card-emerald:hover,
        .theme-light .company-card-emerald:hover {
            border-color: #10b981 !important;
            box-shadow: 0 20px 35px -5px rgba(16, 185, 129, 0.30) !important;
            transform: translateY(-4px) !important;
        }
        html.theme-light .company-card-amber:hover,
        .theme-light .company-card-amber:hover {
            border-color: #f59e0b !important;
            box-shadow: 0 20px 35px -5px rgba(245, 158, 11, 0.30) !important;
            transform: translateY(-4px) !important;
        }

        /* Company Card Internal Typography & Insets */
        html.theme-light [class*="company-card-"] h3,
        .theme-light [class*="company-card-"] h3 {
            color: #0f172a !important;
            font-weight: 800 !important;
        }
        html.theme-light [class*="company-card-"] p.text-slate-400,
        .theme-light [class*="company-card-"] p.text-slate-400 {
            color: #334155 !important;
            font-weight: 500 !important;
        }
        html.theme-light [class*="company-card-"] p.text-\[11px\].text-slate-400,
        .theme-light [class*="company-card-"] p.text-\[11px\].text-slate-400 {
            color: #475569 !important;
            font-weight: 700 !important;
        }
        html.theme-light [class*="company-card-"] .border-t.bg-slate-900\/40,
        .theme-light [class*="company-card-"] .border-t.bg-slate-900\/40 {
            background-color: rgba(255, 255, 255, 0.75) !important;
            border-top: 1px solid rgba(15, 23, 42, 0.08) !important;
        }
        html.theme-light [class*="company-card-"] strong.text-white,
        .theme-light [class*="company-card-"] strong.text-white {
            color: #0f172a !important;
            font-weight: 800 !important;
        }
        html.theme-light [class*="company-card-"] span[class*="bg-slate-800"],
        .theme-light [class*="company-card-"] span[class*="bg-slate-800"] {
            background-color: #f1f5f9 !important;
            border-color: #cbd5e1 !important;
            color: #475569 !important;
        }
        html.theme-light [class*="company-card-"] .w-12.h-12,
        .theme-light [class*="company-card-"] .w-12.h-12 {
            border-color: rgba(15, 23, 42, 0.1) !important;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.06) !important;
        }

        /* Dark Mode Distinct Subtle Accents for Company Cards */
        .company-card-indigo { border-color: rgba(99, 102, 241, 0.35); }
        .company-card-sky { border-color: rgba(14, 165, 233, 0.35); }
        .company-card-purple { border-color: rgba(168, 85, 247, 0.35); }
        .company-card-emerald { border-color: rgba(16, 185, 129, 0.35); }
        .company-card-amber { border-color: rgba(245, 158, 11, 0.35); }

        /* Universal Section Gap Across Entire HRMS */
        main > div[x-data] > * + *,
        main > div:not([x-data]) > * + * {
            margin-top: 1.5rem;
        }
        @media (min-width: 1024px) {
            main > div[x-data] > * + *,
            main > div:not([x-data]) > * + * {
                margin-top: 1.75rem;
            }
        }

        /* 6C. Grid Cards Color Stripes (Teams, Departments, Projects) */
        html.theme-light .grid > [class*="bg-slate-950"]:not([class*="company-card-"]):nth-child(4n+1),
        .theme-light .grid > [class*="bg-slate-950"]:not([class*="company-card-"]):nth-child(4n+1) {
            border-top: 4px solid #6366f1 !important;
            background: linear-gradient(175deg, #f5f7ff 0%, #ffffff 45%) !important;
        }
        html.theme-light .grid > [class*="bg-slate-950"]:not([class*="company-card-"]):nth-child(4n+2),
        .theme-light .grid > [class*="bg-slate-950"]:not([class*="company-card-"]):nth-child(4n+2) {
            border-top: 4px solid #0284c7 !important;
            background: linear-gradient(175deg, #f0f9ff 0%, #ffffff 45%) !important;
        }
        html.theme-light .grid > [class*="bg-slate-950"]:not([class*="company-card-"]):nth-child(4n+3),
        .theme-light .grid > [class*="bg-slate-950"]:not([class*="company-card-"]):nth-child(4n+3) {
            border-top: 4px solid #8b5cf6 !important;
            background: linear-gradient(175deg, #faf5ff 0%, #ffffff 45%) !important;
        }
        html.theme-light .grid > [class*="bg-slate-950"]:not([class*="company-card-"]):nth-child(4n+4),
        .theme-light .grid > [class*="bg-slate-950"]:not([class*="company-card-"]):nth-child(4n+4) {
            border-top: 4px solid #10b981 !important;
            background: linear-gradient(175deg, #f0fdf4 0%, #ffffff 45%) !important;
        }

        /* 7. Vibrant Distinct Tinted KPI Stat Cards */
        html.theme-light [class*="border-t-indigo-500"],
        .theme-light [class*="border-t-indigo-500"] {
            border: 1.5px solid #c7d2fe !important;
            border-top: 5px solid #4f46e5 !important;
            background: linear-gradient(165deg, #eef2ff 0%, #e0e7ff 40%, #f8faff 100%) !important;
            box-shadow: 0 8px 24px -4px rgba(79, 70, 229, 0.14), 0 2px 6px -1px rgba(79, 70, 229, 0.06) !important;
        }
        html.theme-light [class*="border-t-indigo-500"] h3,
        .theme-light [class*="border-t-indigo-500"] h3 {
            color: #312e81 !important;
            font-weight: 800 !important;
        }

        html.theme-light [class*="border-t-sky-500"],
        .theme-light [class*="border-t-sky-500"] {
            border: 1.5px solid #bae6fd !important;
            border-top: 5px solid #0284c7 !important;
            background: linear-gradient(165deg, #f0f9ff 0%, #e0f2fe 40%, #f8fafc 100%) !important;
            box-shadow: 0 8px 24px -4px rgba(2, 132, 199, 0.14), 0 2px 6px -1px rgba(2, 132, 199, 0.06) !important;
        }
        html.theme-light [class*="border-t-sky-500"] h3,
        .theme-light [class*="border-t-sky-500"] h3 {
            color: #0c4a6e !important;
            font-weight: 800 !important;
        }

        html.theme-light [class*="border-t-purple-500"],
        .theme-light [class*="border-t-purple-500"] {
            border: 1.5px solid #e9d5ff !important;
            border-top: 5px solid #8b5cf6 !important;
            background: linear-gradient(165deg, #faf5ff 0%, #f3e8ff 40%, #fdf4ff 100%) !important;
            box-shadow: 0 8px 24px -4px rgba(139, 92, 246, 0.14), 0 2px 6px -1px rgba(139, 92, 246, 0.06) !important;
        }
        html.theme-light [class*="border-t-purple-500"] h3,
        .theme-light [class*="border-t-purple-500"] h3 {
            color: #581c87 !important;
            font-weight: 800 !important;
        }

        html.theme-light [class*="border-t-emerald-500"],
        .theme-light [class*="border-t-emerald-500"] {
            border: 1.5px solid #a7f3d0 !important;
            border-top: 5px solid #10b981 !important;
            background: linear-gradient(165deg, #f0fdf4 0%, #dcfce7 40%, #f8fafc 100%) !important;
            box-shadow: 0 8px 24px -4px rgba(16, 185, 129, 0.14), 0 2px 6px -1px rgba(16, 185, 129, 0.06) !important;
        }
        html.theme-light [class*="border-t-emerald-500"] h3,
        .theme-light [class*="border-t-emerald-500"] h3 {
            color: #064e3b !important;
            font-weight: 800 !important;
        }

        html.theme-light [class*="border-t-amber-500"],
        .theme-light [class*="border-t-amber-500"] {
            border: 1.5px solid #fde68a !important;
            border-top: 5px solid #f59e0b !important;
            background: linear-gradient(165deg, #fffbeb 0%, #fef3c7 40%, #fffdf5 100%) !important;
            box-shadow: 0 8px 24px -4px rgba(245, 158, 11, 0.14), 0 2px 6px -1px rgba(245, 158, 11, 0.06) !important;
        }
        html.theme-light [class*="border-t-amber-500"] h3,
        .theme-light [class*="border-t-amber-500"] h3 {
            color: #78350f !important;
            font-weight: 800 !important;
        }

        html.theme-light [class*="border-t-blue-500"],
        .theme-light [class*="border-t-blue-500"] {
            border: 1.5px solid #bfdbfe !important;
            border-top: 5px solid #2563eb !important;
            background: linear-gradient(165deg, #eff6ff 0%, #dbeafe 40%, #f8faff 100%) !important;
            box-shadow: 0 8px 24px -4px rgba(37, 99, 235, 0.14), 0 2px 6px -1px rgba(37, 99, 235, 0.06) !important;
        }
        html.theme-light [class*="border-t-blue-500"] h3,
        .theme-light [class*="border-t-blue-500"] h3 {
            color: #1e3a8a !important;
            font-weight: 800 !important;
        }

        html.theme-light [class*="border-t-rose-500"],
        .theme-light [class*="border-t-rose-500"] {
            border: 1.5px solid #fecdd3 !important;
            border-top: 5px solid #e11d48 !important;
            background: linear-gradient(165deg, #fff1f2 0%, #ffe4e6 40%, #fff5f5 100%) !important;
            box-shadow: 0 8px 24px -4px rgba(225, 29, 72, 0.14), 0 2px 6px -1px rgba(225, 29, 72, 0.06) !important;
        }
        html.theme-light [class*="border-t-rose-500"] h3,
        .theme-light [class*="border-t-rose-500"] h3 {
            color: #881337 !important;
            font-weight: 800 !important;
        }

        /* KPI Icon Badges with Vibrant Tints */
        html.theme-light .w-12.h-12[class*="bg-indigo-500/10"],
        .theme-light .w-12.h-12[class*="bg-indigo-500/10"] {
            background: #eef2ff !important;
            border: 1px solid #c7d2fe !important;
            color: #4f46e5 !important;
            box-shadow: 0 2px 6px rgba(79, 70, 229, 0.12) !important;
        }
        html.theme-light .w-12.h-12[class*="bg-emerald-500/10"],
        .theme-light .w-12.h-12[class*="bg-emerald-500/10"] {
            background: #ecfdf5 !important;
            border: 1px solid #a7f3d0 !important;
            color: #059669 !important;
            box-shadow: 0 2px 6px rgba(16, 185, 129, 0.12) !important;
        }
        html.theme-light .w-12.h-12[class*="bg-amber-500/10"],
        .theme-light .w-12.h-12[class*="bg-amber-500/10"] {
            background: #fffbeb !important;
            border: 1px solid #fde68a !important;
            color: #d97706 !important;
            box-shadow: 0 2px 6px rgba(245, 158, 11, 0.12) !important;
        }
        html.theme-light .w-12.h-12[class*="bg-blue-500/10"],
        .theme-light .w-12.h-12[class*="bg-blue-500/10"] {
            background: #eff6ff !important;
            border: 1px solid #bfdbfe !important;
            color: #2563eb !important;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.12) !important;
        }

        /* 8. Kanban Boards & Workload Grids (Projects & ClickUp Style) */
        html.theme-light [class*="min-h-[500px]"][class*="bg-slate-900"],
        .theme-light [class*="min-h-[500px]"][class*="bg-slate-900"] {
            background: #f1f5f9 !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.04) !important;
        }
        html.theme-light [class*="min-h-[500px]"] h3,
        .theme-light [class*="min-h-[500px]"] h3 {
            color: #0f172a !important;
            font-weight: 700 !important;
        }
        html.theme-light [class*="min-h-[500px]"] span[class*="bg-slate-800"],
        .theme-light [class*="min-h-[500px]"] span[class*="bg-slate-800"] {
            background: #e2e8f0 !important;
            color: #334155 !important;
            font-weight: 700 !important;
        }
        html.theme-light [class*="min-h-[500px]"] .space-y-3 > div,
        .theme-light [class*="min-h-[500px]"] .space-y-3 > div {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.05), 0 1px 2px -1px rgba(15, 23, 42, 0.03) !important;
        }
        html.theme-light [class*="min-h-[500px]"] .space-y-3 > div:hover,
        .theme-light [class*="min-h-[500px]"] .space-y-3 > div:hover {
            border-color: #cbd5e1 !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 16px -2px rgba(15, 23, 42, 0.08) !important;
        }
        html.theme-light [class*="min-h-[500px]"] .space-y-3 > div h4,
        .theme-light [class*="min-h-[500px]"] .space-y-3 > div h4 {
            color: #0f172a !important;
            font-weight: 700 !important;
        }

        /* 9. Data Tables (Employees, Attendance, Leaves, Payroll) */
        html.theme-light thead tr,
        .theme-light thead tr {
            background-color: #f8fafc !important;
            border-bottom: 2px solid #e2e8f0 !important;
        }
        html.theme-light thead th,
        .theme-light thead th {
            color: #475569 !important;
            font-weight: 700 !important;
            font-size: 11px !important;
            letter-spacing: 0.05em !important;
        }
        html.theme-light tbody tr,
        .theme-light tbody tr {
            background-color: #ffffff !important;
            transition: background-color 0.15s ease !important;
        }
        html.theme-light tbody tr:hover,
        html.theme-light tr[class*="hover:bg-slate-900"]:hover,
        .theme-light tbody tr:hover,
        .theme-light tr[class*="hover:bg-slate-900"]:hover {
            background-color: #f8faff !important;
        }
        html.theme-light tbody td,
        .theme-light tbody td {
            border-bottom: 1px solid #f1f5f9 !important;
            color: #334155 !important;
        }
        html.theme-light tbody td p.font-bold,
        html.theme-light tbody td span.font-bold,
        html.theme-light tbody td span.font-extrabold,
        .theme-light tbody td p.font-bold,
        .theme-light tbody td span.font-bold,
        .theme-light tbody td span.font-extrabold {
            color: #0f172a !important;
        }

        /* Table Action Buttons */
        html.theme-light tbody td button.hover\:bg-slate-800:hover,
        .theme-light tbody td button.hover\:bg-slate-800:hover {
            background-color: #eef2ff !important;
            color: #4f46e5 !important;
        }

        /* 10. Status Badges & Pills (Rich Saturated Jewel & Pastel Tints) */
        /* Active / Approved / Present (Emerald) */
        html.theme-light [class*="bg-emerald-500/10"]:not(aside nav *),
        html.theme-light [class*="bg-emerald-500/15"]:not(aside nav *),
        html.theme-light [class*="bg-emerald-500/20"]:not(aside nav *),
        .theme-light [class*="bg-emerald-500/10"]:not(aside nav *),
        .theme-light [class*="bg-emerald-500/15"]:not(aside nav *),
        .theme-light [class*="bg-emerald-500/20"]:not(aside nav *) {
            background-color: #ecfdf5 !important;
            border: 1px solid #a7f3d0 !important;
            color: #047857 !important;
            font-weight: 700 !important;
        }
        html.theme-light [class*="text-emerald-400"]:not(header *):not(aside nav *),
        .theme-light [class*="text-emerald-400"]:not(header *):not(aside nav *) {
            color: #059669 !important;
        }

        /* Sky / Cyan Badges & Metric Pills */
        html.theme-light [class*="bg-sky-500/10"]:not(aside nav *),
        html.theme-light [class*="bg-sky-500/15"]:not(aside nav *),
        html.theme-light [class*="bg-sky-500/20"]:not(aside nav *),
        .theme-light [class*="bg-sky-500/10"]:not(aside nav *),
        .theme-light [class*="bg-sky-500/15"]:not(aside nav *),
        .theme-light [class*="bg-sky-500/20"]:not(aside nav *) {
            background-color: #e0f2fe !important;
            border: 1px solid #bae6fd !important;
            color: #0284c7 !important;
            font-weight: 700 !important;
        }
        html.theme-light [class*="text-sky-400"]:not(header *):not(aside nav *),
        .theme-light [class*="text-sky-400"]:not(header *):not(aside nav *) {
            color: #0284c7 !important;
        }

        /* Pending / On Break / Warning (Amber) */
        html.theme-light [class*="bg-amber-500/10"]:not(aside nav *),
        html.theme-light [class*="bg-amber-500/15"]:not(aside nav *),
        html.theme-light [class*="bg-amber-500/20"]:not(aside nav *),
        .theme-light [class*="bg-amber-500/10"]:not(aside nav *),
        .theme-light [class*="bg-amber-500/15"]:not(aside nav *),
        .theme-light [class*="bg-amber-500/20"]:not(aside nav *) {
            background-color: #fffbeb !important;
            border: 1px solid #fde68a !important;
            color: #b45309 !important;
            font-weight: 700 !important;
        }
        html.theme-light [class*="text-amber-400"]:not(header *):not(aside nav *),
        .theme-light [class*="text-amber-400"]:not(header *):not(aside nav *) {
            color: #d97706 !important;
        }

        /* Inactive / Rejected / Exceeded / Overdue (Rose/Crimson) */
        html.theme-light [class*="bg-rose-500/10"]:not(aside nav *),
        html.theme-light [class*="bg-rose-500/15"]:not(aside nav *),
        html.theme-light [class*="bg-rose-500/20"]:not(aside nav *),
        .theme-light [class*="bg-rose-500/10"]:not(aside nav *),
        .theme-light [class*="bg-rose-500/15"]:not(aside nav *),
        .theme-light [class*="bg-rose-500/20"]:not(aside nav *) {
            background-color: #fff1f2 !important;
            border: 1px solid #fecdd3 !important;
            color: #be123c !important;
            font-weight: 700 !important;
        }
        html.theme-light [class*="text-rose-400"]:not(header *):not(aside nav *),
        .theme-light [class*="text-rose-400"]:not(header *):not(aside nav *) {
            color: #e11d48 !important;
        }

        /* Brand / Information / Teams (Indigo / Blue) */
        html.theme-light [class*="bg-indigo-500/10"]:not(header *):not(aside nav *):not([class*="from-indigo-900"] *),
        html.theme-light [class*="bg-indigo-500/15"]:not(header *):not(aside nav *):not([class*="from-indigo-900"] *),
        html.theme-light [class*="bg-indigo-500/20"]:not(header *):not(aside nav *):not([class*="from-indigo-900"] *),
        html.theme-light [class*="bg-blue-500/10"]:not(header *):not(aside nav *):not([class*="from-indigo-900"] *),
        html.theme-light [class*="bg-blue-500/15"]:not(header *):not(aside nav *):not([class*="from-indigo-900"] *),
        html.theme-light [class*="bg-blue-500/20"]:not(header *):not(aside nav *):not([class*="from-indigo-900"] *),
        .theme-light [class*="bg-indigo-500/10"]:not(header *):not(aside nav *):not([class*="from-indigo-900"] *),
        .theme-light [class*="bg-indigo-500/15"]:not(header *):not(aside nav *):not([class*="from-indigo-900"] *),
        .theme-light [class*="bg-indigo-500/20"]:not(header *):not(aside nav *):not([class*="from-indigo-900"] *),
        .theme-light [class*="bg-blue-500/10"]:not(header *):not(aside nav *):not([class*="from-indigo-900"] *),
        .theme-light [class*="bg-blue-500/15"]:not(header *):not(aside nav *):not([class*="from-indigo-900"] *),
        .theme-light [class*="bg-blue-500/20"]:not(header *):not(aside nav *):not([class*="from-indigo-900"] *) {
            background-color: #eef2ff !important;
            border: 1px solid #c7d2fe !important;
            color: #4338ca !important;
            font-weight: 700 !important;
        }

        /* Purple / Violet Badges & Chips */
        html.theme-light [class*="bg-purple-500/10"]:not(aside nav *),
        html.theme-light [class*="bg-purple-500/15"]:not(aside nav *),
        html.theme-light [class*="bg-purple-500/20"]:not(aside nav *),
        .theme-light [class*="bg-purple-500/10"]:not(aside nav *),
        .theme-light [class*="bg-purple-500/15"]:not(aside nav *),
        .theme-light [class*="bg-purple-500/20"]:not(aside nav *) {
            background-color: #f5f3ff !important;
            border: 1px solid #ddd6fe !important;
            color: #6d28d9 !important;
            font-weight: 700 !important;
        }
        html.theme-light [class*="text-purple-400"]:not(header *):not(aside nav *),
        .theme-light [class*="text-purple-400"]:not(header *):not(aside nav *) {
            color: #7c3aed !important;
        }

        /* Unassigned / Neutral / Slate Badge */
        html.theme-light span[class*="bg-slate-900"]:not(aside *),
        .theme-light span[class*="bg-slate-900"]:not(aside *) {
            background-color: #f1f5f9 !important;
            border: 1px solid #cbd5e1 !important;
            color: #475569 !important;
            font-weight: 600 !important;
        }

        /* 11. Buttons & Interactive Controls */
        /* Primary Brand Action Buttons (Vibrant Royal Sapphire & Gradient Shine) */
        html.theme-light button[class*="bg-indigo-600"],
        html.theme-light a[class*="bg-indigo-600"]:not(aside nav *),
        html.theme-light button[class*="bg-blue-600"],
        html.theme-light a[class*="bg-blue-600"]:not(aside nav *),
        html.theme-light button[class*="bg-indigo-500"]:not(header *):not(aside *),
        html.theme-light button[class*="bg-blue-500"]:not(header *):not(aside *),
        .theme-light button[class*="bg-indigo-600"],
        .theme-light a[class*="bg-indigo-600"]:not(aside nav *),
        .theme-light button[class*="bg-blue-600"],
        .theme-light a[class*="bg-blue-600"]:not(aside nav *),
        .theme-light button[class*="bg-indigo-500"]:not(header *):not(aside *),
        .theme-light button[class*="bg-blue-500"]:not(header *):not(aside *) {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%) !important;
            border: 1px solid #3730a3 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.28) !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        html.theme-light button[class*="bg-indigo-600"]:hover,
        html.theme-light a[class*="bg-indigo-600"]:not(aside nav *):hover,
        html.theme-light button[class*="bg-blue-600"]:hover,
        html.theme-light a[class*="bg-blue-600"]:not(aside nav *):hover,
        .theme-light button[class*="bg-indigo-600"]:hover,
        .theme-light a[class*="bg-indigo-600"]:not(aside nav *):hover,
        .theme-light button[class*="bg-blue-600"]:hover,
        .theme-light a[class*="bg-blue-600"]:not(aside nav *):hover {
            background: linear-gradient(135deg, #4338ca 0%, #2563eb 100%) !important;
            box-shadow: 0 6px 18px rgba(79, 70, 229, 0.38) !important;
            transform: translateY(-1px) !important;
        }

        /* Action Buttons: Sky / Edit Button */
        html.theme-light button[class*="text-sky-400"][class*="bg-sky-500/10"],
        .theme-light button[class*="text-sky-400"][class*="bg-sky-500/10"] {
            background-color: #e0f2fe !important;
            border: 1px solid #bae6fd !important;
            color: #0284c7 !important;
        }
        html.theme-light button[class*="text-sky-400"][class*="bg-sky-500/10"]:hover,
        .theme-light button[class*="text-sky-400"][class*="bg-sky-500/10"]:hover {
            background-color: #bae6fd !important;
            color: #0369a1 !important;
        }

        /* Action Buttons: Rose / Delete Button */
        html.theme-light button[class*="text-rose-400"][class*="bg-rose-500/10"],
        .theme-light button[class*="text-rose-400"][class*="bg-rose-500/10"] {
            background-color: #fff1f2 !important;
            border: 1px solid #fecdd3 !important;
            color: #e11d48 !important;
        }
        html.theme-light button[class*="text-rose-400"][class*="bg-rose-500/10"]:hover,
        .theme-light button[class*="text-rose-400"][class*="bg-rose-500/10"]:hover {
            background-color: #fecdd3 !important;
            color: #be123c !important;
        }

        /* Secondary Neutral Buttons */
        html.theme-light button[class*="bg-slate-800"]:not(header *),
        html.theme-light a[class*="bg-slate-800"]:not(header *),
        .theme-light button[class*="bg-slate-800"]:not(header *),
        .theme-light a[class*="bg-slate-800"]:not(header *) {
            background-color: #f8fafc !important;
            border: 1px solid #cbd5e1 !important;
            color: #334155 !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04) !important;
        }
        html.theme-light button[class*="bg-slate-800"]:hover:not(header *),
        html.theme-light a[class*="bg-slate-800"]:hover:not(header *),
        .theme-light button[class*="bg-slate-800"]:hover:not(header *),
        .theme-light a[class*="bg-slate-800"]:hover:not(header *) {
            background-color: #f1f5f9 !important;
            border-color: #94a3b8 !important;
            color: #0f172a !important;
            transform: translateY(-1px) !important;
        }

        /* Navigation Tab Bars (Settings, Projects, Employee Modals) */
        html.theme-light [class*="bg-slate-950/90"].border-slate-800\/80,
        html.theme-light [class*="bg-slate-950"].border-slate-800.text-xs,
        .theme-light [class*="bg-slate-950/90"].border-slate-800\/80,
        .theme-light [class*="bg-slate-950"].border-slate-800.text-xs {
            background: #f1f5f9 !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.04) !important;
        }

        /* File Selector Button */
        html.theme-light input::file-selector-button,
        .theme-light input::file-selector-button {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%) !important;
            color: #ffffff !important;
            border: none !important;
            border-radius: 0.75rem !important;
            padding: 0.375rem 0.75rem !important;
            font-weight: 700 !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
        }

        /* 12. Forms, Inputs, Selects & Native Dropdowns */
        html.theme-light label,
        .theme-light label {
            color: #1e293b !important;
            font-weight: 600 !important;
        }
        html.theme-light input:not([type="submit"]):not([type="button"]):not([type="checkbox"]):not([type="radio"]),
        html.theme-light select,
        html.theme-light textarea,
        .theme-light input:not([type="submit"]):not([type="button"]):not([type="checkbox"]):not([type="radio"]),
        .theme-light select,
        .theme-light textarea {
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            color: #0f172a !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04) !important;
            border-radius: 0.75rem !important;
        }
        html.theme-light select option,
        .theme-light select option {
            background-color: #ffffff !important;
            color: #0f172a !important;
        }
        html.theme-light input::placeholder,
        html.theme-light textarea::placeholder,
        .theme-light input::placeholder,
        .theme-light textarea::placeholder {
            color: #94a3b8 !important;
        }
        html.theme-light input:focus,
        html.theme-light select:focus,
        html.theme-light textarea:focus,
        .theme-light input:focus,
        .theme-light select:focus,
        .theme-light textarea:focus {
            border-color: #4f46e5 !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.16) !important;
            outline: none !important;
        }

        /* 13. Modals, Drawers & Backdrop Overlay */
        html.theme-light .fixed.inset-0[class*="bg-black"],
        html.theme-light [class*="bg-black/80"],
        html.theme-light [class*="bg-black/75"],
        html.theme-light [class*="bg-black/60"],
        html.theme-light [class*="bg-black/50"],
        .theme-light .fixed.inset-0[class*="bg-black"],
        .theme-light [class*="bg-black/80"],
        .theme-light [class*="bg-black/75"],
        .theme-light [class*="bg-black/60"],
        .theme-light [class*="bg-black/50"] {
            background-color: rgba(15, 23, 42, 0.45) !important;
            backdrop-filter: blur(12px) !important;
            -webkit-backdrop-filter: blur(12px) !important;
        }
        html.theme-light .fixed.inset-0 .rounded-3xl[class*="bg-slate-900"],
        html.theme-light .fixed.inset-0 .rounded-3xl[class*="bg-slate-950"],
        .theme-light .fixed.inset-0 .rounded-3xl[class*="bg-slate-900"],
        .theme-light .fixed.inset-0 .rounded-3xl[class*="bg-slate-950"] {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 25px 60px -12px rgba(15, 23, 42, 0.20), 0 0 0 1px rgba(0, 0, 0, 0.04) !important;
        }
        html.theme-light .fixed.inset-0 .border-b,
        .theme-light .fixed.inset-0 .border-b {
            border-bottom-color: #e2e8f0 !important;
        }
        html.theme-light .fixed.inset-0 .border-t,
        .theme-light .fixed.inset-0 .border-t {
            border-top-color: #e2e8f0 !important;
        }
        html.theme-light .fixed.inset-0 button.text-slate-400:hover,
        .theme-light .fixed.inset-0 button.text-slate-400:hover {
            color: #0f172a !important;
            background-color: #f1f5f9 !important;
        }

        /* 14. Flatpickr Calendar in Light Theme */
        html.theme-light .flatpickr-calendar,
        body.theme-light .flatpickr-calendar {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.12), 0 8px 10px -6px rgba(15, 23, 42, 0.08) !important;
        }
        html.theme-light .flatpickr-months {
            background: #ffffff !important;
            border-bottom: 1px solid #f1f5f9 !important;
        }
        html.theme-light .flatpickr-current-month {
            color: #0f172a !important;
        }
        html.theme-light .flatpickr-current-month .flatpickr-monthDropdown-months {
            background: #ffffff !important;
            color: #0f172a !important;
        }
        html.theme-light span.flatpickr-weekday {
            color: #64748b !important;
        }
        html.theme-light .flatpickr-day {
            color: #334155 !important;
        }
        html.theme-light .flatpickr-day:hover {
            background: #f1f5f9 !important;
            color: #0f172a !important;
        }
        html.theme-light .flatpickr-day.today {
            border-color: #4f46e5 !important;
            color: #4f46e5 !important;
        }
        html.theme-light .flatpickr-day.selected {
            background: #4f46e5 !important;
            color: #ffffff !important;
            border-color: #4f46e5 !important;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.35) !important;
        }

        /* 15. AI Copilot Drawer & Modal (Porcelain Luxury) */
        html.theme-light #ai-chat-box,
        .theme-light #ai-chat-box {
            background-color: #ffffff !important;
        }
        html.theme-light #ai-chat-box [class*="bg-slate-900"],
        .theme-light #ai-chat-box [class*="bg-slate-900"] {
            background-color: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04) !important;
        }
        html.theme-light #ai-chat-box [class*="text-slate-200"],
        .theme-light #ai-chat-box [class*="text-slate-200"] {
            color: #1e293b !important;
        }
        html.theme-light #ai-chat-box [class*="bg-indigo-600"],
        .theme-light #ai-chat-box [class*="bg-indigo-600"] {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%) !important;
            color: #ffffff !important;
        }
        html.theme-light button[class*="from-indigo-600"][class*="via-purple-600"],
        .theme-light button[class*="from-indigo-600"][class*="via-purple-600"] {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%) !important;
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.35) !important;
        }

        /* 16. Floating Real-Time Live Toasts */
        html.theme-light .fixed.bottom-5.right-5 [class*="bg-slate-950"],
        html.theme-light .fixed.bottom-5.right-5 [class*="bg-rose-950"],
        html.theme-light .fixed.bottom-5.right-5 [class*="bg-amber-950"],
        html.theme-light .fixed.bottom-5.right-5 [class*="bg-emerald-950"],
        .theme-light .fixed.bottom-5.right-5 [class*="bg-slate-950"],
        .theme-light .fixed.bottom-5.right-5 [class*="bg-rose-950"],
        .theme-light .fixed.bottom-5.right-5 [class*="bg-amber-950"],
        .theme-light .fixed.bottom-5.right-5 [class*="bg-emerald-950"] {
            background: rgba(255, 255, 255, 0.96) !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 20px 35px -5px rgba(15, 23, 42, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.04) !important;
            color: #0f172a !important;
        }
        html.theme-light .fixed.bottom-5.right-5 p.text-white,
        .theme-light .fixed.bottom-5.right-5 p.text-white {
            color: #0f172a !important;
            font-weight: 700 !important;
        }
        html.theme-light .fixed.bottom-5.right-5 p.text-slate-300,
        .theme-light .fixed.bottom-5.right-5 p.text-slate-300 {
            color: #475569 !important;
        }

        /* 17. Flash Message Banners */
        html.theme-light .p-4.rounded-2xl.bg-emerald-500\/10,
        .theme-light .p-4.rounded-2xl.bg-emerald-500\/10 {
            background-color: #ecfdf5 !important;
            border: 1px solid #a7f3d0 !important;
            color: #065f46 !important;
        }
        html.theme-light .p-4.rounded-2xl.bg-amber-500\/10,
        .theme-light .p-4.rounded-2xl.bg-amber-500\/10 {
            background-color: #fffbeb !important;
            border: 1px solid #fde68a !important;
            color: #92400e !important;
        }
        html.theme-light .p-4.rounded-2xl.bg-rose-500\/10,
        .theme-light .p-4.rounded-2xl.bg-rose-500\/10 {
            background-color: #fff1f2 !important;
            border: 1px solid #fecdd3 !important;
            color: #9f1239 !important;
        }

        /* 18. Typography & Contrast Integrity */
        html.theme-light h1,
        html.theme-light h2,
        html.theme-light h3,
        html.theme-light h4,
        .theme-light h1,
        .theme-light h2,
        .theme-light h3,
        .theme-light h4 {
            color: #0f172a !important;
            letter-spacing: -0.02em !important;
        }
        html.theme-light p.text-white:not(button *):not([class*="bg-indigo-600"] *):not([class*="bg-emerald-600"] *):not([class*="bg-rose-600"] *),
        html.theme-light span.text-white:not(button *):not([class*="bg-indigo-600"] *):not([class*="bg-emerald-600"] *):not([class*="bg-rose-600"] *):not([class*="bg-purple-600"] *):not([class*="to-indigo-500"] *),
        .theme-light p.text-white:not(button *):not([class*="bg-indigo-600"] *):not([class*="bg-emerald-600"] *):not([class*="bg-rose-600"] *),
        .theme-light span.text-white:not(button *):not([class*="bg-indigo-600"] *):not([class*="bg-emerald-600"] *):not([class*="bg-rose-600"] *):not([class*="bg-purple-600"] *):not([class*="to-indigo-500"] *) {
            color: #0f172a !important;
        }
        html.theme-light .text-slate-100,
        html.theme-light .text-slate-200,
        .theme-light .text-slate-100,
        .theme-light .text-slate-200 {
            color: #1e293b !important;
        }
        html.theme-light .text-slate-300,
        .theme-light .text-slate-300 {
            color: #334155 !important;
        }
        html.theme-light .text-slate-400,
        .theme-light .text-slate-400 {
            color: #64748b !important;
        }
        html.theme-light .text-slate-500,
        .theme-light .text-slate-500 {
            color: #64748b !important;
        }
        html.theme-light [class*="border-slate-800"],
        html.theme-light [class*="border-slate-700"],
        .theme-light [class*="border-slate-800"],
        .theme-light [class*="border-slate-700"] {
            border-color: #e2e8f0 !important;
        }
        html.theme-light [class*="divide-slate-800"],
        .theme-light [class*="divide-slate-800"] {
            border-color: #f1f5f9 !important;
        }

        /* 19. Elevation & Shadows System */
        html.theme-light .shadow-2xl,
        html.theme-light .shadow-xl,
        .theme-light .shadow-2xl,
        .theme-light .shadow-xl {
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05), 0 8px 10px -6px rgba(15, 23, 42, 0.03) !important;
        }
    </style>
</head>
@php
    $canAccessAiCopilot = false;
    if (auth()->check()) {
        $currUser = auth()->user();
        $isSuper = method_exists($currUser, 'isSuperAdmin') && $currUser->isSuperAdmin();
        $isDev = method_exists($currUser, 'isDeveloperAdmin') && $currUser->isDeveloperAdmin();
        // The AI Copilot is exclusively restricted to Super Admin and Developer Admin accounts
        if ($isSuper || $isDev) {
            $canAccessAiCopilot = true;
        }
    }
@endphp
<body class="bg-slate-900 text-slate-100 antialiased min-h-screen flex" 
      x-data="{ 
          sidebarOpen: false, 
          aiModalOpen: false, 
          aiQuery: '', 
          isThinking: false, 
          aiModel: '{{ \App\Models\SystemSetting::get("gemini_default_model", "gemini-1.5-flash") }}',
          currentTheme: (window.getHrmsTheme ? window.getHrmsTheme() : (localStorage.getItem('hrms_theme') || 'dark')),
          notifOpen: false,
          unreadNotifCount: 0,
          notificationsList: [],
          livePulseInterval: null,
          lastPulse: { production_hash: '', dashboard_hash: '', unread_notifications: -1, active_breaks_count: -1 },
          liveConnected: true,
          liveToasts: [],
          soundEnabled: (localStorage.getItem('hrms_sound_enabled') !== 'false'),
          toggleSound() {
              this.soundEnabled = !this.soundEnabled;
              try {
                  localStorage.setItem('hrms_sound_enabled', this.soundEnabled ? 'true' : 'false');
              } catch(e) {}
              if (this.soundEnabled) {
                  this.playSound('preview');
              }
          },
          playSound(type = 'default') {
              if (!this.soundEnabled && type !== 'preview') return;
              try {
                  const AudioContext = window.AudioContext || window.webkitAudioContext;
                  if (!AudioContext) return;
                  const ctx = new AudioContext();
                  if (ctx.state === 'suspended') {
                      ctx.resume();
                  }
                  const now = ctx.currentTime;

                  if (type === 'BREAK_EXCEEDED' || type === 'error' || type === 'alert') {
                      // High-urgency tri-tone alert
                      const osc1 = ctx.createOscillator();
                      const gain1 = ctx.createGain();
                      osc1.type = 'sawtooth';
                      osc1.frequency.setValueAtTime(587.33, now); // D5
                      gain1.gain.setValueAtTime(0.08, now);
                      gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.18);
                      osc1.connect(gain1);
                      gain1.connect(ctx.destination);
                      osc1.start(now);
                      osc1.stop(now + 0.18);

                      const osc2 = ctx.createOscillator();
                      const gain2 = ctx.createGain();
                      osc2.type = 'sawtooth';
                      osc2.frequency.setValueAtTime(440, now + 0.1); // A4
                      gain2.gain.setValueAtTime(0.1, now + 0.1);
                      gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
                      osc2.connect(gain2);
                      gain2.connect(ctx.destination);
                      osc2.start(now + 0.1);
                      osc2.stop(now + 0.35);
                  } else if (type === 'BREAK_STARTED' || type === 'warning') {
                      // Warm notification chime (F5 -> D5)
                      const osc = ctx.createOscillator();
                      const gain = ctx.createGain();
                      osc.type = 'sine';
                      osc.frequency.setValueAtTime(698.46, now);
                      osc.frequency.exponentialRampToValueAtTime(587.33, now + 0.2);
                      gain.gain.setValueAtTime(0.14, now);
                      gain.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
                      osc.connect(gain);
                      gain.connect(ctx.destination);
                      osc.start(now);
                      osc.stop(now + 0.35);
                  } else {
                      // Crystal Clear Corporate Glass Chime (E5 659Hz -> A5 880Hz)
                      const osc1 = ctx.createOscillator();
                      const gain1 = ctx.createGain();
                      osc1.type = 'sine';
                      osc1.frequency.setValueAtTime(659.25, now);
                      gain1.gain.setValueAtTime(0.12, now);
                      gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.22);
                      osc1.connect(gain1);
                      gain1.connect(ctx.destination);
                      osc1.start(now);
                      osc1.stop(now + 0.22);

                      const osc2 = ctx.createOscillator();
                      const gain2 = ctx.createGain();
                      osc2.type = 'sine';
                      osc2.frequency.setValueAtTime(880.00, now + 0.08);
                      gain2.gain.setValueAtTime(0.16, now + 0.08);
                      gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.42);
                      osc2.connect(gain2);
                      gain2.connect(ctx.destination);
                      osc2.start(now + 0.08);
                      osc2.stop(now + 0.42);
                  }
              } catch(e) {}
          },
          toggleTheme() {
              this.currentTheme = (this.currentTheme === 'light') ? 'dark' : 'light';
              try {
                  localStorage.setItem('hrms_theme', this.currentTheme);
              } catch(e) {}
              if (window.applyHrmsTheme) {
                  window.applyHrmsTheme(this.currentTheme);
              }
          },
          async fetchNotifications() {
              try {
                  const res = await fetch('{{ route("notifications.feed") }}', {
                      headers: {
                          'Accept': 'application/json',
                          'X-Requested-With': 'XMLHttpRequest'
                      }
                  });
                  if (res.ok) {
                      const data = await res.json();
                      const previousCount = this.unreadNotifCount;
                      this.unreadNotifCount = data.unread_count || 0;
                      this.notificationsList = data.notifications || [];

                      // If new unread notification arrived and tab is active, trigger live toast & sound
                      if (data.unread_count > previousCount && previousCount >= 0 && data.notifications.length > 0) {
                          const latest = data.notifications[0];
                          this.playSound(latest.type || 'default');
                          this.triggerLiveToast({
                              id: latest.id || Date.now(),
                              title: latest.title || 'New HRMS Alert',
                              message: latest.message || '',
                              type: latest.type || 'INFO',
                              icon: latest.icon || 'fa-bell',
                              action_url: latest.action_url || null
                          });
                      }
                  }
              } catch(e) {}
          },
          async markNotificationRead(notif) {
              try {
                  await fetch('{{ url("notifications") }}/' + notif.id + '/read', {
                      method: 'POST',
                      headers: {
                          'Content-Type': 'application/json',
                          'Accept': 'application/json',
                          'X-Requested-With': 'XMLHttpRequest',
                          'X-CSRF-TOKEN': '{{ csrf_token() }}'
                      }
                  });
                  notif.is_read = true;
                  this.unreadNotifCount = Math.max(0, this.unreadNotifCount - 1);
                  if (notif.action_url) {
                      window.location.href = notif.action_url;
                  }
              } catch(e) {}
          },
          async markAllNotificationsRead() {
              try {
                  await fetch('{{ route("notifications.read-all") }}', {
                      method: 'POST',
                      headers: {
                          'Content-Type': 'application/json',
                          'Accept': 'application/json',
                          'X-Requested-With': 'XMLHttpRequest',
                          'X-CSRF-TOKEN': '{{ csrf_token() }}'
                      }
                  });
                  this.unreadNotifCount = 0;
                  this.notificationsList.forEach(n => n.is_read = true);
              } catch(e) {}
          },
          triggerLiveToast(toast) {
              const toastObj = {
                  id: toast.id || Date.now(),
                  title: toast.title,
                  message: toast.message,
                  type: toast.type || 'INFO',
                  icon: toast.icon || 'fa-bell',
                  action_url: toast.action_url || null
              };
              this.liveToasts.push(toastObj);
              this.playSound(toastObj.type);
              setTimeout(() => {
                  this.removeLiveToast(toastObj.id);
              }, 6000);
          },
          removeLiveToast(id) {
              this.liveToasts = this.liveToasts.filter(t => t.id !== id);
          },
          async runLivePulse() {
              try {
                  const res = await fetch('{{ route("live.pulse") }}', {
                      headers: {
                          'Accept': 'application/json',
                          'X-Requested-With': 'XMLHttpRequest'
                      }
                  });
                  if (!res.ok) {
                      this.liveConnected = false;
                      return;
                  }
                  this.liveConnected = true;
                  const data = await res.json();

                  // 1. Check Unread Notifications Delta
                  if (this.lastPulse.unread_notifications !== -1 && data.unread_notifications !== this.lastPulse.unread_notifications) {
                      this.fetchNotifications();
                  }

                  // 2. Check Production Floor & Active Breaks Delta
                  if (data.production_hash !== this.lastPulse.production_hash) {
                      window.dispatchEvent(new CustomEvent('hrms:production-sync', { detail: data }));
                  }

                  // 3. Check Dashboard & KPI Delta
                  if (data.dashboard_hash !== this.lastPulse.dashboard_hash) {
                      window.dispatchEvent(new CustomEvent('hrms:dashboard-sync', { detail: data }));
                  }

                  // 4. Dispatch Universal Live Sync Event
                  window.dispatchEvent(new CustomEvent('hrms:live-update', { detail: data }));

                  // Update cached pulse state
                  this.lastPulse = {
                      production_hash: data.production_hash,
                      dashboard_hash: data.dashboard_hash,
                      unread_notifications: data.unread_notifications,
                      active_breaks_count: data.active_breaks_count
                  };
              } catch(e) {
                  this.liveConnected = false;
              }
          },
          startLiveSync() {
              this.runLivePulse();

              const scheduleInterval = (delayMs) => {
                  if (this.livePulseInterval) clearInterval(this.livePulseInterval);
                  this.livePulseInterval = setInterval(() => {
                      this.runLivePulse();
                  }, delayMs);
              };

              // 4s polling when tab is active
              scheduleInterval(4000);

              // Adaptive visibility handler: slow down when tab is hidden, immediate check on focus
              document.addEventListener('visibilitychange', () => {
                  if (document.hidden) {
                      scheduleInterval(30000);
                  } else {
                      this.runLivePulse();
                      scheduleInterval(4000);
                  }
              });
          },
          karachiLiveClock: '',
          karachiLiveDate: '',
          updateLiveClock() {
              const now = new Date();
              this.karachiLiveClock = now.toLocaleTimeString('en-US', { timeZone: 'Asia/Karachi', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
              this.karachiLiveDate = now.toLocaleDateString('en-US', { timeZone: 'Asia/Karachi', weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });
          },
          init() {
              window._hrmsPlaySound = (type) => this.playSound(type);
              this.updateLiveClock();
              setInterval(() => this.updateLiveClock(), 1000);
              if (window.applyHrmsTheme) {
                  window.applyHrmsTheme(this.currentTheme);
              }
              this.fetchNotifications();
              this.startLiveSync();
          },
          aiMessages: [
              { sender: 'ai', text: 'Executive Copilot online. I have live scanning access across all AxeCorp workforce, attendance, multi-company payrolls, leaves, and projects. Toggle between ⚡ Flash and 🧠 Pro in the top header.' }
          ],
          async sendAiQuery(promptText) {
              const q = promptText || this.aiQuery;
              if (!q.trim()) return;
              
              this.aiMessages.push({ sender: 'user', text: q });
              this.aiQuery = '';
              this.isThinking = true;

              try {
                  const res = await fetch('{{ route("ai.chat") }}', {
                      method: 'POST',
                      headers: {
                          'Content-Type': 'application/json',
                          'X-CSRF-TOKEN': '{{ csrf_token() }}'
                      },
                      body: JSON.stringify({ prompt: q, model: this.aiModel })
                  });
                  const data = await res.json();
                  this.aiMessages.push({ sender: 'ai', responseData: data });
              } catch (e) {
                  this.aiMessages.push({ sender: 'ai', text: 'Sorry, an error occurred while processing your AI request.' });
              } finally {
                  this.isThinking = false;
                  $nextTick(() => {
                      const chatBox = document.getElementById('ai-chat-box');
                      if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
                  });
              }
          }
      }"
      @if($canAccessAiCopilot)
      @keydown.window.cmd.k.prevent="aiModalOpen = true"
      @keydown.window.ctrl.k.prevent="aiModalOpen = true"
      @endif>

    <!-- Mobile Sidebar Backdrop -->
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden"></div>

    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-950 border-r border-slate-800 transition-transform duration-300 ease-in-out lg:translate-x-0 flex flex-col justify-between">
        <div>
            <!-- Brand Logo -->
            <div class="h-16 flex items-center px-6 border-b border-slate-800/80 bg-slate-950/50">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 group min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-slate-900/90 border border-slate-800/80 flex items-center justify-center p-1.5 shadow-lg shadow-indigo-500/10 group-hover:scale-105 group-hover:border-indigo-500/40 transition-all shrink-0">
                        <img src="{{ axecorp_logo_src() }}" alt="AxeCorp Logo" class="w-full h-full object-contain">
                    </div>
                    <span class="font-extrabold text-lg tracking-tight text-white group-hover:text-indigo-300 transition-colors truncate" title="AxeCorp">AxeCorp</span>
                </a>
            </div>

            <!-- Navigation Links -->
            <!-- Navigation Links with Rich Jewel-Toned Micro-Icon Badges -->
            <nav class="p-4 space-y-1.5">
                <a href="{{ route('dashboard') }}" class="group/nav flex items-center space-x-3 px-3 py-2.5 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('dashboard') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 shadow-md shadow-indigo-600/10' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200' }}">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-all shrink-0 {{ request()->routeIs('dashboard') ? 'bg-indigo-500 text-white shadow-sm' : 'bg-indigo-500/10 text-indigo-400 group-hover/nav:bg-indigo-500/20 group-hover/nav:text-indigo-300 group-hover/nav:scale-105' }}">
                        <i class="fa-solid fa-chart-pie text-xs"></i>
                    </div>
                    <span class="font-semibold">Dashboard</span>
                </a>

                @feature('companies')
                @superadmin
                <a href="{{ route('companies.index') }}" class="group/nav flex items-center space-x-3 px-3 py-2.5 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('companies.*') ? 'bg-sky-600/20 text-sky-400 border border-sky-500/30 shadow-md shadow-sky-600/10' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200' }}">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-all shrink-0 {{ request()->routeIs('companies.*') ? 'bg-sky-500 text-white shadow-sm' : 'bg-sky-500/10 text-sky-400 group-hover/nav:bg-sky-500/20 group-hover/nav:text-sky-300 group-hover/nav:scale-105' }}">
                        <i class="fa-solid fa-building text-xs"></i>
                    </div>
                    <span class="flex-1 font-semibold">Companies</span>
                </a>
                @endsuperadmin
                @endfeature

                @feature('employees')
                @admin
                <a href="{{ route('employees.index') }}" class="group/nav flex items-center space-x-3 px-3 py-2.5 rounded-xl font-medium text-sm transition-all {{ (request()->routeIs('employees.*') || request()->routeIs('admin.profile-requests.*')) ? 'bg-emerald-600/20 text-emerald-400 border border-emerald-500/30 shadow-md shadow-emerald-600/10' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200' }}">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-all shrink-0 {{ (request()->routeIs('employees.*') || request()->routeIs('admin.profile-requests.*')) ? 'bg-emerald-500 text-white shadow-sm' : 'bg-emerald-500/10 text-emerald-400 group-hover/nav:bg-emerald-500/20 group-hover/nav:text-emerald-300 group-hover/nav:scale-105' }}">
                        <i class="fa-solid fa-users text-xs"></i>
                    </div>
                    <span class="flex-1 font-semibold">Employees</span>
                    @if(Auth::user()->isSystemAdmin() || Auth::user()->isSuperAdmin() || Auth::user()->isOrgAdmin())
                        @php
                            $sidebarPendingApprovals = \App\Models\ProfileChangeRequest::where('status', 'PENDING')->count();
                        @endphp
                        @if($sidebarPendingApprovals > 0)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-500 text-slate-950 shadow-xs" title="{{ $sidebarPendingApprovals }} Pending Bank & NIC Approvals">
                                {{ $sidebarPendingApprovals }}
                            </span>
                        @endif
                    @endif
                </a>
                @endadmin
                @endfeature

                @feature('teams')
                <a href="{{ route('teams.index') }}" class="group/nav flex items-center space-x-3 px-3 py-2.5 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('teams.*') ? 'bg-purple-600/20 text-purple-400 border border-purple-500/30 shadow-md shadow-purple-600/10' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200' }}">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-all shrink-0 {{ request()->routeIs('teams.*') ? 'bg-purple-500 text-white shadow-sm' : 'bg-purple-500/10 text-purple-400 group-hover/nav:bg-purple-500/20 group-hover/nav:text-purple-300 group-hover/nav:scale-105' }}">
                        <i class="fa-solid fa-people-group text-xs"></i>
                    </div>
                    <span class="flex-1 font-semibold">
                        @if(Auth::user()->canManageTeams())
                            Teams & Squads
                        @else
                            My Team
                        @endif
                    </span>
                    @if(Auth::user()->isTeamLeader())
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-amber-500/20 text-amber-300 border border-amber-500/30 uppercase">
                            Lead
                        </span>
                    @endif
                </a>
                @endfeature

                @feature('attendance')
                <a href="{{ route('attendance.index') }}" class="group/nav flex items-center space-x-3 px-3 py-2.5 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('attendance.*') ? 'bg-amber-600/20 text-amber-400 border border-amber-500/30 shadow-md shadow-amber-600/10' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200' }}">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-all shrink-0 {{ request()->routeIs('attendance.*') ? 'bg-amber-500 text-white shadow-sm' : 'bg-amber-500/10 text-amber-400 group-hover/nav:bg-amber-500/20 group-hover/nav:text-amber-300 group-hover/nav:scale-105' }}">
                        <i class="fa-solid fa-user-clock text-xs"></i>
                    </div>
                    <span class="flex-1 font-semibold">Attendance</span>
                </a>
                @endfeature

                @feature('leaves')
                <a href="{{ route('leaves.index') }}" class="group/nav flex items-center space-x-3 px-3 py-2.5 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('leaves.*') ? 'bg-rose-600/20 text-rose-400 border border-rose-500/30 shadow-md shadow-rose-600/10' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200' }}">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-all shrink-0 {{ request()->routeIs('leaves.*') ? 'bg-rose-500 text-white shadow-sm' : 'bg-rose-500/10 text-rose-400 group-hover/nav:bg-rose-500/20 group-hover/nav:text-rose-300 group-hover/nav:scale-105' }}">
                        <i class="fa-solid fa-calendar-check text-xs"></i>
                    </div>
                    <span class="flex-1 font-semibold">{{ Auth::user()->isEmployee() ? 'My Leaves' : 'Leave Requests' }}</span>
                </a>
                @endfeature

                @feature('projects')
                <a href="{{ route('projects.index') }}" class="group/nav flex items-center space-x-3 px-3 py-2.5 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('projects.*') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30 shadow-md shadow-blue-600/10' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200' }}">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-all shrink-0 {{ request()->routeIs('projects.*') ? 'bg-blue-500 text-white shadow-sm' : 'bg-blue-500/10 text-blue-400 group-hover/nav:bg-blue-500/20 group-hover/nav:text-blue-300 group-hover/nav:scale-105' }}">
                        <i class="fa-solid fa-diagram-project text-xs"></i>
                    </div>
                    <span class="flex-1 font-semibold">Projects & Tasks</span>
                </a>
                @endfeature

                @feature('payroll')
                <a href="{{ route('payroll.index', ['lock' => 1]) }}" class="group/nav flex items-center space-x-3 px-3 py-2.5 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('payroll.*') ? 'bg-emerald-600/20 text-emerald-400 border border-emerald-500/30 shadow-md shadow-emerald-600/10' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200' }}">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-all shrink-0 {{ request()->routeIs('payroll.*') ? 'bg-emerald-600 text-white shadow-sm' : 'bg-emerald-500/10 text-emerald-400 group-hover/nav:bg-emerald-500/20 group-hover/nav:text-emerald-300 group-hover/nav:scale-105' }}">
                        <i class="fa-solid fa-file-invoice-dollar text-xs"></i>
                    </div>
                    <span class="flex-1 font-semibold">{{ Auth::user()->isEmployee() ? 'My Payslips' : 'Payroll' }}</span>
                </a>
                @endfeature

                @feature('settings')
                @admin
                <a href="{{ route('settings.index') }}" class="group/nav flex items-center space-x-3 px-3 py-2.5 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('settings.*') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 shadow-md shadow-indigo-600/10' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200' }}">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-all shrink-0 {{ request()->routeIs('settings.*') ? 'bg-slate-600 text-white shadow-sm' : 'bg-slate-500/10 text-slate-400 group-hover/nav:bg-slate-500/20 group-hover/nav:text-slate-300 group-hover/nav:scale-105' }}">
                        <i class="fa-solid fa-sliders text-xs"></i>
                    </div>
                    <span class="flex-1 font-semibold">Settings & Controls</span>
                </a>
                @endadmin
                @endfeature
            </nav>
        </div>

        <!-- User Footer Profile Info -->
        <div class="p-3.5 border-t border-slate-800/80 bg-slate-950/60">
            <div class="flex items-center justify-between gap-2">
                <a href="{{ route('profile.index') }}" class="flex items-center space-x-3 min-w-0 flex-1 hover:bg-slate-900/80 p-1.5 rounded-2xl transition-all group" title="Click to edit profile & picture">
                    @if(Auth::user()->avatar_url)
                        <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="w-9 h-9 rounded-full object-cover ring-2 ring-indigo-500/40 shrink-0 shadow-md group-hover:ring-indigo-400 transition-all">
                    @else
                        <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-purple-500 to-indigo-500 flex items-center justify-center font-bold text-white text-sm shrink-0 shadow-md ring-2 ring-indigo-500/20 group-hover:ring-indigo-400 transition-all">
                            {{ Auth::user()->initials }}
                        </div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold text-white truncate group-hover:text-indigo-300 transition-colors">{{ Auth::user()->name }}</p>
                        @developer
                            <p class="text-[10px] text-amber-400 truncate font-bold flex items-center space-x-1">
                                <i class="fa-solid fa-code text-[9px]"></i>
                                <span>Master Developer</span>
                            </p>
                        @else
                            <p class="text-[10px] text-indigo-400 truncate font-medium">{{ Auth::user()->role->label ?? 'User' }}</p>
                        @enddeveloper
                    </div>
                </a>
                <form action="{{ route('logout') }}" method="POST" class="shrink-0">
                    @csrf
                    <button type="submit" class="p-2 text-slate-400 hover:text-red-400 hover:bg-red-500/10 rounded-xl transition-colors" title="Logout">
                        <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 lg:pl-64 flex flex-col min-w-0">
        <!-- Top Navbar Header -->
        <header class="h-16 bg-slate-950/80 border-b border-slate-800/80 sticky top-0 z-30 backdrop-blur-md px-4 lg:px-8 flex items-center justify-between">
            <div class="flex items-center space-x-4 flex-1 max-w-xl">
                <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-900 lg:hidden">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                
                @if($canAccessAiCopilot)
                <!-- Global AI Search Trigger Input -->
                <button @click="aiModalOpen = true" class="w-full max-w-md px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 text-xs flex items-center justify-between hover:border-slate-700 transition-all">
                    <div class="flex items-center space-x-2.5">
                        <i class="fa-solid fa-wand-magic-sparkles text-indigo-400"></i>
                        <span>Search anything or ask AI Copilot...</span>
                    </div>
                    <kbd class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700 text-[10px] text-slate-400 font-mono">Ctrl K</kbd>
                </button>
                @endif
            </div>

            <!-- Top Actions -->
            <div class="flex items-center space-x-3">
                @if($canAccessAiCopilot)
                <button @click="aiModalOpen = true" class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold text-xs shadow-md shadow-indigo-600/20 flex items-center space-x-1.5">
                    <i class="fa-solid fa-robot"></i>
                    <span>AI Copilot</span>
                </button>
                @endif

                <!-- Dark / White Theme Toggle Switch Button (Before Date/Time) -->
                <button type="button" 
                        id="hrms-theme-toggle-btn"
                        @click="toggleTheme()" 
                        class="relative inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border transition-all duration-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/40 select-none group cursor-pointer"
                        :class="currentTheme === 'light' 
                            ? 'bg-white border-slate-200/90 text-slate-700 hover:bg-slate-50 hover:border-slate-300 shadow-xs' 
                            : 'bg-slate-900 border-slate-800 text-slate-300 hover:text-white hover:border-slate-700'"
                        :title="currentTheme === 'light' ? 'Switch to Dark Theme' : 'Switch to White / Light Theme'"
                        aria-label="Toggle dark and light theme">
                    
                    <!-- Sun / Moon Icon with smooth rotation -->
                    <div class="w-5 h-5 rounded-lg flex items-center justify-center transition-all duration-300"
                         :class="currentTheme === 'light' ? 'bg-amber-50 text-amber-500 border border-amber-200/60 shadow-xs' : 'bg-indigo-500/20 text-indigo-400'">
                        <i class="fa-solid text-xs transition-transform duration-300" 
                           :class="currentTheme === 'light' ? 'fa-sun rotate-0' : 'fa-moon -rotate-12'"></i>
                    </div>
                    
                    <!-- Theme Text Label -->
                    <span class="text-xs font-bold hidden sm:inline" 
                          :class="currentTheme === 'light' ? 'text-slate-700' : 'text-slate-300'"
                          x-text="currentTheme === 'light' ? 'Light' : 'Dark'">Dark</span>

                    <!-- Sliding Pill Switch Indicator -->
                    <div class="w-7 h-3.5 rounded-full p-0.5 transition-colors duration-300 flex items-center"
                         :class="currentTheme === 'light' ? 'bg-slate-200' : 'bg-slate-800 border border-slate-700'">
                        <div class="w-2.5 h-2.5 rounded-full transition-transform duration-300 transform shadow-xs"
                             :class="currentTheme === 'light' ? 'translate-x-3.5 bg-amber-500' : 'translate-x-0 bg-indigo-400'"></div>
                    </div>
                </button>

                <!-- Notification Sound Toggle Button -->
                <button type="button" 
                        @click="toggleSound()"
                        class="relative px-3 py-2 rounded-xl border transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 flex items-center space-x-2"
                        :class="currentTheme === 'light'
                            ? 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50 shadow-sm'
                            : 'bg-slate-900 border-slate-800 text-slate-300 hover:text-white hover:border-slate-700'"
                        :title="soundEnabled ? 'Notification Sounds: Active (Click to mute)' : 'Notification Sounds: Muted (Click to unmute)'"
                        aria-label="Toggle system notification sounds">
                    <i class="fa-solid text-xs" :class="soundEnabled ? 'fa-volume-high text-emerald-400' : 'fa-volume-xmark text-slate-500'"></i>
                    <span class="text-xs font-bold hidden sm:inline" x-text="soundEnabled ? 'Sound ON' : 'Muted'">Sound ON</span>
                </button>

                <!-- Real-Time Manager & Admin Notification Bell Hub -->
                <div class="relative" x-data="{}" @click.away="notifOpen = false">
                    <button type="button" 
                            @click="notifOpen = !notifOpen; if(notifOpen) fetchNotifications();"
                            class="relative p-2 rounded-xl border transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                            :class="currentTheme === 'light'
                                ? 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50 shadow-sm'
                                : 'bg-slate-900 border-slate-800 text-slate-300 hover:text-white hover:border-slate-700'"
                            title="Notifications & Break Alerts">
                        <i class="fa-solid fa-bell text-sm"></i>
                        
                        <!-- Unread Count Badge with pulse -->
                        <template x-if="unreadNotifCount > 0">
                            <span class="absolute -top-1 -right-1 flex h-4 min-w-[16px] px-1 items-center justify-center rounded-full bg-rose-500 text-[9px] font-black text-white shadow-md animate-pulse">
                                <span x-text="unreadNotifCount > 99 ? '99+' : unreadNotifCount"></span>
                            </span>
                        </template>
                    </button>

                    <!-- Notifications Dropdown Menu -->
                    <div x-show="notifOpen" 
                         x-cloak 
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                         class="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl border shadow-2xl z-50 overflow-hidden"
                         :class="currentTheme === 'light' ? 'bg-white border-slate-200 text-slate-800' : 'bg-slate-950 border-slate-800 text-slate-100'">
                        
                        <!-- Dropdown Header with Test Sound Action -->
                        <div class="px-4 py-3 border-b flex items-center justify-between"
                             :class="currentTheme === 'light' ? 'border-slate-100 bg-slate-50/80' : 'border-slate-800/80 bg-slate-900/60'">
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-xs tracking-tight">Live Alerts & Notifications</span>
                                <template x-if="unreadNotifCount > 0">
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-500/20 text-rose-400 border border-rose-500/30" x-text="unreadNotifCount + ' unread'"></span>
                                </template>
                            </div>
                            <div class="flex items-center space-x-3">
                                <button type="button" 
                                        @click="playSound('preview')"
                                        class="text-[10px] font-semibold text-slate-400 hover:text-white flex items-center space-x-1 px-2 py-0.5 rounded-lg bg-slate-800/60 hover:bg-slate-800 transition-colors"
                                        title="Play test chime">
                                    <i class="fa-solid fa-volume-high text-indigo-400 text-[10px]"></i>
                                    <span>Test Sound</span>
                                </button>
                                <button type="button" 
                                        @click="markAllNotificationsRead()"
                                        class="text-[11px] font-semibold text-indigo-400 hover:text-indigo-300 transition-colors">
                                    Mark read
                                </button>
                            </div>
                        </div>

                        <!-- Notifications List -->
                        <div class="max-h-80 overflow-y-auto divide-y"
                             :class="currentTheme === 'light' ? 'divide-slate-100' : 'divide-slate-800/60'">
                            
                            <template x-if="notificationsList.length === 0">
                                <div class="py-8 text-center px-4">
                                    <div class="w-10 h-10 rounded-full bg-slate-800/40 text-slate-500 flex items-center justify-center mx-auto mb-2 text-sm">
                                        <i class="fa-regular fa-bell-slash"></i>
                                    </div>
                                    <p class="text-xs font-medium text-slate-400">No new notifications</p>
                                    <p class="text-[10px] text-slate-500 mt-0.5">You will receive real-time break and clock-in alerts here.</p>
                                </div>
                            </template>

                            <template x-for="notif in notificationsList" :key="notif.id">
                                <div @click="markNotificationRead(notif)" 
                                     class="p-3.5 flex items-start space-x-3 cursor-pointer transition-colors"
                                     :class="[
                                         notif.is_read 
                                             ? (currentTheme === 'light' ? 'hover:bg-slate-50' : 'hover:bg-slate-900/40 opacity-70') 
                                             : (currentTheme === 'light' ? 'bg-indigo-50/50 hover:bg-indigo-50' : 'bg-slate-900/90 hover:bg-slate-900')
                                     ]">
                                    
                                    <!-- Icon -->
                                    <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 text-xs border"
                                         :class="[
                                             notif.type === 'BREAK_EXCEEDED' ? 'bg-rose-500/10 border-rose-500/30 text-rose-400' :
                                             (notif.type === 'BREAK_STARTED' ? 'bg-amber-500/10 border-amber-500/30 text-amber-400' :
                                             (notif.type === 'BREAK_ENDED' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' :
                                             'bg-indigo-500/10 border-indigo-500/30 text-indigo-400'))
                                         ]">
                                        <i class="fa-solid" :class="notif.icon || 'fa-bell'"></i>
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <p class="text-xs font-bold truncate" :class="notif.is_read ? 'text-slate-300' : 'text-white'" x-text="notif.title"></p>
                                            <span class="text-[10px] text-slate-400 ml-2 whitespace-nowrap" x-text="notif.time_ago"></span>
                                        </div>
                                        <p class="text-[11px] text-slate-400 mt-0.5 line-clamp-2 leading-relaxed" x-text="notif.message"></p>
                                    </div>

                                    <!-- Unread Dot -->
                                    <template x-if="!notif.is_read">
                                        <span class="w-2 h-2 rounded-full bg-indigo-500 shrink-0 mt-1.5"></span>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <!-- Dropdown Footer -->
                        <div class="px-4 py-2 border-t text-center"
                             :class="currentTheme === 'light' ? 'border-slate-100 bg-slate-50' : 'border-slate-800/80 bg-slate-900/60'">
                            <a href="{{ route('attendance.index') }}" class="text-[11px] font-bold text-indigo-400 hover:text-indigo-300 transition-colors">
                                View Full Attendance & Break Logs &rarr;
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Live Pulse Real-Time Indicator -->
                <div class="hidden md:flex items-center space-x-1.5 text-[11px] font-bold px-2.5 py-1.5 rounded-lg border transition-all"
                     :class="liveConnected ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' : 'bg-rose-500/10 border-rose-500/20 text-rose-400'"
                     :title="liveConnected ? 'Live Auto-Sync Active (Zero Page Reload)' : 'Reconnecting to Live Sync...'">
                    <span class="w-2 h-2 rounded-full" :class="liveConnected ? 'bg-emerald-400 animate-pulse' : 'bg-rose-500 animate-ping'"></span>
                    <span class="tracking-wider uppercase text-[10px]" x-text="liveConnected ? 'LIVE' : 'OFFLINE'"></span>
                </div>

                <!-- Live Karachi Standard Time Clock -->
                <div class="hidden sm:flex items-center space-x-2 text-xs font-semibold px-3 py-1.5 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 font-mono shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span x-text="karachiLiveClock || '{{ date('h:i:s A') }}'">{{ date('h:i:s A') }}</span>
                    <span class="text-[9px] font-extrabold text-indigo-400 bg-indigo-500/20 px-1 py-0.5 rounded">PKT</span>
                    <span class="text-slate-600 text-[11px] font-sans font-normal hidden lg:inline">|</span>
                    <span class="text-slate-400 font-sans text-xs hidden lg:inline" x-text="karachiLiveDate || '{{ date('D, d M Y') }}'">{{ date('D, d M Y') }}</span>
                </div>

                <!-- Topbar Profile Link -->
                <a href="{{ route('profile.index') }}" class="flex items-center space-x-2 p-1.5 pl-2 pr-2.5 rounded-xl border transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 group"
                   :class="currentTheme === 'light' ? 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50 shadow-sm' : 'bg-slate-900 border-slate-800 text-slate-300 hover:text-white hover:border-slate-700'"
                   title="My Profile & Settings">
                    @if(Auth::user()->avatar_url)
                        <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="w-6 h-6 rounded-lg object-cover ring-1 ring-indigo-500/40">
                    @else
                        <div class="w-6 h-6 rounded-lg bg-gradient-to-tr from-purple-500 to-indigo-500 flex items-center justify-center font-extrabold text-white text-[10px] shadow-xs">
                            {{ Auth::user()->initials }}
                        </div>
                    @endif
                    <span class="text-xs font-bold hidden md:inline group-hover:text-indigo-400 transition-colors">{{ explode(' ', Auth::user()->name)[0] }}</span>
                </a>
            </div>
        </header>

        <!-- Flash Messages -->
        <main class="flex-1 px-4 lg:px-8 py-6 lg:py-8 w-full space-y-6 lg:space-y-8">
            @if(session('success'))
                <div x-data="{ show: true }" x-init="setTimeout(() => playSound('success'), 300)" x-show="show" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm font-medium flex items-center justify-between shadow-lg shadow-emerald-500/5">
                    <div class="flex items-center space-x-3">
                        <i class="fa-solid fa-circle-check text-emerald-400 text-base shrink-0"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button type="button" @click="show = false" class="text-emerald-400/60 hover:text-emerald-300 ml-3"><i class="fa-solid fa-xmark"></i></button>
                </div>
            @endif

            @if(session('warning'))
                <div x-data="{ show: true }" x-init="setTimeout(() => playSound('warning'), 300)" x-show="show" class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-400 text-sm font-medium flex items-center justify-between shadow-lg shadow-amber-500/5">
                    <div class="flex items-center space-x-3">
                        <i class="fa-solid fa-triangle-exclamation text-amber-400 text-base shrink-0"></i>
                        <span>{{ session('warning') }}</span>
                    </div>
                    <button type="button" @click="show = false" class="text-amber-400/60 hover:text-amber-300 ml-3"><i class="fa-solid fa-xmark"></i></button>
                </div>
            @endif

            @if(session('error') || session('danger'))
                <div x-data="{ show: true }" x-init="setTimeout(() => playSound('error'), 300)" x-show="show" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm font-medium flex items-center justify-between shadow-lg shadow-rose-500/5">
                    <div class="flex items-center space-x-3">
                        <i class="fa-solid fa-circle-xmark text-rose-400 text-base shrink-0"></i>
                        <span>{{ session('error') ?? session('danger') }}</span>
                    </div>
                    <button type="button" @click="show = false" class="text-rose-400/60 hover:text-rose-300 ml-3"><i class="fa-solid fa-xmark"></i></button>
                </div>
            @endif

            @if($errors->any())
                <div x-data="{ show: true }" x-show="show" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs font-medium shadow-lg shadow-rose-500/5 space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2 font-bold text-sm">
                            <i class="fa-solid fa-triangle-exclamation text-rose-400"></i>
                            <span>Please review and fix the following form errors:</span>
                        </div>
                        <button type="button" @click="show = false" class="text-rose-400/60 hover:text-rose-300"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <ul class="list-disc list-inside space-y-1 pl-2 text-rose-300/90 font-medium">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @yield('fullscreen_lock')

    @if($canAccessAiCopilot)
    <!-- Floating AI Assistant Launcher Button -->
    <div class="fixed bottom-6 right-6 z-40">
        <button @click="aiModalOpen = true" class="relative group p-4 rounded-full bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-700 text-white shadow-2xl hover:scale-105 transition-all flex items-center justify-center">
            <span class="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full bg-emerald-400 animate-pulse border-2 border-slate-900"></span>
            <i class="fa-solid fa-robot text-xl"></i>
            <span class="max-w-0 overflow-hidden group-hover:max-w-xs transition-all duration-300 ease-in-out whitespace-nowrap text-xs font-bold pl-0 group-hover:pl-2">AI Copilot</span>
        </button>
    </div>

    <!-- AI Intelligence Assistant Modal / Copilot Drawer -->
    <div x-show="aiModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-md">
        <div @click.away="aiModalOpen = false" class="bg-slate-950 border border-slate-800 rounded-3xl max-w-2xl w-full h-[85vh] flex flex-col shadow-2xl overflow-hidden relative">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-900/60">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white text-lg font-bold shadow-lg shadow-indigo-500/20">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="text-sm font-extrabold text-white tracking-tight">AxeCorp Executive Copilot</h3>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">SUPER ADMIN</span>
                        </div>
                        <p class="text-[11px] text-slate-400 font-medium">Whole-HRMS Scanning & Reasoning Engine</p>
                    </div>
                </div>

                <!-- Dual-Model On-The-Fly Switcher -->
                <div class="flex items-center space-x-3">
                    <div class="flex items-center bg-slate-950 p-1 rounded-xl border border-slate-800 shadow-inner">
                        <button type="button" @click="aiModel = 'gemini-flash-latest'" :class="aiModel.includes('flash') ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white'" class="px-2.5 py-1 rounded-lg text-[10px] transition-all flex items-center space-x-1">
                            <span>⚡ Flash</span>
                        </button>
                        <button type="button" @click="aiModel = 'gemini-pro-latest'" :class="aiModel.includes('pro') ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30' : 'text-slate-400 hover:text-white'" class="px-2.5 py-1 rounded-lg text-[10px] transition-all flex items-center space-x-1">
                            <span>🧠 Pro</span>
                        </button>
                    </div>

                    <button @click="aiModalOpen = false" class="text-slate-400 hover:text-white p-2 rounded-lg transition-colors"><i class="fa-solid fa-xmark text-lg"></i></button>
                </div>
            </div>

            <!-- Suggestion Pills -->
            <div class="px-6 py-2.5 bg-slate-900/30 border-b border-slate-800/60 flex flex-wrap items-center gap-2 text-[11px]">
                <span class="text-slate-500 font-semibold shrink-0">Scan:</span>
                <button @click="sendAiQuery('Who was late or absent this week and what are their delay minutes?')" class="px-2.5 py-1 rounded-full bg-indigo-600/20 border border-indigo-500/30 hover:bg-indigo-600 hover:text-white text-indigo-300 font-medium transition-all shrink-0"><i class="fa-solid fa-user-clock mr-1"></i>Late attendance this week</button>
                <button @click="sendAiQuery('Summarize total payroll payouts across companies and pending advance salaries')" class="px-2.5 py-1 rounded-full bg-emerald-600/20 border border-emerald-500/30 hover:bg-emerald-600 hover:text-white text-emerald-300 font-medium transition-all shrink-0"><i class="fa-solid fa-coins mr-1"></i>Payroll & Advance Salaries</button>
                <button @click="sendAiQuery('Show pending leave requests and employees with zero remaining quota')" class="px-2.5 py-1 rounded-full bg-amber-600/20 border border-amber-500/30 hover:bg-amber-600 hover:text-white text-amber-300 font-medium transition-all shrink-0"><i class="fa-solid fa-calendar-xmark mr-1"></i>Pending Leaves</button>
                <button @click="sendAiQuery('Software engineering active sprints, overdue tasks and developer workload')" class="px-2.5 py-1 rounded-full bg-purple-600/20 border border-purple-500/30 hover:bg-purple-600 hover:text-white text-purple-300 font-medium transition-all shrink-0"><i class="fa-solid fa-list-check mr-1"></i>Overdue Tasks & Projects</button>
            </div>

            <!-- Message Stream Area -->
            <div id="ai-chat-box" class="flex-1 p-6 overflow-y-auto space-y-4 text-xs">
                <template x-for="(msg, index) in aiMessages" :key="index">
                    <div>
                        <!-- User Message -->
                        <template x-if="msg.sender === 'user'">
                            <div class="flex justify-end mb-3">
                                <div class="bg-indigo-600 text-white font-medium px-4 py-2.5 rounded-2xl rounded-tr-none max-w-md shadow-md">
                                    <p x-text="msg.text"></p>
                                </div>
                            </div>
                        </template>

                        <!-- AI Response -->
                        <template x-if="msg.sender === 'ai'">
                            <div class="flex items-start space-x-3 mb-4">
                                <div class="w-7 h-7 rounded-xl bg-purple-600/20 text-purple-400 border border-purple-500/30 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                                    <i class="fa-solid fa-robot"></i>
                                </div>
                                <div class="bg-slate-900 border border-slate-800 rounded-2xl rounded-tl-none p-4 max-w-xl w-full space-y-3 shadow-xl">
                                    
                                    <!-- Telemetry / Model Badge Header on AI message -->
                                    <template x-if="msg.responseData && msg.responseData.model">
                                        <div class="flex items-center justify-between text-[10px] pb-2 border-b border-slate-800/80 font-mono text-slate-400">
                                            <div class="flex items-center space-x-1.5">
                                                <span x-show="msg.responseData.model.includes('pro')" class="px-1.5 py-0.5 rounded-md bg-purple-500/20 text-purple-300 font-bold border border-purple-500/30">🧠 Gemini Pro</span>
                                                <span x-show="!msg.responseData.model.includes('pro')" class="px-1.5 py-0.5 rounded-md bg-indigo-500/20 text-indigo-300 font-bold border border-indigo-500/30">⚡ Gemini Flash</span>
                                                <template x-if="msg.responseData.tools_called && msg.responseData.tools_called.length">
                                                    <span class="text-slate-500">•</span>
                                                </template>
                                                <template x-for="t in (msg.responseData.tools_called || [])" :key="t">
                                                    <span class="px-1.5 py-0.5 rounded bg-slate-800 text-indigo-300 border border-slate-700 font-mono" x-text="t"></span>
                                                </template>
                                            </div>
                                            <template x-if="msg.responseData.telemetry">
                                                <span class="text-slate-500" x-text="`${msg.responseData.telemetry.total_tokens || 0} tok • ${msg.responseData.telemetry.latency_ms || 0}ms`"></span>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- Render Markdown Answer -->
                                    <template x-if="msg.responseData && msg.responseData.summary">
                                        <div class="text-slate-200 font-medium leading-relaxed space-y-2" x-html="window.formatAiMarkdown ? window.formatAiMarkdown(msg.responseData.summary) : msg.responseData.summary"></div>
                                    </template>

                                    <!-- Fallback simple text message -->
                                    <template x-if="msg.text && (!msg.responseData || !msg.responseData.summary)">
                                        <div class="text-slate-200 font-medium leading-relaxed" x-html="window.formatAiMarkdown ? window.formatAiMarkdown(msg.text) : msg.text"></div>
                                    </template>

                                    <!-- Action Button Link if provided -->
                                    <template x-if="msg.responseData && msg.responseData.action_link">
                                        <div class="pt-2">
                                            <a :href="msg.responseData.action_link" class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-indigo-600/20 border border-indigo-500/30 text-indigo-300 font-bold text-xs hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                                                <span x-text="msg.responseData.action_text || 'Open Link'"></span>
                                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                            </a>
                                        </div>
                                    </template>

                                </div>
                            </div>
                        </template>

                    </div>
                </template>

                <!-- Thinking Indicator -->
                <div x-show="isThinking" class="flex items-center space-x-3 mb-4">
                    <div class="w-7 h-7 rounded-xl bg-purple-600/20 text-purple-400 border border-purple-500/30 flex items-center justify-center font-bold text-xs shrink-0">
                        <i class="fa-solid fa-robot animate-pulse"></i>
                    </div>
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl rounded-tl-none px-4 py-3 text-slate-400 font-medium text-xs flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full bg-indigo-400 animate-ping"></span>
                        <span>AI Agent is querying database...</span>
                    </div>
                </div>
            </div>

            <!-- Input Box -->
            <div class="p-4 border-t border-slate-800 bg-slate-900/60">
                <form @submit.prevent="sendAiQuery()" class="flex items-center space-x-3">
                    <input type="text" x-model="aiQuery" placeholder="Ask AI Copilot or search (e.g. 'How to add employee', 'Find John Doe')..." class="flex-1 px-4 py-3 bg-slate-950 border border-slate-800 rounded-2xl text-xs text-white focus:outline-none focus:border-indigo-500">
                    
                    <button type="submit" class="px-5 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-2xl shadow-lg shadow-indigo-600/30 transition-all flex items-center space-x-1.5 shrink-0">
                        <span>Ask AI</span>
                        <i class="fa-solid fa-paper-plane text-xs"></i>
                    </button>
                </form>
            </div>

        </div>
    </div>
    @endif

    <!-- Real-Time Floating Live Toasts Stack -->
    <div class="fixed bottom-5 right-5 z-50 flex flex-col space-y-2.5 max-w-sm w-full pointer-events-none">
        <template x-for="toast in liveToasts" :key="toast.id">
            <div x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                 class="pointer-events-auto p-4 rounded-2xl shadow-2xl border flex items-start space-x-3 backdrop-blur-xl transition-all"
                 :class="[
                     toast.type === 'BREAK_EXCEEDED' ? 'bg-rose-950/90 border-rose-500/40 text-rose-200 shadow-rose-500/10' :
                     (toast.type === 'BREAK_STARTED' ? 'bg-amber-950/90 border-amber-500/40 text-amber-200 shadow-amber-500/10' :
                     (toast.type === 'BREAK_ENDED' ? 'bg-emerald-950/90 border-emerald-500/40 text-emerald-200 shadow-emerald-500/10' :
                     'bg-slate-950/90 border-slate-700 text-slate-100 shadow-indigo-500/10'))
                 ]">
                
                <!-- Toast Icon -->
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 text-xs border"
                     :class="[
                         toast.type === 'BREAK_EXCEEDED' ? 'bg-rose-500/20 border-rose-500/40 text-rose-400' :
                         (toast.type === 'BREAK_STARTED' ? 'bg-amber-500/20 border-amber-500/40 text-amber-400' :
                         (toast.type === 'BREAK_ENDED' ? 'bg-emerald-500/20 border-emerald-500/40 text-emerald-400' :
                         'bg-indigo-500/20 border-indigo-500/40 text-indigo-400'))
                     ]">
                    <i class="fa-solid" :class="toast.icon || 'fa-bell'"></i>
                </div>

                <!-- Toast Content -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-bold text-white truncate" x-text="toast.title"></p>
                        <button type="button" @click="removeLiveToast(toast.id)" class="text-slate-400 hover:text-white text-xs ml-2">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-300 mt-0.5 line-clamp-2 leading-relaxed" x-text="toast.message"></p>
                    
                    <template x-if="toast.action_url">
                        <a :href="toast.action_url" class="inline-block text-[10px] font-bold text-indigo-400 hover:text-indigo-300 mt-1.5 underline">
                            View details &rarr;
                        </a>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <script>
        // Global Date Picker Click Handler: Allow clicking anywhere on native date inputs to open picker
        document.addEventListener('click', function(e) {
            var target = e.target;
            if (target && target.tagName === 'INPUT' && target.type === 'date') {
                try {
                    if (typeof target.showPicker === 'function') {
                        target.showPicker();
                    }
                } catch (err) {}
            }
        });

        // Lightweight Markdown & Table Parser for AI Copilot Responses
        window.formatAiMarkdown = function(md) {
            if (!md) return '';
            let html = md;
            
            // Clean scripts
            html = html.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
            
            // Headers: ### Header
            html = html.replace(/^### (.*$)/gim, '<h4 class="text-xs font-black text-indigo-300 uppercase tracking-wide mt-3 mb-1">$1</h4>');
            html = html.replace(/^## (.*$)/gim, '<h3 class="text-sm font-extrabold text-white mt-3 mb-1.5">$1</h3>');
            html = html.replace(/^# (.*$)/gim, '<h2 class="text-base font-black text-white mt-3 mb-2">$1</h2>');

            // Bold: **text**
            html = html.replace(/\*\*(.*?)\*\*/g, '<strong class="text-white font-extrabold">$1</strong>');
            
            // Markdown Links: [text](url)
            html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" class="text-indigo-400 hover:text-indigo-300 font-bold underline transition-colors" target="_self">$1 &rarr;</a>');
            
            // Code: `code`
            html = html.replace(/`([^`]+)`/g, '<code class="px-1.5 py-0.5 rounded bg-slate-950 text-indigo-300 font-mono text-[10px] border border-slate-800">$1</code>');
            
            // Markdown Tables:
            if (html.includes('|')) {
                const lines = html.split('\n');
                let inTable = false;
                let tableHtml = '';
                let processedLines = [];
                
                for (let i = 0; i < lines.length; i++) {
                    const line = lines[i].trim();
                    if (line.startsWith('|') && line.endsWith('|')) {
                        const cells = line.split('|').slice(1, -1);
                        if (line.includes('---')) {
                            continue; // delimiter row
                        }
                        
                        if (!inTable) {
                            inTable = true;
                            tableHtml = '<div class="overflow-x-auto my-3 rounded-xl border border-slate-800 shadow-md"><table class="w-full text-left text-xs border-collapse bg-slate-950/80"><thead><tr class="border-b border-slate-800 bg-slate-900/90 text-slate-200 font-bold text-[10px] uppercase tracking-wider">';
                            cells.forEach(c => {
                                tableHtml += `<th class="py-2.5 px-3 whitespace-nowrap">${c.trim()}</th>`;
                            });
                            tableHtml += '</tr></thead><tbody class="divide-y divide-slate-800/60 text-slate-300">';
                        } else {
                            tableHtml += '<tr class="hover:bg-slate-900/40 transition-colors">';
                            cells.forEach(c => {
                                tableHtml += `<td class="py-2 px-3">${c.trim()}</td>`;
                            });
                            tableHtml += '</tr>';
                        }
                    } else {
                        if (inTable) {
                            inTable = false;
                            tableHtml += '</tbody></table></div>';
                            processedLines.push(tableHtml);
                            tableHtml = '';
                        }
                        processedLines.push(line);
                    }
                }
                if (inTable) {
                    tableHtml += '</tbody></table></div>';
                    processedLines.push(tableHtml);
                }
                html = processedLines.join('\n');
            }
            
            // Bullet points
            html = html.replace(/^[*-]\s+(.*)$/gm, '<li class="ml-4 list-disc text-slate-200 leading-relaxed">$1</li>');
            
            // Double Linebreaks
            html = html.replace(/\n\n+/g, '<br><br>').replace(/\n/g, '<br>');
            
            return html;
        };
    </script>
    @stack('scripts')
    @include('partials.security-lockdown')
</body>
</html>
