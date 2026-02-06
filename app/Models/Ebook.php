<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;



class Ebook extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'price',
        'ebook_file'
    ];

    public function images()
    {
        return $this->hasMany(EbookImage::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'ebook_category', 'ebook_id', 'category_id');
    }


    protected static function booted()
    {
        static::deleting(function ($ebook) {
            foreach ($ebook->images as $image) {
                if (Storage::disk('public')->exists($image->image_path)) {
                    Storage::disk('public')->delete($image->image_path);
                }
                $image->delete();
            }
        });
    }

}
