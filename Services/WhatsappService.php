<?php

namespace Modules\Cuti\Services;

use Illuminate\Support\Facades\Http;
use Modules\Pengaturan\Entities\Pegawai;

class WhatsappService
{
    protected $baseUrl = 'https://sit.poliwangi.ac.id/v2/api/v1/sitapi/wa ';
    protected $token;

    public function __construct($token = null)
    {
        // Gunakan token default atau token khusus
        $this->token = $token ?? env('SIT_API_TOKEN');
    }

    /**
     * Kirim pesan ke API WhatsApp
     *
     * @param string $username
     * @param string $message
     * @return array
     */
    public function sendMessage($username, $message)
    {
        try {
            $response = Http::withToken($this->token)
                ->post($this->baseUrl, [
                    'username' => $username,
                    'message' => $message,
                ]);

            return [
                'status' => $response->successful() ? 'success' : 'failed',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}
