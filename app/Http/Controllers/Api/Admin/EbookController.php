<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ebook;
use Illuminate\Support\Str;
use App\Filters\EbookFilter;
use Illuminate\Support\Facades\Storage;


class EbookController extends Controller
{
   public function store(Request $request)
{
    $request->validate([
        'title' => 'required',
        'description' => 'required',
        'price' => 'required|numeric',
        'ebook_file' => 'required|file|mimes:pdf',
        'categories' => 'required|array',
        'images.*' => 'image|max:2048',
    ]);

    // First create ebook without ebook_file
    $ebook = Ebook::create([
        'title' => $request->title,
        'slug' => Str::slug($request->title),
        'description' => $request->description,
        'price' => $request->price,
    ]);

    // Handle ebook file
    if ($request->hasFile('ebook_file')) {
        $file = $request->file('ebook_file');
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $filename = $originalName . '_' . $ebook->id . '.' . $extension;
        $path = $file->storeAs('ebooks', $filename);

        $ebook->update(['ebook_file' => $path]);
    }

    // Attach categories
    $ebook->categories()->sync($request->categories);

    // Multiple images
    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $image) {
            $imageName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $image->getClientOriginalExtension();
            $filename = $imageName . '_' . $ebook->id . '.' . $extension;

            $path = $image->storeAs('ebook-images', $filename);
            $ebook->images()->create(['image_path' => $path]);
        }
    }

    return response()->json([
        'message' => 'Ebook created successfully',
        'ebook' => $ebook->load('categories','images')
    ], 201);
}


public function update(Request $request, $id)
{
    $ebook = Ebook::findOrFail($id);

    $request->validate([
        'title' => 'sometimes|required',
        'description' => 'sometimes|required',
        'price' => 'sometimes|required|numeric',
        'categories' => 'sometimes|array',
        'ebook_file' => 'sometimes|file|mimes:pdf',
        'images.*' => 'image|max:2048',
    ]);

    $data = $request->only(['title','description','price']);

    // ====== Handle main ebook file ======
    if ($request->hasFile('ebook_file')) {
        // Delete old file if exists
        if ($ebook->ebook_file && Storage::exists($ebook->ebook_file)) {
            Storage::delete($ebook->ebook_file);
        }

        $file = $request->file('ebook_file');
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $filename = $originalName . '_' . $ebook->id . '.' . $extension;

        $path = $file->storeAs('ebooks', $filename);
        $data['ebook_file'] = $path;
    }

    $ebook->update($data);

    // ====== Sync categories ======
    if ($request->filled('categories')) {
        $ebook->categories()->sync($request->categories);
    }

    // ====== Handle images ======
    if ($request->hasFile('images')) {
    // Delete old images from storage and DB
    foreach ($ebook->images as $oldImage) {
        if (Storage::exists($oldImage->image_path)) {
            Storage::delete($oldImage->image_path);
        }
        $oldImage->delete();
    }

    // Upload new images
    foreach ($request->file('images') as $image) {
        $imageName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $image->getClientOriginalExtension();
        $filename = $imageName . '_' . $ebook->id . '.' . $extension;

        $path = $image->storeAs('ebook-images', $filename);
        $ebook->images()->create(['image_path' => $path]);
    }
}


    return response()->json([
        'message' => 'Ebook updated',
        'ebook' => $ebook->load('categories','images')
    ]);
}


public function show($id)
{
    return Ebook::with(['categories','images'])->findOrFail($id);
}

// public function index()
// {
//     return Ebook::with(['categories','images'])->latest()->get();
// }

public function index(Request $request)
{
    
    $query = Ebook::with(['categories','images']);
$query = (new EbookFilter)->apply($query, $request->all());
//dd($query->toSql(), $query->getBindings());

    
    $ebooks = $query->latest()->paginate(10); // paginated for performance

    return response()->json([
        'success' => true,
        'data' => $ebooks
    ]);
}


public function destroy($id)
{
    $ebook = Ebook::findOrFail($id);
    $ebook->delete();

    return response()->json(['message'=>'Ebook deleted']);
}


}
