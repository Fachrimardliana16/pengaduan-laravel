@extends('layouts.app')

@section('title', 'Pengaduan Berhasil | Perumdam Tirta Perwira')

@section('content')

<div class="app-card overflow-hidden">
    <div class="flex flex-col sm:flex-row">

        {{-- ── LEFT PANEL — Brand + Confirmation Message ──────────────────── --}}
        <div class="sm:w-[42%] bg-blue-900 dark:bg-slate-950 text-white
                    flex flex-col gap-6 p-7 sm:p-8">

            {{-- Logo --}}
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-blue-500 dark:bg-blue-600
                            flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C10.5 4.5 5 11 5 15a7 7 0 0 0 14 0c0-4-5.5-10.5-7-13z"/>
                    </svg>
                </div>
                <div class="leading-tight">
                    <p class="font-bold text-sm text-white">PERUMDAM</p>
                    <p class="text-blue-300 text-xs font-light">Tirta Perwira</p>
                </div>
            </div>

            {{-- Headline --}}
            <div class="flex-1">
                <h2 class="text-3xl sm:text-4xl font-black leading-tight mb-4 text-white">
                    Terima<br>Kasih<br>
                    <span class="text-emerald-400">Budi!</span>
                </h2>
                <p class="text-blue-200 text-sm leading-relaxed">
                    Pengaduan Anda telah kami terima. Tim kami akan segera
                    menindaklanjuti selama jam kerja.
                </p>

                <ul class="mt-6 space-y-3">
                    <li class="flex items-center gap-3 text-sm text-blue-100">
                        <span class="w-7 h-7 rounded-lg bg-blue-800/70 flex items-center justify-center shrink-0">
                            <i class="fa fa-clock text-blue-300 text-xs"></i>
                        </span>
                        Diproses Senin – Jumat, 07.00 – 15.00 WIB
                    </li>
                    <li class="flex items-center gap-3 text-sm text-blue-100">
                        <span class="w-7 h-7 rounded-lg bg-blue-800/70 flex items-center justify-center shrink-0">
                            <i class="fa fa-whatsapp text-blue-300 text-xs"></i>
                        </span>
                        Notifikasi dikirim ke WhatsApp Anda
                    </li>
                    <li class="flex items-center gap-3 text-sm text-blue-100">
                        <span class="w-7 h-7 rounded-lg bg-blue-800/70 flex items-center justify-center shrink-0">
                            <i class="fa fa-magnifying-glass text-blue-300 text-xs"></i>
                        </span>
                        Pantau progress via tab "Cek Status"
                    </li>
                </ul>
            </div>

            {{-- Progress steps --}}
            <div class="bg-blue-800/50 rounded-2xl p-4 border border-blue-700/50">
                <p class="text-blue-300 text-xs font-semibold uppercase tracking-wide mb-3">Alur Penanganan</p>
                <ol class="space-y-2.5">
                    @foreach (['Pengaduan Diterima', 'Ditugaskan ke Petugas', 'Dalam Penanganan', 'Selesai'] as $i => $step)
                        <li class="flex items-center gap-2.5">
                            <span class="w-5 h-5 rounded-full {{ $i === 0 ? 'bg-emerald-500' : 'bg-blue-700' }}
                                         flex items-center justify-center shrink-0 text-xs font-bold">
                                {{ $i === 0 ? '✓' : ($i + 1) }}
                            </span>
                            <span class="text-xs {{ $i === 0 ? 'text-white font-semibold' : 'text-blue-300' }}">
                                {{ $step }}
                            </span>
                        </li>
                    @endforeach
                </ol>
            </div>

        </div>{{-- /left panel --}}

        {{-- ── RIGHT PANEL — Confirmation Detail ──────────────────────────── --}}
        <div class="flex-1 bg-white dark:bg-slate-800 flex flex-col justify-center p-7 sm:p-10">

            {{-- Success icon --}}
            <div class="w-16 h-16 mb-6 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30
                        border-4 border-emerald-100 dark:border-emerald-800
                        flex items-center justify-center">
                <i class="fa fa-circle-check text-3xl text-emerald-500"></i>
            </div>

            <h3 class="text-2xl font-bold text-slate-800 dark:text-white mb-2">
                Pengaduan Berhasil Dikirim!
            </h3>
            <p class="text-slate-500 dark:text-slate-400 text-sm mb-7 leading-relaxed max-w-xs">
                Pengaduan Anda telah kami terima dan akan segera ditangani oleh petugas.
            </p>

            {{-- Nomor pengaduan --}}
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800
                        rounded-2xl px-6 py-5 mb-7">
                <p class="text-xs text-blue-500 dark:text-blue-400 font-semibold uppercase tracking-widest mb-2">
                    Nomor Pengaduan Anda
                </p>
                <p class="text-3xl font-black text-blue-700 dark:text-blue-300 tracking-wider">
                    {{ $idPengaduan }}
                </p>
                <p class="text-xs text-slate-400 mt-2">
                    Simpan nomor ini untuk memantau status pengaduan.
                </p>
            </div>

            {{-- Actions --}}
            <div class="flex flex-col gap-2.5">
                <a href="{{ route('pengaduan.index') }}" class="btn-primary">
                    <i class="fa fa-house text-xs"></i>Kembali ke Halaman Utama
                </a>
                <a href="{{ route('pengaduan.index') }}#cari" class="btn-outline">
                    <i class="fa fa-magnifying-glass text-xs"></i>Cek Status Pengaduan
                </a>
            </div>

        </div>{{-- /right panel --}}

    </div>
</div>

@endsection
