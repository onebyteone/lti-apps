<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'course_id',
        'user_id',
        'title',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
