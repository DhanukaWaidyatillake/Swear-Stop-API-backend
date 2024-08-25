<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TextFilterAudit extends Model
{
    use HasFactory;

    protected $table = 'text_filter_audits';

    protected $fillable = [
        'user_id',
        'request_body',
        'response_body',
        'is_successful',
        'is_free_request'
    ];
}
