<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhitelistedWord extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime:Y-m-d h:m:s',
        ];
    }
}
