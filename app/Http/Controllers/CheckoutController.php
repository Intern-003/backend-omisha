<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Ebook;

class CheckoutController extends Controller
{
    // ebooks => usually no shipping, tax maybe 0 (change if needed)
    private float $taxRate = 0.00;     // set 0.18 if you want GST
    private float $shipping = 0.00;

    // POST /api/checkout/validate
    // validate cart + address + price validation + totals
    public function validateCheckout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'      => 'required|exists:users,id',
            'cart_id'      => 'required|exists:carts,id',
            'phone_number' => 'required|string|max:15',
            'address'      => 'required|string|max:500',
            'pincode'      => 'required|string|max:10',

            // optional anti-tamper check
            'expected_total' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $cart = Cart::with('items.ebook')
            ->where('id', $request->cart_id)
            ->where('user_id', $request->user_id)
            ->where('status', 'ACTIVE')
            ->first();

        if (!$cart) {
            return response()->json(['status' => false, 'message' => 'Invalid or inactive cart'], 400);
        }

        if ($cart->items->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Cart is empty'], 400);
        }

        // ✅ always recalc from cart_items table
        $cart->recalculateTotals();
        $cart->refresh();

        $issues = [];

        // Price validation (recommended):
        // Compare cart_items.price vs current ebook price
        foreach ($cart->items as $item) {
            if (!$item->ebook) {
                $issues[] = [
                    'type' => 'EBOOK_MISSING',
                    'cart_item_id' => $item->id,
                    'message' => 'Ebook not found.',
                ];
                continue;
            }

            $currentPrice = (float) ($item->ebook->price ?? 0);
            $cartPrice    = (float) ($item->price ?? 0);

            if (abs($currentPrice - $cartPrice) > 0.01) {
                $issues[] = [
                    'type' => 'PRICE_CHANGED',
                    'ebook_id' => $item->ebook->id,
                    'message' => 'Price changed for ebook.',
                    'old_price' => $cartPrice,
                    'new_price' => $currentPrice,
                ];
            }

            // Optional: verify total_price correctness
            $expectedTotalPrice = round($cartPrice * (int)$item->quantity, 2);
            $savedTotalPrice = round((float)($item->total_price ?? 0), 2);
            if (abs($expectedTotalPrice - $savedTotalPrice) > 0.01) {
                $issues[] = [
                    'type' => 'TOTAL_PRICE_MISMATCH',
                    'cart_item_id' => $item->id,
                    'message' => 'Cart item total_price mismatch.',
                    'expected' => $expectedTotalPrice,
                    'found' => $savedTotalPrice,
                ];
            }
        }

        if (!empty($issues)) {
            return response()->json([
                'status' => false,
                'message' => 'Checkout validation failed',
                'issues' => $issues,
            ], 400);
        }

