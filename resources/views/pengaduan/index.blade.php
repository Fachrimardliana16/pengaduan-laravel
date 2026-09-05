@extends('layouts.app')

@section('title', 'Layanan Pengaduan | Perumdam Tirta Perwira')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@endpush

@section('content')

{{-- Validation error summary --}}
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

{{-- ═══ Main Card ═══════════════════════════════════════════════════════════ --}}
<div class="app-card"
     x-data="{
         activeTab: window.location.hash === '#cari' ? 'cari' : 'form',
         switchTab(tab) {
             this.activeTab = tab;
             history.replaceState(null, '', tab === 'cari' ? '#cari' : '#');
         }
     }"
     {{-- Lazy-init Leaflet when form tab first becomes visible --}}
     x-effect="activeTab === 'form' && $nextTick(() => window.leafletMapInit?.())">

    <div class="flex flex-col sm:flex-row">

        {{-- ── LEFT PANEL: Tabs + Content ─────────────────────────────────── --}}
        <div class="flex-1 min-w-0">

            {{-- Tab bar --}}
            <div class="flex border-b border-slate-200 dark:border-slate-700
                        bg-slate-50/80 dark:bg-slate-800/60">
                <button @click="switchTab('form')" class="tab-btn"
                        :class="activeTab === 'form'
                            ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400 bg-white dark:bg-slate-800 font-semibold'
                            : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-white/60 dark:hover:bg-slate-700/40'">
                    <i class="fa fa-pen-to-square text-xs"></i>
                    <span>Form Pengaduan</span>
                </button>
                <button @click="switchTab('cari')" class="tab-btn"
                        :class="activeTab === 'cari'
                            ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400 bg-white dark:bg-slate-800 font-semibold'
                            : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-white/60 dark:hover:bg-slate-700/40'">
                    <i class="fa fa-magnifying-glass text-xs"></i>
                    <span>Cek Status</span>
                </button>
            </div>

            {{-- ── TAB: FORM PENGADUAN ──────────────────────────────────── --}}
            <div x-show="activeTab === 'form'">
                <div class="card-body">
                    <form id="formPengaduan"
                          method="POST"
                          action="{{ route('pengaduan.store') }}"
                          {{-- Cegah double submit: tangkap event submit, bukan click button --}}
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
                                          const latlng = [pos.coords.latitude, pos.coords.longitude];
                                          this.lat = latlng[0];
                                          this.lng = latlng[1];
                                          window.leafletMap?.setView(latlng, 17);
                                          window.leafletMarker?.setLatLng(latlng);
                                      },
                                      err => {
                                          const msgs = {
                                              1: 'Izin lokasi ditolak. Aktifkan di pengaturan browser.',
                                              2: 'Posisi tidak dapat ditentukan.',
                                              3: 'Permintaan lokasi habis waktu.',
                                          };
                                          this.geoError = msgs[err.code] ?? 'Gagal mendapatkan lokasi.';
                                      },
                                      { enableHighAccuracy: true, timeout: 12000 }
                                  );
                              }
                          }"
                          x-init="loadKecamatan()">

                        @csrf

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            {{-- Nama Pelapor --}}
                            <div>
                                <label class="field-label">Nama Pelapor <span class="text-red-500">*</span></label>
                                <input type="text" name="Name" placeholder="Nama lengkap"
                                       value="{{ old('Name') }}" maxlength="100" required
                                       class="field-input {{ $errors->has('Name') ? 'field-input-error' : '' }}">
                                @error('Name') <p class="field-error">{{ $message }}</p> @enderror
                            </div>

                            {{-- Nomor Telepon --}}
                            <div>
                                <label class="field-label">Nomor Telepon <span class="text-red-500">*</span></label>
                                <input type="tel" name="PhoneNumber" placeholder="08xxxxxxxxxx"
                                       value="{{ old('PhoneNumber') }}" maxlength="15" required
                                       class="field-input {{ $errors->has('PhoneNumber') ? 'field-input-error' : '' }}">
                                @error('PhoneNumber') <p class="field-error">{{ $message }}</p> @enderror
                            </div>

                            {{-- Nomor Pelanggan --}}
                            <div class="sm:col-span-2">
                                <label class="field-label">
                                    Nomor Pelanggan
                                    <span class="text-slate-400 font-normal text-xs ml-1">(opsional)</span>
                                </label>
                                <input type="text" name="CustomerNumber" placeholder="Nomor pelanggan PDAM"
                                       value="{{ old('CustomerNumber') }}" maxlength="12"
                                       class="field-input">
                            </div>

                            {{-- Alamat --}}
                            <div class="sm:col-span-2">
                                <label class="field-label">Alamat <span class="text-red-500">*</span></label>
                                <input type="text" name="Address" placeholder="Jl. Contoh No. 1, RT/RW, …"
                                       value="{{ old('Address') }}" maxlength="250" required
                                       class="field-input {{ $errors->has('Address') ? 'field-input-error' : '' }}">
                                @error('Address') <p class="field-error">{{ $message }}</p> @enderror
                            </div>

                            {{-- Kecamatan --}}
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

                            {{-- Desa / Kelurahan --}}
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

                            {{-- Jenis Pengaduan --}}
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

                            {{-- Deskripsi --}}
                            <div class="sm:col-span-2">
                                <label class="field-label">Deskripsi Pengaduan <span class="text-red-500">*</span></label>
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

                            {{-- Hidden koordinat --}}
                            <input type="hidden" name="LatCoords" id="LatCoords" x-bind:value="lat ?? ''">
                            <input type="hidden" name="LngCoords" id="LngCoords" x-bind:value="lng ?? ''">

                            {{-- Peta --}}
                            <div class="sm:col-span-2">
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="field-label mb-0">
                                        <i class="fa fa-location-dot text-red-500 mr-1"></i>
                                        Lokasi
                                        <span class="text-slate-400 font-normal text-xs ml-1">(geser pin ke TKP)</span>
                                    </label>
                                    <button type="button" @click="requestGeolocation()"
                                            class="text-xs text-blue-600 dark:text-blue-400 hover:underline
                                                   flex items-center gap-1 shrink-0">
                                        <i class="fa fa-crosshairs text-xs"></i>
                                        Lokasi Saya
                                    </button>
                                </div>
                                <div id="peta" class="w-full h-48 sm:h-52 rounded-lg overflow-hidden
                                                       border border-slate-200 dark:border-slate-600"></div>
                                <p x-show="geoError" x-cloak class="field-error mt-1">
                                    <i class="fa fa-triangle-exclamation mr-1"></i>
                                    <span x-text="geoError"></span>
                                </p>
                            </div>

                            {{-- reCAPTCHA --}}
                            <div class="sm:col-span-2">
                                <label class="field-label">Verifikasi</label>
                                <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
                                @error('g-recaptcha-response') <p class="field-error">{{ $message }}</p> @enderror
                            </div>

                            {{-- Submit / Reset --}}
                            <div class="sm:col-span-2 flex gap-3 pt-1">
                                {{-- Button disabled via Alpine reactive binding; @submit on form prevents double submit --}}
                                <button type="submit" class="btn-primary flex-1" :disabled="submitting">
                                    <span x-show="!submitting" class="flex items-center gap-2">
                                        <i class="fa fa-paper-plane text-xs"></i>Kirim Pengaduan
                                    </span>
                                    <span x-show="submitting" x-cloak class="flex items-center gap-2">
                                        <svg class="spinner" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                                    stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                        </svg>
                                        Mengirim…
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
            </div>

            {{-- ── TAB: CEK STATUS ─────────────────────────────────────── --}}
            <div x-show="activeTab === 'cari'" x-cloak>
                <div class="card-body"
                     x-data="{
                         noPengaduan: '',
                         loading: false,
                         result: null,
                         error: '',

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
                                 } else if (json.data && json.data.length > 0) {
                                     this.result = json.data;
                                 } else {
                                     this.error = 'Nomor pengaduan tidak ditemukan.';
                                 }
                             } catch {
                                 this.error = 'Terjadi kesalahan. Periksa koneksi Anda.';
                             } finally {
                                 this.loading = false;
                             }
                         }
                     }">

                    <p class="field-hint">
                        <i class="fa fa-circle-info"></i>
                        Masukkan nomor pengaduan yang Anda terima setelah mengajukan laporan.
                    </p>

                    {{-- Search bar --}}
                    <div class="flex gap-2 mb-5">
                        <input type="text" x-model="noPengaduan" @keydown.enter.prevent="cari()"
                               placeholder="Contoh: PBG-2024-XXXXX"
                               class="field-input flex-1">
                        <button @click="cari()" :disabled="loading" class="btn-primary px-4 shrink-0">
                            <span x-show="!loading"><i class="fa fa-magnifying-glass text-xs"></i></span>
                            <span x-show="loading" x-cloak>
                                <svg class="spinner" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                </svg>
                            </span>
                            <span class="hidden sm:inline">Cari</span>
                        </button>
                    </div>

                    <div x-show="error" x-cloak class="alert-warning mb-4">
                        <i class="fa fa-triangle-exclamation shrink-0"></i>
                        <span x-text="error"></span>
                    </div>

                    <template x-if="result">
                        <div class="space-y-4">
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800
                                        rounded-xl p-4 space-y-2">
                                <div class="flex items-center gap-2">
                                    <i class="fa fa-user text-blue-500 w-4 text-center shrink-0"></i>
                                    <span class="text-slate-500 dark:text-slate-400 text-xs">Atas Nama:</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-100 text-sm"
                                          x-text="result[0].nama"></span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <i class="fa fa-map-marker-alt text-blue-500 w-4 text-center shrink-0 mt-0.5"></i>
                                    <span class="text-slate-500 dark:text-slate-400 text-xs pt-0.5">Alamat:</span>
                                    <span class="text-slate-700 dark:text-slate-300 text-xs"
                                          x-text="result[0].alamat"></span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <i class="fa fa-comment text-blue-500 w-4 text-center shrink-0 mt-0.5"></i>
                                    <span class="text-slate-500 dark:text-slate-400 text-xs pt-0.5">Pengaduan:</span>
                                    <span class="text-slate-700 dark:text-slate-300 text-xs"
                                          x-text="result[0].pengaduan"></span>
                                </div>
                            </div>

                            <div>
                                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400
                                          uppercase tracking-wide mb-3">Riwayat Status</p>
                                <ol class="relative border-l-2 border-blue-200 dark:border-blue-800 ml-2 space-y-5">
                                    <template x-for="(item, i) in result" :key="i">
                                        <li class="ml-5">
                                            <span class="absolute -left-2 flex items-center justify-center
                                                         w-4 h-4 rounded-full bg-blue-600
                                                         ring-4 ring-white dark:ring-slate-800"></span>
                                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100"
                                               x-text="item.status"></p>
                                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5"
                                               x-text="formatTanggal(item.tanggal)"></p>
                                        </li>
                                    </template>
                                </ol>
                            </div>
                        </div>
                    </template>

                    <template x-if="!result && !error && !loading">
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

        </div>{{-- /left panel --}}

        {{-- ── RIGHT PANEL: Info (always visible) ─────────────────────────── --}}
        <div class="sm:w-56 bg-blue-700 dark:bg-blue-900 text-white flex flex-col gap-5 p-5
                    border-t sm:border-t-0 sm:border-l border-blue-600 dark:border-blue-800">

            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-blue-200 mb-2">Jam Layanan</p>
                <p class="text-sm font-semibold">Senin – Jumat</p>
                <p class="text-sm text-blue-200">07.00 – 15.00 WIB</p>
            </div>

            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-blue-200 mb-2">Hubungi Kami</p>
                <p class="text-sm font-semibold flex items-center gap-2">
                    <i class="fa fa-phone text-blue-300 text-xs"></i>(0281) 895xxx
                </p>
                <p class="text-sm text-blue-200 flex items-center gap-2 mt-1">
                    <i class="fa fa-envelope text-blue-300 text-xs"></i>info@pdampurbalingga.co.id
                </p>
            </div>

            <div class="bg-blue-600/50 dark:bg-blue-800/60 rounded-xl p-3 text-xs text-blue-100 leading-relaxed">
                <p class="font-semibold mb-1 flex items-center gap-1.5">
                    <i class="fa fa-circle-info"></i>Informasi Penting
                </p>
                Pengaduan akan diproses sesuai antrian pada jam kerja. Simpan nomor pengaduan untuk memantau progress.
            </div>

            <div class="mt-auto">
                <a href="https://tagihan.pdampurbalingga.co.id" target="_blank"
                   class="w-full flex items-center justify-center gap-2 text-sm font-medium
                          bg-white/15 hover:bg-white/25 transition-colors rounded-xl px-4 py-2.5">
                    <i class="fa fa-file-invoice text-xs"></i>
                    Cek Tagihan
                </a>
            </div>

        </div>{{-- /right panel --}}

    </div>{{-- /flex --}}

