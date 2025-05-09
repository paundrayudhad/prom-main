<?php

namespace App\Libraries;

use Exception;

class WhatsApp
{
    const TOKEN = '6bc04737-5845-4f44-b41a-f2b9fc3c5545';

    public static function filterNumber($number)
    {
        return preg_replace('/^08/', '628', $number);
    }

    public static function send($sender, $number, $message)
    {
        $query = http_build_query([
            'number' => $sender,
            'target' => $number,
            'message' => $message
        ]);
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://103.175.224.59:3333/api/chat/send-message?' . $query,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "Authorization: Bearer " . self::TOKEN,
            ],
            CURLOPT_RETURNTRANSFER => TRUE
        ));
        curl_exec($curl);
        curl_close($curl);

        return true;
    }

    public static function addDevice($number)
    {
        $query = http_build_query([
            'number' => $number,
            'webhook_url' => url('/webhook/whatsapp')
        ]);
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://103.175.224.59:3333/api/device/init?' . $query,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "Authorization: Bearer " . self::TOKEN,
            ],
            CURLOPT_RETURNTRANSFER => TRUE
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response);

        if ($response->status == false)
            throw new Exception($response->message);

        return true;
    }

    public static function statusDevice($number)
    {
        $query = http_build_query([
            'number' => $number
        ]);
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://103.175.224.59:3333/api/device/status?' . $query,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "Authorization: Bearer " . self::TOKEN,
            ],
            CURLOPT_RETURNTRANSFER => TRUE
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response);
        
        if ($response->status == false)
            throw new Exception($response->message);
        
        return $response->data->status;
    }

    public static function generateQrDevice($number)
    {
        $query = http_build_query([
            'number' => $number
        ]);
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://103.175.224.59:3333/api/device/qr?' . $query,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "Authorization: Bearer " . self::TOKEN,
            ],
            CURLOPT_RETURNTRANSFER => TRUE
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response);

        if ($response->status == false)
            throw new Exception($response->message);

        if ($response->data->status == 'qr')
            return [
                'status' => 'Proses...',
                'image_url' => "https://public-api.qr-code-generator.com/v1/create/free?image_format=SVG&image_width=500&foreground_color=%23000000&frame_color=%23000000&frame_name=no-frame&qr_code_logo=&qr_code_pattern=&qr_code_text=" . urlencode($response->data->qr)
            ];
        if ($response->data->status == 'connected')
            return [
                'status' => 'Terhubung!',
                'image_url' => "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQgmonNXpXbVWG73MMkOkDUcz5UHlyJYliTkA&s"
            ];

        throw new Exception("Terjadi kesalahan yang tidak terduga!");
    }

    public static function removeDevice($number)
    {
        $query = http_build_query([
            'number' => $number
        ]);
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://103.175.224.59:3333/api/device/logout?' . $query,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "Authorization: Bearer " . self::TOKEN,
            ],
            CURLOPT_RETURNTRANSFER => TRUE
        ));
        curl_exec($curl);
        curl_close($curl);

        return true;
    }

    public static function messageHistory($number)
    {
        $query = http_build_query([
            'number' => $number
        ]);
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://103.175.224.59:3333/api/chat/history?' . $query,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "Authorization: Bearer " . self::TOKEN,
            ],
            CURLOPT_RETURNTRANSFER => TRUE
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response);

        if (!empty($response->status) && $response->status == false)
            throw new Exception($response->message);

        return $response->data;
    }
}