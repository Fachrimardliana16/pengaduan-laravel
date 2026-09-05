<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePengaduanRequest;
use App\Services\PdamSoapService;
use App\Services\WhatsAppService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class PengaduanController extends Controller
{
    public function __construct(
        private readonly PdamSoapService $soap,
        private readonly WhatsAppService $whatsapp,
    ) {}

    /** Tampilkan halaman utama form pengaduan. */
    public function index(): View
    {
        return view('pengaduan.index');
    }

    /**
     * Proses pengiriman pengaduan baru.
     * Alur: validasi → kirim ke SOAP → kirim notif WA → tampilkan sukses.
     */
    public function store(StorePengaduanRequest $request): View|RedirectResponse
    {
        $data = $request->validated();

        try {
            $idPengaduan = $this->soap->submitComplaint($data);
        } catch (Exception $e) {
            report($e);
            return back()
                ->withErrors(['pengaduan' => 'Terjadi kesalahan saat mengirim pengaduan. Silakan coba lagi.'])
                ->withInput();
        }

        if ($idPengaduan === null) {
            return back()
                ->withErrors(['pengaduan' => 'Pengaduan tidak dapat diproses saat ini. Silakan coba beberapa saat lagi.'])
                ->withInput();
        }

        // Kirim notifikasi WhatsApp; kegagalan tidak menghentikan alur utama
        $this->whatsapp->sendToCustomer($data['PhoneNumber'], $data['Name'], $idPengaduan);
        $this->whatsapp->sendToGroup($data, $idPengaduan);

        return view('pengaduan.success', compact('idPengaduan'));
    }

    /** Kembalikan daftar kecamatan; di-cache 6 jam karena data jarang berubah. */
    public function kecamatan(): JsonResponse
    {
        $data = Cache::remember('soap.kecamatan', now()->addHours(6), fn () => $this->soap->getSubdistricts());
        return response()->json(['data' => $data]);
    }

    /** Kembalikan daftar desa per kecamatan; di-cache per ID selama 6 jam. */
    public function desa(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);
        if ($id < 1) {
            return response()->json(['data' => []]);
        }
        $data = Cache::remember("soap.desa.{$id}", now()->addHours(6), fn () => $this->soap->getVillagesBySubdistrict($id));
        return response()->json(['data' => $data]);
    }

    /** Kembalikan status pengaduan; validasi format nomor sebelum meneruskan ke SOAP. */
    public function cek(Request $request): JsonResponse
    {
        $noPengaduan = trim((string) $request->query('NoPengaduan', ''));

        if ($noPengaduan === '' || strlen($noPengaduan) > 30 || ! preg_match('/^[A-Za-z0-9\-]+$/', $noPengaduan)) {
            return response()->json(['data' => null, 'error' => 'Format nomor pengaduan tidak valid.'], 422);
        }

        $data = $this->soap->checkComplaint($noPengaduan);
        return response()->json(['data' => $data]);
    }
}
