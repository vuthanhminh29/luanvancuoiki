<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // Luong: Thuc thi truy van va lay ket qua tu CSDL.
        $response = $this->get('/');

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $response->assertStatus(200);
    }
}
