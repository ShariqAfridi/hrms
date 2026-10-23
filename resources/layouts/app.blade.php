<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'HRMS Portal') - AxeCorp HRMS</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome 6 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 antialiased min-h-screen flex" x-data="{ sidebarOpen: false }">

    <!-- Mobile Sidebar Backdrop -->
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden"></div>

    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-950 border-r border-slate-800 transition-transform duration-300 ease-in-out lg:translate-x-0 flex flex-col justify-between">
        <div>
            <!-- Brand Logo -->
            <div class="h-16 flex items-center px-6 border-b border-slate-800/80 bg-slate-950/50">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 rounded-xl bg-slate-900/90 border border-slate-800/80 flex items-center justify-center p-1.5 shadow-lg shadow-indigo-500/10 group-hover:scale-105 group-hover:border-indigo-500/40 transition-all shrink-0">
                        <img src="{{ axecorp_logo_src() }}" alt="AxeCorp Logo" class="w-full h-full object-contain">
                    </div>
                    <span class="font-extrabold text-lg tracking-tight text-white group-hover:text-indigo-300 transition-colors">AxeCorp HRMS</span>
                </a>
            </div>

            <!-- Navigation Links -->
            <nav class="p-4 space-y-1.5">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('dashboard') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 shadow-md shadow-indigo-600/10' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200' }}">
                    <i class="fa-solid fa-chart-pie w-5 text-center text-base"></i>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('employees.index') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('employees.*') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 shadow-md shadow-indigo-600/10' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200' }}">
                    <i class="fa-solid fa-users w-5 text-center text-base"></i>
                    <span>Employees</span>
                </a>

                <a href="{{ route('attendance.index') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('attendance.*') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 shadow-md shadow-indigo-600/10' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200' }}">
                    <i class="fa-solid fa-user-clock w-5 text-center text-base"></i>
                    <span>Attendance</span>
                </a>

                <a href="{{ route('leaves.index') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('leaves.*') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 shadow-md shadow-indigo-600/10' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200' }}">
                    <i class="fa-solid fa-calendar-check w-5 text-center text-base"></i>
                    <span>Leave Requests</span>
                </a>

                <a href="{{ route('payroll.index') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('payroll.*') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 shadow-md shadow-indigo-600/10' : 'text-slate-400 hover:bg-slate-900 hover:text-slate-200' }}">
                    <i class="fa-solid fa-file-invoice-dollar w-5 text-center text-base"></i>
                    <span>Payroll</span>
                </a>
            </nav>
        </div>

        <!-- User Footer Profile Info -->
        <div class="p-4 border-t border-slate-800/80 bg-slate-950/60">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3 min-w-0">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-purple-500 to-indigo-500 flex items-center justify-center font-bold text-white text-sm shrink-0 shadow-md">
                        {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-white truncate">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-indigo-400 truncate font-medium">{{ Auth::user()->role->label ?? 'User' }}</p>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="p-2 text-slate-400 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-colors" title="Logout">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 lg:pl-64 flex flex-col min-w-0">
        <!-- Top Navbar Header -->
        <header class="h-16 bg-slate-950/80 border-b border-slate-800/80 sticky top-0 z-30 backdrop-blur-md px-4 lg:px-8 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-900 lg:hidden">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <h2 class="text-lg font-bold text-white tracking-tight">@yield('title', 'Dashboard')</h2>
            </div>

            <!-- Top Actions -->
            <div class="flex items-center space-x-4">
                <div class="hidden sm:flex items-center space-x-2 text-xs font-semibold px-3 py-1.5 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-400">
                    <i class="fa-regular fa-clock"></i>
                    <span>{{ date('l, d M Y') }}</span>
                </div>
                <div class="w-8 h-8 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-slate-300 text-xs">
                    <i class="fa-regular fa-bell"></i>
                </div>
            </div>
        </header>

        <!-- Flash Messages -->
        <main class="flex-1 p-4 lg:p-8 max-w-7xl w-full mx-auto space-y-6">
            @if(session('success'))
                <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm font-medium flex items-center justify-between shadow-lg shadow-emerald-500/5">
                    <div class="flex items-center space-x-3">
                        <i class="fa-solid fa-circle-check text-emerald-400 text-base"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if(session('warning'))
                <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 text-sm font-medium flex items-center justify-between shadow-lg shadow-amber-500/5">
                    <div class="flex items-center space-x-3">
                        <i class="fa-solid fa-triangle-exclamation text-amber-400 text-base"></i>
                        <span>{{ session('warning') }}</span>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm font-medium flex items-center justify-between shadow-lg shadow-rose-500/5">
                    <div class="flex items-center space-x-3">
                        <i class="fa-solid fa-circle-xmark text-rose-400 text-base"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

</body>
</html>
