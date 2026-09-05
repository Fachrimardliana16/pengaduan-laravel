@extends('layouts.app')

@section('title', 'Pengaduan Berhasil | Perumdam Tirta Perwira')
@section('page-badge', '<i class="fa fa-circle-check text-xs"></i> Pengaduan Berhasil Dikirim')

@section('content')

<div class="app-card overflow-hidden">

    {{-- Green accent bar --}}
    <div class="h-1.5 bg-gradient-to-r from-emerald-400 via-emerald-500 to-teal-500"></div>

    <div class="card-body text-center py-8">

        {{-- Success icon --}}
        <div class="w-20 h-20 mx-auto mb-5 rounded-full
                    bg-emerald-50 dark:bg-emerald-900/30
                    ring-8 ring-emerald-50 dark:ring-emerald-900/20
                    flex items-center justify-center">
            <i class="fa fa-circle-check text-4xl text-emerald-500"></i>
        </div>

        <h2 class="text-xl font-bold text-slate-800 dark:text-white mb-2">
            Pengaduan Berhasil Dikirim!
        </h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 leading-relaxed max-w-sm mx-auto">
            Pengaduan Anda telah kami terima dan akan segera ditangani oleh petugas selama jam kerja.
        </p>

        {{-- Nomor pengaduan --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800
                    rounded-xl px-5 py-4 mb-6 inline-block min-w-48">
            <p class="text-xs text-blue-500 dark:text-blue-400 font-medium mb-1.5 uppercase tracking-wide">
                Nomor Pengaduan Anda
            </p>
            <p class="text-2xl font-bold text-blue-700 dark:text-blue-300 tracking-wider">
                {{ $idPengaduan }}
            </p>
        </div>

        <p class="text-xs text-slate-400 dark:text-slate-500 mb-7 leading-relaxed max-w-xs mx-auto">
            Simpan nomor di atas untuk memantau progress penanganan.
            Notifikasi juga telah dikirimkan ke nomor WhatsApp Anda.
        </p>

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row gap-2.5 justify-center">
            <a href="{{ route('pengaduan.index') }}" class="btn-primary">
                <i class="fa fa-house text-xs"></i>Halaman Utama
            </a>
            <a href="{{ route('pengaduan.index') }}#cari" class="btn-outline">
                <i class="fa fa-magnifying-glass text-xs"></i>Cek Status
            </a>
        </div>

    </div>
</div>

@endsection
