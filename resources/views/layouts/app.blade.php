<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistem Antrian Klinik') - Kedokter</title>
    <!-- Tailwind CSS CDN for instant beautiful UI -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @yield('styles')
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col font-sans">

    <!-- Top Navigation -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white font-bold text-lg shadow-md shadow-blue-500/20">
                    <i class="fa-solid fa-hospital-user"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-slate-900 leading-tight">Kedokter</h1>
                    <p class="text-xs text-slate-500">{{ $clinic->name ?? 'Sistem Antrian Mandiri' }}</p>
                </div>
            </div>

            <nav class="flex items-center space-x-1.5 sm:space-x-2">
                <a href="{{ route('queue.index') }}" class="px-3 py-2 rounded-lg text-xs sm:text-sm font-medium transition {{ request()->routeIs('queue.index') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    <i class="fa-solid fa-table-list mr-1"></i> Antrian
                </a>

                @auth
                    @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.doctors.index') }}" class="px-3 py-2 rounded-lg text-xs sm:text-sm font-medium transition {{ request()->routeIs('admin.doctors.*') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        <i class="fa-solid fa-user-doctor mr-1"></i> Dokter
                    </a>
                    <a href="{{ route('admin.schedules.index') }}" class="px-3 py-2 rounded-lg text-xs sm:text-sm font-medium transition {{ request()->routeIs('admin.schedules.*') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        <i class="fa-solid fa-calendar-days mr-1"></i> Jadwal & Kuota
                    </a>
                    <a href="{{ route('admin.holidays.index') }}" class="px-3 py-2 rounded-lg text-xs sm:text-sm font-medium transition {{ request()->routeIs('admin.holidays.*') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        <i class="fa-solid fa-mug-hot mr-1"></i> Libur
                    </a>
                    <a href="{{ route('admin.whatsapp.index') }}" class="px-3 py-2 rounded-lg text-xs sm:text-sm font-medium transition {{ request()->routeIs('admin.whatsapp.*') ? 'bg-emerald-50 text-emerald-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        <i class="fa-brands fa-whatsapp mr-1 text-emerald-600"></i> WhatsApp
                    </a>
                    @endif
                @endauth

                @if(isset($clinic))
                <a href="{{ route('display', $clinic->slug) }}" target="_blank" class="px-3 py-2 rounded-lg text-xs sm:text-sm font-medium bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition hidden md:flex items-center">
                    <i class="fa-solid fa-desktop mr-1.5"></i> Display TV <i class="fa-solid fa-arrow-up-right-from-square ml-1 text-xs"></i>
                </a>
                @endif

                @auth
                <div class="h-6 w-px bg-slate-200 mx-1"></div>

                <div class="flex items-center space-x-2 pl-1">
                    <div class="text-right hidden sm:block">
                        <span class="text-xs font-bold text-slate-800 block leading-tight">{{ auth()->user()->name }}</span>
                        <span class="text-[10px] font-semibold uppercase tracking-wider px-1.5 py-0.2 rounded bg-slate-100 text-slate-600">
                            {{ auth()->user()->role }}
                        </span>
                    </div>

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Keluar / Logout">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        </button>
                    </form>
                </div>
                @endauth
            </nav>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @if(session('success'))
            <div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center shadow-sm">
                <i class="fa-solid fa-circle-check text-emerald-600 text-xl mr-3"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center shadow-sm">
                <i class="fa-solid fa-circle-exclamation text-rose-600 text-xl mr-3"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if(session('warning'))
            <div class="mb-5 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 flex items-center shadow-sm">
                <i class="fa-solid fa-triangle-exclamation text-amber-600 text-xl mr-3"></i>
                <span>{{ session('warning') }}</span>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="bg-white border-t border-slate-200 py-4 text-center text-xs text-slate-500">
        Kedokter &copy; {{ date('Y') }} &mdash; Sistem Antrian Klinik Open Source (Lisensi MIT)
    </footer>

    @yield('scripts')
</body>
</html>

