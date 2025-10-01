<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageOverride extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'page_overrides';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'lesson_plan_id',
        'page_position',
        'original_page',
        'custom_page',
        'grade'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'lesson_plan_id' => 'integer',
        'page_position' => 'integer',
        'original_page' => 'integer',
        'custom_page' => 'integer',
        'grade' => 'decimal:2'
    ];

    /**
     * Get the lesson plan that owns the page override.
     */
    public function lessonPlan()
    {
        return $this->belongsTo(LessonPlan::class, 'lesson_plan_id');
    }

    /**
     * Scope to get overrides for a specific lesson plan.
     */
    public function scopeForLessonPlan($query, $lessonPlanId)
    {
        return $query->where('lesson_plan_id', $lessonPlanId);
    }

    /**
     * Scope to get overrides for a specific page position.
     */
    public function scopeForPagePosition($query, $pagePosition)
    {
        return $query->where('page_position', $pagePosition);
    }

    /**
     * Check if this page has been overridden (custom_page != original_page).
     */
    public function isPageOverridden()
    {
        return $this->custom_page !== $this->original_page;
    }

    /**
     * Check if this override has a grade.
     */
    public function hasGrade()
    {
        return $this->grade !== null;
    }

    /**
     * Get the grade status based on the grade value.
     */
    public function getGradeStatus()
    {
        if ($this->grade === null) {
            return 'empty';
        }
        
        return $this->grade >= 7 ? 'completed' : 'partial';
    }
}