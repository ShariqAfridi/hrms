<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - AxeCorp HRMS</title>
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

    <div class="w-full max-w-md relative z-10" x-data="{ showPass1: false, showPass2: false }">
        <!-- Header Title -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold text-white tracking-tight">AxeCorp HRMS</h1>
            <p class="text-xs text-slate-400 mt-1">Set New Account Password</p>
        </div>

        <!-- Reset Password Card -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-8 shadow-2xl backdrop-blur-xl space-y-6">
            
            <div class="text-center space-y-2">
                <div class="w-12 h-12 rounded-2xl bg-emerald-600/20 border border-emerald-500/30 text-emerald-400 flex items-center justify-center text-xl mx-auto shadow-lg">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h2 class="text-lg font-bold text-white">Choose New Password</h2>
                <p class="text-xs text-slate-400">Enter a secure new password for your account.</p>
            </div>

            @if($errors->any())
                <div class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs font-medium space-y-1">
                    @foreach($errors->all() as $error)
                        <p><i class="fa-solid fa-circle-exclamation mr-1"></i> {{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('password.update') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Account Email</label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-4 top-3.5 text-slate-500 text-sm"></i>
                        <input type="text" name="email" readonly value="{{ $email }}" class="w-full pl-11 pr-4 py-3 bg-slate-950/60 border border-slate-800 rounded-xl text-sm font-medium text-slate-400 cursor-not-allowed">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">New Password</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-4 top-3.5 text-slate-500 text-sm"></i>
                        <input :type="showPass1 ? 'text' : 'password'" name="password" required class="w-full pl-11 pr-11 py-3 bg-slate-950/80 border border-slate-800 rounded-xl text-sm font-medium text-white placeholder-slate-500 focus:outline-none focus:border-[#4f46e5] focus:ring-1 focus:ring-[#4f46e5] transition-all" placeholder="Enter at least 6 characters">
                        
                        <button type="button" @click="showPass1 = !showPass1" class="absolute right-3.5 top-3.5 text-slate-500 hover:text-slate-300 transition-colors focus:outline-none" title="Toggle password visibility">
                            <i :class="showPass1 ? 'fa-solid fa-eye-slash text-[#4f46e5]' : 'fa-solid fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Confirm New Password</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock-open absolute left-4 top-3.5 text-slate-500 text-sm"></i>
                        <input :type="showPass2 ? 'text' : 'password'" name="password_confirmation" required class="w-full pl-11 pr-11 py-3 bg-slate-950/80 border border-slate-800 rounded-xl text-sm font-medium text-white placeholder-slate-500 focus:outline-none focus:border-[#4f46e5] focus:ring-1 focus:ring-[#4f46e5] transition-all" placeholder="Re-enter new password">
                        
                        <button type="button" @click="showPass2 = !showPass2" class="absolute right-3.5 top-3.5 text-slate-500 hover:text-slate-300 transition-colors focus:outline-none" title="Toggle password visibility">
                            <i :class="showPass2 ? 'fa-solid fa-eye-slash text-[#4f46e5]' : 'fa-solid fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full py-3.5 px-4 bg-gradient-to-r from-emerald-600 to-[#4f46e5] hover:from-emerald-500 hover:to-[#4338ca] text-white font-bold rounded-xl text-sm shadow-lg shadow-[#4f46e5]/30 transition-all duration-200 flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-check text-xs"></i>
                    <span>Reset & Save New Password</span>
                </button>
            </form>

            <div class="pt-2 border-t border-slate-800 text-center">
                <a href="{{ route('login') }}" class="text-xs font-semibold text-slate-400 hover:text-white transition-colors inline-flex items-center space-x-1.5">
                    <i class="fa-solid fa-arrow-left text-[11px]"></i>
                    <span>Cancel and Back to Login</span>
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
