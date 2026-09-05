<?php

namespace App\Http\Requests;

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
        return [
            'Name'                 => ['required', 'string', 'max:100'],
            'PhoneNumber'          => ['required', 'digits_between:10,15'],
            'CustomerNumber'       => ['nullable', 'digits_between:1,12'],
            'Address'              => ['required', 'string', 'max:250'],
            'SubDistricts'         => ['required', 'integer', 'min:1'],
            'Villages'             => ['required', 'integer', 'min:1'],
            'CompliantType'        => ['required', 'integer', 'between:1,6'],
            'CompliantContent'     => ['required', 'string', 'max:500'],
            'LatCoords'            => ['nullable', 'numeric'],
            'LngCoords'            => ['nullable', 'numeric'],
            'g-recaptcha-response' => ['required', $this->recaptchaRule()],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'g-recaptcha-response.required' => 'Harap selesaikan verifikasi reCAPTCHA.',
            'PhoneNumber.digits_between'     => 'Nomor telepon harus 10–15 digit angka.',
            'CompliantContent.max'           => 'Deskripsi pengaduan maksimal 500 karakter.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'Name'             => 'Nama Pelapor',
            'PhoneNumber'      => 'Nomor Telepon',
            'CustomerNumber'   => 'Nomor Pelanggan',
            'Address'          => 'Alamat',
            'SubDistricts'     => 'Kecamatan',
            'Villages'         => 'Desa',
            'CompliantType'    => 'Jenis Pengaduan',
            'CompliantContent' => 'Deskripsi Pengaduan',
        ];
    }

    /** Validasi token reCAPTCHA ke server Google secara server-side. */
    private function recaptchaRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $secret = config('services.recaptcha.secret');
            if (empty($secret)) {
                return; // lewati validasi jika secret belum dikonfigurasi (dev/staging)
            }

            $response = Http::asForm()->timeout(5)->post(
                'https://www.google.com/recaptcha/api/siteverify',
                ['secret' => $secret, 'response' => $value, 'remoteip' => $this->ip()]
            );

            if (! $response->json('success')) {
                $fail('Verifikasi reCAPTCHA gagal. Silakan coba lagi.');
            }
        };
    }
}
