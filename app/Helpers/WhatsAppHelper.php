<?php

namespace App\Helpers;

class WhatsAppHelper
{
    public static function sendMessage($to, $message)
    {
        // Menyiapkan data POST
        $post_data = [
            'sessions' => 'session_1',
            'target' => self::filterNoWhatsApp($to),
            'message' => $message
        ];
        
        // Inisialisasi cURL
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://whatsapp-gateway-production.up.railway.app/api/sendtext", // URL tetap, query string dihilangkan
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POST => true, // Menggunakan metode POST
            CURLOPT_POSTFIELDS => http_build_query($post_data), // Mengirimkan data POST
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20
        ]);

        // Menjalankan cURL dan mendapatkan respons
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public static function filterNoWhatsApp($number)
    {
        return preg_replace('/^08/', '628', $number);
    }
}
