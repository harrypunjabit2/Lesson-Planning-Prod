<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class LessonPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'month',
        'date',
        'subject',
        'level',
        'worksheet',
        'student_first_name',
        'student_last_name',
        'new_concept',
        'is_class_day',
        'year',
        'last_completed_page',
        'repeats',
        'repeat_pages',
        'col_1', 'col_2', 'col_3', 'col_4', 'col_5',
        'col_6', 'col_7', 'col_8', 'col_9', 'col_10',
        'input_field','hw_completed','time','is_test_day','is_skipped'
    ];

    protected $casts = [
        'date' => 'integer',
        'year' => 'integer',
        'worksheet' => 'integer',
        'last_completed_page' => 'integer',
        'repeats' => 'integer',
    ];

    public function studentConfig()
    {
        return $this->belongsTo(StudentConfig::class, ['student_first_name', 'student_last_name', 'subject'], ['student_first_name', 'student_last_name', 'subject']);
    }

    public function getFullNameAttribute()
    {
        return trim($this->student_first_name . ' ' . $this->student_last_name);
    }

    public function getAssignmentTypeAttribute()
    {
        return $this->is_class_day === 'Y' ? 'CW' : 'HW';
    }

    public function getIsClassDayBooleanAttribute()
    {
        return $this->is_class_day === 'Y';
    }

    public function getIsNewConceptBooleanAttribute()
    {
        return $this->new_concept === 'Y';
    }

    public function getDateObjectAttribute()
    {
        $monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        
        $monthIndex = array_search($this->month, $monthNames);
        if ($monthIndex !== false) {
            return Carbon::create($this->year, $monthIndex + 1, $this->date);
        }
        
        return null;
    }

    public function getIsInPastAttribute()
    {
        $dateObj = $this->date_object;
        return $dateObj && $dateObj->isPast();
    }

    public static function wrapWorksheetNumber($worksheet)
    {
        if ($worksheet > 200) {
            return (($worksheet - 1) % 200) + 1;
        }
        return $worksheet;
    }

    public function scopeForStudent($query, $firstName, $lastName = null)
    {
        $query->where('student_first_name', $firstName);
        if ($lastName) {
            $query->where('student_last_name', $lastName);
        }
        return $query;
    }

    public function scopeForSubject($query, $subject)
    {
        return $query->where('subject', $subject);
    }

    public function scopeForMonth($query, $month, $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }

    public function scopeOrderByDate($query)
    {
        return $query->orderBy('year')->orderBy('month')->orderBy('date');
    }
    public function grading()
{
    return $this->hasMany(Grading::class, 'lessonplanid', 'id');
}
}
