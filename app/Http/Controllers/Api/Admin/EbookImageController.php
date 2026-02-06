<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ebook;
use App\Models\EbookImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EbookImageController extends Controller
{
    // List images of an ebook
    public function index($ebookId)
    {
        $ebook = Ebook::with('images')->findOrFail($ebookId);

        return response()->json([
            'success' => true,
            'data' => $ebook->images
        ]);
    }

    // Upload image
    // public function store(Request $request, $ebookId)
    // {
    //     $request->validate([
    //         'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
    //     ]);

    //     $ebook = Ebook::findOrFail($ebookId);

    //     $path = $request->file('image')->store('ebooks', 'public');

    //     $image = EbookImage::create([
    //         'ebook_id' => $ebook->id,
    //         'image_path' => $path
    //     ]);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Image uploaded successfully',
    //         'data' => $image
    //     ], 201);
    // }
//     public function store(Request $request, $ebookId)
// {
//     $request->validate([
//         'images'   => 'required|array|min:1',
//         'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
//     ]);

//     $ebook = Ebook::findOrFail($ebookId);

//     $savedImages = [];

//     foreach ($request->file('images') as $image) {
//         $path = $image->store('ebooks', 'public');

//         $savedImages[] = EbookImage::create([
//             'ebook_id'   => $ebook->id,
//             'image_path'=> $path
//         ]);
//     }

//     return response()->json([
//         'success' => true,
//         'message' => 'Images uploaded successfully',
//         'data'    => $savedImages
//     ], 201);
// }

public function store(Request $request, $ebookId)
{
    $request->validate([
        'images'   => 'required|array|min:1',
        'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    $ebook = Ebook::findOrFail($ebookId);

    $savedImages = [];

    foreach ($request->file('images') as $image) {
        // Change 'ebooks' to 'ebook_images' to store in consistent folder
        $path = $image->store('ebook_images', 'public');

        $savedImages[] = EbookImage::create([
            'ebook_id'   => $ebook->id,
            'image_path' => $path
        ]);
    }

    return response()->json([
        'success' => true,
        'message' => 'Images uploaded successfully',
        'data'    => $savedImages
    ], 201);
}

public function update(Request $request, $id)
{
    $request->validate([
        'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    $image = EbookImage::findOrFail($id);

    // delete old image
    if (Storage::disk('public')->exists($image->image_path)) {
        Storage::disk('public')->delete($image->image_path);
    }

    // Use same folder: 'ebook_images'
    $path = $request->file('image')->store('ebook_images', 'public');

    $image->update([
        'image_path' => $path
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Image updated successfully',
        'data' => $image
    ]);
}

    // Show single image
    public function show($id)
    {
        $image = EbookImage::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $image
        ]);
    }

    // Update image
    // public function update(Request $request, $id)
    // {
    //     $request->validate([
    //         'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
    //     ]);

    //     $image = EbookImage::findOrFail($id);

    //     // delete old image
    //     if (Storage::disk('public')->exists($image->image_path)) {
    //         Storage::disk('public')->delete($image->image_path);
    //     }

    //     $path = $request->file('image')->store('ebooks', 'public');

    //     $image->update([
    //         'image_path' => $path
    //     ]);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Image updated successfully',
    //         'data' => $image
    //     ]);
    // }

    // Delete image
    public function destroy($id)
    {
        $image = EbookImage::findOrFail($id);

        if (Storage::disk('public')->exists($image->image_path)) {
            Storage::disk('public')->delete($image->image_path);
        }

        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully'
        ]);
    }
}
