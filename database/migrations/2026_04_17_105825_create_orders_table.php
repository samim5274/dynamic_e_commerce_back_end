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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reg')->unique();
            $table->date('date');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('transaction_id')->nullable()->unique();
            // unpaid|pending|paid|failed|canceled|processing
            $table->enum('status', ['Pending', 'Cancelled', 'Processing', 'Delivered'])->default('Pending');
            $table->decimal('total', 10, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
