<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Order;
use Illuminate\Support\Facades\Validator;

class CheckoutController extends Controller
{
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
