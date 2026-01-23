<x-mail::message>
# Chào {{ $account?->displayName() ?? ($account?->name ?? 'Bạn') }}

Quản trị viên vừa yêu cầu xác minh email cho tài khoản **{{ $targetEmail ?? ($account?->email ?? '') }}**.
Vui lòng nhấn nút bên dưới để hoàn tất. Liên kết sẽ hết hạn sau 72 giờ và chỉ dùng được một lần.

<x-mail::panel>
Nếu bạn không yêu cầu thao tác này, vui lòng bỏ qua email.
</x-mail::panel>

<x-mail::button :url="$verificationUrl">
Xác minh email ngay
</x-mail::button>

Trân trọng,<br>
{{ config('app.name') }}
</x-mail::message>
