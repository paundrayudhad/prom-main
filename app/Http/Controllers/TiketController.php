<?php
namespace App\Http\Controllers;

use App\Models\Tiket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendEmail;
use App\Libraries\Whatsapp;
use App\Helpers\WhatsAppHelper;


class TiketController extends Controller
{
    public function index()
    {
        $data = Tiket::latest()->get();
        return view('tiket.index', compact('data'));
    }

    public function verifikasi($id)
    {
        $tiket = Tiket::findOrFail($id);
        $tiket->status = 'completed';
        $tiket->save();

        $data = [
            'nis' => $tiket->nis,
            'nama' => $tiket->nama,
            'kelas' => $tiket->kelas,
            'status' => $tiket->status,
            'order_id' => $tiket->order_id,
            'email' => $tiket->email,
            'no_hp' => $tiket->phone,
            'url' => url('/eticket/' . $tiket->order_id . '?nis=' . $tiket->nis),
        ];

        Mail::to($tiket->email)->send(new SendEmail($data));
        

        $message = $message = "Hai {$data['nama']},
        
 Kamu telah berhasil memesan tiket untuk acara *Heptasyn - Prom Night*.
        
Klik link di bawah ini untuk melihat e-ticket kamu (ada QR code dan detail tiketnya):
{$data['url']}
        
Pastikan untuk menunjukkan QR code tersebut saat tiba di acara.
_Jangan sebarkan link ini ke orang lain ya!_
        
Butuh bantuan? Hubungi kami via WhatsApp:
https://wa.me/6282180833304
        
© 2025 Panitia Atas Nama Masa Muda";

        $token = '6bc04737-5845-4f44-b41a-f2b9fc3c5545';
        $target = $tiket->phone;

       WhatsAppHelper::sendMessage($target, $message);

        return redirect()->back()->with('success', 'Pembayaran berhasil diverifikasi.');
    }

    public function validateScan(Request $request)
    {
        try {
            $request->validate([
                'qr' => 'required|string|max:255'
            ]);

            $tiket = Tiket::where('order_id', $request->qr)->first();
            
            if (!$tiket) {
                return response()->json([
                    'valid' => false,
                    'message' => 'ticket_not_found'
                ]);
            }

            if ($tiket->status == 'completed' && $tiket->entry == 'no') {
                // Update entry status to yes
                $tiket->entry = 'yes';
                $tiket->save();

                return response()->json([
                    'valid' => true,
                    'message' => 'Check-In Berhasil',
                    'ticket' => [
                        'nis' => $tiket->nis,
                        'nama_siswa' => $tiket->nama,
                        'kelas' => $tiket->kelas,
                        'status' => $tiket->status,
                        'order_id' => $tiket->order_id,
                        'email' => $tiket->email,
                        'no_hp' => $tiket->phone
                    ]
                ]);
            } else if ($tiket->status == 'pending') {
                return response()->json([
                    'valid' => false,
                    'message' => 'ticket_pending'
                ]);
            } else if ($tiket->status == 'completed' && $tiket->entry == 'yes') {
                return response()->json([
                    'valid' => false,
                    'message' => 'ticket_already_used'
                ]);
            } else {
                return response()->json([
                    'valid' => false,
                    'message' => 'ticket_invalid'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Terjadi kesalahan saat memvalidasi tiket'
            ], 500);
        }
    }

    public function show($id, Request $request)
    {
        $nis = $request->query('nis');

        $tiket = Tiket::where('order_id', $id)->where('nis', $nis)->first();

        if (!$tiket) {
            return abort(404, 'Tiket tidak ditemukan.');
        }

        if ($tiket->status === 'pending') {
            return abort(404, 'Tiket belum dibayar.');
        }

        
        return view('eticket.show', compact('tiket'));
    }

    public function manualCheckin(Request $request)
{
    Log::info('Manual checkin request', $request->all());

    $request->validate([
        'order_id' => 'required|string'
    ]);

    $ticket = Tiket::where('order_id', $request->order_id)->first();

    if (!$ticket) {
        return response()->json(['valid' => false, 'message' => 'ticket_not_found']);
    }

    if ($ticket->status === 'pending') {
        return response()->json(['valid' => false, 'message' => 'ticket_pending']);
    }

    if ($ticket->entry === 'yes') {
        return response()->json(['valid' => false, 'message' => 'ticket_already_used']);
    }

    $ticket->entry = 'yes';
    $ticket->save();

    return response()->json([
        'valid' => true,
        'message' => 'Tiket berhasil digunakan',
        'ticket' => [
            'nis' => $ticket->nis,
            'nama_siswa' => $ticket->nama,
            'kelas' => $ticket->kelas,
            'status' => $ticket->status,
            'order_id' => $ticket->order_id,
            'email' => $ticket->email,
            'no_hp' => $ticket->phone
        ]
    ]);
}



}
