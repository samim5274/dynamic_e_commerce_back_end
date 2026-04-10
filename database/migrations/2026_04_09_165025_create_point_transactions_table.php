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
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['earn', 'spend', 'bonus', 'matching'])->default('earn');
            $table->integer('points');

            $table->decimal('bonus_amount', 15, 2)->default(0);
            $table->enum('bonus_status', ['deposit', 'withdrawal'])->nullable();

            $table->string('source')->nullable(); // order, referral, admin
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
    }
};
