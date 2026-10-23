<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AxeCorp HRMS</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4 antialiased relative overflow-hidden">

    <!-- Glowing Background Orbs -->
    <div class="absolute top-1/4 left-1/3 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-1/3 w-96 h-96 bg-purple-600/20 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10">
        <!-- Logo & Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-slate-900/90 border border-slate-800 p-2.5 shadow-2xl shadow-indigo-500/20 mb-4 hover:scale-105 transition-transform">
                <img src="{{ axecorp_logo_src() }}" alt="AxeCorp Logo" class="w-full h-full object-contain">
            </div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">AxeCorp HRMS</h1>
            <p class="text-sm font-medium text-slate-400 mt-1">Enterprise Workforce & Payroll Management Portal</p>
        </div>

        <!-- Login Card -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-8 shadow-2xl backdrop-blur-xl space-y-6">
            @if(session('info'))
                <div class="p-3.5 rounded-xl bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 text-xs font-medium text-center">
                    {{ session('info') }}
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
                        <input type="email" name="email" id="emailInput" required value="{{ old('email', 'admin@axecorp.com') }}" class="w-full pl-11 pr-4 py-3 bg-slate-950/80 border border-slate-800 rounded-xl text-sm font-medium text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="name@company.com">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Password</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-4 top-3.5 text-slate-500 text-sm"></i>
                        <input type="password" name="password" id="passwordInput" required value="Password@123" class="w-full pl-11 pr-4 py-3 bg-slate-950/80 border border-slate-800 rounded-xl text-sm font-medium text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all" placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center space-x-2 text-slate-400 font-medium cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-0 focus:ring-offset-0">
                        <span>Remember me</span>
                    </label>
                    <span class="text-indigo-400 font-medium hover:underline cursor-pointer">Forgot password?</span>
                </div>

                <button type="submit" class="w-full py-3.5 px-4 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 text-white font-bold rounded-xl text-sm shadow-lg shadow-indigo-600/30 transition-all duration-200">
                    Sign In to Portal
                </button>
            </form>

            <!-- Quick Demo Accounts -->
            <div class="pt-4 border-t border-slate-800/80">
                <p class="text-xs font-semibold text-slate-400 text-center mb-3">Quick Demo Accounts (1-Click Fill)</p>
                <div class="grid grid-cols-3 gap-2">
                    <button onclick="fillDemo('admin@axecorp.com')" class="px-2 py-2 bg-slate-950 hover:bg-slate-800 border border-slate-800 rounded-lg text-xs font-semibold text-indigo-400 transition-colors text-center truncate">
                        <i class="fa-solid fa-user-shield block mb-1 text-sm"></i> Admin
                    </button>
                    <button onclick="fillDemo('hr@axecorp.com')" class="px-2 py-2 bg-slate-950 hover:bg-slate-800 border border-slate-800 rounded-lg text-xs font-semibold text-purple-400 transition-colors text-center truncate">
                        <i class="fa-solid fa-user-tie block mb-1 text-sm"></i> HR Manager
                    </button>
                    <button onclick="fillDemo('dev@axecorp.com')" class="px-2 py-2 bg-slate-950 hover:bg-slate-800 border border-slate-800 rounded-lg text-xs font-semibold text-cyan-400 transition-colors text-center truncate">
                        <i class="fa-solid fa-code block mb-1 text-sm"></i> Developer
                    </button>
                </div>
            </div>
        </div>

        <p class="text-center text-xs text-slate-500 mt-6">
            &copy; 2026 AxeCorp HRMS. All rights reserved.
        </p>
    </div>

    <script>
        function fillDemo(email) {
            document.getElementById('emailInput').value = email;
            document.getElementById('passwordInput').value = 'Password@123';
        }
    </script>
</body>
</html>
