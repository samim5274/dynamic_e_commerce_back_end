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

            // Registration/Session Tracking
            $table->string('reg')->index();

            // Foreign Keys
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');

            // Product Details (Snapshot)
            $table->integer('quantity')->default(1);
            $table->decimal('price', 12, 2)->default(0.00)->comment('Price per unit at the time of adding');
            $table->decimal('discount', 12, 2)->default(0.00)->comment('Any discount applied');

            // Metadata
            $table->text('note')->nullable();
            $table->timestamps();

            // Indexing for performance
            $table->index(['user_id', 'reg']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
