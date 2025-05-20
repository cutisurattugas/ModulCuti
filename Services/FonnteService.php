<?php

namespace Modules\Cuti\Services;

use Illuminate\Support\Facades\Http;
use Modules\Pengaturan\Entities\Pegawai;

class FonnteService
{
    protected string $apiUrl = 'https://api.fonnte.com/send';
    protected string $token;

    public function __construct()
    {
        $this->token = 'nctHnWs9PbWxxxgDPx4M';
    }

    /**
     * Kirim pesan teks WhatsApp via Fonnte
     *
     * @param string $target Format: '6281234567890' atau '6281234567890|Nama|Var1'
     * @param string $message Pesan teks. Bisa gunakan {name}, {var1}, dsb.
     * @param array $options Optional: delay, typing, schedule, followup, countryCode
     * @return array
     */
    public function sendText(string $target, string $message, array $options = []): array
    {
        $postFields = array_merge([
            'target' => $target,
            'message' => $message,
        ], $options);

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->token
            ],
        ]);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
            curl_close($curl);
            return ['success' => false, 'error' => $error_msg];
        }

        curl_close($curl);
        return json_decode($response, true);
    }
}
