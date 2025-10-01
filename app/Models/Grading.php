<?php

// app/Models/Grading.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Grading extends Model
{
    use HasFactory;

    protected $table = 'grading';

    protected $fillable = [
        'lessonplanid',
        'newpage',
        'grade',
        'time',
        'planned_page_position'
    ];

    protected $casts = [
        'grade' => 'float',
    ];

    /**
     * Relationship with LessonPlan
     */
    public function lessonPlan()
    {
        return $this->belongsTo(LessonPlan::class, 'lessonplanid', 'id');
    }
}