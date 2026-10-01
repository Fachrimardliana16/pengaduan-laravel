<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private const API_URL = 'https://api.fonnte.com/send';

    /** Kirim notifikasi konfirmasi pengaduan ke nomor pelanggan. */
    public function sendToCustomer(string $phone, string $name, string $idPengaduan): void
    {
        $normalizedPhone = $this->normalizePhoneNumber($phone);
        if ($normalizedPhone === '' || strlen($normalizedPhone) < 10) {
            Log::warning('WhatsAppService: nomor pelanggan tidak valid, pengiriman dibatalkan.', [
                'target' => $this->maskTarget($normalizedPhone),
            ]);

            return;
        }

        $message = "Terimakasih, {$name}. Pengaduan anda sudah kami terima dengan nomor pengaduan {$idPengaduan}. "
            .'Untuk mengetahui progress pengaduan anda silahkan masukan nomor pengaduan pada menu cari pengaduan '
            .'di website https://pengaduan.pdampurbalingga.co.id '
            ."Terimakasih dan Mohon Maaf atas ketidaknyamanannya.\u{1F64F}";

        $this->dispatch($normalizedPhone, $message, ['countryCode' => '62']);
    }

    /**
     * Kirim notifikasi pengaduan baru ke grup WhatsApp petugas.
     *
     * @param  array<string, mixed>  $data  Data pengaduan tervalidasi
     */
    public function sendToGroup(array $data, string $idPengaduan): void
    {
        $groupTargets = $this->parseGroupTargets((string) config('services.fonnte.group_targets', ''));
        if ($groupTargets === []) {
            return;
        }

        $message = "*Hallo semua para awak Tukang Ledeng*.\n"
            ."Ada pengaduan baru dengan nomor pengaduan {$idPengaduan} dari {$data['Name']}. "
            ."Mohon segera ditindaklanjuti.\n"
            ."Update progress di https://e-office.pdampurbalingga.co.id/\n"
            ."Monitoring Progress di https://pengaduan.pdampurbalingga.co.id/\n\n"
            ."\u{1F4A7}PENGADUAN PELANGGAN\u{1F4A7}\n"
            ."Nama           : {$data['Name']}\n"
            .'No. Pelanggan  : '.($data['CustomerNumber'] ?? '-')."\n"
            ."Alamat         : {$data['Address']}\n"
            ."Nomor Telepon  : {$data['PhoneNumber']}\n"
            ."Deskripsi      : {$data['CompliantContent']}\n\n"
            ."Terimakasih atas kerjasamanya\u{1F64F}";

        foreach ($groupTargets as $groupTarget) {
            $this->dispatch($groupTarget, $message, ['delay' => '6']);
        }
    }

    /** Normalisasi nomor telepon lokal/internasional ke format standar Fonnte (62xxx). */
    public function normalizePhoneNumber(string $phone): string
    {
        $clean = preg_replace('/[^\d]/', '', $phone) ?: '';

        if (str_starts_with($clean, '0')) {
            return '62'.substr($clean, 1);
        }

        if (str_starts_with($clean, '62')) {
            return $clean;
        }

        return $clean;
    }

    /** Kirim satu pesan melalui Fonnte API; catat error ke log tanpa menghentikan alur utama. */
    private function dispatch(string $target, string $message, array $extra = []): void
    {
        $token = (string) config('services.fonnte.token', '');
        if ($token === '') {
            Log::warning('WhatsAppService: FONNTE_TOKEN belum dikonfigurasi.');

            return;
        }

        try {
            $response = Http::withHeaders(['Authorization' => $token])
                ->asForm()
                ->timeout(10)
                ->post(self::API_URL, array_merge(
                    ['target' => $target, 'message' => $message],
                    $extra
                ));

            if (! $response->successful()) {
                Log::warning('WhatsAppService: Fonnte API mengembalikan status non-2xx', [
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                    'target' => $this->maskTarget($target),
                ]);

                return;
            }

            if ($response->json('status') !== true) {
                Log::warning('WhatsAppService: Fonnte API merespons gagal.', [
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                    'target' => $this->maskTarget($target),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('WhatsAppService gagal mengirim pesan', [
                'reason' => $e->getMessage(),
                'target' => $this->maskTarget($target),
            ]);
        }
    }

    /** Pecah target grup dari env agar tiap grup dapat dikirim terpisah. */
    private function parseGroupTargets(string $targets): array
    {
        $parts = preg_split('/[\s,;]+/', $targets) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts), static fn (string $item): bool => $item !== ''));

        return array_values(array_unique($parts));
    }

    /** Samarkan target untuk logging tanpa membocorkan data penuh. */
    private function maskTarget(string $target): string
    {
        if ($target === '') {
            return 'empty';
        }

        return substr($target, 0, 4).'****';
    }
}
