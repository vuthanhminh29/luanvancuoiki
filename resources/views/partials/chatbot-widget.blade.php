{{--
    Widget trợ lý tư vấn AI, nhúng ở layouts/app.blade.php nên có mặt trên mọi
    trang phía khách. Toàn bộ trạng thái nằm ở public/js/chatbot-widget.js.
--}}
@php
    // Gợi ý bấm nhanh: 4 câu khách hay hỏi nhất, mỗi câu dẫn tới một nhánh xử lý
    // khác nhau của chatbot (sản phẩm, tròng kính, dịch vụ tại cửa hàng, khuyến mãi).
    //
    // Các câu này phải hỏi thứ cửa hàng THẬT SỰ có. Bản đầu để "tròng chống ánh
    // sáng xanh" trong khi bảng lens_options không có loại đó — gợi ý sẵn mà bấm
    // vào lại nhận "mình chưa có thông tin" là ấn tượng đầu tiên tệ nhất.
    $chatbotQuickPrompts = [
        'Gọng kính nào hợp mặt tròn?',
        'Tròng kính giá bao nhiêu?',
        'Đặt lịch đo mắt thế nào?',
        'Đang có mã giảm giá nào không?',
    ];
@endphp

<div id="aoc-chat"
     class="aoc-chat"
     data-endpoint="{{ route('chatbot.chat') }}"
     data-history-limit="{{ config('chatbot.context.history_limit', 12) }}"
     data-greeting="Chào bạn! Mình là trợ lý tư vấn của {{ config('chatbot.shop_name') }}. Bạn đang tìm gọng kính, tròng kính hay cần đặt lịch đo mắt ạ?">

    <button type="button" class="aoc-chat__launcher" data-chat-open aria-label="Mở khung tư vấn">
        <i class="fas fa-comment-dots" aria-hidden="true"></i>
        <span>Tư vấn ngay</span>
    </button>

    <div class="aoc-chat__panel" data-chat-panel role="dialog" aria-label="Trợ lý tư vấn {{ config('chatbot.shop_name') }}">
        <div class="aoc-chat__header">
            <span class="aoc-chat__avatar"><i class="fas fa-glasses" aria-hidden="true"></i></span>
            <span class="aoc-chat__title">
                <strong>Trợ lý tư vấn</strong>
                <span>Giá và tồn kho lấy trực tiếp từ hệ thống</span>
            </span>
            <button type="button" class="aoc-chat__icon-btn" data-chat-reset aria-label="Xoá hội thoại" title="Xoá hội thoại">
                <i class="fas fa-rotate-left" aria-hidden="true"></i>
            </button>
            <button type="button" class="aoc-chat__icon-btn aoc-chat__close" data-chat-close aria-label="Đóng khung tư vấn">&times;</button>
        </div>

        <div class="aoc-chat__body" data-chat-body></div>

        <div class="aoc-chat__prompts" data-chat-prompts>
            @foreach ($chatbotQuickPrompts as $prompt)
                <button type="button" class="aoc-chat__prompt" data-chat-prompt>{{ $prompt }}</button>
            @endforeach
        </div>

        <div class="aoc-chat__error" data-chat-error style="display:none;"></div>

        <form class="aoc-chat__form" data-chat-form>
            <textarea class="aoc-chat__input"
                      data-chat-input
                      rows="1"
                      maxlength="500"
                      placeholder="Nhập câu hỏi của bạn..."
                      aria-label="Nội dung cần tư vấn"></textarea>
            <button type="submit" class="aoc-chat__send" data-chat-send aria-label="Gửi">
                <i class="fas fa-paper-plane" aria-hidden="true"></i>
            </button>
        </form>
    </div>
</div>
