<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    Schema::create('carts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
    $table->integer('total_items')->default(0);
    $table->decimal('subtotal', 10, 2)->default(0);
    $table->enum('status', ['ACTIVE', 'CHECKED_OUT'])->default('ACTIVE');
    $table->timestamps();
});

// id
// user_id
// status (active, completed)
// created_at
// updated_at



    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
