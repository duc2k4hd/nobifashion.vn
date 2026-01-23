<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phản hồi liên hệ</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #00a19b;
            color: #fff;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
            border-top: none;
        }
        .message-box {
            background: #fff;
            border-left: 4px solid #00a19b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            background: #f5f5f5;
            padding: 15px;
            text-align: center;
            border-radius: 0 0 8px 8px;
            border: 1px solid #ddd;
            border-top: none;
            font-size: 12px;
            color: #666;
        }
        .info {
            margin: 15px 0;
            padding: 10px;
            background: #e8f4f3;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Phản hồi liên hệ</h1>
    </div>
    
    <div class="content">
        <p>Xin chào <strong>{{ $contact->name ?? 'Quý khách' }}</strong>,</p>
        
        <p>Cảm ơn bạn đã liên hệ với chúng tôi. Dưới đây là phản hồi của chúng tôi:</p>
        
        <div class="message-box">
            {!! nl2br(e($replyMessage)) !!}
        </div>
        
        @if($contact->subject)
        <div class="info">
            <strong>Chủ đề liên hệ của bạn:</strong> {{ $contact->subject }}
        </div>
        @endif
        
        <p><strong>Thời gian phản hồi:</strong> {{ now()->format('d/m/Y H:i') }}</p>
        
        <p>Nếu bạn có thêm câu hỏi hoặc cần hỗ trợ thêm, vui lòng liên hệ lại với chúng tôi.</p>
    </div>
    
    <div class="footer">
        <p>Trân trọng,<br>
        <strong>{{ config('app.name') }}</strong></p>
        <p style="margin-top: 10px; color: #999;">
            Email này được gửi tự động, vui lòng không trả lời trực tiếp.
        </p>
    </div>
</body>
</html>

