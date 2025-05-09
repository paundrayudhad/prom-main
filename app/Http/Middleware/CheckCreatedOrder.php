<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCreatedOrder
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Cek apakah session 'created_order_id' ada
        if (session()->has('created_order_id')) {
            // Ambil order_id dari session
            $order_id = session('created_order_id');

            // Cari tiket berdasarkan order_id
            $tiket = \App\Models\Tiket::where('order_id', $order_id)->first();

            // Jika tiket ditemukan, arahkan ke /payment/instructions/{order_id}
            if ($tiket) {
                return redirect()->route('payment.instructions', ['order_id' => $tiket->order_id]);
            }
        }

        // Jika tidak ada session atau tiket tidak ditemukan, lanjutkan ke request berikutnya
        return $next($request);
    }
}

