<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Ebook;

class CartController extends Controller
{
    // Get current user's cart
    public function viewCart()
    {
        // Make sure to eager load 'items.ebook', not 'items.ebook_id'
        $cart = Cart::with('items.ebook')
            ->firstOrCreate([
                'user_id' => auth()->id(),
                // 'user_id' => 1,
                'status' => 'ACTIVE'
            ]);

        return response()->json($cart);
    }

    // Add item to cart
    public function addItem(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:ebooks,id',
                'quantity' => 'required|integer|min:1',
            ]);

            $product = Ebook::findOrFail($request->product_id);

            $cart = Cart::firstOrCreate([
                'user_id' => auth()->id(),
                'status' => 'ACTIVE',    
            ]);

            $item = CartItem::firstOrNew([
                'cart_id' => $cart->id,
                'ebook_id' => $product->id
            ]);

            $item->quantity = ($item->quantity ?? 0) + $request->quantity;
            $item->price = $product->price; // always use backend price
            
            $item->save();

            $cart->recalculateTotals();

            // Reload items with ebook relationship for response
            $cart->load('items.ebook');

            return response()->json($cart);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 501);
        }
    }

    // Update quantity
    public function updateItem(Request $request, $itemId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $item = CartItem::findOrFail($itemId);
        $item->quantity = $request->quantity;

        if ($item->quantity == 0) {
            $item->delete();
        } else {
            $item->save();
        }

        $item->cart->recalculateTotals();
        $item->cart->load('items.ebook');

        return response()->json($item->cart);
    }

    // Remove item
    public function removeItem($itemId)
    {
        $item = CartItem::findOrFail($itemId);
        $cart = $item->cart;
        $item->delete();
        $cart->recalculateTotals();
        $cart->load('items.ebook');

        return response()->json($cart);
    }
}
