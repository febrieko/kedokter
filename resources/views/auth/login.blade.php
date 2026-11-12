<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Sistem Antrian Kedokter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950 text-slate-800 min-h-screen flex items-center justify-center p-4 antialiased font-sans">

    <div class="max-w-md w-full">
        <!-- Logo & Title -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-3xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white text-3xl font-bold shadow-xl shadow-blue-500/30 mb-4">
                <i class="fa-solid fa-hospital-user"></i>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight">Kedokter</h1>
            <p class="text-sm text-slate-400 mt-1">Sistem Manajemen & Antrian Klinik</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white/95 backdrop-blur-xl rounded-3xl p-8 shadow-2xl border border-white/20">
            <h2 class="text-lg font-bold text-slate-900 mb-1">Masuk ke Dashboard</h2>
            <p class="text-xs text-slate-500 mb-6">Silakan masuk menggunakan akun staf atau admin klinik.</p>

            @if(session('info'))
                <div class="mb-4 p-3.5 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-xs flex items-center">
                    <i class="fa-solid fa-circle-info mr-2 text-sm text-blue-600"></i>
                    <span>{{ session('info') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center">
                    <i class="fa-solid fa-triangle-exclamation mr-2 text-sm text-rose-600"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Alamat Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-regular fa-envelope"></i>
                        </div>
                        <input type="email" name="email" value="{{ old('email', 'resepsionis@klinik.id') }}" required autofocus
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                            placeholder="nama@klinik.id">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Kata Sandi</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <input type="password" name="password" value="password" required
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                            placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center space-x-2 text-slate-600 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
                        <span>Ingat saya</span>
                    </label>
                </div>

                <button type="submit" class="w-full mt-2 py-3 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 active:scale-[0.99] text-white font-semibold text-sm shadow-lg shadow-blue-500/25 transition flex items-center justify-center">
                    <i class="fa-solid fa-right-to-bracket mr-2"></i> Masuk Sekarang
                </button>
            </form>

            <!-- Quick Demo Accounts Box -->
            <div class="mt-6 pt-5 border-t border-slate-100">
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-2">Akun Demo Bawaan:</p>
                <div class="space-y-1.5 text-xs text-slate-600">
                    <div class="flex justify-between items-center p-2 rounded-lg bg-slate-50 border border-slate-200/60 cursor-pointer hover:bg-blue-50 transition" onclick="fillAccount('resepsionis@klinik.id', 'password')">
                        <div>
                            <strong class="text-slate-800">Resepsionis</strong>
                            <span class="text-[11px] text-slate-500 block">resepsionis@klinik.id</span>
                        </div>
                        <span class="text-[10px] font-mono bg-white px-2 py-0.5 rounded border border-slate-200 text-slate-500">password</span>
                    </div>

                    <div class="flex justify-between items-center p-2 rounded-lg bg-slate-50 border border-slate-200/60 cursor-pointer hover:bg-blue-50 transition" onclick="fillAccount('admin@klinik.id', 'password')">
                        <div>
                            <strong class="text-slate-800">Admin Klinik</strong>
                            <span class="text-[11px] text-slate-500 block">admin@klinik.id</span>
                        </div>
                        <span class="text-[10px] font-mono bg-white px-2 py-0.5 rounded border border-slate-200 text-slate-500">password</span>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-center text-xs text-slate-500 mt-6">
            Kedokter &copy; {{ date('Y') }} &mdash; Lisensi Open Source MIT
        </p>
    </div>

    <script>
        function fillAccount(email, password) {
            document.querySelector('input[name="email"]').value = email;
            document.querySelector('input[name="password"]').value = password;
        }
    </script>
</body>
</html>

