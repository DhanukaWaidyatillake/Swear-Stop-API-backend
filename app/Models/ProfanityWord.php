<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfanityWord extends Model
{
    use HasFactory;

    protected $table = 'profanity_dataset';

    protected $fillable = ['word_1', 'word_2', 'word_3', 'profanity_category_id'];

    public function profanity_category()
    {
        return $this->belongsTo(ProfanityCategory::class, 'profanity_category_id');
    }
}
