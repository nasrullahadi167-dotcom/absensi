@extends('layouts.member')

@section('title', 'Kamera Selfie Presensi: ' . $event->title)

@section('content')
<div class="max-w-xl mx-auto space-y-6"
     x-data="cameraEngine({
         csrfToken: '{{ csrf_token() }}',
         submitUrl: '{{ route('member.attendance.store', $event) }}',
         eventUrl: '{{ route('member.events.show', $event) }}'
     })"
     x-init="init()">

    {{-- Event Header Info --}}
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xs p-5">
        <div class="flex items-center justify-between">
            <div>
                <span class="font-mono text-[11px] font-bold text-indigo-600 bg-indigo-50 px-2.5 py-0.5 rounded">
                    {{ $event->code }}
                </span>
                <h1 class="text-base font-extrabold text-slate-900 mt-1 leading-tight">{{ $event->title }}</h1>
                <p class="text-xs text-slate-500 mt-0.5">Sesi: {{ $session->title }} (Toleransi: {{ $session->late_tolerance_minutes }} menit)</p>
            </div>
            <a href="{{ route('member.events.show', $event) }}" class="text-xs font-bold text-slate-400 hover:text-slate-700">
                Batal
            </a>
        </div>
    </div>

    {{-- Camera & Preview Card --}}
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden p-6 space-y-5">

        {{-- Camera Permission Warning Banner --}}
        <div x-show="cameraError" class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-900" x-cloak>
            <div class="flex items-start gap-2.5">
                <span class="text-base font-bold text-amber-600">⚠️</span>
                <div class="flex-1">
                    <p class="font-bold text-amber-950">Akses Kamera Terkendala</p>
                    <p class="mt-0.5" x-text="cameraError"></p>
                    <p class="mt-1 text-[11px] text-amber-800">
                        Anda dapat beralih menggunakan tombol <strong>"Kamera Perangkat (Fallback)"</strong> di bawah ini.
                    </p>
                </div>
            </div>
        </div>

        {{-- Live Viewfinder / Canvas Area --}}
        <div class="relative w-full aspect-[3/4] sm:aspect-[4/3] bg-slate-950 rounded-2xl overflow-hidden flex items-center justify-center border border-slate-800 shadow-inner">

            {{-- 1. Video Stream Element --}}
            <video x-ref="videoElement"
                   x-show="streaming && !captured"
                   autoplay
                   playsinline
                   muted
                   class="w-full h-full object-cover transform -scale-x-100"></video>

            {{-- 2. Captured Image Preview --}}
            <img x-show="captured && photoData"
                 :src="photoData"
                 alt="Preview Selfie"
                 class="w-full h-full object-cover"
                 x-cloak>

            {{-- 3. Camera Initializing Placeholder --}}
            <div x-show="!streaming && !captured && !useFallback" class="text-center p-6 space-y-2 text-slate-400">
                <div class="w-10 h-10 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
                <p class="text-xs font-semibold text-slate-300">Menghubungkan ke kamera depan...</p>
                <p class="text-[11px] text-slate-500">Mohon izinkan akses kamera jika diminta oleh browser.</p>
            </div>

            {{-- 4. Fallback File Selected Placeholder --}}
            <div x-show="useFallback && !captured" class="text-center p-6 space-y-3 text-slate-400" x-cloak>
                <div class="w-16 h-16 rounded-full bg-slate-800 text-slate-400 flex items-center justify-center mx-auto text-2xl">
                    📷
                </div>
                <p class="text-xs font-semibold text-slate-300">Gunakan Kamera Bawaan HP</p>
                <label class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl cursor-pointer shadow-md">
                    <span>Buka Kamera HP</span>
                    <input type="file" accept="image/*" capture="user" @change="handleFallbackFile($event)" class="hidden">
                </label>
            </div>

            {{-- Face Framing Guide Overlay --}}
            <div x-show="streaming && !captured" class="absolute inset-0 pointer-events-none flex items-center justify-center" x-cloak>
                <div class="w-56 h-72 border-2 border-dashed border-white/60 rounded-full"></div>
            </div>

            {{-- Hidden Processing Canvas --}}
            <canvas x-ref="canvasElement" class="hidden"></canvas>
        </div>

        {{-- Submission Form Fields --}}
        <div>
            <label for="notes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Catatan Kehadiran (Opsional)</label>
            <input type="text" id="notes" x-model="notes" placeholder="Contoh: Mengikuti sesi pagi tepat waktu"
                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-xs">
        </div>

        {{-- Dynamic Action Controls --}}
        <div class="space-y-3 pt-2">

            {{-- When live stream is active and not captured yet --}}
            <template x-if="streaming && !captured">
                <div class="space-y-2">
                    <button type="button"
                            @click="takeSnapshot()"
                            class="w-full py-4 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-sm shadow-xl shadow-indigo-600/30 flex items-center justify-center gap-2 transition-all active:scale-95">
                        <span class="w-4 h-4 rounded-full bg-white"></span>
                        Ambil Foto Selfie
                    </button>
                    <button type="button"
                            @click="enableFallback()"
                            class="w-full py-2 text-slate-400 hover:text-slate-600 text-[11px] font-semibold">
                        Gunakan Kamera Bawaan HP (Fallback)
                    </button>
                </div>
            </template>

            {{-- When photo is captured and ready for review --}}
            <template x-if="captured">
                <div class="space-y-2">
                    <button type="button"
                            @click="submitAttendance()"
                            :disabled="isSubmitting"
                            class="w-full py-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-400 text-white font-black text-sm shadow-xl shadow-emerald-600/30 flex items-center justify-center gap-2 transition-all">
                        <template x-if="isSubmitting">
                            <span class="inline-flex items-center gap-2">
                                <span class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                Memvalidasi & Mengirim Presensi...
                            </span>
                        </template>
                        <template x-if="!isSubmitting">
                            <span class="inline-flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                Kirim Presensi Sekarang
                            </span>
                        </template>
                    </button>

                    <button type="button"
                            @click="retakeSnapshot()"
                            :disabled="isSubmitting"
                            class="w-full py-3 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors">
                        ↻ Ulangi Foto
                    </button>
                </div>
            </template>

            {{-- When error or fallback mode --}}
            <template x-if="!streaming && !captured && cameraError">
                <div>
                    <label class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-2xl cursor-pointer shadow-lg flex items-center justify-center gap-2">
                        <span>📷 Ambil Foto dari Kamera HP</span>
                        <input type="file" accept="image/*" capture="user" @change="handleFallbackFile($event)" class="hidden">
                    </label>
                </div>
            </template>

        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
