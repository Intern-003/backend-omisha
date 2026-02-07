<?php

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ebook;
use App\Filters\EbookFilter;


class ProductController extends Controller
{
    public function index(Request $request, EbookFilter $filter)
    {
        $query = Ebook::with('categories');

        $filteredQuery = $filter->apply($query, $request->all());

        return $filteredQuery->paginate(10);
    }

    public function show($id)
    {
        return Ebook::with('categories')->findOrFail($id);
    }

    public function related($id)
    {
        $ebook = Ebook::with('categories')->findOrFail($id);

        $categoryIds = $ebook->categories->pluck('id');

        return Ebook::whereHas('categories', function ($q) use ($categoryIds) {
            $q->whereIn('categories.id', $categoryIds);
        })
        ->where('id', '!=', $id)
        ->limit(5)
        ->get();
    }
}
