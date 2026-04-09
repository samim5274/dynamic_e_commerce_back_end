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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();

            $table->string('reg')->unique()->comment('Batch or Transaction Number')->default(0);

            $table->date('date');

            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');

            $table->integer('stockIn')->default(0);
            $table->integer('stockOut')->default(0);

            $table->text('remark')->nullable();

            $table->string('status')->default('active');

            $table->timestamps();

            $table->index(['product_id', 'date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
