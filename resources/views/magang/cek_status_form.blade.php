@extends('magang.layout.app')

@section('title', 'Portal Peserta - PLN SC')

@section('content')
    <main class="container mx-auto px-4 py-16 max-w-lg flex-grow flex flex-col justify-center">

        <div
            class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden text-center p-8 animate-fade-in-up">

            <div class="w-16 h-16 bg-pln-light rounded-full flex items-center justify-center mx-auto mb-6 text-2xl">
                🔍
            </div>

            <h1 class="text-2xl font-bold text-slate-800 mb-2">Portal Peserta</h1>
            <p class="text-slate-500 text-sm mb-8">Masukkan alamat email yang Anda gunakan saat pendaftaran untuk melihat
                progres terkini.</p>

            @if (session('error'))
                <div class="bg-red-50 text-red-600 p-3 rounded-lg text-sm mb-6 border border-red-200">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('magang.cek-status.result') }}" method="POST" class="space-y-4">
                @csrf
                <div class="text-left">
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1 ml-1">Alamat Email</label>
                    <input type="email" name="email" required
                        class="w-full px-5 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-pln-green focus:outline-none transition font-medium"
                        placeholder="nama@email.com">
                </div>

                <button type="submit"
                    class="w-full bg-pln-green hover:bg-pln-dark text-white font-bold py-3 rounded-xl shadow-lg shadow-pln-green/20 transition-all transform hover:-translate-y-1">
                    Buka Portal Peserta
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-100">
                <a href="{{ route('magang.form') }}" class="text-sm text-slate-400 hover:text-pln-green transition">
                    Belum mendaftar? <span class="font-bold underline">Isi formulir di sini</span>
                </a>
            </div>
        </div>

    </main>
@endsection
