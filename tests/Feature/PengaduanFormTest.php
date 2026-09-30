<?php

namespace Tests\Feature;

use App\Services\PdamSoapService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class PengaduanFormTest extends TestCase
{
    public function test_form_shows_local_recaptcha_disabled_message_when_key_is_missing(): void
    {
        config([
            'services.recaptcha.site_key' => null,
            'services.recaptcha.secret' => null,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Verifikasi reCAPTCHA nonaktif di environment lokal.')
            ->assertDontSee('www.google.com/recaptcha/api.js');
    }

    public function test_duplicate_submission_token_does_not_call_soap_twice(): void
    {
        config([
            'services.recaptcha.site_key' => null,
            'services.recaptcha.secret' => null,
        ]);
        Cache::setDefaultDriver('array');
        Cache::flush();

        $soap = Mockery::mock(PdamSoapService::class);
        $soap->shouldReceive('submitComplaint')
            ->once()
            ->andReturn('TEST-001');
        $this->app->instance(PdamSoapService::class, $soap);

        $whatsapp = Mockery::mock(WhatsAppService::class);
        $whatsapp->shouldReceive('sendToCustomer')->once();
        $whatsapp->shouldReceive('sendToGroup')->once();
        $this->app->instance(WhatsAppService::class, $whatsapp);

        $payload = [
            'Name' => 'Budi',
            'PhoneNumber' => '081234567890',
            'CustomerNumber' => '12345',
            'Address' => 'Jl. Mawar No. 1',
            'SubDistricts' => 1,
            'Villages' => 1,
            'CompliantType' => 1,
            'CompliantContent' => 'Air tidak mengalir sejak pagi.',
            'LatCoords' => '-7.404609',
            'LngCoords' => '109.3747799',
            'submission_token' => (string) Str::uuid(),
        ];

        $this->post('/pengaduan', $payload)
            ->assertOk()
            ->assertSee('TEST-001');

        $this->post('/pengaduan', $payload)
            ->assertSessionHasErrors('pengaduan');
    }

    public function test_api_cek_returns_masked_pii(): void
    {
        $soap = Mockery::mock(PdamSoapService::class);
        $soap->shouldReceive('checkComplaint')
            ->with('04092026-1')
            ->once()
            ->andReturn([[
                'nama' => 'B*** S******',
                'alamat' => 'Jl. M*** ***gga',
                'ticket' => '04092026-1',
                'no_pelanggan' => '12***6',
                'pengaduan' => 'Pipa bocor',
                'status' => 'Diterima',
                'tanggal_masuk' => '2026-09-30',
                'tanggal_selesai' => '',
                'catatan' => '',
            ]]);
        $this->app->instance(PdamSoapService::class, $soap);

        $this->getJson('/api/cek-pengaduan?NoPengaduan=04092026-1')
            ->assertOk()
            ->assertJsonPath('data.0.nama', 'B*** S******')
            ->assertJsonPath('data.0.no_pelanggan', '12***6');
    }

    public function test_api_cek_rejects_invalid_ticket_format(): void
    {
        $this->getJson('/api/cek-pengaduan?NoPengaduan=invalid@ticket!')
            ->assertStatus(422)
            ->assertJsonPath('error', 'Format nomor pengaduan tidak valid.');
    }

    public function test_whatsapp_service_normalizes_local_phone_number(): void
    {
        $service = new WhatsAppService();
        $this->assertSame('628123456789', $service->normalizePhoneNumber('08123456789'));
        $this->assertSame('628123456789', $service->normalizePhoneNumber('+628123456789'));
        $this->assertSame('628123456789', $service->normalizePhoneNumber('628123456789'));
    }

    public function test_kecamatan_does_not_cache_empty_result(): void
    {
        Cache::flush();
        $soap = Mockery::mock(PdamSoapService::class);
        $soap->shouldReceive('getSubdistricts')->once()->andReturn([]);
        $this->app->instance(PdamSoapService::class, $soap);

        $this->getJson('/api/kecamatan')
            ->assertOk()
            ->assertJson(['data' => []]);

        $this->assertFalse(Cache::has('soap.kecamatan'));
    }
}
