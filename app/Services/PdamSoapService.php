<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use Exception;
use Illuminate\Support\Facades\Log;
use SoapClient;
use SoapFault;

class PdamSoapService
{
    private string $wsdl;

    public function __construct()
    {
        // URL SOAP dibaca dari config/services.php → env('PDAM_SOAP_WSDL')
        $this->wsdl = (string) config('services.pdam.wsdl');
    }

    /** Membangun SoapClient baru untuk setiap request agar tidak ada state sisa. */
    private function client(): SoapClient
    {
        return new SoapClient($this->wsdl, [
            'trace' => true,
            'exceptions' => true,
            'connection_timeout' => 10,
        ]);
    }

    /** Kembalikan daftar kecamatan dari SOAP backend. */
    public function getSubdistricts(): array
    {
        try {
            $client = $this->client();
            $client->__call('subDistricts', [['Id' => 1]]);

            return $this->parseComboLookup($client->__getLastResponse());
        } catch (SoapFault) {
            return [];
        }
    }

    /** Kembalikan daftar desa berdasarkan ID kecamatan. */
    public function getVillagesBySubdistrict(int $subdistrictId): array
    {
        try {
            $client = $this->client();
            $client->__call('VillageBySubdistrict', [[
                'FilterName' => 'Subdistrictid',
                'FilterValue' => $subdistrictId,
            ]]);

            return $this->parseComboLookup($client->__getLastResponse());
        } catch (SoapFault) {
            return [];
        }
    }

