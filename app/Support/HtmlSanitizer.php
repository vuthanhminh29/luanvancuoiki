<?php

declare(strict_types=1);

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Làm sạch HTML do người biên tập nhập (CKEditor) trước khi in ra bằng {!! !!}.
 *
 * Lý do không dùng strip_tags(): strip_tags chỉ bỏ THẺ, giữ nguyên THUỘC TÍNH.
 * Nghĩa là <img src=x onerror="..."> hay <a href="javascript:..."> vẫn lọt qua,
 * tạo lỗ hổng stored XSS - tài khoản STAFF viết bài có thể chèn JS chạy trong
 * trình duyệt của mọi khách, kể cả ADMIN đang đăng nhập.
 *
 * Ở đây parse thành DOM rồi duyệt từng node: thẻ không nằm trong allowlist bị
 * gỡ bỏ, thuộc tính không nằm trong allowlist của chính thẻ đó bị xóa, và
 * href/src bắt buộc phải dùng scheme an toàn.
 */
class HtmlSanitizer
{
    /**
     * Thẻ được giữ lại => danh sách thuộc tính được giữ lại của thẻ đó.
     *
     * @var array<string, list<string>>
     */
    private const ALLOWED_TAGS = [
        'p' => [],
        'br' => [],
        'strong' => [],
        'b' => [],
        'em' => [],
        'i' => [],
        'u' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'h1' => [],
        'h2' => [],
        'h3' => [],
        'h4' => [],
        'h5' => [],
        'h6' => [],
        'a' => ['href', 'title'],
        'img' => ['src', 'alt', 'title'],
        'blockquote' => [],
        'table' => [],
        'thead' => [],
        'tbody' => [],
        'tr' => [],
        'th' => [],
        'td' => [],
        'span' => [],
        'div' => [],
        'figure' => [],
        'figcaption' => [],
    ];

    /**
     * Thẻ bị xóa cả nội dung bên trong, không chỉ xóa thẻ.
     *
     * @var list<string>
     */
    private const DROP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'svg', 'math'];

    /**
     * Scheme được phép xuất hiện trong href/src.
     *
     * @var list<string>
     */
    private const ALLOWED_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    /**
     * Làm sạch nội dung bài viết/mô tả trước khi render.
     */
    public static function clean(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');

        // CKEditor trả về fragment chứ không phải document hoàn chỉnh, và
        // libxml mặc định đọc theo ISO-8859-1 nên phải ép UTF-8 bằng meta charset.
        $previousErrorState = libxml_use_internal_errors(true);

        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"?><div id="__sanitizer_root__">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorState);

        // Không parse được thì coi như nội dung không đáng tin: trả về text thuần.
        if (! $loaded) {
            return '<p>' . e(strip_tags($html)) . '</p>';
        }

        $xpath = new DOMXPath($document);

        // Bỏ toàn bộ comment, vì comment có điều kiện của IE từng là vector XSS.
        foreach (iterator_to_array($xpath->query('//comment()') ?: []) as $comment) {
            $comment->parentNode?->removeChild($comment);
        }

        // Xóa hẳn các thẻ nguy hiểm kèm nội dung bên trong.
        foreach (self::DROP_WITH_CONTENT as $tag) {
            foreach (iterator_to_array($document->getElementsByTagName($tag)) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        // iterator_to_array() để chụp danh sách trước khi sửa cây DOM,
        // nếu duyệt trực tiếp NodeList đang thay đổi sẽ bỏ sót node.
        foreach (iterator_to_array($xpath->query('//*') ?: []) as $element) {
            if (! $element instanceof DOMElement) {
                continue;
            }

            self::cleanElement($element);
        }

        $root = $document->getElementById('__sanitizer_root__');

        if (! $root) {
            return '';
        }

        $result = '';
        foreach ($root->childNodes as $child) {
            $result .= $document->saveHTML($child);
        }

        return trim($result);
    }

    /**
     * Gỡ thẻ không hợp lệ và lọc thuộc tính của thẻ hợp lệ.
     */
    private static function cleanElement(DOMElement $element): void
    {
        // Node đã bị gỡ khỏi cây ở vòng lặp trước thì bỏ qua.
        if (! $element->parentNode) {
            return;
        }

        $tag = strtolower($element->tagName);

        if ($tag === 'div' && $element->getAttribute('id') === '__sanitizer_root__') {
            return;
        }

        // Thẻ lạ: giữ lại nội dung text bên trong, chỉ bỏ chính cái thẻ đó.
        if (! array_key_exists($tag, self::ALLOWED_TAGS)) {
            self::unwrap($element);

            return;
        }

        $allowedAttributes = self::ALLOWED_TAGS[$tag];

        // Duyệt ngược vì removeAttributeNode() làm ngắn danh sách đang duyệt.
        for ($i = $element->attributes->length - 1; $i >= 0; $i--) {
            $attribute = $element->attributes->item($i);

            if ($attribute === null) {
                continue;
            }

            $name = strtolower($attribute->nodeName);

            // Mọi thuộc tính ngoài allowlist đều bị xóa, nên on* (onerror,
            // onclick...) và style không cần liệt kê riêng.
            if (! in_array($name, $allowedAttributes, true)) {
                $element->removeAttribute($attribute->nodeName);

                continue;
            }

            if (($name === 'href' || $name === 'src') && ! self::isSafeUrl($attribute->nodeValue)) {
                $element->removeAttribute($attribute->nodeName);
            }
        }

        // Link ra ngoài mở tab mới thì phải chặn tabnabbing.
        if ($tag === 'a' && $element->hasAttribute('href')) {
            $element->setAttribute('rel', 'noopener noreferrer nofollow');
        }
    }

    /**
     * Thay một thẻ bằng chính các node con của nó.
     */
    private static function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;

        if (! $parent) {
            return;
        }

        while ($element->firstChild instanceof DOMNode) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    /**
     * Chỉ cho phép URL tương đối hoặc scheme nằm trong allowlist.
     */
    private static function isSafeUrl(?string $url): bool
    {
        $url = trim((string) $url);

        if ($url === '') {
            return false;
        }

        // Ký tự điều khiển hay dùng để né bộ lọc, ví dụ "java\0script:".
        if (preg_match('/[\x00-\x1F\x7F]/', $url) === 1) {
            return false;
        }

        // URL tương đối (/anh.jpg, anh.jpg, #muc-1) không có scheme nên an toàn.
        if (! preg_match('/^([a-z][a-z0-9+.\-]*):/i', $url, $matches)) {
            return true;
        }

        return in_array(strtolower($matches[1]), self::ALLOWED_SCHEMES, true);
    }
}
