@extends('layouts.master-blank')

@section('css')
    <link href="{{ URL::asset('css/cerah-portal.css') }}?v=1" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @include('layouts.user-navbar', ['active' => 'directory'])
    <div class="attendance-page">
        <div class="attendance-shell">
            <div class="attendance-topbar">
                <div class="attendance-greeting">
                    <span>Temukan sesama peserta</span>
                    <h1>Direktori Peserta</h1>
                </div>
            </div>
            @include('includes.directory-content')
        </div>
    </div>
@endsection
