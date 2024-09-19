<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\TokenManagerService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'free_request_count',
        'is_active',
        'user_inactivity_message'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function createToken(TokenManagerService $tokenManagerService): bool
    {
        $str = Str::random(60);
        ApiToken::create([
            'user_id' => $this->id,
            'encrypted_api_key' => $tokenManagerService->encrypt($str)
        ]);
        return true;
    }

    public function apiKeys(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ApiToken::class);
    }
}
