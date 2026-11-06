@extends('layouts.master-blank')

@section('body-class', 'cs-auth')
@section('css')
    <link href="{{ URL::asset('css/cerah-auth.css') }}?v=1" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    <div class="auth-shell">
        <div class="auth-grid">
            <div class="auth-brand">
                <div class="auth-brand-content">
                    <div class="auth-logo">
                        <img src="{{ asset('assets/images/logo-pln1.png') }}" alt="PLNSC">
                    </div>
                    <p>Sistem untuk pengelolaan peserta magang secara terstruktur dan efisien.</p>
                </div>
            </div>

            <div class="auth-form-panel">
                <div class="auth-title">Selamat Datang Kembali</div>
                <div class="auth-subtitle">Masuk ke akun Anda</div>

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="auth-field">
                        <label for="email">Alamat Email</label>
                        <input id="email" type="email"
                            class="form-control auth-input @error('email') is-invalid @enderror" name="email"
                            value="{{ old('email') }}" required autocomplete="email" autofocus
                            placeholder="Masukkan email Anda">

                        @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="auth-field">
                        <label for="password">Kata Sandi</label>
                        <div class="password-wrap">
                            <input id="password" type="password"
                                class="form-control auth-input @error('password') is-invalid @enderror" name="password"
                                required autocomplete="current-password" placeholder="Masukkan kata sandi">
                            <button type="button" class="password-toggle" id="toggle-password"
                                aria-label="Tampilkan atau sembunyikan kata sandi">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path d="M1 12s4-7 11-7s11 7 11 7s-4 7-11 7S1 12 1 12z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>

                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="auth-row">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember"
                                {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember">Ingat Saya</label>
                        </div>
                        <a class="forgot-link" href="javascript:void(0)">Lupa Kata Sandi?</a>
                    </div>

                    <button class="auth-submit" type="submit">Masuk</button>
                </form>

                <div class="auth-link">
                    Belum punya akun?
                    <a href="{{ route('register') }}">Daftar sekarang</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        (function() {
            var toggle = document.getElementById('toggle-password');
            var password = document.getElementById('password');
            if (!toggle || !password) {
                return;
            }
            toggle.addEventListener('click', function() {
                password.type = password.type === 'text' ? 'password' : 'text';
            });
        })();
    </script>
@endsection
