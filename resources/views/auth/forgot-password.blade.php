<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - AxeCorp HRMS</title>
    @include('partials.security-lockdown')
    <!-- Immediate Theme Initialization (Default Dark, Persistent) -->
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
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap');
        *, html, body, h1, h2, h3, h4, h5, h6, p, span, div, a, button, input, label {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        }
        i[class*="fa-"], .fa, .fa-solid, .fa-regular {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands", sans-serif !important;
        }
        [x-cloak] { display: none !important; }

        /* Light Theme Auth Styles (Purple / Indigo Matching Design) */
        html.theme-light,
        html.theme-light body,
        body.theme-light {
            background-color: #f4f6fb !important;
            color: #1e293b !important;
        }
        html.theme-light .bg-slate-950,
        html.theme-light .bg-slate-950\/80,
        html.theme-light .bg-slate-900,
        html.theme-light .bg-slate-900\/80,
        html.theme-light .bg-slate-900\/90 {
            background-color: #ffffff !important;
            border-color: #eef2f6 !important;
        }
        html.theme-light .border-slate-800 {
            border-color: #eef2f6 !important;
        }
        html.theme-light .text-white {
            color: #0f172a !important;
        }
        html.theme-light .text-slate-300,
        html.theme-light .text-slate-400 {
            color: #64748b !important;
        }
        html.theme-light input:not([type="submit"]):not([type="button"]):not([type="checkbox"]) {
            background-color: #ffffff !important;
            border-color: #cbd5e1 !important;
            color: #0f172a !important;
        }
        html.theme-light input:focus {
            border-color: #4f46e5 !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.20) !important;
        }
        html.theme-light input::placeholder {
            color: #94a3b8 !important;
        }
        html.theme-light .shadow-2xl {
            box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.06) !important;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4 antialiased relative overflow-hidden"
      x-data="{
          currentTheme: localStorage.getItem('hrms_theme') || 'dark',
          toggleTheme() {
              this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
              if (window.applyHrmsTheme) {
                  window.applyHrmsTheme(this.currentTheme);
              } else {
                  if (this.currentTheme === 'light') {
                      document.documentElement.classList.add('theme-light');
                      document.documentElement.classList.remove('dark');
                  } else {
                      document.documentElement.classList.remove('theme-light');
                      document.documentElement.classList.add('dark');
                  }
                  localStorage.setItem('hrms_theme', this.currentTheme);
              }
          }
      }">

    <!-- Top Right Theme Toggle -->
    <div class="fixed top-4 right-4 z-50">
        <button @click="toggleTheme()" 
                type="button" 
                class="flex items-center space-x-2 px-3.5 py-2 rounded-xl text-xs font-semibold border transition-all duration-200 shadow-md backdrop-blur-md"
                :class="currentTheme === 'light' 
                    ? 'bg-amber-50/90 border-amber-200 text-amber-700 hover:bg-amber-100' 
                    : 'bg-slate-900/80 border-slate-800 text-slate-300 hover:text-white hover:border-slate-700'"
                :title="currentTheme === 'light' ? 'Switch to Dark Theme' : 'Switch to Light Theme'">
            <i class="fa-solid" :class="currentTheme === 'light' ? 'fa-sun text-amber-500' : 'fa-moon text-[#4f46e5]'"></i>
            <span x-text="currentTheme === 'light' ? 'Light' : 'Dark'"></span>
        </button>
    </div>

    <!-- Glowing Background Orbs (Indigo / Purple Brand Colors) -->
    <div class="absolute top-1/4 left-1/3 w-96 h-96 bg-[#4f46e5]/15 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-1/3 w-96 h-96 bg-[#6366f1]/15 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10">
        <!-- Header Title -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-slate-900/90 border border-slate-800 p-2.5 shadow-2xl shadow-[#4f46e5]/20 mb-4 hover:scale-105 transition-transform">
                <img src="{{ axecorp_logo_src() }}" alt="AxeCorp Logo" class="w-full h-full object-contain">
            </div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">Forgot Password</h1>
            <p class="text-xs text-slate-400 mt-1">AxeCorp Unified Identity &amp; Access Recovery</p>
        </div>

        <!-- Auth Card -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-8 shadow-2xl backdrop-blur-xl space-y-6">
            @if(session('status'))
                <div class="p-4 rounded-2xl bg-[#4f46e5]/10 border border-[#4f46e5]/30 text-xs space-y-3">
                    <div class="flex items-center space-x-2 text-emerald-400 font-bold">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>{{ session('status') }}</span>
                    </div>
                    @if(config('app.debug') && !app()->environment('production') && session('reset_url'))
                        <div class="p-3 bg-amber-500/10 border border-amber-500/30 rounded-xl space-y-2">
                            <p class="text-[11px] text-amber-400 font-medium"><i class="fa-solid fa-triangle-exclamation mr-1"></i> [Local Debug Mode Only] Quick Test Link:</p>
                            <a href="{{ session('reset_url') }}" class="block w-full py-2 px-3 bg-[#4f46e5] hover:bg-[#4338ca] text-white text-center font-bold text-xs rounded-lg shadow-md transition-all">
                                Set New Password (Debug) →
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            @if($errors->any())
                <div class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs font-medium space-y-1">
                    @foreach($errors->all() as $error)
                        <p><i class="fa-solid fa-circle-exclamation mr-1"></i> {{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('password.email') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Registered Email</label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-4 top-3.5 text-slate-500 text-sm"></i>
                        <input type="text" name="email" required value="{{ old('email', session('email')) }}" class="w-full pl-11 pr-4 py-3 bg-slate-950/80 border border-slate-800 rounded-xl text-sm font-medium text-white placeholder-slate-500 focus:outline-none focus:border-[#4f46e5] focus:ring-1 focus:ring-[#4f46e5] transition-all" placeholder="e.g. devel@axecorp">
                    </div>
                </div>

                <button type="submit" class="w-full py-3.5 px-4 bg-gradient-to-r from-[#4f46e5] to-[#6366f1] hover:from-[#4338ca] hover:to-[#4f46e5] text-white font-bold rounded-xl text-sm shadow-lg shadow-[#4f46e5]/30 transition-all duration-200 flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-paper-plane text-xs"></i>
                    <span>Generate Reset Authorization</span>
                </button>
            </form>

            <div class="pt-2 border-t border-slate-800 text-center">
                <a href="{{ route('login') }}" class="text-xs font-semibold text-slate-400 hover:text-white transition-colors inline-flex items-center space-x-1.5">
                    <i class="fa-solid fa-arrow-left text-[11px]"></i>
                    <span>Back to Portal Login</span>
                </a>
            </div>
        </div>

        <p class="text-center text-xs text-slate-500 mt-6">
            &copy; 2026 AxeCorp. All rights reserved.
        </p>
    </div>

    @include('partials.security-lockdown')
</body>
</html>
