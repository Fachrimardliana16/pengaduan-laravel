<?php

namespace App\Http\Requests;

use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Http;

class StorePengaduanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = [
            'Name' => ['required', 'string', 'max:100'],
            'PhoneNumber' => ['required', 'digits_between:10,15'],
            'CustomerNumber' => ['nullable', 'digits_between:1,12'],
            'Address' => ['required', 'string', 'max:250'],
            'SubDistricts' => ['required', 'integer', 'min:1'],
            'Villages' => ['required', 'integer', 'min:1'],
            'CompliantType' => ['required', 'integer', 'between:1,6'],
            'CompliantContent' => ['required', 'string', 'max:500'],
            'LatCoords' => ['nullable', 'numeric'],
            'LngCoords' => ['nullable', 'numeric'],
            'submission_token' => ['required', 'uuid'],
        ];

        if ($this->recaptchaConfigured() || app()->environment('production')) {
            $rules['g-recaptcha-response'] = ['required', $this->recaptchaRule()];
        } else {
            $rules['g-recaptcha-response'] = ['nullable'];
        }

        return $rules;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'g-recaptcha-response.required' => 'Harap selesaikan verifikasi reCAPTCHA.',
            'PhoneNumber.digits_between' => 'Nomor telepon harus 10–15 digit angka.',
            'CompliantContent.max' => 'Deskripsi pengaduan maksimal 500 karakter.',
            'submission_token.required' => 'Sesi formulir tidak valid. Silakan muat ulang halaman.',
            'submission_token.uuid' => 'Sesi formulir tidak valid. Silakan muat ulang halaman.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'Name' => 'Nama Pelapor',
            'PhoneNumber' => 'Nomor Telepon',
            'CustomerNumber' => 'Nomor Pelanggan',
            'Address' => 'Alamat',
            'SubDistricts' => 'Kecamatan',
            'Villages' => 'Desa',
            'CompliantType' => 'Jenis Pengaduan',
            'CompliantContent' => 'Deskripsi Pengaduan',
        ];
    }

    private function recaptchaConfigured(): bool
    {
        return filled(config('services.recaptcha.site_key')) && filled(config('services.recaptcha.secret'));
    }

    /** Validasi token reCAPTCHA ke server Google secara server-side. */
    private function recaptchaRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $secret = config('services.recaptcha.secret');
            if (empty($secret)) {
                if (app()->environment('production')) {
                    report(new Exception('RECAPTCHA_SECRET_KEY tidak dikonfigurasi di environment production.'));
                    $fail('Verifikasi reCAPTCHA tidak aktif. Hubungi administrator.');
                }

                return; // lewati validasi jika secret belum dikonfigurasi (dev/staging)
            }

            try {
                $response = Http::asForm()->timeout(5)->post(
                    'https://www.google.com/recaptcha/api/siteverify',
                    ['secret' => $secret, 'response' => $value, 'remoteip' => $this->ip()]
                );

                if (! $response->json('success')) {
                    $fail('Verifikasi reCAPTCHA gagal. Silakan coba lagi.');
                }
            } catch (\Throwable $e) {
                report($e);
                $fail('Gagal memverifikasi reCAPTCHA ke server Google. Silakan coba beberapa saat lagi.');
            }
        };
    }
}
