@extends('admins.layouts.master')

@section('title', 'Sửa email')
@section('page-title', '✏️ Sửa email')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/email-icon.webp') }}" type="image/x-icon">
@endpush

@section('content')
    @include('admins.email-accounts.form', ['emailAccount' => $emailAccount])
@endsection

