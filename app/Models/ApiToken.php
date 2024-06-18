<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiToken extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'encrypted_api_key'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
