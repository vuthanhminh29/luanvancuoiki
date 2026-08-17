<?php

namespace Tests\Feature;

use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_coming_soon_store_location_cannot_be_booked(): void
    {
        $response = $this->from('/dat-lich-do-mat')->post('/dat-lich-do-mat', $this->validPayload([
            'store_location_id' => 'hcm-q7',
        ]));

        $response->assertRedirect('/dat-lich-do-mat');
        $response->assertSessionHasErrors('store_location_id');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_taken_slot_cannot_be_booked_again_for_same_store_location(): void
    {
        Appointment::create([
            'code' => 'AO-20260810-TEST',
            'service_code' => 'CO_BAN',
            'service_name' => 'Đo thị lực cơ bản',
            'price' => 0,
            'store_location_id' => 'hcm-q1',
            'store_location_name' => 'Atelier Optique Studio - Quận 1',
            'store_location_address' => '123 Nguyễn Trãi, P. Bến Thành, Q.1, TP.HCM',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '09:00',
            'customer_name' => 'Nguyen Van A',
            'customer_phone' => '0900000000',
            'status' => 'PENDING',
        ]);

        $response = $this->from('/dat-lich-do-mat')->post('/dat-lich-do-mat', $this->validPayload());

        $response->assertRedirect('/dat-lich-do-mat');
        $response->assertSessionHasErrors('appointment_time');
        $this->assertDatabaseCount('appointments', 1);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'service_code' => 'CO_BAN',
            'store_location_id' => 'hcm-q1',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '09:00',
            'customer_name' => 'Nguyen Van B',
            'customer_phone' => '0911111111',
            'customer_email' => 'customer@example.com',
            'note' => 'Can tu van them.',
        ], $overrides);
    }
}