        // optional expected_total check (client total vs server subtotal)
        if ($request->filled('expected_total')) {
            $expected = (float) $request->expected_total;
            $serverSubtotal = (float) $cart->subtotal;

            if (abs($expected - $serverSubtotal) > 0.50) {
                return response()->json([
                    'status' => false,
                    'message' => 'Expected total does not match server subtotal.',
                    'expected_total' => round($expected, 2),
                    'server_subtotal' => round($serverSubtotal, 2),
                ], 400);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Checkout validated successfully',
            'cart' => [
                'cart_id' => $cart->id,
                'total_items' => (int) $cart->total_items,
                'subtotal' => (float) $cart->subtotal,
            ],
        ]);
    }

    // POST /api/checkout/summary
    public function summary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'cart_id' => 'required|exists:carts,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $cart = Cart::with('items')
            ->where('id', $request->cart_id)
            ->where('user_id', $request->user_id)
            ->where('status', 'ACTIVE')
            ->first();

        if (!$cart) {
            return response()->json(['status' => false, 'message' => 'Invalid or inactive cart'], 400);
        }

        if ($cart->items->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Cart is empty'], 400);
        }

        $cart->recalculateTotals();
        $cart->refresh();

        $subtotal = (float) $cart->subtotal;
        $tax = $subtotal * $this->taxRate;
        $shipping = $this->shipping;
        $grandTotal = $subtotal + $tax + $shipping;

        return response()->json([
            'status' => true,
            'data' => [
                'cart_id' => $cart->id,
                'item_count' => (int) $cart->total_items,
                'subtotal' => round($subtotal, 2),
                'tax_rate' => $this->taxRate,
                'tax' => round($tax, 2),
                'shipping' => round($shipping, 2),
                'grand_total' => round($grandTotal, 2),
            ],
        ]);
    }

    // POST /api/checkout/place-order
    // Only CASH mode active (COD)
    public function placeOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'      => 'required|exists:users,id',
            'cart_id'      => 'required|exists:carts,id',
            'phone_number' => 'required|string|max:15',
            'address'      => 'required|string|max:500',
            'pincode'      => 'required|string|max:10',

            // for now only cash
            'payment_method' => 'required|string|in:CASH',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $cart = Cart::with('items.ebook')
            ->where('id', $request->cart_id)
            ->where('user_id', $request->user_id)
            ->where('status', 'ACTIVE')
            ->first();

        if (!$cart) {
            return response()->json(['status' => false, 'message' => 'Invalid or inactive cart'], 400);
        }

        if ($cart->items->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Cart is empty'], 400);
        }

        // recalc totals server-side
        $cart->recalculateTotals();
        $cart->refresh();

        // price validation again (safe)
        foreach ($cart->items as $item) {
            if (!$item->ebook) {
                return response()->json(['status' => false, 'message' => 'One or more ebooks are missing'], 400);
            }
            $currentPrice = (float) ($item->ebook->price ?? 0);
            $cartPrice    = (float) ($item->price ?? 0);

            if (abs($currentPrice - $cartPrice) > 0.01) {
                return response()->json([
                    'status' => false,
                    'message' => 'Price changed. Please refresh cart.',
                    'ebook_id' => $item->ebook->id,
                    'old_price' => $cartPrice,
                    'new_price' => $currentPrice,
                ], 400);
            }
        }

        $subtotal = (float) $cart->subtotal;
        $tax = $subtotal * $this->taxRate;
        $shipping = $this->shipping;
        $grandTotal = $subtotal + $tax + $shipping;

        // ✅ Create order (adjust fields to match your orders table)
        $order = Order::create([
            'user_id'      => $request->user_id,
            'cart_id'      => $request->cart_id,
            'phone_number' => $request->phone_number,
            'address'      => $request->address,
            'pincode'      => $request->pincode,
            'bill_amount'  => round($grandTotal, 2),

            // if you have these columns:
            'order_no'       => 'ORD-' . strtoupper(Str::random(10)),
            'payment_mode'   => 'CASH',
            'payment_status' => 'PENDING',
            'order_status'   => 'PLACED',
        ]);

        // update cart status
        $cart->status = 'CHECKED_OUT';
        $cart->save();

        return response()->json([
            'status' => true,
            'message' => 'Order placed successfully',
            'order' => $order,
            'amount_breakup' => [
                'subtotal' => round($subtotal, 2),
                'tax' => round($tax, 2),
                'shipping' => round($shipping, 2),
                'grand_total' => round($grandTotal, 2),
            ],
        ], 201);
    }
        public function checkout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'      => 'required|exists:users,id',
            'cart_id'      => 'required|exists:carts,id',
            'phone_number' => 'required|string|max:15',
            'address'      => 'required|string',
            'pincode'      => 'required|string|max:10',
            'bill_amount'  => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // 2️⃣ Get cart and verify ownership + status
        $cart = Cart::with('items.ebook')
            ->where('id', $request->cart_id)
            ->where('user_id', $request->user_id)
            ->where('status', 'ACTIVE')
            ->first();
        
        if (!$cart) {
            return response()->json([
                'message' => 'Invalid or inactive cart'
            ], 400);
        }

        if ($cart->items->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty'
            ], 400);
        }

        // 3️⃣ Store order
        $order = Order::create([
            'user_id'      => $request->user_id,
            'cart_id'      => $request->cart_id,
            'phone_number' => $request->phone_number,
            'address'      => $request->address,
            'pincode'      => $request->pincode,
            'bill_amount'  => $request->bill_amount,
        ]);

        // (Optional) update cart status
        $cart->status = 'CHECKED_OUT';
        $cart->save();

        // 4️⃣ Response
        return response()->json([
            'message' => 'Order placed successfully',
            'order'   => $order
        ], 201);
    }

    public function orderhistory(Request $request,$id)
    {
        $userId = $id;

        $orders = Order::where('user_id', $userId)->get();

    if ($orders->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'No orders found'
        ]);
    }
        return response()->json([
            'status' => true,
            'data' => $orders
        ]);
    }
}