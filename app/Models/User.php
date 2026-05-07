<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'user_id', 'phone', 'password', 'role',
        'dob', 'gender', 'blood_group', 'national_id', 'religion', 'is_active',
        'present_address', 'permanent_address', 'photo', 'wallet_balance',
        'refer_id','rank', 'is_match',
        'parent_id', 'left_child_id', 'right_child_id', 
        
        'left_total_point', 'right_total_point',
        'left_carry_point', 'right_carry_point',
        'own_total_point', 'total_match'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'bonus_balance',
        'total_points',
        'total_own_points',
        'total_calculation',
    ];

    protected static function booted()
    {
        static::creating(function ($user) {
            if (empty($user->password)) {
                $user->password = Hash::make('password');
                // $user->password = Hash::make(bin2hex(random_bytes(4)));
            }

            // $attempt = 0;
            // do {
            //     $userId = 'DBMBL00' . strtoupper(Str::random(10));
            //     $attempt++;
            // } while (User::where('user_id', $userId)->exists() && $attempt < 5);
            // $user->user_id = $userId;
        });
    }

    protected $casts = [
        'email_verified_at' => 'datetime',
        'wallet_balance' => 'decimal:2',
        'dob' => 'date',
        'is_active' => 'boolean',
        'left_total_point' => 'integer',
        'right_total_point' => 'integer',
        'own_total_point' => 'integer', 
        'total_match' => 'integer',     
    ];

    // --- Relationships ---
    public function parent() { return $this->belongsTo(User::class, 'parent_id'); }
    
    public function children() { return $this->hasMany(User::class, 'parent_id')->with('children'); }

    public function leftChild() { return $this->belongsTo(User::class, 'left_child_id')->with(['leftChild', 'rightChild']); }

    public function rightChild() { return $this->belongsTo(User::class, 'right_child_id')->with(['leftChild', 'rightChild']); }

    public function referrer() { return $this->belongsTo(User::class, 'refer_id'); }

    public function referrals() { return $this->hasMany(User::class, 'refer_id'); }

    public function pointTransactions() { return $this->hasMany(PointTransaction::class); }

    public function orders() { return $this->hasMany(Order::class, 'user_id'); }

    // --- Accessors (Calculated Fields) ---

    /**
     * 1. Bonus Balance (Amount)
     * ক্যালকুলেশন: মোট ডিপোজিট বোনাস - মোট উইথড্র বোনাস
     */
    public function getBonusBalanceAttribute()
    {
        $credit = $this->pointTransactions()
            ->where('bonus_status', 'credit')
            ->sum('bonus_amount') ?? 0;
            
        $debit = $this->pointTransactions()
            ->where('bonus_status', 'debit')
            ->sum('bonus_amount') ?? 0;

        return number_format($credit - $debit, 2, '.', '');
    }

    /**
     * 2. Total Points
     * ট্রানজেকশন টেবিল থেকে সর্বমোট পয়েন্টের যোগফল
     */
    public function getTotalPointsAttribute()
    {
        return (int) ($this->pointTransactions()->sum('points') ?? 0);
    }

    /**
     * 3. Total Points (Accessor)
     * এটি এখন সরাসরি ডাটাবেসের own_total_point রিটার্ন করবে
     */
    public function getTotalOwnPointsAttribute()
    {
        return (int) ($this->own_total_point ?? 0);
    }

    /**
     * 4. Account is_active observe codition ar jonno
     */
    public function getTotalCalculationAttribute()
    {
        return (int) (
            ($this->left_total_point ?? 0) + 
            ($this->right_total_point ?? 0) + 
            ($this->own_total_point ?? 0)
        );
    }

}
