<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseContext extends Model
{
    protected $fillable = [
        'course_id',
        'context_type',
        'source_id',
        'content',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
