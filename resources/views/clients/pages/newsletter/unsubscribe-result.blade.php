@extends('clients.layouts.master')

@section('title', 'Hủy đăng ký Newsletter')

@section('content')
    <div style="max-width: 600px; margin: 60px auto; padding: 40px; background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); text-align: center;">
        @if($success)
            <div style="width: 80px; height: 80px; margin: 0 auto 24px; background: #dbeafe; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-envelope-open" style="font-size: 40px; color: #3b82f6;"></i>
            </div>
            <h1 style="font-size: 24px; color: #0f172a; margin: 0 0 16px;">Đã hủy đăng ký</h1>
            <p style="color: #64748b; font-size: 16px; margin: 0 0 32px;">{{ $message }}</p>
        @else
            <div style="width: 80px; height: 80px; margin: 0 auto 24px; background: #fee2e2; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-exclamation-triangle" style="font-size: 40px; color: #ef4444;"></i>
            </div>
            <h1 style="font-size: 24px; color: #0f172a; margin: 0 0 16px;">Không thể hủy đăng ký</h1>
            <p style="color: #64748b; font-size: 16px; margin: 0 0 32px;">{{ $message }}</p>
        @endif
        
        <a href="{{ route('client.home.index') }}" style="display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; border-radius: 8px; text-decoration: none; font-weight: 600;">
            Về trang chủ
        </a>
    </div>
@endsection

