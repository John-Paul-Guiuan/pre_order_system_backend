<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function processPayment(Request $request, $order_id)
    {
        //dd('Controller reached'); // Add this line temporarily
        $request->validate([
            'payment_method' => 'required|string|in:gcash,card,paypal'
        ]);

        $order = Order::findOrFail($order_id);

        if ($order->payment_status === 'paid') {
            return response()->json([
                'message' => 'Order already paid.',
                'order'   => $order
            ], 400);
        }

        $order->payment_status = 'paid';
        $order->payment_method = $request->payment_method;
        $order->status = 'processing';
        $order->save();

        // 👇 make sure this “return response()->json” line really exists
        return response()->json([
            'message' => 'Payment successful!',
            'order'   => $order
        ]);
    }
}
