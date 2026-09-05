<!DOCTYPE html>
<html lang="id" x-data x-bind:class="{ 'dark': $store.theme.dark }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Layanan Pengaduan | Perumdam Tirta Perwira')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('theme', {
                dark: false,
                init() {
                    const saved = localStorage.getItem('theme');
                    this.dark = saved === 'dark'
                        || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches);
                },
                toggle() {
                    this.dark = !this.dark;
                    localStorage.setItem('theme', this.dark ? 'dark' : 'light');
                }
            });
        });
    </script>
</head>
<body class="min-h-screen font-sans antialiased relative overflow-x-hidden
             bg-gradient-to-b from-sky-100 via-blue-50 to-slate-100
             dark:from-slate-950 dark:via-slate-900 dark:to-slate-900">

    {{-- Mountain silhouette — fixed at bottom so it stays while scrolling --}}
    <div class="fixed bottom-0 left-0 right-0 z-0 pointer-events-none select-none">
        <svg viewBox="0 0 1440 150" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg"
             class="w-full text-blue-900 dark:text-slate-700">
            <path d="M0,150 L0,95 L90,58 L200,88 L330,38 L470,78 L590,28 L710,72 L830,42 L960,74 L1080,32 L1200,68 L1320,44 L1440,64 L1440,150 Z"
                  fill="currentColor" fill-opacity="0.07"/>
            <path d="M0,150 L0,115 L170,88 L320,108 L470,80 L610,102 L730,72 L855,98 L975,82 L1095,100 L1215,82 L1340,100 L1440,86 L1440,150 Z"
                  fill="currentColor" fill-opacity="0.13"/>
            <path d="M0,150 L0,134 L260,110 L450,128 L640,108 L800,125 L960,110 L1120,128 L1280,112 L1440,126 L1440,150 Z"
                  fill="currentColor" fill-opacity="0.22"/>
        </svg>
    </div>

    {{-- Floating theme toggle — top-right corner --}}
    <button @click="$store.theme.toggle()"
            class="fixed top-4 right-4 z-50 w-9 h-9 rounded-xl
                   bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm
                   shadow-md border border-slate-200/60 dark:border-slate-700/60
                   flex items-center justify-center
                   text-slate-500 dark:text-slate-400
                   hover:text-blue-600 dark:hover:text-blue-400 transition-all"
            :title="$store.theme.dark ? 'Mode Terang' : 'Mode Gelap'">
        <i class="fa fa-sun text-sm"  x-show=" $store.theme.dark" x-cloak></i>
        <i class="fa fa-moon text-sm" x-show="!$store.theme.dark"></i>
    </button>

    <div class="relative z-10">

        {{-- Brand hero — identical look to reference site --}}
        <div class="text-center pt-12 pb-8 px-4">
            <div class="w-16 h-16 mx-auto mb-5 rounded-2xl bg-blue-600 dark:bg-blue-700
                        flex items-center justify-center
                        shadow-lg shadow-blue-300/60 dark:shadow-blue-900/60">
                <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C10.5 4.5 5 11 5 15a7 7 0 0 0 14 0c0-4-5.5-10.5-7-13z"/>
                </svg>
            </div>

            <h1 class="text-2xl sm:text-3xl font-black tracking-tight mb-3
                       text-blue-950 dark:text-white">
                PERUMDAM <span class="font-light">Tirta Perwira</span>
            </h1>

            <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-sm font-medium
                         bg-white/80 dark:bg-slate-800/70 backdrop-blur-sm
                         border border-blue-200/80 dark:border-slate-700
                         text-blue-700 dark:text-blue-300 shadow-sm">
                @yield('page-badge', '<i class="fa fa-headset text-xs"></i> Layanan Pengaduan Pelanggan')
            </span>
        </div>

        <main class="max-w-2xl mx-auto px-4 pb-48">
            @yield('content')
        </main>

        <footer class="text-center text-xs text-slate-400/80 dark:text-slate-600 pb-8 px-4">
            &copy; {{ date('Y') }} Perumdam Tirta Perwira &mdash; Kabupaten Purbalingga
        </footer>

    </div>

    {{-- Toast --}}
    <div id="toastContainer"
         class="fixed top-4 right-14 z-50 flex flex-col gap-2 w-64 sm:w-72 pointer-events-none">
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>
    <script>
        function showToast(message, type = 'success') {
            const colors = { success: 'bg-emerald-500', danger: 'bg-red-500', warning: 'bg-amber-500', info: 'bg-blue-500' };
            const el = document.createElement('div');
            el.className = `pointer-events-auto ${colors[type] ?? 'bg-blue-500'} text-white text-sm px-4 py-3 rounded-xl shadow-lg flex items-start gap-2`;
            el.innerHTML = `<span class="flex-1">${message}</span>
                            <button onclick="this.parentElement.remove()" class="opacity-70 hover:opacity-100 text-xs mt-0.5">&#x2715;</button>`;
            document.getElementById('toastContainer').prepend(el);
            setTimeout(() => el.remove(), 4500);
        }
    </script>
    @stack('scripts')
</body>
</html>
