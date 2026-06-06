<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Auth\App\Models\Profile;

class User extends Authenticatable
{
    use HasApiTokens, HasRoles, Notifiable;

    protected $guard_name = 'sanctum';

    protected $fillable = [
        'name',
        'email',
        'firebase_uid',
        'password',
    ];

    protected $hidden = [
        'remember_token',
    ];
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }
    public function address()
    {
        return $this->hasMany(\Modules\User\App\Models\Address::class);
    }

    public function wishlists()
    {
        return $this->hasMany(\Modules\Wishlist\App\Models\Wishlist::class);
    }
}
