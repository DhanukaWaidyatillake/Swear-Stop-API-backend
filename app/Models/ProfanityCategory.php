<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfanityCategory extends Model
{
    use HasFactory;

    protected $table = 'profanity_categories';

    protected $fillable = ['profanity_category_code', 'profanity_category_name'];
}
