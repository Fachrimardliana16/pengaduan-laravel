<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private const API_URL = 'https://api.fonnte.com/send';

    private string $token;
    private string $groupTargets;

    public function __construct()
    {
        $this->token        = (string) config('services.fonnte.token', '');
        $this->groupTargets = (string) config('services.fonnte.group_targets', '');
    }

    /** Kirim notifikasi konfirmasi pengaduan ke nomor pelanggan. */
    public function sendToCustomer(string $phone, string $name, string $idPengaduan): void
    {
        $message = "Terimakasih, {$name}. Pengaduan anda sudah kami terima dengan nomor pengaduan {$idPengaduan}. "
            . 'Untuk mengetahui progress pengaduan anda silahkan masukan nomor pengaduan pada menu cari pengaduan '
            . 'di website https://pengaduan.pdampurbalingga.co.id '
            . "Terimakasih dan Mohon Maaf atas ketidaknyamanannya.\u{1F64F}";

        $this->dispatch($phone, $message, ['countryCode' => '62']);
    }

    /**
     * Kirim notifikasi pengaduan baru ke grup WhatsApp petugas.
     *
     * @param  array<string, mixed>  $data  Data pengaduan tervalidasi
     */
    public function sendToGroup(array $data, string $idPengaduan): void
    {
        $message = "*Hallo semua para awak Tukang Ledeng*.\n"
            . "Ada pengaduan baru dengan nomor pengaduan {$idPengaduan} dari {$data['Name']}. "
            . "Mohon segera ditindaklanjuti.\n"
            . "Update progress di https://e-office.pdampurbalingga.co.id/\n"
            . "Monitoring Progress di https://pengaduan.pdampurbalingga.co.id/\n\n"
            . "\u{1F4A7}PENGADUAN PELANGGAN\u{1F4A7}\n"
            . "Nama           : {$data['Name']}\n"
            . "No. Pelanggan  : " . ($data['CustomerNumber'] ?? '-') . "\n"
            . "Alamat         : {$data['Address']}\n"
            . "Nomor Telepon  : {$data['PhoneNumber']}\n"
            . "Deskripsi      : {$data['CompliantContent']}\n\n"
            . "Terimakasih atas kerjasamanya\u{1F64F}";

        if ($this->groupTargets !== '') {
            $this->dispatch($this->groupTargets, $message, ['delay' => '6']);
        }
    }

    /** Kirim satu pesan melalui Fonnte API; catat error ke log tanpa menghentikan alur utama. */
    private function dispatch(string $target, string $message, array $extra = []): void
    {
        if ($this->token === '') {
            Log::warning('WhatsAppService: FONNTE_TOKEN belum dikonfigurasi.');
            return;
        }

        try {
            Http::withHeaders(['Authorization' => $this->token])
                ->timeout(10)
                ->post(self::API_URL, array_merge(
                    ['target' => $target, 'message' => $message],
                    $extra
                ));
        } catch (\Throwable $e) {
            Log::error('WhatsAppService gagal mengirim pesan: ' . $e->getMessage());
        }
    }
}
