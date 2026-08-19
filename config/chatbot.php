<?php

return [
    // Tên hiển thị của cửa hàng trong lời chào và trong system prompt. Không lấy
    // từ APP_NAME vì biến đó đang là giá trị mặc định của Laravel.
    'shop_name' => env('CHATBOT_SHOP_NAME', 'Atelier Optique'),

    // KHÔNG đặt giá trị mặc định cho api_key. Key của nhà cung cấp AI là thứ duy
    // nhất chặn người khác tiêu tiền trên tài khoản của shop, nên nó chỉ được
    // phép nằm trong .env (đã gitignore). Thiếu key thì ChatCompletionAiService
    // ::isConfigured() trả về false và chatbot tự động rơi về chế độ trả lời
    // thẳng từ database, khách vẫn dùng được chứ không thấy lỗi 500.
    'api_key' => env('CHATBOT_API_KEY', ''),

    // Endpoint chuẩn OpenAI Chat Completions. Đổi base_url là dùng được cả
    // OpenAI, Groq, OpenRouter hay một server tự host tương thích, không phải
    // sửa code service.
    'base_url' => rtrim((string) env('CHATBOT_BASE_URL', 'https://api.openai.com/v1'), '/'),
    'model' => env('CHATBOT_MODEL', 'gpt-4o-mini'),

    // Windows/WAMP hay thiếu CA bundle nên cURL báo lỗi 60 khi gọi HTTPS.
    // Trỏ CHATBOT_CA_BUNDLE tới cacert.pem để giữ SSL verify đúng chuẩn.
    'ssl_verify' => filter_var(env('CHATBOT_SSL_VERIFY', true), FILTER_VALIDATE_BOOLEAN),
    'ca_bundle' => env('CHATBOT_CA_BUNDLE')
        ?: env('CURL_CA_BUNDLE')
        ?: ini_get('curl.cainfo')
        ?: ini_get('openssl.cafile')
        ?: null,

    // Nhiệt độ thấp: bài toán ở đây là đọc đúng giá và tồn kho trong ngữ cảnh,
    // không phải sáng tác. Để cao hơn thì model bắt đầu tự chế thông số sản phẩm.
    'temperature' => (float) env('CHATBOT_TEMPERATURE', 0.35),
    'max_tokens' => (int) env('CHATBOT_MAX_TOKENS', 800),

    // Timeout ngắn vì đây là request đồng bộ chặn ngay trước mặt khách. Quá thời
    // gian này thì trả lời bằng dữ liệu database còn hơn để khách chờ.
    'timeout' => (int) env('CHATBOT_TIMEOUT', 20),

    'context' => [
        // Cửa sổ hội thoại gửi kèm mỗi lượt. 12 tin đủ để hiểu câu hỏi nối tiếp
        // ("còn màu gì?", "cái đó giá bao nhiêu?") mà chưa tốn token đáng kể.
        'history_limit' => 12,
        // Số tin gần nhất được dùng để cộng điểm từ khóa khi truy xuất sản phẩm.
        'history_keyword_window' => 6,
        // Trần sản phẩm nạp vào ngữ cảnh RAG. Nhiều hơn chỉ làm loãng prompt.
        'max_products' => 8,
        'max_variants_per_product' => 6,
        'max_promotions' => 5,
        'max_lens_options' => 8,
    ],
];
