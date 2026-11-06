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
                    <h2>{{ __('global.create_account') }}</h2>
                    <p>Daftar akun baru untuk mulai menggunakan sistem absensi.</p>
                </div>
            </div>

            <div class="auth-form-panel">
                <div class="auth-title">{{ __('global.create_account') }}</div>
                <div class="auth-subtitle">Lengkapi data berikut sesuai profil kamu.</div>

                <form method="POST" action="{{ route('register') }}">
                    @csrf
                    <div class="auth-grid-fields">
                        <div class="auth-field">
                            <label for="name">{{ __('global.name') }}<span class="required-mark">*</span></label>
                            <input id="name" type="text"
                                class="form-control auth-input @error('name') is-invalid @enderror" name="name"
                                value="{{ old('name') }}" required autocomplete="name"
                                placeholder="{{ __('global.placeholder_full_name') }}" autofocus>

                            @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="auth-field">
                            <label for="phone_number">{{ __('global.phone_number') }}<span
                                    class="required-mark">*</span></label>
                            <input id="phone_number" type="text"
                                class="form-control auth-input @error('phone_number') is-invalid @enderror"
                                name="phone_number" value="{{ old('phone_number') }}" required autocomplete="tel"
                                placeholder="{{ __('global.placeholder_phone') }}">

                            @error('phone_number')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="auth-field">
                            <label for="address">{{ __('global.address') }}<span class="required-mark">*</span></label>
                            <input id="address" type="text"
                                class="form-control auth-input @error('address') is-invalid @enderror" name="address"
                                value="{{ old('address') }}" required autocomplete="street-address"
                                placeholder="{{ __('global.placeholder_address') }}">

                            @error('address')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="auth-field">
                            <label for="birth_date">{{ __('global.birth_date') }}<span
                                    class="required-mark">*</span></label>
                            <input id="birth_date" type="date"
                                class="form-control auth-input @error('birth_date') is-invalid @enderror" name="birth_date"
                                value="{{ old('birth_date') }}" required autocomplete="bday">

                            @error('birth_date')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="auth-field">
                            <label for="institution">{{ __('global.institution') }}<span
                                    class="required-mark">*</span></label>
                            <input id="institution" type="text"
                                class="form-control auth-input @error('institution') is-invalid @enderror"
                                name="institution" value="{{ old('institution') }}" required autocomplete="organization"
                                placeholder="{{ __('global.placeholder_institution') }}">

                            @error('institution')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="auth-field">
                            <label for="major">{{ __('global.major') }}<span class="required-mark">*</span></label>
                            <input id="major" type="text"
                                class="form-control auth-input @error('major') is-invalid @enderror" name="major"
                                value="{{ old('major') }}" required autocomplete="major"
                                placeholder="{{ __('global.placeholder_major') }}">

                            @error('major')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="auth-field">
                            <label for="email">{{ __('global.email') }}<span class="required-mark">*</span></label>
                            <input id="email" type="email"
                                class="form-control auth-input @error('email') is-invalid @enderror" name="email"
                                value="{{ old('email') }}" required autocomplete="email"
                                placeholder="{{ __('global.placeholder_email') }}">

                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="auth-field">
                            <label for="password">{{ __('global.login_password') }}<span
                                    class="required-mark">*</span></label>
                            <div class="password-wrap">
                                <input id="password" type="password"
                                    class="form-control auth-input @error('password') is-invalid @enderror"
                                    name="password" required autocomplete="new-password">
                                <button type="button" class="password-toggle"
                                    id="toggle-password">{{ __('global.show_password') }}</button>
                            </div>
                            <small class="text-muted d-block mt-1">Minimal 8 karakter, wajib huruf besar, huruf kecil,
                                angka, dan simbol.</small>
                            <div id="password-strength" class="strength-indicator"></div>

                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="auth-field">
                            <label for="password-confirm">{{ __('global.confirm_password') }}<span
                                    class="required-mark">*</span></label>
                            <input id="password-confirm" type="password" class="form-control auth-input"
                                name="password_confirmation" required autocomplete="new-password">
                            <div id="password-match" class="match-indicator"></div>
                        </div>
                    </div>

                    <button class="auth-submit" type="submit">{{ __('global.register') }}</button>
                </form>

                <div class="auth-link">
                    {{ __('global.already_have_account') }}
                    <a href="{{ route('login') }}">{{ __('global.login') }}</a>
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
            var confirm = document.getElementById('password-confirm');
            var strength = document.getElementById('password-strength');
            var match = document.getElementById('password-match');

            if (!toggle || !password || !confirm || !strength || !match) {
                return;
            }

            function evaluateStrength(value) {
                if (!value) {
                    strength.textContent = '';
                    strength.className = 'strength-indicator';
                    return;
                }

                var score = 0;
                if (value.length >= 8) score++;
                if (/[a-z]/.test(value)) score++;
                if (/[A-Z]/.test(value)) score++;
                if (/\d/.test(value)) score++;
                if (/[^A-Za-z0-9]/.test(value)) score++;

                if (score <= 2) {
                    strength.textContent = 'Kekuatan kata sandi: lemah';
                    strength.className = 'strength-indicator strength-weak';
                } else if (score <= 4) {
                    strength.textContent = 'Kekuatan kata sandi: sedang';
                    strength.className = 'strength-indicator strength-medium';
                } else {
                    strength.textContent = 'Kekuatan kata sandi: kuat';
                    strength.className = 'strength-indicator strength-strong';
                }
            }

            function evaluateMatch() {
                if (!confirm.value) {
                    match.textContent = '';
                    match.className = 'match-indicator';
                    return;
                }

                if (password.value === confirm.value) {
                    match.textContent = 'Konfirmasi kata sandi cocok';
                    match.className = 'match-indicator match-ok';
                } else {
                    match.textContent = 'Konfirmasi kata sandi belum cocok';
                    match.className = 'match-indicator match-bad';
                }
            }

            toggle.addEventListener('click', function() {
                var isText = password.type === 'text';
                var type = isText ? 'password' : 'text';
                password.type = type;
                confirm.type = type;
                toggle.textContent = isText ? '{{ __('global.show_password') }}' : 'Sembunyikan';
            });

            password.addEventListener('input', function() {
                evaluateStrength(password.value);
                evaluateMatch();
            });

            confirm.addEventListener('input', evaluateMatch);

            evaluateStrength(password.value);
            evaluateMatch();
        })();
    </script>
@endsection
