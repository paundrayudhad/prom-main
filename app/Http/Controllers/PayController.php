<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tiket;
use App\Models\Nis;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mansjoer\Fonnte\Facades\Fonnte;
use App\Helpers\WhatsAppHelper;

class PayController extends Controller
{
    public function validateNis($nis)
    {
        $siswa = Nis::where('nis', $nis)->first();

        if ($siswa) {
            return response()->json([
                'valid' => true,
                'siswa' => $siswa
            ]);
        }

        return response()->json([
            'valid' => false,
            'message' => 'NIS tidak ditemukan'
        ]);
    }

    public function initPayment(Request $request)
    {
        $request->validate([
            'nis' => 'required|exists:nis,nis',
            'nama_siswa' => 'required|string|max:255',
            'kelas' => 'required|string|max:50',
            'bawa_tamu' => 'required|boolean',
            'harga' => 'required|numeric|min:0'
        ]);

        $order_id = 'ORDER-' . Str::random(8);

        $paymentData = array_merge($request->all(), ['order_id' => $order_id]);
        Session::put('payment_data', $paymentData);

        return view('payment.payment', $paymentData);
    }

    public function processPayment(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'metodebayar' => 'required|in:bca,dana'
        ]);

        if (!Session::has('payment_data')) {
            return redirect()->route('pesan');
        }

        $paymentData = Session::get('payment_data');

        // Update session with email, phone, and metodebayar
        $paymentData = array_merge($paymentData, [
            'email' => $request->email,
            'phone' => $request->phone,
            'metodebayar' => $request->metodebayar
        ]);
        Session::put('payment_data', $paymentData);

        return view('payment.instructions', ['tiket' => (object) $paymentData]);
    }

    public function uploadbukti(Request $request)
    {
        $request->validate([
            'bukti' => 'required|image|mimes:jpeg,jpg,png|max:2048',
        ]);

        if (!Session::has('payment_data')) {
            return response()->json([
                'success' => false,
                'message' => 'Data pembayaran tidak ditemukan.'
            ]);
        }

        $paymentData = Session::get('payment_data');
        $file = $request->file('bukti');

        try {
            $newFileName = 'bukti_' . Str::slug($paymentData['nama_siswa'], '_') . '.' . $file->getClientOriginalExtension();

            $response = Http::asMultipart()
                ->attach('image', fopen($file->getRealPath(), 'r'), $newFileName)
                ->post('https://api.imgbb.com/1/upload?key=' . env('IMGBB_API_KEY'));

            $result = $response->json();

            if (!$response->successful() || !isset($result['data']['url'])) {
                Log::error('Gagal upload gambar ke imgbb.', [
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Gagal upload gambar ke imgbb.'
                ]);
            }

            $imageUrl = $result['data']['url'];
            $imageUrl = str_replace('ibb.co', 'ibb.co.com', $imageUrl);
            Log::info('Hasil response dari imgbb:', $result);

            // Create Tiket record
            $tiket = Tiket::create([
                'order_id' => $paymentData['order_id'],
                'nis' => $paymentData['nis'],
                'nama' => $paymentData['nama_siswa'],
                'email' => $paymentData['email'],
                'phone' => $paymentData['phone'],
                'kelas' => $paymentData['kelas'],
                'jumlah_tiket' => $paymentData['bawa_tamu'] ? 2 : 1,
                'harga' => $paymentData['harga'],
                'metodebayar' => $paymentData['metodebayar'],
                'status' => 'pending',
                'bukti' => $imageUrl
            ]);

            $message = "📢 Bukti pembayaran baru telah diterima!\n\n" .
            "👤 Nama: {$tiket->nama}\n" .
            "🆔 Order ID: {$tiket->order_id}\n" .
            "💳 Metode Pembayaran: {$tiket->metodebayar}\n" .
            "🖼️ Foto Bukti: {$imageUrl}";
        WhatsAppHelper::sendMessage('62895366575360', $message);
            WhatsAppHelper::sendMessage('6285600706531', $message);

            // Clear session after payment complete
            Session::forget('payment_data');

            return response()->json([
                'success' => true,
                'image_url' => $imageUrl,
                'order_id' => $tiket->order_id,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }
}
