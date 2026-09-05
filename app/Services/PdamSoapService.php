<?php

namespace App\Services;

use DOMDocument;
use Exception;
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
            'trace'      => true,
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
                'FilterName'  => 'Subdistrictid',
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
     * @throws Exception Jika SOAP server mengembalikan fault
     */
    public function submitComplaint(array $payload): ?string
    {
        $today = now()->toDateString();

        $ViewModel = [
            'Id'                  => 0,
            'DateCompliant'       => $today,
            'Number'              => '0',
            'ComplianerName'      => $payload['Name'],
            'ComplianerAddress'   => $payload['Address'],
            'PhoneNumber'         => $payload['PhoneNumber'],
            'Email'               => '-',
            'PDAMCustNumber'      => $payload['CustomerNumber'] ?? '-',
            'LatCoords'           => $payload['LatCoords'] ?? '0',
            'LngCoords'           => $payload['LngCoords'] ?? '0',
            'CompliantContent'    => $payload['CompliantContent'],
            'Photo'               => '-',
            'CompliantStatusId'   => 1,
            'CompliantStatusName' => 'Inputed',
            'SubDistrictsId'      => (int) $payload['SubDistricts'],
            'VillagesId'          => (int) $payload['Villages'],
            'InputedDate'         => $today,
            'isDeleted'           => 1,
            'UpdatedDate'         => $today,
            'SubDistrictName'     => '1',
            'VillagesName'        => '1',
            'InputedBy'           => 'web',
            'CompliantTypeId'     => (int) $payload['CompliantType'],
            'CompliantTypeName'   => '1',
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
            throw new Exception('SOAP CustomerCompliant error: ' . $e->getMessage(), 0, $e);
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
            $dom = $this->parseDom($client->__getLastResponse());
            $entries = $dom->getElementsByTagName('CompliantLogViewModel');

            $results = [];
            foreach ($entries as $entry) {
                $results[] = [
                    'nama'      => $entry->childNodes->item(1)?->nodeValue ?? '',
                    'alamat'    => $entry->childNodes->item(2)?->nodeValue ?? '',
                    'ticket'    => $entry->childNodes->item(3)?->nodeValue ?? '',
                    'pengaduan' => $entry->childNodes->item(5)?->nodeValue ?? '',
                    'status'    => $entry->childNodes->item(6)?->nodeValue ?? '',
                    'tanggal'   => $entry->childNodes->item(9)?->nodeValue ?? '',
                ];
            }

            return $results;
        } catch (SoapFault) {
            return [];
        }
    }

    /** Parse elemen ComboLookUp dari respons XML SOAP. */
    private function parseComboLookup(string $xml): array
    {
        $dom = $this->parseDom($xml);
        $items = [];
        foreach ($dom->getElementsByTagName('ComboLookUp') as $entry) {
            $items[] = [
                'Id'   => $entry->childNodes->item(0)?->nodeValue ?? '',
                'Name' => $entry->childNodes->item(1)?->nodeValue ?? '',
            ];
        }
        return $items;
    }

    /** Buat DOMDocument dari string XML; lempar Exception jika gagal. */
    private function parseDom(string $xml): DOMDocument
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        if (!$dom->loadXML($xml)) {
            libxml_clear_errors();
            throw new Exception('Gagal mem-parse respons XML dari SOAP backend.');
        }
        return $dom;
    }
}
