@extends('layouts.app')

@section('title', 'Layanan Pengaduan | Perumdam Tirta Perwira')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@endpush

@section('content')

{{-- Validation errors --}}
@if ($errors->any())
    <div class="alert-error mb-5">
        <i class="fa fa-circle-exclamation mt-0.5 shrink-0"></i>
        <ul class="space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- ═══ Split Card (StartGlobal style) ════════════════════════════════════════ --}}
<div class="app-card overflow-hidden"
     x-data="{
         activeTab: window.location.hash === '#cari' ? 'cari' : 'form',
         switchTab(tab) {
             this.activeTab = tab;
             history.replaceState(null, '', tab === 'cari' ? '#cari' : '#');
         }
     }"
     x-effect="activeTab === 'form' && $nextTick(() => window.leafletMapInit?.())">

    <div class="flex flex-col sm:flex-row">

        {{-- ══════════════════════════════════════════════════════════════════ --}}
        {{-- LEFT PANEL — Brand + Info                                         --}}
        {{-- ══════════════════════════════════════════════════════════════════ --}}
        <div class="hidden sm:flex sm:w-[42%] bg-blue-900 dark:bg-slate-950 text-white
                    flex-col gap-6 p-7 sm:p-8">

            {{-- Logo + Brand --}}
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-blue-500 dark:bg-blue-600
                            flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C10.5 4.5 5 11 5 15a7 7 0 0 0 14 0c0-4-5.5-10.5-7-13z"/>
                    </svg>
                </div>
                <div class="leading-tight">
                    <p class="font-bold text-sm text-white">PERUMDAM Tirta Perwira</p>
                    <p class="text-blue-300 text-xs font-light">Kabupaten Purbalingga</p>
                </div>
            </div>

            {{-- Headline --}}
            <div class="flex-1">
                <h2 class="text-3xl sm:text-4xl font-black leading-tight mb-4 text-white">
                    Sampaikan Pengaduan
                    <span class="text-blue-300">Anda</span>
                </h2>
                <p class="text-blue-200 text-sm leading-relaxed">
                    Layanan pengaduan online Perumdam Tirta Perwira, Kabupaten Purbalingga.
                    Kami tangani setiap keluhan dengan cepat dan profesional.
                </p>

                {{-- Service info bullets --}}
                <ul class="mt-6 space-y-3">
                    <li class="flex items-center gap-3 text-sm text-blue-100">
                        <span class="w-7 h-7 rounded-lg bg-blue-800/70 flex items-center justify-center shrink-0">
                            <i class="fa fa-clock text-blue-300 text-xs"></i>
                        </span>
                        Senin – Kamis, 07.30 – 15.00 WIB <br>
                        Jumat, 07.30 – 11.00 WIB <br>
                        Sabtu, 07.30 - 13.00 WIB
                    </li>
                    <li class="flex items-center gap-3 text-sm text-blue-100">
                        <span class="w-7 h-7 rounded-lg bg-blue-800/70 flex items-center justify-center shrink-0">
                            <i class="fa fa-phone text-blue-300 text-xs"></i>
                        </span>
                        (0281) 891706 / 0858-9302-0100
                    </li>
                    <li class="flex items-center gap-3 text-sm text-blue-100">
                        <span class="w-7 h-7 rounded-lg bg-blue-800/70 flex items-center justify-center shrink-0">
                            <i class="fa fa-shield-halved text-blue-300 text-xs"></i>
                        </span>
                        Data Anda terlindungi & aman
                    </li>
                </ul>
            </div>

           {{-- Survey kepuasan pelanggan --}}
            <a href="#"
               class="group block bg-blue-800/40 hover:bg-blue-700/50 border border-blue-700/50
                      hover:border-blue-500/70 rounded-2xl p-4 transition-all duration-200">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-xl bg-yellow-400/20 border border-yellow-400/30
                                flex items-center justify-center shrink-0">
                        <i class="fa fa-star text-yellow-400 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-white text-sm font-semibold leading-tight">Survei Kepuasan Pelanggan</p>
                        <p class="text-blue-300 text-xs mt-0.5">Bantu kami meningkatkan layanan</p>
                    </div>
                    <i class="fa fa-arrow-right text-blue-400 group-hover:text-white text-xs ml-auto
                              group-hover:translate-x-0.5 transition-transform duration-200"></i>
                </div>
                <p class="text-blue-200 text-xs leading-relaxed">
                    Ceritakan pengalaman Anda menggunakan layanan Perumdam Tirta Perwira.
                </p>
            </a>

        </div>{{-- /left panel --}}

        {{-- ══════════════════════════════════════════════════════════════════ --}}
        {{-- RIGHT PANEL — Tabs + Form/Cek                                     --}}
        {{-- ══════════════════════════════════════════════════════════════════ --}}
        <div class="flex-1 flex flex-col bg-white dark:bg-slate-800 min-w-0">

            {{-- Section heading + tab bar --}}
            <div class="px-7 sm:px-8 pt-8 pb-0">
                <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-1">
                    <span x-show="activeTab === 'form'">Buat Pengaduan Baru</span>
                    <span x-show="activeTab === 'cari'" x-cloak>Cek Status Pengaduan</span>
                </h3>
                <p class="text-slate-400 dark:text-slate-500 text-sm mb-5">
                    <span x-show="activeTab === 'form'">Silakan lengkapi formulir ini dengan data yang akurat agar kami dapat membantu Anda dengan lebih baik.</span>
                    <span x-show="activeTab === 'cari'" x-cloak>Masukkan nomor pengaduan Anda untuk memeriksa status laporan.</span>
                </p>

                {{-- Segmented tab control — centered --}}
                <div class="flex justify-center mb-6">
                <div class="inline-flex rounded-lg bg-slate-100 dark:bg-slate-700/60 p-1 gap-1">
                    <button @click="switchTab('form')"
                            :class="activeTab === 'form'
                                ? 'bg-white dark:bg-slate-600 text-blue-600 dark:text-blue-300 shadow-sm font-semibold'
                                : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
                            class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-md text-sm transition-all">
                        <i class="fa fa-pen-to-square text-xs"></i>Form Pengaduan
                    </button>
                    <button @click="switchTab('cari')"
                            :class="activeTab === 'cari'
                                ? 'bg-white dark:bg-slate-600 text-blue-600 dark:text-blue-300 shadow-sm font-semibold'
                                : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
                            class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-md text-sm transition-all">
                        <i class="fa fa-magnifying-glass text-xs"></i>Cek Status
                    </button>
                </div>{{-- /inline-flex --}}
                </div>{{-- /flex justify-center --}}
            </div>

            {{-- ── TAB: FORM PENGADUAN ──────────────────────────────────── --}}
            <div x-show="activeTab === 'form'" class="px-7 sm:px-8 pb-8">
                <form id="formPengaduan"
                      method="POST"
                      action="{{ route('pengaduan.store') }}"
                      @submit="if (submitting) { $event.preventDefault(); return; } submitting = true;"
                      x-data="{
                          submitting: false,
                          kecamatanId: '',
                          kecamatanList: [],
                          desaList: [],
                          lat: null,
                          lng: null,
                          geoError: '',
                          compliantContent: @js(old('CompliantContent', '')),

                          async loadKecamatan() {
                              try {
                                  const res  = await fetch('{{ route('api.kecamatan') }}');
                                  const json = await res.json();
                                  this.kecamatanList = json.data ?? [];
                                  if (this.kecamatanList.length > 0) {
                                      this.kecamatanId = this.kecamatanList[0].Id;
                                      await this.loadDesa();
                                  }
                              } catch(e) {}
                          },

                          async loadDesa() {
                              if (!this.kecamatanId) return;
                              try {
                                  const res  = await fetch('{{ route('api.desa') }}?id=' + this.kecamatanId);
                                  const json = await res.json();
                                  this.desaList = json.data ?? [];
                              } catch(e) {}
                          },

                          requestGeolocation() {
                              this.geoError = '';
                              if (!navigator.geolocation) {
                                  this.geoError = 'Browser tidak mendukung geolokasi.';
                                  return;
                              }
                              navigator.geolocation.getCurrentPosition(
                                  pos => {
                                      const ll = [pos.coords.latitude, pos.coords.longitude];
                                      this.lat = ll[0]; this.lng = ll[1];
                                      window.leafletMap?.setView(ll, 17);
                                      window.leafletMarker?.setLatLng(ll);
                                  },
                                  err => {
                                      const msgs = { 1: 'Izin lokasi ditolak. Aktifkan di pengaturan browser.',
                                                     2: 'Posisi tidak dapat ditentukan.', 3: 'Permintaan habis waktu.' };
                                      this.geoError = msgs[err.code] ?? 'Gagal mendapatkan lokasi.';
                                  },
                                  { enableHighAccuracy: true, timeout: 12000 }
                              );
                          }
                      }"
                      x-init="loadKecamatan()">

                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div>
                            <label class="field-label">Nama Pelapor <span class="text-red-500">*</span></label>
                            <input type="text" name="Name" placeholder="Nama lengkap"
                                   value="{{ old('Name') }}" maxlength="100" required
                                   class="field-input {{ $errors->has('Name') ? 'field-input-error' : '' }}">
                            @error('Name') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="field-label">Nomor Telepon <span class="text-red-500">*</span></label>
                            {{-- <span class="text-slate-400 font-normal text-xs ml-1">(WhatsApp)</span> --}}
                            <input type="tel" name="PhoneNumber" placeholder="Nomor HP (Terhubung WhatsApp)"
                                   value="{{ old('PhoneNumber') }}" maxlength="15" required
                                   class="field-input {{ $errors->has('PhoneNumber') ? 'field-input-error' : '' }}">
                            @error('PhoneNumber') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="field-label">
                                Nomor Pelanggan
                                <span class="text-slate-400 font-normal text-xs ml-1">(opsional)</span>
                            </label>
                            <input type="text" name="CustomerNumber" placeholder="Nomor pelanggan PDAM"
                                   value="{{ old('CustomerNumber') }}" maxlength="12"
                                   class="field-input">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="field-label">Alamat <span class="text-red-500">*</span></label>
                            <input type="text" name="Address" placeholder="Jl. Contoh No. 1, RT/RW, …"
                                   value="{{ old('Address') }}" maxlength="250" required
                                   class="field-input {{ $errors->has('Address') ? 'field-input-error' : '' }}">
                            @error('Address') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="field-label">Kecamatan <span class="text-red-500">*</span></label>
                            <select name="SubDistricts" x-model="kecamatanId" @change="loadDesa()" required
                                    class="field-input {{ $errors->has('SubDistricts') ? 'field-input-error' : '' }}">
                                <option value="">— Pilih Kecamatan —</option>
                                <template x-for="item in kecamatanList" :key="item.Id">
                                    <option :value="item.Id" x-text="item.Name"></option>
                                </template>
                            </select>
                            @error('SubDistricts') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="field-label">Desa / Kelurahan <span class="text-red-500">*</span></label>
                            <select name="Villages" required
                                    class="field-input {{ $errors->has('Villages') ? 'field-input-error' : '' }}">
                                <option value="">— Pilih Desa —</option>
                                <template x-for="item in desaList" :key="item.Id">
                                    <option :value="item.Id" x-text="item.Name"></option>
                                </template>
                            </select>
                            @error('Villages') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="field-label">Jenis Pengaduan <span class="text-red-500">*</span></label>
                            <select name="CompliantType" required
                                    class="field-input {{ $errors->has('CompliantType') ? 'field-input-error' : '' }}">
                                <option value="">— Pilih Jenis —</option>
                                @foreach ([1 => 'Aliran Pelanggan', 2 => 'Aliran Komplek', 3 => 'Bocor Pelanggan',
                                           4 => 'Bocor Pipa / Aliran', 5 => 'Tagihan / Rekening', 6 => 'Lainnya']
                                          as $val => $label)
                                    <option value="{{ $val }}" {{ old('CompliantType') == $val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('CompliantType') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="field-label">Deskripsi <span class="text-red-500">*</span></label>
                            <textarea name="CompliantContent" rows="4"
                                      x-model="compliantContent"
                                      placeholder="Tuliskan detail pengaduan Anda…"
                                      maxlength="500" required
                                      class="field-input resize-none {{ $errors->has('CompliantContent') ? 'field-input-error' : '' }}"></textarea>
                            <p class="text-xs text-right mt-1 tabular-nums"
                               :class="compliantContent.length >= 450 ? 'text-amber-500 font-medium' : 'text-slate-400 dark:text-slate-500'">
                                <span x-text="compliantContent.length"></span>/500
                            </p>
                            @error('CompliantContent') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <input type="hidden" name="LatCoords" id="LatCoords" x-bind:value="lat ?? ''">
                        <input type="hidden" name="LngCoords" id="LngCoords" x-bind:value="lng ?? ''">

                        {{-- Peta --}}
                        <div class="sm:col-span-2">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="field-label mb-0">
                                    <i class="fa fa-location-dot text-red-500 mr-1"></i>Lokasi
                                    <span class="text-slate-400 font-normal text-xs ml-1">(Geser pin ke titik aduan)</span>
                                </label>
                                <button type="button" @click="requestGeolocation()"
                                        class="text-xs text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                                    <i class="fa fa-crosshairs text-xs"></i>Lokasi Saya
                                </button>
                            </div>
                            <div id="peta" class="w-full h-48 rounded-lg border border-slate-200 dark:border-slate-600 overflow-hidden"></div>
                            <p x-show="geoError" x-cloak class="field-error mt-1">
                                <i class="fa fa-triangle-exclamation mr-1"></i><span x-text="geoError"></span>
                            </p>
                        </div>

                        {{-- reCAPTCHA --}}
                        <div class="sm:col-span-2">
                            <label class="field-label">Verifikasi</label>
                            <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
                            @error('g-recaptcha-response') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        {{-- Actions --}}
                        <div class="sm:col-span-2 flex gap-3 pt-2">
                            <button type="submit" class="btn-primary flex-1" :disabled="submitting">
                                <span x-show="!submitting" class="flex items-center gap-2">
                                    <i class="fa fa-paper-plane text-xs"></i>Kirim Pengaduan
                                </span>
                                <span x-show="submitting" x-cloak class="flex items-center gap-2">
                                    <svg class="spinner" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                    </svg>Mengirim…
                                </span>
                            </button>
                            <button type="reset" class="btn-outline"
                                    @click="submitting = false; geoError = ''">
                                <i class="fa fa-rotate-left text-xs"></i>Reset
                            </button>
                        </div>

                    </div>{{-- /grid --}}
                </form>
            </div>

            {{-- ── TAB: CEK STATUS ─────────────────────────────────────── --}}
            <div x-show="activeTab === 'cari'" x-cloak class="px-7 sm:px-8 pb-8">
                <div x-data="{
                         noPengaduan: '',
                         loading: false,
                         result: null,
                         error: '',
                         searched: false,

                         async cari() {
                             if (!this.noPengaduan.trim()) return;
                             this.loading = true;
                             this.result  = null;
                             this.error   = '';
                             try {
                                 const res  = await fetch('{{ route('api.cek') }}?NoPengaduan=' + encodeURIComponent(this.noPengaduan));
                                 const json = await res.json();
                                 if (!res.ok) {
                                     this.error = json.error ?? 'Terjadi kesalahan.';
                                 } else if (json.data?.length > 0) {
                                     this.result   = json.data;
                                     this.searched = true;
                                 } else {
                                     this.error = 'Nomor pengaduan tidak ditemukan.';
                                 }
                             } catch {
                                 this.error = 'Gagal terhubung. Periksa koneksi Anda.';
                             } finally {
                                 this.loading = false;
                             }
                         },

                         reset() {
                             this.result     = null;
                             this.error      = '';
                             this.searched   = false;
                             this.noPengaduan = '';
                         }
                     }">

                    {{-- Search form — hidden after successful search --}}
                    <div x-show="!searched">
                        <div class="flex gap-2 mb-4">
                            <input type="text"
                                   x-model="noPengaduan"
                                   @keydown.enter.prevent="cari()"
                                   placeholder="Contoh: 04092026-1"
                                   class="field-input flex-1">
                            <button @click="cari()" :disabled="loading"
                                    class="btn-primary px-5 shrink-0">
                                <span x-show="!loading" class="flex items-center gap-1.5">
                                    <i class="fa fa-magnifying-glass text-xs"></i>
                                    <span class="hidden sm:inline">Cari</span>
                                </span>
                                <span x-show="loading" x-cloak>
                                    <svg class="spinner" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                              d="M4 12a8 8 0 018-8v8z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>

                        {{-- Error --}}
                        <div x-show="error" x-cloak class="alert-warning">
                            <i class="fa fa-triangle-exclamation shrink-0"></i>
                            <span x-text="error"></span>
                        </div>
                    </div>

                    {{-- Result card --}}
                    <template x-if="result">
                        <div class="space-y-4 sm:space-y-5">

                            {{-- Header row: nomor + status badge + reset button --}}
                            <div class="rounded-xl border border-slate-200 dark:border-slate-700
                                        bg-slate-50/70 dark:bg-slate-900/30 px-4 py-3">
                                <div class="flex items-start justify-between gap-3 flex-wrap">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="min-w-0">
                                            <p class="text-[11px] uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                                Nomor Pengaduan
                                            </p>
                                            <p class="font-bold text-slate-900 dark:text-white text-lg leading-tight truncate"
                                               x-text="result[0].ticket || noPengaduan"></p>
                                        </div>
                                        {{-- Status badge --}}
                                        <span class="shrink-0 inline-flex items-center gap-1.5 text-xs font-bold
                                                     px-2.5 py-1 rounded-full"
                                              :class="{
                                                  'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300': result[0].status === 'Selesai',
                                                  'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300':             result[0].status === 'Dikerjakan',
                                                  'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300':         result[0].status === 'Diterima',
                                                  'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200':            result[0].status === 'Dilaporkan',
                                              }">
                                            <i class="text-[10px]"
                                               :class="{
                                                   'fa fa-check-circle': result[0].status === 'Selesai',
                                                   'fa fa-gear':         result[0].status === 'Dikerjakan',
                                                   'fa fa-inbox':        result[0].status === 'Diterima',
                                                   'fa fa-flag':         result[0].status === 'Dilaporkan',
                                               }"></i>
                                            <span x-text="result[0].status"></span>
                                        </span>
                                    </div>
                                    <button @click="reset()"
                                            class="shrink-0 inline-flex items-center gap-1.5 text-xs text-blue-600
                                                   dark:text-blue-400 hover:underline font-medium pt-0.5">
                                        <i class="fa fa-rotate-left text-xs"></i>Cari Nomor Lain
                                    </button>
                                </div>
                            </div>

                            {{-- Informasi Pelapor (2-column grid) --}}
                            <div class="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                                <p class="text-xs font-semibold text-slate-400 dark:text-slate-500
                                           uppercase tracking-wide px-4 pt-3 pb-2">
                                    Informasi Pelapor
                                </p>
                                <dl class="grid grid-cols-1 sm:grid-cols-[7rem_1fr] gap-x-3 gap-y-0
                                           divide-y divide-slate-100 dark:divide-slate-700/60">
                                    <dt class="text-slate-500 dark:text-slate-400 text-xs px-4 py-2.5 flex items-center">
                                        Atas Nama
                                    </dt>
                                    <dd class="font-semibold text-slate-800 dark:text-white text-sm px-4 py-2.5 sm:border-l
                                               border-slate-100 dark:border-slate-700/60"
                                        x-text="result[0].nama"></dd>

                                    <dt class="text-slate-500 dark:text-slate-400 text-xs px-4 py-2.5 flex items-start">
                                        Alamat
                                    </dt>
                                    <dd class="text-slate-700 dark:text-slate-300 text-sm px-4 py-2.5 sm:border-l
                                               border-slate-100 dark:border-slate-700/60"
                                        x-text="result[0].alamat"></dd>

                                    <dt class="text-slate-500 dark:text-slate-400 text-xs px-4 py-2.5 flex items-start">
                                        Pengaduan
                                    </dt>
                                    <dd class="text-slate-700 dark:text-slate-300 text-sm px-4 py-2.5 sm:border-l
                                               border-slate-100 dark:border-slate-700/60"
                                        x-text="result[0].pengaduan"></dd>
                                </dl>
                            </div>

                            {{-- Lifecycle timeline built from single SOAP record --}}
                            <div class="rounded-xl border border-slate-200 dark:border-slate-700 px-4 py-3.5">
                                <p class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-3">
                                    Riwayat Status
                                </p>

                                <ol class="space-y-3">
                                    {{-- Step 1: Dilaporkan --}}
                                    <li class="relative pl-8">
                                        <span class="absolute left-0 top-0.5 flex items-center justify-center
                                                     w-4 h-4 rounded-full bg-slate-500 dark:bg-slate-400">
                                            <i class="fa fa-flag text-white text-[7px]"></i>
                                        </span>
                                            <span x-show="result[0].tanggal_selesai" x-cloak
                                                class="absolute left-1.75 top-5 h-8 w-px bg-slate-200 dark:bg-slate-600"></span>

                                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-100 leading-tight">
                                            Dilaporkan
                                        </p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1"
                                           x-text="formatTanggal(result[0].tanggal_masuk)"></p>
                                    </li>

                                    {{-- Step 2: Selesai (only if processed date is valid) --}}
                                    <template x-if="result[0].tanggal_selesai">
                                        <li class="relative pl-8">
                                            <span class="absolute left-0 top-0.5 flex items-center justify-center
                                                         w-4 h-4 rounded-full bg-emerald-500">
                                                <i class="fa fa-check text-white text-[7px]"></i>
                                            </span>
                                            <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-400 leading-tight">
                                                Selesai
                                            </p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1"
                                               x-text="formatTanggal(result[0].tanggal_selesai)"></p>
                                        </li>
                                    </template>
                                </ol>
                            </div>

                            {{-- Catatan petugas --}}
                            <template x-if="result[0].catatan">
                                <div class="rounded-xl border border-slate-200 dark:border-slate-700
                                            bg-slate-50 dark:bg-slate-900/30 px-4 py-3.5">
                                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400
                                               uppercase tracking-wide mb-1.5">
                                        <i class="fa fa-note-sticky mr-1"></i>Catatan Petugas
                                    </p>
                                    <p class="text-sm text-slate-800 dark:text-slate-200 font-medium"
                                       x-text="result[0].catatan"></p>
                                </div>
                            </template>

                        </div>
                    </template>

                    {{-- Empty state --}}
                    <template x-if="!result && !searched && !loading">
                        <div class="text-center py-10">
                            <div class="w-14 h-14 mx-auto mb-3 rounded-full
                                        bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                                <i class="fa fa-magnifying-glass text-xl text-slate-300 dark:text-slate-500"></i>
                            </div>
                            <p class="text-sm text-slate-400 dark:text-slate-500">
                                Masukkan nomor pengaduan untuk melihat status.
                            </p>
                        </div>
                    </template>

                </div>
            </div>

        </div>{{-- /right panel --}}

    </div>{{-- /flex --}}
