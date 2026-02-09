<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Wishlist;

class WishlistController extends Controller
{
    // GET /api/wishlist
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $wishlist = Wishlist::with('ebook')
            ->where('user_id', $request->user_id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $wishlist
        ]);
    }

    // POST /api/wishlist
    // body: { "user_id": 1, "product_id": 5 }  (product_id = ebook_id)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'product_id' => 'required|exists:ebooks,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $row = Wishlist::firstOrCreate([
            'user_id' => $request->user_id,
            'ebook_id' => $request->product_id,
        ]);

        return response()->json([
            'status' => true,
            'message' => $row->wasRecentlyCreated ? 'Added to wishlist' : 'Already in wishlist',
            'data' => $row->load('ebook')
        ], 201);
    }

    // DELETE /api/wishlist/{productId}
    public function destroy(Request $request, $productId)
    {
        $validator = Validator::make(array_merge($request->all(), [
            'product_id' => $productId
        ]), [
            'user_id' => 'required|exists:users,id',
            'product_id' => 'required|exists:ebooks,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $deleted = Wishlist::where('user_id', $request->user_id)
            ->where('ebook_id', $productId)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'status' => false,
                'message' => 'Item not found in wishlist'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Removed from wishlist'
        ]);
    }
}