</div>{{-- /app-card --}}

@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV/XN/WPbA=" crossorigin=""></script>
<script>
    // Leaflet init is called lazily by Alpine x-effect when form tab becomes visible.
    // This avoids the "map in hidden div" problem caused by x-show/x-cloak.
    let _mapInitialized = false;

    window.leafletMapInit = function () {
        if (_mapInitialized) {
            window.leafletMap?.invalidateSize();
            return;
        }
        _mapInitialized = true;

        const defaultLat = -7.404609;
        const defaultLng = 109.3747799;

        const map = L.map('peta', { scrollWheelZoom: false })
                     .setView([defaultLat, defaultLng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(map);

        const marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);

        window.leafletMap    = map;
        window.leafletMarker = marker;

        function updateCoords(latlng) {
            document.getElementById('LatCoords').value = latlng.lat;
            document.getElementById('LngCoords').value = latlng.lng;
        }

        marker.on('dragend', e => updateCoords(e.target.getLatLng()));

        // Auto-request location on first map open
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                pos => {
                    const ll = [pos.coords.latitude, pos.coords.longitude];
                    map.setView(ll, 16);
                    marker.setLatLng(ll);
                    updateCoords({ lat: ll[0], lng: ll[1] });
                },
                () => { /* silently fall back to default coords */ },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        }

        updateCoords({ lat: defaultLat, lng: defaultLng });
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