    /**
     * Kirim pengaduan ke SOAP backend.
     *
     * @param  array<string, mixed>  $payload  Data tervalidasi dari Form Request
     * @return string|null ID pengaduan yang diterima, atau null jika gagal
     *
     * @throws Exception Jika SOAP server mengembalikan fault
     */
    public function submitComplaint(array $payload): ?string
    {
        $today = now()->toDateString();

        $ViewModel = [
            'Id' => 0,
            'DateCompliant' => $today,
            'Number' => '0',
            'ComplianerName' => $payload['Name'],
            'ComplianerAddress' => $payload['Address'],
            'PhoneNumber' => $payload['PhoneNumber'],
            'Email' => '-',
            'PDAMCustNumber' => $payload['CustomerNumber'] ?? '-',
            'LatCoords' => $payload['LatCoords'] ?? '0',
            'LngCoords' => $payload['LngCoords'] ?? '0',
            'CompliantContent' => $payload['CompliantContent'],
            'Photo' => '-',
            'CompliantStatusId' => 1,
            'CompliantStatusName' => 'Inputed',
            'SubDistrictsId' => (int) $payload['SubDistricts'],
            'VillagesId' => (int) $payload['Villages'],
            'InputedDate' => $today,
            'isDeleted' => 1,
            'UpdatedDate' => $today,
            'SubDistrictName' => '1',
            'VillagesName' => '1',
            'InputedBy' => 'web',
            'CompliantTypeId' => (int) $payload['CompliantType'],
            'CompliantTypeName' => '1',
        ];

        try {
            $client = $this->client();
            $client->__call('CustomerCompliant', [['ViewModel' => $ViewModel]]);
            $dom = $this->parseDom($client->__getLastResponse());
            $entries = $dom->getElementsByTagName('CustomerCompliantViewModel');

            foreach ($entries as $entry) {
                $id = $entry->childNodes->item(2)?->nodeValue;
                if ($id !== null && $id !== '') {
                    return $id;
                }
            }

            return null;
        } catch (SoapFault $e) {
            throw new Exception('SOAP CustomerCompliant error: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Cek status dan log pengaduan berdasarkan nomor tiket.
     *
     * @return array<int, array<string, string>>
     */
    public function checkComplaint(string $noPengaduan): array
    {
        try {
            $client = $this->client();
            $client->__call('CekCompliantLoglist', [['NoPengaduan' => $noPengaduan]]);
            $results = $this->parseCompliantLogList($client->__getLastResponse());
            if ($results !== []) {
                return $results;
            }

            // Fallback: tiket baru (mis. status "Dilaporkan") kadang belum punya entri log.
            // Ambil data dari endpoint ringkasan agar nomor pengaduan tetap bisa ditemukan.
            $client = $this->client();
            $client->__call('CekPengaduan', [['NoPengaduan' => $noPengaduan]]);

            return $this->parseComplaintSummary($client->__getLastResponse());
        } catch (SoapFault) {
            return [];
        }
    }

    /** Parse daftar log pengaduan dari SOAP CekCompliantLoglist. */
    private function parseCompliantLogList(string $xml): array
    {
        $dom = $this->parseDom($xml);
        $entries = $dom->getElementsByTagName('CompliantLogViewModel');

        $results = [];
        foreach ($entries as $entry) {
            if (! $entry instanceof DOMElement) {
                continue;
            }

            if (config('app.debug')) {
                $nodes = [];
                foreach ($entry->childNodes as $i => $node) {
                    $nodes[$i] = $node->nodeName.' = '.substr((string) $node->nodeValue, 0, 60);
                }
                Log::debug('CompliantLogViewModel child nodes', $nodes);
            }

            $statusName = trim($this->getChildNodeValue($entry, 'CompliantStatusName'));
            $statusId = trim($this->getChildNodeValue($entry, 'CompliantStatusId'));
            $processedRaw = trim($this->getChildNodeValue($entry, 'ProcessedDate'));

            $results[] = [
                'nama' => $this->getChildNodeValue($entry, 'CustomerCompliantName'),
                'alamat' => $this->getChildNodeValue($entry, 'CustomerCompliantAddress'),
                'ticket' => $this->getChildNodeValue($entry, 'CustomerCompliantNumber'),
                'no_pelanggan' => $this->getChildNodeValue($entry, 'CustomerCompliantNolangg'),
                'pengaduan' => $this->getChildNodeValue($entry, 'CustomerCompliantContent'),
                'status' => $this->resolveStatusLabel($statusName, $statusId),
                'tanggal_masuk' => $this->getChildNodeValue($entry, 'InputedDate'),
                'tanggal_selesai' => str_starts_with($processedRaw, '0001') ? '' : $processedRaw,
                'catatan' => trim($this->getChildNodeValue($entry, 'FastMessageReply')),
            ];
        }

        return $results;
    }

    /** Parse ringkasan pengaduan dari SOAP CekPengaduan (fallback). */
    private function parseComplaintSummary(string $xml): array
    {
        $dom = $this->parseDom($xml);
        $entries = $dom->getElementsByTagName('CustomerCompliantViewModel');
        if ($entries->length === 0) {
            $entries = $dom->getElementsByTagName('CekPengaduanResult');
        }

        $results = [];
        foreach ($entries as $entry) {
            if (! $entry instanceof DOMElement) {
                continue;
            }

            $statusName = trim($this->getChildNodeValue($entry, 'CompliantStatusName'));
            $statusId = trim($this->getChildNodeValue($entry, 'CompliantStatusId'));
            $processedRaw = trim($this->getChildNodeValue($entry, 'ProcessedDate'));

            $tanggalMasuk = $this->getChildNodeValue($entry, 'InputedDate');
            if ($tanggalMasuk === '' || str_starts_with($tanggalMasuk, '0001')) {
                $tanggalMasuk = $this->getChildNodeValue($entry, 'DateCompliant');
            }

            $results[] = [
                'nama' => $this->getChildNodeValue($entry, 'ComplianerName'),
                'alamat' => $this->getChildNodeValue($entry, 'ComplianerAddress'),
                'ticket' => $this->getChildNodeValue($entry, 'Number'),
                'no_pelanggan' => $this->getChildNodeValue($entry, 'PDAMCustNumber'),
                'pengaduan' => $this->getChildNodeValue($entry, 'CompliantContent'),
                'status' => $this->resolveStatusLabel($statusName, $statusId),
                'tanggal_masuk' => $tanggalMasuk,
                'tanggal_selesai' => str_starts_with($processedRaw, '0001') ? '' : $processedRaw,
                'catatan' => trim($this->getChildNodeValue($entry, 'FastMessageReply')),
            ];
        }

        return $results;
    }

    /** Normalisasi status SOAP ke label yang konsisten untuk UI. */
    private function resolveStatusLabel(string $statusName, string $statusId): string
    {
        $statusLabels = [
            '0' => 'Dilaporkan',
            '1' => 'Diterima',
            '2' => 'Dikerjakan',
            '3' => 'Selesai',
            'inputed' => 'Dilaporkan',
            'received' => 'Diterima',
            'inprogress' => 'Dikerjakan',
            'done' => 'Selesai',
            'completed' => 'Selesai',
        ];

        if ($statusName !== '' && ! is_numeric($statusName)) {
            return $statusName;
        }

        $key = strtolower($statusName);

        return $statusLabels[$statusId] ?? $statusLabels[$key] ?? ($statusId !== '' ? "Status {$statusId}" : 'Status tidak diketahui');
    }

    /** Ambil nilai node anak pertama berdasarkan nama tag XML. */
    private function getChildNodeValue(DOMElement $entry, string $tagName): string
    {
        return trim((string) $entry->getElementsByTagName($tagName)->item(0)?->nodeValue);
    }

    /** Parse elemen ComboLookUp dari respons XML SOAP. */
    private function parseComboLookup(string $xml): array
    {
        $dom = $this->parseDom($xml);
        $items = [];
        foreach ($dom->getElementsByTagName('ComboLookUp') as $entry) {
            $items[] = [
                'Id' => $entry->childNodes->item(0)?->nodeValue ?? '',
                'Name' => $entry->childNodes->item(1)?->nodeValue ?? '',
            ];
        }

        return $items;
    }

    /** Buat DOMDocument dari string XML; lempar Exception jika gagal. */
    private function parseDom(string $xml): DOMDocument
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        if (! $dom->loadXML($xml)) {
            libxml_clear_errors();
            throw new Exception('Gagal mem-parse respons XML dari SOAP backend.');
        }

        return $dom;
    }
}
