<?php

namespace Modules\Auth\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Otp extends Model
{
    protected $table = 'otps';

    protected $fillable = [
        'phone',
        'email',
        'otp',
        'expired_at',
        'purpose',
    ];

    protected $casts = [
        'expired_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return now()->greaterThan($this->expired_at);
    }
}