function cameraEngine(config) {
    return {
        stream: null,
        streaming: false,
        captured: false,
        photoData: null,
        notes: '',
        isSubmitting: false,
        cameraError: null,
        useFallback: false,

        init() {
            this.startCamera();
        },

        async startCamera() {
            this.cameraError = null;
            this.useFallback = false;

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                this.cameraError = 'Browser Anda tidak mendukung akses kamera langsung. Silakan gunakan kamera bawaan.';
                this.useFallback = true;
                return;
            }

            try {
                // Request front camera (facingMode: user) with optimal resolution
                const constraints = {
                    video: {
                        facingMode: 'user',
                        width: { ideal: 1280 },
                        height: { ideal: 960 }
                    },
                    audio: false
                };

                this.stream = await navigator.mediaDevices.getUserMedia(constraints);
                const video = this.$refs.videoElement;
                video.srcObject = this.stream;
                await video.play();
                this.streaming = true;
            } catch (err) {
                console.warn('Camera access error:', err);
                if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                    this.cameraError = 'Izin kamera ditolak. Silakan izinkan akses kamera pada pengaturan browser.';
                } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                    this.cameraError = 'Kamera depan tidak ditemukan pada perangkat Anda.';
                } else {
                    this.cameraError = 'Tidak dapat mengakses kamera: ' . (err.message || 'Kamera sedang digunakan aplikasi lain.');
                }
                this.useFallback = true;
                this.streaming = false;
            }
        },

        takeSnapshot() {
            const video = this.$refs.videoElement;
            const canvas = this.$refs.canvasElement;

            if (!video || !this.streaming) return;

            const width = video.videoWidth || 640;
            const height = video.videoHeight || 480;

            canvas.width = width;
            canvas.height = height;

            const ctx = canvas.getContext('2d');
            // Mirror flip matching the user selfie preview
            ctx.translate(width, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(video, 0, 0, width, height);

            // Compress to standard JPEG quality 0.8
            this.photoData = canvas.toDataURL('image/jpeg', 0.82);
            this.captured = true;

            // Stop active camera stream while previewing
            this.stopStream();
        },

        retakeSnapshot() {
            this.photoData = null;
            this.captured = false;
            if (!this.useFallback) {
                this.startCamera();
            }
        },

        stopStream() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
                this.streaming = false;
            }
        },

        enableFallback() {
            this.stopStream();
            this.useFallback = true;
        },

        handleFallbackFile(event) {
            const file = event.target.files && event.target.files[0];
            if (!file) return;

            // Validate file type
            if (!file.type.match(/^image\/(jpeg|png|webp|jpg)$/i)) {
                alert('Format file tidak didukung. Mohon gunakan format JPG, PNG, atau WEBP.');
                return;
            }

            // Read and compress via Image + Canvas
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    const canvas = this.$refs.canvasElement;
                    let width = img.width;
                    let height = img.height;

                    // Resize if larger than 1280px
                    const maxDim = 1280;
                    if (width > maxDim || height > maxDim) {
                        if (width > height) {
                            height = Math.round((height * maxDim) / width);
                            width = maxDim;
                        } else {
                            width = Math.round((width * maxDim) / height);
                            height = maxDim;
                        }
                    }

                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    this.photoData = canvas.toDataURL('image/jpeg', 0.82);
                    this.captured = true;
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        async submitAttendance() {
            if (!this.photoData) {
                alert('Foto selfie wajib diambil terlebih dahulu.');
                return;
            }

            this.isSubmitting = true;

            try {
                const response = await fetch(config.submitUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken
                    },
                    body: JSON.stringify({
                        photo_data: this.photoData,
                        notes: this.notes
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    alert(data.message || 'Absensi berhasil dicatat!');
                    window.location.href = data.redirect_url || config.eventUrl;
                } else {
                    alert('Gagal presensi: ' + (data.message || 'Terjadi kesalahan sistem.'));
                    this.isSubmitting = false;
                }
            } catch (err) {
                console.error(err);
                alert('Terjadi kendala jaringan saat mengirim absensi. Silakan coba lagi.');
                this.isSubmitting = false;
            }
        }
    };
}
</script>
@endpush
