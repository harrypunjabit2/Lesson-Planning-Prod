<?php

// app/Models/StudentConfig.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_first_name',
        'student_last_name',
        'subject',
        'class_day_1',
        'class_day_2',
        'pattern',
        'level',
        'month',
        'year'
    ];

    protected $casts = [
        'year' => 'integer',
    ];

    public function lessonPlans()
    {
        return $this->hasMany(LessonPlan::class, ['student_first_name', 'student_last_name', 'subject'], ['student_first_name', 'student_last_name', 'subject']);
    }

    public function getFullNameAttribute()
    {
        return trim($this->student_first_name . ' ' . $this->student_last_name);
    }

    public function getDisplayNameAttribute()
    {
        return $this->full_name . ' - ' . $this->subject;
    }

    public function getPatternValuesAttribute()
    {
        return array_map('intval', explode(':', $this->pattern));
    }
}