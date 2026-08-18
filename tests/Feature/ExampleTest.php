<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    // RefreshDatabase trước đây bị comment out nên test chạy trên database không
    // có bảng nào và luôn báo 500. Bật lên thì đây thành smoke test thật sự:
    // trang chủ phải render được trên một cài đặt mới, chưa có sản phẩm/banner nào.
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
