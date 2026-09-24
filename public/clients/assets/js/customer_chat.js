/**
 * Nobi Fashion - Customer Chat Widget Controller (Database Driven)
 * Tích hợp lưu trữ trực tiếp Database & YDC Index Search API
 */

(function () {
    'use strict';

    const SESSION_STORAGE_KEY = 'nobifashion_chat_session_id';

    let sessionId = '';
    let isWaitingResponse = false;
    let csrfToken = '';
    let hasMoreMessages = false;
    let isLoadingOlder = false;
    let oldestMessageId = null;

    document.addEventListener('DOMContentLoaded', () => {
        const metaCsrf = document.querySelector('meta[name="csrf-token"]');
        if (metaCsrf) {
            csrfToken = metaCsrf.getAttribute('content');
        }

        // Khởi tạo hoặc lấy lại Session ID UUID duy nhất của khách
        sessionId = getOrCreateSessionId();
        initChatWidget();
    });

    /**
     * Sinh hoặc lấy UUID Session định danh khách hàng
     */
    function getOrCreateSessionId() {
        let sid = localStorage.getItem(SESSION_STORAGE_KEY);
        if (!sid || sid.length < 10) {
            sid = generateUUID();
            localStorage.setItem(SESSION_STORAGE_KEY, sid);
        }
        return sid;
    }

    function generateUUID() {
        if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
            return crypto.randomUUID();
        }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            const r = (Math.random() * 16) | 0;
            const v = c === 'x' ? r : (r & 0x3) | 0x8;
            return v.toString(16);
        });
    }

    function initChatWidget() {
        const launcherContainer = document.getElementById('nobiChatLauncherContainer');
        const launcherBtn = document.getElementById('nobiChatLauncherBtn');
        const welcomePill = document.getElementById('nobiChatWelcomePill');
        const chatPopup = document.getElementById('nobiChatPopup');
        const btnClose = document.getElementById('nobiChatBtnClose');
        const btnClear = document.getElementById('nobiChatBtnClear');
        const chatBody = document.getElementById('nobiChatBody');
        const chatInput = document.getElementById('nobiChatInput');
        const btnSend = document.getElementById('nobiChatBtnSend');

        if (!launcherBtn || !chatPopup) return;

        // 1. Sự kiện đóng/mở Popup
        let isFirstOpen = true;
        const toggleChat = () => {
            const isOpen = chatPopup.classList.toggle('is-open');
            if (launcherContainer) {
                launcherContainer.classList.toggle('is-active', isOpen);
            }
            if (launcherBtn) {
                launcherBtn.classList.toggle('is-active', isOpen);
            }

            if (welcomePill) {
                welcomePill.style.display = 'none';
            }

            if (isOpen) {
                if (isFirstOpen) {
                    isFirstOpen = false;
                    loadMessagesFromDatabase();
                }
                if (chatInput) {
                    setTimeout(() => chatInput.focus(), 300);
                }
                scrollChatToBottom();
            }
        };

        launcherBtn.addEventListener('click', toggleChat);
        if (welcomePill) {
            welcomePill.addEventListener('click', toggleChat);
        }
        if (btnClose) {
            btnClose.addEventListener('click', toggleChat);
        }

        const bottomAiBtn = document.getElementById('nobifashionBottomAiBtn');
        if (bottomAiBtn) {
            bottomAiBtn.addEventListener('click', (e) => {
                e.preventDefault();
                toggleChat();
            });
        }

        // 2. Xóa lịch sử cuộc trò chuyện (Tạo phiên mới trong DB)
        if (btnClear) {
            btnClear.addEventListener('click', () => {
                if (confirm('Bạn có muốn bắt đầu cuộc trò chuyện mới? Toàn bộ lịch sử cũ sẽ được đóng lại.')) {
                    clearChatConversation();
                }
            });
        }

        // 3. Cuộn lên trên để tải thêm tin nhắn cũ từ Database (Lazy Loading)
        if (chatBody) {
            chatBody.addEventListener('scroll', () => {
                if (chatBody.scrollTop === 0 && hasMoreMessages && !isLoadingOlder) {
                    loadOlderMessages();
                }
            });
        }

        // 4. Gửi tin nhắn
        const handleSendMessage = () => {
            if (!chatInput || isWaitingResponse) return;
            const text = chatInput.value.trim();
            if (!text) return;

            chatInput.value = '';
            sendMessage(text);
        };

        if (btnSend) {
            btnSend.addEventListener('click', handleSendMessage);
        }

        if (chatInput) {
            chatInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    handleSendMessage();
                }
            });
        }
    }

    /**
     * Tải danh sách tin nhắn từ Database theo Session ID
     */
    function loadMessagesFromDatabase() {
        showTypingIndicator();

        fetch(`/chat/messages?session_id=${encodeURIComponent(sessionId)}`, {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
        })
        .then(res => res.json())
        .then(res => {
            hideTypingIndicator();
            const chatBody = document.getElementById('nobiChatBody');
            if (!chatBody) return;

            if (res.success && res.messages && res.messages.length > 0) {
                hasMoreMessages = !!res.has_more;
                oldestMessageId = res.messages[0].id;
                chatBody.innerHTML = '';

                res.messages.forEach(msg => {
                    renderSingleMessage(msg, 'bottom');
                });
                scrollChatToBottom();
            } else {
                // Nếu chưa có tin nhắn nào trong DB, nạp lời chào mừng từ /chat/init
                fetchWelcomeAndSuggestions();
            }
        })
        .catch(err => {
            hideTypingIndicator();
            console.error('Lỗi nạp tin nhắn từ DB:', err);
            fetchWelcomeAndSuggestions();
        });
    }

    /**
     * Tải tin nhắn cũ hơn khi cuộn lên trên cùng (Lazy Loading)
     */
    function loadOlderMessages() {
        if (!oldestMessageId || isLoadingOlder) return;

        isLoadingOlder = true;
        const chatBody = document.getElementById('nobiChatBody');
        const oldScrollHeight = chatBody ? chatBody.scrollHeight : 0;

        fetch(`/chat/messages?session_id=${encodeURIComponent(sessionId)}&before_id=${oldestMessageId}`, {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
        })
        .then(res => res.json())
        .then(res => {
            isLoadingOlder = false;
            if (res.success && res.messages && res.messages.length > 0) {
                hasMoreMessages = !!res.has_more;
                oldestMessageId = res.messages[0].id;

                // Chèn các tin nhắn cũ lên đầu danh sách
                const tempFrag = document.createDocumentFragment();
                res.messages.forEach(msg => {
                    const el = createMessageElement(msg);
                    tempFrag.appendChild(el);
                });
                chatBody.insertBefore(tempFrag, chatBody.firstChild);

                // Giữ nguyên vị trí cuộn của người dùng
                chatBody.scrollTop = chatBody.scrollHeight - oldScrollHeight;
            } else {
                hasMoreMessages = false;
            }
        })
        .catch(err => {
            isLoadingOlder = false;
            console.error('Lỗi tải tin nhắn cũ:', err);
        });
    }

    /**
     * Tải lời chào & câu hỏi gợi ý khi phiên mới bắt đầu
     */
    function fetchWelcomeAndSuggestions() {
        showTypingIndicator();

        fetch('/chat/init', {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
        })
        .then(res => res.json())
        .then(res => {
            hideTypingIndicator();
            if (res.success) {
                const welcomeMsg = {
                    role: 'bot',
                    text: res.welcome_message || 'Xin chào! Em là trợ lý thời trang của Nobi Fashion. Em có thể giúp gì cho bạn hôm nay ạ?',
                    time: getCurrentTime(),
                    suggestions: res.quick_suggestions || [],
                };
                renderSingleMessage(welcomeMsg, 'bottom');
                scrollChatToBottom();
            }
        })
        .catch(err => {
            hideTypingIndicator();
            console.error('Lỗi lấy init data:', err);
            const defaultMsg = {
                role: 'bot',
                text: 'Xin chào! 👋 Em là trợ lý thời trang của Nobi Fashion. Bạn cần tư vấn thông tin gì về sản phẩm hay bài viết cứ nhắn cho em nhé!',
                time: getCurrentTime(),
                suggestions: [
                    'Top shop bán quần áo uy tín tại Hà Nội',
                    'Gợi ý phối đồ nam phong cách hot trend 2026',
                    'Tư vấn cách chọn size quần áo vừa vặn chuẩn',
                ],
            };
            renderSingleMessage(defaultMsg, 'bottom');
            scrollChatToBottom();
        });
    }

    /**
     * Gửi tin nhắn câu hỏi và lưu Database
     */
    function sendMessage(text) {
        if (!text || isWaitingResponse) return;

        // Render tạm tin nhắn User lên giao diện ngay lập tức
        const tempUserMsg = {
            role: 'user',
            text: text,
            time: getCurrentTime(),
        };
        renderSingleMessage(tempUserMsg, 'bottom');
        scrollChatToBottom();

        isWaitingResponse = true;
        const btnSend = document.getElementById('nobiChatBtnSend');
        if (btnSend) btnSend.disabled = true;
        showTypingIndicator();

        fetch('/chat/send', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                message: text,
                session_id: sessionId,
            }),
        })
        .then(res => res.json())
        .then(res => {
            hideTypingIndicator();
            isWaitingResponse = false;
            if (btnSend) btnSend.disabled = false;

            if (res.success && res.data && res.data.bot_message) {
                const botMsg = res.data.bot_message;
                renderSingleMessage({
                    id: botMsg.id,
                    role: 'bot',
                    text: botMsg.text,
                    time: botMsg.time,
                    articles: botMsg.articles || [],
                }, 'bottom');
                scrollChatToBottom();
            } else {
                renderSingleMessage({
                    role: 'bot',
                    text: 'Dạ, hệ thống đang bận một chút. Bạn vui lòng thử lại sau giây lát nhé!',
                    time: getCurrentTime(),
                }, 'bottom');
                scrollChatToBottom();
            }
        })
        .catch(err => {
            hideTypingIndicator();
            isWaitingResponse = false;
            if (btnSend) btnSend.disabled = false;
            console.error('Lỗi gửi tin nhắn:', err);
            renderSingleMessage({
                role: 'bot',
                text: 'Dạ, có lỗi kết nối mạng. Bạn vui lòng kiểm tra lại kết nối hoặc gọi Hotline để được hỗ trợ ngay ạ!',
                time: getCurrentTime(),
            }, 'bottom');
            scrollChatToBottom();
        });
    }

    /**
     * Đóng phiên cũ và tạo phiên mới
     */
    function clearChatConversation() {
        const chatBody = document.getElementById('nobiChatBody');
        if (chatBody) chatBody.innerHTML = '';

        fetch('/chat/clear', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ session_id: sessionId }),
        })
        .finally(() => {
            // Sinh session ID mới
            sessionId = generateUUID();
            localStorage.setItem(SESSION_STORAGE_KEY, sessionId);
            hasMoreMessages = false;
            oldestMessageId = null;
            fetchWelcomeAndSuggestions();
        });
    }

    /**
     * Định dạng văn bản tin nhắn chat: chống XSS an toàn và xử lý markdown (in đậm, in nghiêng, link)
     */
    function formatChatMessageText(rawText) {
        if (!rawText) return '';
        let safe = escapeHtml(rawText);

        // Chuyển markdown in đậm **text** thành <strong>text</strong>
        safe = safe.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');

        // Chuyển markdown in nghiêng *text* thành <em>text</em>
        safe = safe.replace(/(^|[^*])\*(?!\*)(.+?)(?<!\*)\*(?!\*)/g, '$1<em>$2</em>');

        // Chuyển markdown link [text](url) thành thẻ <a>
        safe = safe.replace(/\[([^\]]+)\]\(((?:https?:\/\/|\/)[^\s\)]+)\)/g, '<a href="$2" target="_blank" rel="noopener" class="nobi-chat-text-link">$1</a>');

        // Chuyển ký tự xuống dòng thành thẻ <br>
        safe = safe.replace(/\n/g, '<br>');

        return safe;
    }

    /**
     * Tạo phần tử DOM của một tin nhắn
     */
    function createMessageElement(msg) {
        const isUser = msg.role === 'user';
        const msgDiv = document.createElement('div');
        msgDiv.className = `nobi-chat-msg ${isUser ? 'nobi-chat-msg-user' : 'nobi-chat-msg-bot'}`;
        if (msg.id) {
            msgDiv.dataset.msgId = msg.id;
        }

        let avatarHtml = '';
        if (!isUser) {
            avatarHtml = `<div class="nobi-chat-msg-avatar"><img width="100%" height="100%" src="/clients/assets/img/business/setting-site_favicon-1758009788.png" alt="Nobi Fashion AI"></div>`;
        }

        const safeText = formatChatMessageText(msg.text);

        // Render Cards bài viết nếu có
        let articlesHtml = '';
        if (msg.articles && msg.articles.length > 0) {
            articlesHtml = '<div class="nobi-chat-articles-grid">';
            msg.articles.forEach(art => {
                const thumb = art.thumbnail_url || '/clients/assets/no-image.webp';
                const safeTitle = escapeHtml(art.title);
                const safeDesc = escapeHtml(art.description || '');
                const isProduct = (art.url || '').includes('/san-pham/');
                const actionText = isProduct ? 'Xem sản phẩm & Mua ngay →' : 'Xem chi tiết ngay →';
                articlesHtml += `
                    <a href="${art.url}" target="_blank" rel="noopener" class="nobi-chat-article-card">
                        <img src="${thumb}" alt="${safeTitle}" class="nobi-chat-article-thumb" onerror="this.src='/clients/assets/no-image.webp'">
                        <div class="nobi-chat-article-info">
                            <div class="nobi-chat-article-title">${safeTitle}</div>
                            ${safeDesc ? `<div class="nobi-chat-article-desc">${safeDesc}</div>` : ''}
                            <span class="nobi-chat-article-link">${actionText}</span>
                        </div>
                    </a>
                `;
            });
            articlesHtml += '</div>';
        }

        // Render Chips câu hỏi nhanh nếu có
        let suggestionsHtml = '';
        if (msg.suggestions && msg.suggestions.length > 0) {
            suggestionsHtml = '<div class="nobi-chat-quick-suggestions">';
            msg.suggestions.forEach(sug => {
                const safeSug = escapeHtml(sug);
                suggestionsHtml += `<button type="button" class="nobi-chat-chip" data-query="${safeSug}">💬 ${safeSug}</button>`;
            });
            suggestionsHtml += '</div>';
        }

        msgDiv.innerHTML = `
            ${avatarHtml}
            <div class="nobi-chat-msg-content">
                <div class="nobi-chat-bubble">
                    ${safeText}
                    ${articlesHtml}
                    ${suggestionsHtml}
                </div>
                <div class="nobi-chat-msg-time">${msg.time || ''}</div>
            </div>
        `;

        msgDiv.querySelectorAll('.nobi-chat-chip').forEach(btn => {
            btn.addEventListener('click', function () {
                const query = this.dataset.query;
                if (query) {
                    sendMessage(query);
                }
            });
        });

        return msgDiv;
    }

    function renderSingleMessage(msg, position = 'bottom') {
        const chatBody = document.getElementById('nobiChatBody');
        if (!chatBody) return;

        const el = createMessageElement(msg);
        if (position === 'top') {
            chatBody.insertBefore(el, chatBody.firstChild);
        } else {
            chatBody.appendChild(el);
        }
    }

    function showTypingIndicator() {
        hideTypingIndicator();
        const chatBody = document.getElementById('nobiChatBody');
        if (!chatBody) return;

        const typingDiv = document.createElement('div');
        typingDiv.id = 'nobiChatTypingIndicator';
        typingDiv.className = 'nobi-chat-msg nobi-chat-msg-bot';
        typingDiv.innerHTML = `
            <div class="nobi-chat-msg-avatar"><img width="100%" height="100%" src="/clients/assets/img/business/setting-site_favicon-1758009788.png" alt="Nobi Fashion AI"></div>
            <div class="nobi-chat-msg-content">
                <div class="nobi-chat-typing">
                    <span></span><span></span><span></span>
                </div>
            </div>
        `;
        chatBody.appendChild(typingDiv);
        scrollChatToBottom();
    }

    function hideTypingIndicator() {
        const typing = document.getElementById('nobiChatTypingIndicator');
        if (typing) {
            typing.remove();
        }
    }

    function scrollChatToBottom() {
        const chatBody = document.getElementById('nobiChatBody');
        if (chatBody) {
            setTimeout(() => {
                chatBody.scrollTop = chatBody.scrollHeight;
            }, 50);
        }
    }

    function getCurrentTime() {
        const now = new Date();
        return `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
    }

    function escapeHtml(string) {
        if (!string) return '';
        const entityMap = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
            '/': '&#x2F;'
        };
        return String(string).replace(/[&<>"'/]/g, s => entityMap[s]);
    }
})();
