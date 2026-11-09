@extends('layouts.master')

@section('breadcrumb')
    <div class="col-sm-6">
        <h4 class="page-title text-left">Direktori Peserta</h4>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/admin">{{ __('global.home') }}</a></li>
            <li class="breadcrumb-item active"><a href="{{ route('directory.index') }}">Direktori Peserta</a></li>
        </ol>
    </div>
@endsection

@section('content')
    @include('includes.directory-content')
@endsection
