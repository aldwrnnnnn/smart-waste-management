@extends('layouts.app')

@section('title', 'Masuk ke Akun - Smart Management System')

@section('content')
<main class="min-h-[100dvh] w-full bg-[#EBF2ED] flex items-center justify-center p-4 sm:p-6 lg:p-8">
    <!-- Card Utama (Sesuai Sketsa: Kartu 2-Panel Terbagi dengan Lengkungan Organik S-Curve) -->
    <div class="relative w-full max-w-md md:max-w-3xl lg:max-w-[820px] rounded-[24px] sm:rounded-[28px] overflow-hidden border-2 border-[#758C7D] bg-white shadow-xl shadow-forest-950/10 md:min-h-[500px] flex flex-col md:flex-row">

        <!-- Lapisan Latar Belakang SVG S-Curve (Desktop >= 768px) -->
        <div class="hidden md:block absolute inset-y-0 left-0 w-[46%] pointer-events-none z-0">
            <svg class="w-full h-full text-forest-900 fill-current" viewBox="0 0 500 1000" preserveAspectRatio="none" aria-hidden="true">
                <!-- Bidang hijau panel kiri dengan lengkungan S-curve pada batas kanan -->
                <path d="M 0,0 L 475,0 C 455,180 460,340 475,480 C 495,640 500,820 485,940 L 475,1000 L 0,1000 Z" />
                <!-- Garis pembatas melengkung sesuai garis pena pada sketsa -->
                <path d="M 475,0 C 455,180 460,340 475,480 C 495,640 500,820 485,940 L 475,1000" fill="none" stroke="#758C7D" stroke-width="2.5" vector-effect="non-scaling-stroke" />
            </svg>
        </div>

        <!-- Lapisan Latar Belakang Wave (Mobile < 768px) -->
        <div class="md:hidden absolute inset-x-0 top-0 h-[280px] pointer-events-none z-0">
            <svg class="w-full h-full text-forest-900 fill-current" viewBox="0 0 1000 400" preserveAspectRatio="none" aria-hidden="true">
                <path d="M 0,0 L 1000,0 L 1000,340 Q 500,410 0,340 Z" />
                <path d="M 0,340 Q 500,410 1000,340" fill="none" stroke="#758C7D" stroke-width="2.5" vector-effect="non-scaling-stroke" />
            </svg>
        </div>

        <!-- Panel Kiri: Identitas & Informasi Sistem (Sesuai Sketsa) -->
        <section
            aria-label="Informasi Smart Management System"
            class="relative z-10 w-full md:w-[46%] text-white p-5 sm:p-6 lg:p-7 flex flex-col justify-between"
        >
            <div class="flex flex-col items-center text-center my-auto py-1">
                <!-- Medali Logo Lingkaran (Sesuai Sketsa: LOGO dalam lingkaran) -->
                <div
                    class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-forest-800 border-2 border-[#A7C4B5] shadow-md flex items-center justify-center select-none shrink-0 overflow-hidden"
                    aria-label="Logo Smart Management System"
                >
                    @if(file_exists(public_path('images/logo.png')))
                        <img src="{{ asset('images/logo.png') }}" alt="Logo Smart Waste" class="w-full h-full object-cover">
                    @else
                        <div class="flex flex-col items-center justify-center">
                            <svg class="w-6 h-6 text-[#D5E0D8] mb-0.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-4.97 0-9 4.03-9 9 0 4.17 2.84 7.67 6.69 8.69L12 21l2.31-.31C18.16 19.67 21 16.17 21 12c0-4.97-4.03-9-9-9zm0 0v18m0-12l4 4m-4-4L8 13"/>
                            </svg>
                            <span class="font-mono text-[10px] font-semibold uppercase tracking-widest text-[#D5E0D8]">[LOGO]</span>
                        </div>
                    @endif
                </div>

                <!-- Judul Sistem -->
                <h1 class="mt-3 text-lg sm:text-xl font-bold tracking-tight text-white leading-snug">
                    Smart Management System
                </h1>

                <!-- Deskripsi Sistem (Representasi Garis Gelombang pada Sketsa) -->
                <p class="mt-2 text-xs sm:text-sm text-[#D5E0D8] leading-relaxed max-w-xs">
                    Sistem pengelolaan sampah berbasis teknologi. Laporkan temuan sampah di lingkungan anda dan deteksi menggunakan AI.
                </p>

                <!-- Tag Kategori Klasifikasi Sampah -->
                <div class="mt-3.5 flex flex-wrap items-center justify-center gap-1.5">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-forest-950/80 border border-[#A7C4B5]/40 text-[11px] font-medium text-[#D5E0D8]">
                        <svg class="w-3 h-3 text-[#A7C4B5]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Organik
                    </span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-forest-950/80 border border-[#A7C4B5]/40 text-[11px] font-medium text-[#D5E0D8]">
                        <svg class="w-3 h-3 text-[#A7C4B5]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Plastik
                    </span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-forest-950/80 border border-[#A7C4B5]/40 text-[11px] font-medium text-[#D5E0D8]">
                        <svg class="w-3 h-3 text-[#A7C4B5]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Kertas
                    </span>
                </div>
            </div>

            <!-- Tombol Cepat Akun Demo (Untuk Kemudahan Pengujian) -->
            <div class="mt-4 pt-2.5 border-t border-[#A7C4B5]/25">
                <div class="text-[10px] font-medium uppercase tracking-wider text-[#A7C4B5] text-center mb-1.5">
                    Isi Akun Demo Cepat
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        id="demoAdminBtn"
                        onclick="fillDemoCredentials('admin@waste.com', 'password')"
                        class="px-2 py-1.5 min-h-[38px] rounded-lg bg-forest-950/80 hover:bg-forest-800 border border-[#A7C4B5]/40 text-left transition-colors flex flex-col justify-center"
                    >
                        <span class="text-[11px] font-bold text-white block leading-tight">Admin</span>
                        <span class="text-[10px] font-mono text-[#D5E0D8] truncate block leading-tight">admin@waste.com</span>
                    </button>
                    <button
                        type="button"
                        id="demoUserBtn"
                        onclick="fillDemoCredentials('user@waste.com', 'password')"
                        class="px-2 py-1.5 min-h-[38px] rounded-lg bg-forest-950/80 hover:bg-forest-800 border border-[#A7C4B5]/40 text-left transition-colors flex flex-col justify-center"
                    >
                        <span class="text-[11px] font-bold text-white block leading-tight">Warga Pelapor</span>
                        <span class="text-[10px] font-mono text-[#D5E0D8] truncate block leading-tight">user@waste.com</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- Panel Kanan: Formulir Login (Sesuai Sketsa) -->
        <section
            aria-labelledby="loginHeading"
            class="relative z-10 w-full md:w-[54%] text-ink p-5 sm:p-7 lg:p-9 flex flex-col justify-center bg-white md:bg-transparent"
        >
            <div class="w-full max-w-sm mx-auto">
                <header class="text-center">
                    <h2 id="loginHeading" class="text-xl sm:text-2xl font-bold text-ink tracking-tight">
                        Masuk ke Akun
                    </h2>
                    <p class="mt-1 text-xs sm:text-sm text-ink-muted">
                        silahkan login terlebih dahulu
                    </p>
                </header>

                @if($errors->any())
                    <div role="alert" class="mt-3.5 rounded-lg bg-[#FEE2E2] border border-[#B91C1C] px-3.5 py-2.5 text-xs sm:text-sm text-[#7F1D1D] flex items-start gap-2">
                        <svg class="w-4 h-4 shrink-0 mt-0.5 text-[#7F1D1D]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zm9-3.75a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="space-y-0.5">
                            @foreach($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form id="loginForm" method="POST" action="{{ route('login.submit') }}" class="mt-4 space-y-3" novalidate>
                    @csrf

                    <div>
                        <label for="email" class="block text-xs font-semibold text-ink mb-1">
                            Alamat Email
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#43544A]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                                </svg>
                            </span>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                required
                                autocomplete="email"
                                placeholder="email@example.com"
                                aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                                class="w-full pl-9 pr-3.5 min-h-[40px] rounded-lg border border-[#758C7D] bg-[#F4F7F4] text-ink placeholder:text-[#5C7063] text-xs sm:text-sm transition-colors focus:bg-white focus:border-forest-700"
                            >
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-semibold text-ink mb-1">
                            Kata Sandi
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#43544A]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                                </svg>
                            </span>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                required
                                autocomplete="current-password"
                                placeholder="Masukkan kata sandi"
                                aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                                class="w-full pl-9 pr-10 min-h-[40px] rounded-lg border border-[#758C7D] bg-[#F4F7F4] text-ink placeholder:text-[#5C7063] text-xs sm:text-sm transition-colors focus:bg-white focus:border-forest-700"
                            >
                            <button
                                type="button"
                                id="togglePasswordBtn"
                                aria-label="Tampilkan kata sandi"
                                aria-pressed="false"
                                class="absolute inset-y-0 right-0 min-w-[40px] min-h-[40px] flex items-center justify-center text-[#43544A] hover:text-ink rounded-r-lg transition-colors"
                            >
                                <svg id="eyeOpenIcon" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <svg id="eyeClosedIcon" class="w-4 h-4 hidden" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="inline-flex items-center gap-2 text-xs sm:text-sm text-ink cursor-pointer min-h-[36px] select-none">
                            <input
                                type="checkbox"
                                name="remember"
                                class="w-3.5 h-3.5 rounded border-[#758C7D] text-forest-700 focus:ring-forest-700"
                            >
                            <span>Ingat sesi saya di perangkat ini</span>
                        </label>
                    </div>

                    <!-- Pesan Validasi Sisi Klien -->
                    <div id="clientValidationAlert" role="alert" class="hidden rounded-lg bg-[#FEE2E2] border border-[#B91C1C] px-3 py-2 text-xs text-[#7F1D1D] items-start gap-2">
                        <svg class="w-4 h-4 shrink-0 mt-0.5 text-[#7F1D1D]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zm9-3.75a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span id="clientValidationText">Harap isi alamat email dan kata sandi terlebih dahulu.</span>
                    </div>

                    <!-- Tombol Masuk (Sesuai Sketsa: Tombol Masuk di Tengah) -->
                    <div class="pt-1 flex justify-center">
                        <button
                            type="submit"
                            id="loginSubmitBtn"
                            class="min-w-[150px] min-h-[42px] px-6 py-2 rounded-lg bg-forest-700 hover:bg-forest-800 active:bg-forest-900 text-white font-semibold text-xs sm:text-sm shadow-sm hover:shadow transition-all inline-flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
                        >
                            <span id="loginBtnLabel">Masuk</span>
                        </button>
                    </div>
                </form>

                <!-- Tautan Daftar (Sesuai Sketsa: Belum punya akun? Daftar) -->
                <div class="mt-3.5 text-center text-xs sm:text-sm text-ink-muted flex flex-wrap items-center justify-center gap-1">
                    <span>Belum punya akun?</span>
                    <a
                        href="{{ route('register') }}"
                        class="inline-flex items-center px-1 min-h-[36px] font-semibold text-forest-700 underline underline-offset-4 hover:text-forest-900 transition-colors"
                    >
                        Daftar
                    </a>
                </div>
            </div>
        </section>

    </div>
</main>

<script>
    function fillDemoCredentials(email, password) {
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const alertBox = document.getElementById('clientValidationAlert');

        if (emailInput && passwordInput) {
            emailInput.value = email;
            passwordInput.value = password;
            alertBox?.classList.add('hidden');
            alertBox?.classList.remove('flex');
            emailInput.focus();
        }
    }

    (function () {
        const form = document.getElementById('loginForm');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const togglePassBtn = document.getElementById('togglePasswordBtn');
        const eyeOpenIcon = document.getElementById('eyeOpenIcon');
        const eyeClosedIcon = document.getElementById('eyeClosedIcon');
        const submitBtn = document.getElementById('loginSubmitBtn');
        const submitLabel = document.getElementById('loginBtnLabel');
        const alertBox = document.getElementById('clientValidationAlert');
        const alertText = document.getElementById('clientValidationText');

        togglePassBtn?.addEventListener('click', function () {
            if (!passwordInput) return;
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            togglePassBtn.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
            togglePassBtn.setAttribute('aria-label', isHidden ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            eyeOpenIcon?.classList.toggle('hidden', isHidden);
            eyeClosedIcon?.classList.toggle('hidden', !isHidden);
        });

        form?.addEventListener('submit', function (e) {
            const emailVal = emailInput?.value.trim() || '';
            const passVal = passwordInput?.value || '';

            if (!emailVal || !passVal) {
                e.preventDefault();
                alertText.textContent = !emailVal && !passVal
                    ? 'Alamat email dan kata sandi belum diisi. Silahkan lengkapi kedua kolom tersebut.'
                    : (!emailVal ? 'Alamat email wajib diisi sebelum melanjutkan.' : 'Kata sandi wajib diisi sebelum melanjutkan.');
                alertBox.classList.remove('hidden');
                alertBox.classList.add('flex');
                (!emailVal ? emailInput : passwordInput)?.focus();
                return;
            }

            alertBox.classList.add('hidden');
            alertBox.classList.remove('flex');
            submitBtn.disabled = true;
            submitLabel.textContent = 'Memproses...';
        });
    })();
</script>
@endsection
