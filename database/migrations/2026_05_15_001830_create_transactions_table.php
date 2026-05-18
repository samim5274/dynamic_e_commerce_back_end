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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->string('transaction_id', 50)->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');

            // Amount info
            $table->decimal('amount', 12, 2); // requested amount
            $table->decimal('charge', 12, 2)->default(0); // withdrawal fee
            $table->decimal('net_amount', 12, 2); // amount after charge

            // Payment method info
            $table->enum('payment_method', [ 'mobile', 'bank'])->nullable();
            // bKash, Nagad, Bank, Rocket etc

            // Bank fields (only for bank)
            $table->string('bank_name')->nullable();
            $table->string('account_holder_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('routing_number')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('swift_code')->nullable(); // optional for international banks

            // Status control
            $table->enum('status', ['pending', 'processing', 'paid', 'rejected', 'cancelled'])
                ->default('pending');

            // Optional admin note
            $table->text('admin_note')->nullable();
            $table->boolean('is_confirm')->default(false);

            // Who processed (admin)
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();

            // Timestamps
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
