<?php

namespace Tests\Unit;

use App\Support\HtmlSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Nội dung bài viết được in ra bằng {!! !!} nên đây là hàng phòng thủ duy nhất
 * chống stored XSS. Tài khoản STAFF viết được bài, nên nếu hàm này lọt payload thì
 * STAFF chèn được JS chạy trong trình duyệt của mọi khách, kể cả ADMIN đang đăng nhập.
 */
class HtmlSanitizerTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function xssPayloads(): array
    {
        return [
            'onerror trong img' => ['<img src=x onerror="alert(1)">', 'onerror'],
            'onclick trong div' => ['<div onclick="alert(1)">hi</div>', 'onclick'],
            'onload trong body' => ['<body onload="alert(1)">hi</body>', 'onload'],
            'href javascript' => ['<a href="javascript:alert(1)">x</a>', 'javascript:'],
            'href javascript hoa thuong lan lon' => ['<a href="  jaVaScRipt:alert(1)">x</a>', 'aScRipt:'],
            'href data html' => ['<a href="data:text/html;base64,PHNjcmlwdD4=">x</a>', 'data:text/html'],
            'the script' => ['<script>alert(1)</script><p>ok</p>', 'alert'],
            'the iframe' => ['<iframe src="//evil.com"></iframe><p>ok</p>', 'iframe'],
            'svg animate' => ['<svg><animate onbegin=alert(1)></svg>', 'onbegin'],
            'the style' => ['<style>body{background:url(javascript:alert(1))}</style>', 'javascript'],
            'the form' => ['<form action="//evil.com"><input name="a"></form>', 'evil.com'],
            'thuoc tinh style' => ['<p style="background:url(//evil.com)">x</p>', 'evil.com'],
        ];
    }

    #[DataProvider('xssPayloads')]
    public function test_payload_xss_bi_loai_bo(string $payload, string $mustNotContain): void
    {
        $clean = HtmlSanitizer::clean($payload);

        $this->assertStringNotContainsStringIgnoringCase(
            $mustNotContain,
            $clean,
            'Payload van con trong ket qua da lam sach: ' . $clean
        );
    }

    public function test_giu_lai_dinh_dang_hop_le(): void
    {
        $clean = HtmlSanitizer::clean('<p>Xin chào <strong>đậm</strong> và <em>nghiêng</em></p>');

        $this->assertStringContainsString('<strong>đậm</strong>', $clean);
        $this->assertStringContainsString('<em>nghiêng</em>', $clean);
        // Tiếng Việt có dấu phải qua được nguyên vẹn, không bị hỏng mã hóa.
        $this->assertStringContainsString('Xin chào', $clean);
    }

    public function test_giu_link_va_anh_an_toan(): void
    {
        $clean = HtmlSanitizer::clean('<a href="https://vidu.com">link</a><img src="/upload/a.jpg" alt="a">');

        $this->assertStringContainsString('href="https://vidu.com"', $clean);
        $this->assertStringContainsString('src="/upload/a.jpg"', $clean);
        // Link ra ngoài phải có rel chống tabnabbing.
        $this->assertStringContainsString('noopener', $clean);
    }

    public function test_bang_va_danh_sach_van_hien_thi(): void
    {
        $clean = HtmlSanitizer::clean('<table><tr><td>o</td></tr></table><ul><li>a</li></ul>');

        $this->assertStringContainsString('<td>o</td>', $clean);
        $this->assertStringContainsString('<li>a</li>', $clean);
    }

    public function test_chuoi_rong_tra_ve_rong(): void
    {
        $this->assertSame('', HtmlSanitizer::clean(''));
        $this->assertSame('', HtmlSanitizer::clean(null));
    }
}
