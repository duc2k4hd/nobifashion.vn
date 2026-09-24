<!-- Chat -->
<section>
    <!-- Cụm nút liên hệ góc phải -->
    <div class="nobifashion_chat">

        <!-- Nút cuộn lên đầu trang -->
        <div class="nobifashion_back_to_top">
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000" version="1.1"
                id="icon" width="30px" height="30px" viewBox="0 0 32 32" xml:space="preserve">
                <style type="text/css">
                    .st0 {
                        fill: none;
                    }
                </style>
                <title>up-to-top</title>
                <polygon points="16,14 6,24 7.4,25.4 16,16.8 24.6,25.4 26,24 " />
                <rect x="4" y="8" width="24" height="2" />
                <rect id="_Transparent_Rectangle_" class="st0" width="32" height="32" />
            </svg>
        </div>

        <!-- Zalo -->
        <a href="https://zalo.me/{{ $settings->contact_zalo ?? '' }}" target="_blank" class="nobifashion_chat_zalo">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                <path
                    d="M164.9 24.6c-7.7-18.6-28-28.5-47.4-23.2l-88 24C12.1 30.2 0 46 0 64C0 311.4 200.6 512 448 512c18 0 33.8-12.1 38.6-29.5l24-88c5.3-19.4-4.6-39.7-23.2-47.4l-96-40c-16.3-6.8-35.2-2.1-46.3 11.6L304.7 368C234.3 334.7 177.3 277.7 144 207.3L193.3 167c13.7-11.2 18.4-30 11.6-46.3l-40-96z" />
            </svg>
        </a>

        <!-- Gọi điện -->
        <a href="tel:{{ $settings->contact_phone ?? '' }}" class="nobifashion_chat_phone">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                <path
                    d="M256.6 8C116.5 8 8 110.3 8 248.6c0 72.3 29.7 134.8 78.1 177.9 8.4 7.5 6.6 11.9 8.1 58.2A19.9 19.9 0 0 0 122 502.3c52.9-23.3 53.6-25.1 62.6-22.7C337.9 521.8 504 423.7 504 248.6 504 110.3 396.6 8 256.6 8zm149.2 185.1l-73 115.6a37.4 37.4 0 0 1 -53.9 9.9l-58.1-43.5a15 15 0 0 0 -18 0l-78.4 59.4c-10.5 7.9-24.2-4.6-17.1-15.7l73-115.6a37.4 37.4 0 0 1 53.9-9.9l58.1 43.5a15 15 0 0 0 18 0l78.4-59.4c10.4-8 24.1 4.5 17.1 15.6z" />
            </svg>
        </a>

        <!-- Chat Trợ lý AI (Thay thế nút Facebook) -->
        <button type="button" id="nobiChatLauncherBtn" class="nobifashion_chat_ai" aria-label="Mở cửa sổ chat hỗ trợ Nobi Fashion">
            <!-- Icon chat mở -->
            <svg class="nobi-chat-icon-open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M12 2C6.477 2 2 6.142 2 11.25c0 2.578 1.157 4.908 3.033 6.559-.193 1.258-.783 2.85-1.85 4.103a.75.75 0 0 0 .755 1.238c2.46-.37 4.544-1.393 5.679-2.189.76.185 1.562.289 2.383.289 5.523 0 10-4.142 10-9.25S17.523 2 12 2zm0 15c-.714 0-1.408-.088-2.067-.253a.75.75 0 0 0-.585.087c-.89.593-2.392 1.34-4.14 1.705.65-.92 1.07-1.99 1.232-2.923a.75.75 0 0 0-.256-.667C4.62 13.568 3.5 11.758 3.5 11.25 3.5 7.245 7.306 3.5 12 3.5s8.5 3.745 8.5 7.75-3.806 7.75-8.5 7.75z"/>
            </svg>
            <!-- Icon đóng -->
            <svg class="nobi-chat-icon-close" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M18.3 5.71a1 1 0 0 0-1.41 0L12 10.59 7.11 5.7a1 1 0 0 0-1.41 1.42L10.59 12 5.7 16.89a1 1 0 1 0 1.41 1.41L12 13.41l4.89 4.89a1 1 0 0 0 1.41-1.41L13.41 12l4.89-4.89a1 1 0 0 0 0-1.4z"/>
            </svg>
            <span class="nobi-chat-online-badge" title="Tư vấn viên đang trực tuyến"></span>
        </button>
    </div>

    <!-- Cửa sổ Chat Popup (Bung mở từ góc trái) -->
    <div id="nobiChatPopup" class="nobi-chat-popup" role="dialog" aria-modal="true" aria-label="Cửa sổ trò chuyện CSKH Nobi Fashion">
        <!-- Header -->
        <div class="nobi-chat-header">
            <div class="nobi-chat-header-profile">
                <div class="nobi-chat-avatar-wrap">
                    <img width="100%" height="100%" src="{{asset('/clients/assets/img/business/'. $settings->site_favicon)}}" alt="Nobi Fashion">
                </div>
                <div class="nobi-chat-header-info">
                    <h4>Nobi Fashion</h4>
                    <div class="nobi-chat-header-status">
                        <span class="nobi-chat-status-dot"></span> Đang trực tuyến
                    </div>
                </div>
            </div>
            <div class="nobi-chat-header-actions">
                <button type="button" id="nobiChatBtnClear" class="nobi-chat-btn-action" title="Xóa lịch sử cuộc trò chuyện">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                    </svg>
                </button>
                <button type="button" id="nobiChatBtnClose" class="nobi-chat-btn-action" title="Thu nhỏ">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Body: Khung tin nhắn -->
        <div id="nobiChatBody" class="nobi-chat-body">
            <!-- Tin nhắn sẽ được nạp động từ LocalStorage hoặc API -->
        </div>

        <!-- Footer: Khung nhập tin nhắn -->
        <div class="nobi-chat-footer">
            <div class="nobi-chat-input-row">
                <input type="text" id="nobiChatInput" class="nobi-chat-input" placeholder="Hỏi Nobi Fashion bất cứ điều gì..." maxlength="1000" autocomplete="off">
                <button type="button" id="nobiChatBtnSend" class="nobi-chat-btn-send" title="Gửi câu hỏi" aria-label="Gửi">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </button>
            </div>
            <div class="nobi-chat-footer-brand">
                Nobi Fashion AI Assistant • Hỗ trợ 24/7
            </div>
        </div>
    </div>
</section>
