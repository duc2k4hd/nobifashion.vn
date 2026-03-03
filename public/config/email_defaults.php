<?php

/**
 * Cấu hình Email Mặc Định cho từng loại email
 * 
 * Cách sử dụng:
 * 1. Vào Admin → Email để xem danh sách email và lấy ID
 * 2. Điền ID vào các constant bên dưới
 * 3. Nếu để null, hệ thống sẽ dùng email mặc định (is_default = true)
 */

return [
    // Email gửi yêu cầu tư vấn sản phẩm (Phone Request)
    'phone_request' => 3, // Thay null bằng ID email, ví dụ: 1

    // Email trả lời liên hệ (Contact Reply)
    'contact_reply' => 2, // Thay null bằng ID email, ví dụ: 2

    // Email xác nhận đăng ký newsletter (Newsletter Verify)
    'newsletter_verify' => 3, // Thay null bằng ID email, ví dụ: 3

    // Email marketing newsletter (Newsletter Marketing)
    'newsletter_marketing' => 3, // Thay null bằng ID email, ví dụ: 4
    // Lưu ý: Email này có thể được chọn từ form campaign, nhưng nếu không chọn sẽ dùng giá trị này

    // Email xác nhận tài khoản (Account Verification)
    'account_verification' => 3, // Thay null bằng ID email, ví dụ: 5

    // Email đặt lại mật khẩu (Password Reset)
    'password_reset' => 3, // Thay null bằng ID email, ví dụ: 3

    // Email chào mừng newsletter (Newsletter Welcome)
    'newsletter_welcome' => 3, // Thay null bằng ID email, ví dụ: 6
];

