<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AxeCorp HRMS</title>
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

        /* Light Theme Auth Styles - Executive Royal Sapphire & Crisp Porcelain */
        html.theme-light,
        html.theme-light body,
        body.theme-light {
            background-color: #f8fafc !important;
            background-image: 
                radial-gradient(1000px circle at 50% 0%, rgba(99, 102, 241, 0.04) 0%, transparent 60%),
                radial-gradient(800px circle at 100% 100%, rgba(59, 130, 246, 0.03) 0%, transparent 50%) !important;
            color: #0f172a !important;
        }
        html.theme-light .bg-slate-950,
        html.theme-light .bg-slate-950\/80,
        html.theme-light .bg-slate-900,
        html.theme-light .bg-slate-900\/80,
        html.theme-light .bg-slate-900\/90 {
            background-color: #ffffff !important;
            border-color: #e2e8f0 !important;
        }
        html.theme-light .border-slate-800 {
            border-color: #e2e8f0 !important;
        }
        html.theme-light .text-white {
            color: #0f172a !important;
        }
        html.theme-light label {
            color: #1e293b !important;
            font-weight: 700 !important;
        }
        html.theme-light .text-slate-300,
        html.theme-light .text-slate-400 {
            color: #475569 !important;
        }
        html.theme-light .text-slate-500 {
            color: #64748b !important;
        }
        html.theme-light input:not([type="submit"]):not([type="button"]):not([type="checkbox"]) {
            background-color: #ffffff !important;
            border-color: #cbd5e1 !important;
            color: #0f172a !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04) !important;
        }
        html.theme-light input:focus {
            border-color: #4f46e5 !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.16) !important;
        }
        html.theme-light input::placeholder {
            color: #94a3b8 !important;
        }
        html.theme-light .shadow-2xl {
            box-shadow: 0 25px 60px -12px rgba(15, 23, 42, 0.12), 0 0 0 1px rgba(0, 0, 0, 0.04) !important;
        }
        html.theme-light button[type="submit"] {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%) !important;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3) !important;
        }
        html.theme-light button[type="submit"]:hover {
            background: linear-gradient(135deg, #4338ca 0%, #3730a3 100%) !important;
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4) !important;
            transform: translateY(-1px);
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4 antialiased relative overflow-hidden"
      x-data="{
          showPassword: false,
          currentTheme: localStorage.getItem('hrms_theme') || 'dark',
          toggleTheme() {
              this.currentTheme = (this.currentTheme === 'light') ? 'dark' : 'light';
              localStorage.setItem('hrms_theme', this.currentTheme);
              this.applyTheme(this.currentTheme);
          },
          applyTheme(theme) {
              if (theme === 'light') {
                  document.documentElement.classList.add('theme-light', 'light');
                  document.documentElement.classList.remove('dark');
                  document.body.classList.add('theme-light');
              } else {
                  document.documentElement.classList.add('dark');
                  document.documentElement.classList.remove('theme-light', 'light');
                  document.body.classList.remove('theme-light');
              }
          },
          init() {
              this.applyTheme(this.currentTheme);
          }
      }">

    <!-- Top Right Corner Theme Switcher -->
    <div class="absolute top-4 right-4 z-20">
        <button type="button" 
                @click="toggleTheme()" 
                class="relative inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border transition-all duration-300 shadow-sm focus:outline-none select-none text-xs font-bold"
                :class="currentTheme === 'light' 
                    ? 'bg-white/80 border-slate-200 text-amber-600 hover:bg-white shadow-sm' 
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
            <h1 class="text-3xl font-extrabold text-white tracking-tight">AxeCorp HRMS</h1>
        </div>

        <!-- Login Card -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-8 shadow-2xl backdrop-blur-xl space-y-6">
            @if(session('success'))
                <div class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-medium text-center flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('info'))
                <div class="p-3.5 rounded-xl bg-[#4f46e5]/10 border border-[#4f46e5]/30 text-indigo-400 text-xs font-medium text-center flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>{{ session('info') }}</span>
                </div>
            @endif

            @if(session('warning'))
                <div class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-medium text-center flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>{{ session('warning') }}</span>
                </div>
            @elseif(request('expired') && !session('info'))
                <div class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-medium text-center flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Your session has expired due to inactivity. Please log in again.</span>
                </div>
            @endif

            @if(session('error'))
                <div class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs font-medium text-center flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs font-medium space-y-1">
                    @foreach($errors->all() as $error)
                        <p><i class="fa-solid fa-circle-exclamation mr-1"></i> {{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="space-y-5" id="loginForm">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Email Address</label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-4 top-3.5 text-slate-500 text-sm"></i>
                        <input type="text" name="email" id="emailInput" required value="{{ old('email') }}" class="w-full pl-11 pr-4 py-3 bg-slate-950/80 border border-slate-800 rounded-xl text-sm font-medium text-white placeholder-slate-500 focus:outline-none focus:border-[#4f46e5] focus:ring-1 focus:ring-[#4f46e5] transition-all" placeholder="name@company.com or username">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Password</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-4 top-3.5 text-slate-500 text-sm"></i>
                        <input :type="showPassword ? 'text' : 'password'" name="password" id="passwordInput" required value="" class="w-full pl-11 pr-11 py-3 bg-slate-950/80 border border-slate-800 rounded-xl text-sm font-medium text-white placeholder-slate-500 focus:outline-none focus:border-[#4f46e5] focus:ring-1 focus:ring-[#4f46e5] transition-all" placeholder="••••••••">
                        
                        <!-- Show / Hide Password Toggle Button -->
                        <button type="button" @click="showPassword = !showPassword" class="absolute right-3.5 top-3.5 text-slate-500 hover:text-slate-300 transition-colors focus:outline-none" title="Toggle password visibility">
                            <i :class="showPassword ? 'fa-solid fa-eye-slash text-[#4f46e5]' : 'fa-solid fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center space-x-2 text-slate-400 font-medium cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded bg-slate-950 border-slate-800 text-[#4f46e5] focus:ring-0 focus:ring-offset-0">
                        <span>Remember me</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="text-[#4f46e5] font-semibold hover:text-[#4338ca] hover:underline transition-colors">
                        Forgot password?
                    </a>
                </div>

                <button type="submit" class="w-full py-3.5 px-4 bg-gradient-to-r from-[#4f46e5] to-[#6366f1] hover:from-[#4338ca] hover:to-[#4f46e5] text-white font-bold rounded-xl text-sm shadow-lg shadow-[#4f46e5]/30 transition-all duration-200 flex items-center justify-center space-x-2">
                    <span>Sign In to Portal</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-slate-500 mt-6">
            &copy; 2026 AxeCorp. All rights reserved.
        </p>
    </div>

    @include('partials.security-lockdown')
</body>
</html>
