<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->onDelete('cascade');
            // ebook_id references ebooks.id
            $table->unsignedBigInteger('ebook_id')->nullable();
            $table->foreign('ebook_id')->references('id')->on('ebooks')->onDelete('cascade');
            // $table->string('title'); // snapshot title
            $table->decimal('price', 10, 2)->nullable(); // snapshot price
            $table->integer('quantity')->nullable();
            $table->decimal('total_price', 10, 2)->default(0.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
