<?php

namespace App\Filters;

class EbookFilter
{
    public function apply($query, $filters)
    {
        foreach ($filters as $filter => $value) {
            if ($value) {
                $method = 'filter' . ucfirst($filter);
                if (method_exists($this, $method)) {
                    $this->$method($query, $value);
                }
            }
        }
        return $query;
    }

    protected function filterSearch($query, $term)
    {
        $query->where('title', 'like', "%$term%");
    }

    protected function filterCategories($query, $categories)
    {

    // Convert comma-separated string to array
    if (!is_array($categories)) {
        $categories = explode(',', $categories);
    }
        $query->whereHas('categories', fn($q) => $q->whereIn('categories.id', $categories));
    }

    protected function filterPriceSort($query, $sort)
    {
        if ($sort === 'low_high') $query->orderBy('price', 'asc');
        if ($sort === 'high_low') $query->orderBy('price', 'desc');
    }

    protected function filterRating($query, $rating)
    {
        $query->where('rating', '>=', $rating);
    }
}
