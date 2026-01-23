@extends('admins.layouts.master')

@section('title', 'Tạo banner mới')
@section('page-title', '🖼️ Tạo banner')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/banners-icon.png') }}" type="image/x-icon">
@endpush

@section('content')
    @include('admins.banners.form', ['banner' => $banner])
@endsection

