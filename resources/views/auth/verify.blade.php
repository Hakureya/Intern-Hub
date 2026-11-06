@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Verifikasi Alamat Email Anda</div>

                    <div class="card-body">
                        @if (session('resent'))
                            <div class="alert alert-success" role="alert">
                                Tautan verifikasi baru telah dikirim ke alamat email Anda.
                            </div>
                        @endif

                        Sebelum melanjutkan, silakan cek email Anda untuk tautan verifikasi.
                        Jika Anda tidak menerima email, <a href="{{ route('verification.resend') }}">klik di sini untuk
                            meminta ulang</a>.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
