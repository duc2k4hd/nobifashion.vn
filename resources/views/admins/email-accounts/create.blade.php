@extends('admins.layouts.master')

@section('title', 'Thêm email mới')
@section('page-title', '➕ Thêm email mới')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/email-icon.webp') }}" type="image/x-icon">
@endpush

@section('content')
    @include('admins.email-accounts.form', ['emailAccount' => new \App\Models\EmailAccount()])
@endsection

