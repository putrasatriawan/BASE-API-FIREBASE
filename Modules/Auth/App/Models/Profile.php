<?php

namespace Modules\Auth\App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'username',
        'phone',
        'avatar',
        'birth_date',
        'gender',
    ];

    protected $appends = [
        'avatar_url'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get full avatar URL from S3
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return null;
        }

        if (str_starts_with($this->avatar, 'http')) {
            return $this->avatar;
        }

        return app(\App\Services\S3ImageService::class)->getImageUrl($this->avatar);
    }
}
