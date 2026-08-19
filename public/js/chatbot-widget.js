/**
 * Trợ lý tư vấn AI — widget phía khách.
 *
 * Trang này là ứng dụng nhiều trang render từ server, mỗi lần khách bấm sang
 * sản phẩm khác là JS chạy lại từ đầu. Vì vậy hội thoại được giữ trong
 * sessionStorage: không có nó thì khách vừa hỏi xong, bấm vào link sản phẩm bot
 * gợi ý là mất sạch đoạn chat.
 */
(function () {
    'use strict';

    var root = document.getElementById('aoc-chat');

    if (!root) {
        return;
    }

    var STORAGE_KEY = 'aoc.chat.history.v1';
    var HISTORY_LIMIT = parseInt(root.dataset.historyLimit || '12', 10);
    var endpoint = root.dataset.endpoint;
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');

    var panel = root.querySelector('[data-chat-panel]');
    var body = root.querySelector('[data-chat-body]');
    var form = root.querySelector('[data-chat-form]');
    var input = root.querySelector('[data-chat-input]');
    var sendButton = root.querySelector('[data-chat-send]');
    var errorBox = root.querySelector('[data-chat-error]');
    var promptBar = root.querySelector('[data-chat-prompts]');

    var messages = loadHistory();
    var pending = false;

    function loadHistory() {
        try {
            var raw = window.sessionStorage.getItem(STORAGE_KEY);
            var parsed = raw ? JSON.parse(raw) : [];

            return Array.isArray(parsed) ? parsed.slice(-HISTORY_LIMIT) : [];
        } catch (error) {
            // sessionStorage bị chặn (chế độ riêng tư trên vài trình duyệt) thì
            // chat vẫn phải chạy, chỉ là không nhớ qua các trang.
            return [];
        }
    }

    function saveHistory() {
        try {
            window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify(messages.slice(-HISTORY_LIMIT)));
        } catch (error) {
            /* Bỏ qua: mất khả năng ghi nhớ không đáng để chặn cuộc trò chuyện. */
        }
    }

    /**
     * Dựng bong bóng tin nhắn.
     *
     * Nội dung luôn được gán bằng textContent chứ không phải innerHTML. Câu trả
     * lời đi qua model AI mà model lại đọc dữ liệu do người dùng nhập, nên coi
     * nó là HTML là mở thẳng đường cho stored XSS.
     */
    function renderMessage(role, content) {
        var bubble = document.createElement('div');
        bubble.className = 'aoc-chat__msg aoc-chat__msg--' + (role === 'user' ? 'user' : 'bot');
        appendLinkedText(bubble, content);
        body.appendChild(bubble);

        return bubble;
    }

    // Tách URL ra thành thẻ <a> thật, phần còn lại giữ nguyên dạng text.
    function appendLinkedText(container, text) {
        var pattern = /https?:\/\/[^\s<>"')]+/g;
        var lastIndex = 0;
        var match;

        while ((match = pattern.exec(text)) !== null) {
            // Câu trả lời viết theo văn nói nên URL thường đứng cuối câu:
            // "...đặt lịch tại http://.../dat-lich-do-mat." Dấu chấm đó là dấu
            // câu, không phải phần của địa chỉ — nuốt nó vào href là ra link 404.
            var url = match[0].replace(/[.,;:!?]+$/, '');

            if (match.index > lastIndex) {
                container.appendChild(document.createTextNode(text.slice(lastIndex, match.index)));
            }

            var link = document.createElement('a');
            link.href = url;
            link.textContent = url;
            link.rel = 'noopener';
            container.appendChild(link);

            lastIndex = match.index + url.length;
        }

        container.appendChild(document.createTextNode(text.slice(lastIndex)));
    }

    function renderProductCards(products) {
        if (!products || !products.length) {
            return;
        }

        var wrapper = document.createElement('div');
        wrapper.className = 'aoc-chat__cards';

        products.forEach(function (product) {
            var card = document.createElement('a');
            card.className = 'aoc-chat__card';
            card.href = product.url;

            if (product.image) {
                var thumb = document.createElement('img');
                thumb.className = 'aoc-chat__card-thumb';
                thumb.src = product.image;
                // alt rỗng có chủ đích: tên sản phẩm nằm ngay bên cạnh trong
                // cùng thẻ này, đọc lại lần nữa chỉ làm screen reader lặp.
                thumb.alt = '';
                thumb.loading = 'lazy';
                card.appendChild(thumb);
            }

            var info = document.createElement('div');
            info.className = 'aoc-chat__card-info';

            var name = document.createElement('div');
            name.className = 'aoc-chat__card-name';
            name.textContent = product.name;
            info.appendChild(name);

            var meta = document.createElement('div');
            meta.className = 'aoc-chat__card-meta';
            meta.textContent = product.in_stock ? 'Còn hàng' : 'Tạm hết hàng';
            info.appendChild(meta);

            var price = document.createElement('div');
            price.className = 'aoc-chat__card-price';
            price.textContent = formatPrice(product.price);

            card.appendChild(info);
            card.appendChild(price);
            wrapper.appendChild(card);
        });

        body.appendChild(wrapper);
    }

    function formatPrice(value) {
        return new Intl.NumberFormat('vi-VN').format(Math.round(Number(value) || 0)) + 'đ';
    }

    function showTyping() {
        var bubble = document.createElement('div');
        bubble.className = 'aoc-chat__msg aoc-chat__msg--bot';

        var dots = document.createElement('span');
        dots.className = 'aoc-chat__typing';
        dots.appendChild(document.createElement('span'));
        dots.appendChild(document.createElement('span'));
        dots.appendChild(document.createElement('span'));

        bubble.appendChild(dots);
        body.appendChild(bubble);
        scrollToBottom();

        return bubble;
    }

    function scrollToBottom() {
        body.scrollTop = body.scrollHeight;
    }

    // Gợi ý bấm nhanh chỉ có ích lúc khách chưa biết hỏi gì. Đã nhắn được một
    // câu rồi thì bốn nút đó thành rác chiếm chỗ, đẩy hội thoại lên cao và che
    // mất ô nhập. Ẩn hẳn từ tin nhắn đầu tiên, kể cả khi khách quay lại trang
    // khác và hội thoại được nạp lại từ sessionStorage.
    function updatePromptsVisibility() {
        if (promptBar) {
            promptBar.hidden = messages.length > 0;
        }
    }

    function setError(message) {
        errorBox.textContent = message || '';
        errorBox.style.display = message ? 'block' : 'none';
    }

    function setPending(state) {
        pending = state;
        sendButton.disabled = state;
        input.disabled = state;
    }

    function send(text) {
        var message = (text || '').trim();

        if (!message || pending) {
            return;
        }

        setError('');
        renderMessage('user', message);
        messages.push({ role: 'user', content: message });
        saveHistory();
        updatePromptsVisibility();
        scrollToBottom();

        input.value = '';
        input.style.height = 'auto';
        setPending(true);

        var typing = showTyping();

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfMeta ? csrfMeta.content : ''
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                message: message,
                // Không gửi kèm chính tin nhắn vừa đẩy vào mảng: server nhận nó
                // riêng ở trường message, gửi hai lần thì model đọc thành khách
                // hỏi lặp.
                history: messages.slice(0, -1).slice(-HISTORY_LIMIT)
            })
        })
            .then(function (response) {
                if (response.status === 429) {
                    throw new Error('Bạn nhắn hơi nhanh, chờ một chút rồi thử lại giúp mình nhé.');
                }

                if (!response.ok) {
                    throw new Error('Trợ lý đang bận, bạn thử lại sau ít phút nhé.');
                }

                return response.json();
            })
            .then(function (data) {
                typing.remove();
                renderMessage('assistant', data.reply);
                renderProductCards(data.products);
                messages.push({ role: 'assistant', content: data.reply });
                messages = messages.slice(-HISTORY_LIMIT);
                saveHistory();
                scrollToBottom();
            })
            .catch(function (error) {
                typing.remove();
                setError(error.message || 'Không gửi được tin nhắn, bạn kiểm tra kết nối mạng nhé.');
            })
            .then(function () {
                setPending(false);
                input.focus();
            });
    }

    // Xoá hội thoại về trạng thái ban đầu.
    //
    // Cần có nút này vì hội thoại được giữ trong sessionStorage để sống sót qua
    // các lần chuyển trang: không có đường xoá thì khách bị kẹt với đoạn chat cũ
    // đến hết phiên, kể cả khi đã đổi hẳn sang nhu cầu khác.
    function resetConversation() {
        messages = [];
        saveHistory();
        body.innerHTML = '';
        setError('');
        renderMessage('assistant', root.dataset.greeting || 'Chào bạn, mình có thể tư vấn gì cho bạn?');
        // Đoạn chat trống thì gợi ý bấm nhanh lại có ích, nên cho hiện lại.
        updatePromptsVisibility();
        scrollToBottom();
        input.focus();
    }

    function openPanel() {
        root.classList.add('is-open');
        // Cờ trên <body> để CSS đẩy nút "lên đầu trang" của layout né sang trái
        // khung chat. Đặt ở body vì nút đó nằm ngoài cây DOM của widget.
        document.body.classList.add('aoc-chat-open');
        scrollToBottom();
        input.focus();
    }

    function closePanel() {
        root.classList.remove('is-open');
        document.body.classList.remove('aoc-chat-open');
    }

    // Vẽ lại hội thoại cũ (nếu có) trước khi hiển thị lời chào.
    messages.forEach(function (entry) {
        renderMessage(entry.role, entry.content);
    });

    if (!messages.length) {
        renderMessage('assistant', root.dataset.greeting || 'Chào bạn, mình có thể tư vấn gì cho bạn?');
    }

    updatePromptsVisibility();

    root.querySelector('[data-chat-open]').addEventListener('click', openPanel);
    root.querySelector('[data-chat-close]').addEventListener('click', closePanel);

    root.querySelector('[data-chat-reset]').addEventListener('click', function () {
        // Chỉ hỏi lại khi có gì để mất. Hội thoại trống mà vẫn bật confirm thì
        // chỉ làm phiền.
        if (!messages.length || window.confirm('Xoá toàn bộ đoạn chat này?')) {
            resetConversation();
        }
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        send(input.value);
    });

    input.addEventListener('keydown', function (event) {
        // Enter gửi, Shift+Enter xuống dòng — thói quen quen thuộc của mọi khung
        // chat, khách không phải học lại.
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            send(input.value);
        }
    });

    input.addEventListener('input', function () {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 96) + 'px';
    });

    if (promptBar) {
        promptBar.addEventListener('click', function (event) {
            var button = event.target.closest('[data-chat-prompt]');

            if (button) {
                send(button.textContent.trim());
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && root.classList.contains('is-open')) {
            closePanel();
        }
    });

    // Panel dùng position: absolute trong khối fixed nên không tự chặn cuộn nền;
    // giữ nguyên hành vi cuộn trang phía sau là chủ ý, khách vừa đọc sản phẩm
    // vừa chat được.
    if (panel) {
        panel.setAttribute('aria-live', 'polite');
    }
})();