</div>{{-- /app-card --}}

@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV/XN/WPbA=" crossorigin=""></script>
<script>
    let _mapInitialized = false;

    window.leafletMapInit = function () {
        if (_mapInitialized) { window.leafletMap?.invalidateSize(); return; }
        _mapInitialized = true;

        const lat0 = -7.404609, lng0 = 109.3747799;
        const map  = L.map('peta', { scrollWheelZoom: false }).setView([lat0, lng0], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(map);

        const marker = L.marker([lat0, lng0], { draggable: true }).addTo(map);

        window.leafletMap    = map;
        window.leafletMarker = marker;

        function sync(ll) {
            document.getElementById('LatCoords').value = ll.lat;
            document.getElementById('LngCoords').value = ll.lng;
        }

        marker.on('dragend', e => sync(e.target.getLatLng()));

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                pos => {
                    const ll = [pos.coords.latitude, pos.coords.longitude];
                    map.setView(ll, 16); marker.setLatLng(ll);
                    sync({ lat: ll[0], lng: ll[1] });
                },
                () => {},
                { enableHighAccuracy: true, timeout: 10000 }
            );
        }

        sync({ lat: lat0, lng: lng0 });
    };

    function formatTanggal(iso) {
        if (!iso) return '';
        const d = new Date(iso);
        if (isNaN(d)) return iso;
        const hari  = ['Minggu','Senin','Selasa','Rabu','Kamis',"Jum'at",'Sabtu'];
        const bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        return `${hari[d.getDay()]}, ${d.getDate()} ${bulan[d.getMonth()]} ${d.getFullYear()}`
             + `  ${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}`;
    }
</script>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endpush
