<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Access Forbidden | AxeCorp HRMS</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Immediate Theme Initialization -->
    <script>
        (function() {
            try {
                const savedTheme = localStorage.getItem('hrms_theme') || 'dark';
                if (savedTheme === 'light') {
                    document.documentElement.classList.add('theme-light', 'light');
                    document.documentElement.classList.remove('dark');
                } else {
                    document.documentElement.classList.add('dark');
                    document.documentElement.classList.remove('theme-light', 'light');
                }
            } catch (e) {}
        })();
    </script>
    <style>
        html.theme-light body {
            background-color: #f8fafc !important;
            background-image: 
                radial-gradient(1000px circle at 50% 0%, rgba(99, 102, 241, 0.04) 0%, transparent 60%),
                radial-gradient(800px circle at 100% 100%, rgba(244, 63, 94, 0.03) 0%, transparent 50%) !important;
            color: #0f172a !important;
        }
        html.theme-light .bg-slate-900\/80 {
            background-color: #ffffff !important;
            border-color: #e2e8f0 !important;
            box-shadow: 0 25px 60px -12px rgba(15, 23, 42, 0.12), 0 0 0 1px rgba(0, 0, 0, 0.04) !important;
        }
        html.theme-light .text-white {
            color: #0f172a !important;
        }
        html.theme-light .text-slate-400 {
            color: #64748b !important;
        }
    </style>
</head>
<body class="bg-[#0b0f19] text-slate-100 min-h-screen flex items-center justify-center p-4 relative overflow-hidden font-sans antialiased">
    <div class="absolute top-1/4 left-1/3 w-96 h-96 bg-rose-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-md w-full text-center space-y-6 relative z-10">
        <div class="flex items-center justify-center space-x-3 mb-2">
            <img src="{{ axecorp_logo_src() }}" alt="AxeCorp Logo" class="h-10 w-auto object-contain">
            <span class="text-xl font-extrabold tracking-tight text-white uppercase">AxeCorp <span class="text-rose-400">HRMS</span></span>
        </div>

        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-8 shadow-2xl backdrop-blur-xl space-y-5">
            <div class="w-16 h-16 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center mx-auto text-2xl shadow-inner">
                <i class="fa-solid fa-shield-halved"></i>
            </div>

            <div class="space-y-1">
                <span class="text-xs font-black uppercase tracking-widest text-rose-400 font-mono">Error 403</span>
                <h1 class="text-2xl font-black text-white tracking-tight">Access Restricted</h1>
                <p class="text-xs text-slate-400 leading-relaxed pt-1">
                    Your current system role or account credentials do not grant authorization to view or execute this corporate resource.
                </p>
            </div>

            <div class="pt-2 flex flex-col gap-3">
                <a href="{{ url('/') }}" class="w-full py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 transition-all flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Return to Dashboard</span>
                </a>
            </div>
        </div>

        <p class="text-xs text-slate-600 font-medium">&copy; {{ date('Y') }} AxeCorp Digital Tech. Protected Enterprise System.</p>
    </div>
    @include('partials.security-lockdown')
</body>
</html>
