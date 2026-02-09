<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\EbookController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\EbookImageController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\User\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Api\AddressController;
  use App\Http\Controllers\WishlistController;

// Route::post('/register', function (Request $r){
//     return $r;
// });

Route::post('/register', [UserController::class, 'register']); //
Route::post('/login', [UserController::class, 'login']); //


// Route::post('/add', function (Request $request) {
//     $data = DB::table('ebooks')->insert([
//             'id'=>$request->id,
//             'title'       => $request->title,
//             'slug'      => $request->slug,
//             'description'   => ($request->description),
//             'price'=>$request->price,
//             'ebook_file'=>$request->ebook_file,
//             'created_at' => now(),
//             'updated_at' => now(),
//         ]);
//     $user= DB::table('ebooks')->get();
//     return response()->json($user);
// });


Route::get('/ha',function (){
    return'running';
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [UserController::class, 'logout']);//
    Route::get('/profile', [UserController::class, 'profile']); // optional
    Route::post('/checkout',[CheckoutController::class,'checkout']);

Route::post('/checkout/validate', [CheckoutController::class, 'validateCheckout']);
Route::post('/checkout/summary', [CheckoutController::class, 'summary']);
Route::post('/checkout/place-order', [CheckoutController::class, 'placeOrder']);
    Route::get('/order-history/{id}',[CheckoutController::class,'orderhistory']);//
    Route::get('/cart', [CartController::class, 'viewCart']);//
    Route::post('/cart/add', [CartController::class, 'addItem']);//
    Route::put('/cart/item/{id}', [CartController::class, 'updateItem']);//
    Route::delete('/cart/item/{id}', [CartController::class, 'removeItem']);//
 Route::get('/addresses', [AddressController::class, 'index']);
  Route::post('/addresses', [AddressController::class, 'store']);

  Route::get('/addresses/{id}', [AddressController::class, 'show']);
  Route::post('/addresses/{id}', [AddressController::class, 'update']); // your requirement
  Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);


Route::get('/wishlist', [WishlistController::class, 'index']);
Route::post('/wishlist', [WishlistController::class, 'store']);
Route::delete('/wishlist/{productId}', [WishlistController::class, 'destroy']);
});

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'role:admin'])
    ->group(function () {

        // Ebooks
        Route::get('/ebooks', [EbookController::class, 'index']);//
        Route::post('/ebooks', [EbookController::class, 'store']);//
        Route::get('/ebooks/{id}', [EbookController::class, 'show']);//
        
        // Update: accept POST + optional _method=PUT
        Route::post('/ebooks/{id}', [EbookController::class, 'update']); // new POST route
        //Route::put('/ebooks/{id}', [EbookController::class, 'update']);  // keep PUT for REST
        //Route::put('/ebooks/{id}', [EbookController::class, 'update']);
        Route::delete('/ebooks/{id}', [EbookController::class, 'destroy']);//

        // Categories
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::get('/categories/{id}', [CategoryController::class, 'show']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

        // Ebook Images
        Route::get('/ebooks/{ebookId}/images', [EbookImageController::class, 'index']);
        Route::post('/ebooks/{ebookId}/images', [EbookImageController::class, 'store']);

        Route::get('/ebook-images/{id}', [EbookImageController::class, 'show']);
        Route::post('/ebook-images/{id}', [EbookImageController::class, 'update']); // file update
        Route::delete('/ebook-images/{id}', [EbookImageController::class, 'destroy']);

    });





