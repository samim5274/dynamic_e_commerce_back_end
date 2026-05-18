<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'user_id',
        'amount',
        'charge',
        'net_amount',
        'payment_method',

        'bank_name',
        'account_holder_name',
        'account_number',
        'routing_number',
        'branch_name',
        'swift_code',

        'status',
        'admin_note',
        'is_confirm',
        'processed_by',
        'requested_at',
        'processed_at',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'charge'        => 'decimal:2',
        'net_amount'    => 'decimal:2',
        'requested_at'  => 'datetime',
        'processed_at'  => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // User who created transaction
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Admin who processed transaction
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes (Filtering easy for controller)
    |--------------------------------------------------------------------------
    */

    // ব্যবহার: Transaction::pending()->get();
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // ব্যবহার: Transaction::processing()->get();
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    // ব্যবহার: Transaction::paid()->get();
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    // ব্যবহার: Transaction::rejected()->get();
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    // ব্যবহার: if ($transaction->isPending()) { ... }
    public function isPending()
    {
        return $this->status === 'pending';
    }

    // ব্যবহার: if ($transaction->isPaid()) { ... }
    public function isPaid()
    {
        return $this->status === 'paid';
    }

    // ব্যবহার: if ($transaction->isRejected()) { ... }
    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    // ব্যবহার: if ($transaction->isBankTransfer()) { ... }
    public function isBankTransfer()
    {
        return $this->payment_method === 'bank';
    }

    // ব্যবহার: if ($transaction->isWallet()) { ... }
    public function isWallet()
    {
        return in_array($this->payment_method, ['bkash', 'nagad', 'rocket']);
    }
}
