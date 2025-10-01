<?php

// app/Models/UserActivityLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class UserActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'student_name',
        'subject',
        'month',
        'date',
        'old_values',
        'new_values',
        'description',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'date' => 'integer'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Static method to log activity
    public static function logActivity(array $data)
    {
        return self::create(array_merge($data, [
            'user_id' => auth()->id(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent()
        ]));
    }

    // Helper method to log lesson plan updates
    public static function logLessonPlanUpdate($action, $studentName, $subject, $month, $date, $oldValues, $newValues, $description = null)
    {
        return self::logActivity([
            'action' => $action,
            'entity_type' => 'lesson_plan',
            'student_name' => $studentName,
            'subject' => $subject,
            'month' => $month,
            'date' => $date,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description ?: self::generateDescription($action, $studentName, $subject, $month, $date, $oldValues, $newValues)
        ]);
    }

    // Helper method to log grade updates
    public static function logGradeUpdate($action, $studentName, $subject, $month, $date, $oldValues, $newValues, $description = null)
    {
        return self::logActivity([
            'action' => $action,
            'entity_type' => 'grade',
            'student_name' => $studentName,
            'subject' => $subject,
            'month' => $month,
            'date' => $date,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description ?: self::generateGradeDescription($action, $studentName, $subject, $month, $date, $oldValues, $newValues)
        ]);
    }

    // Generate human-readable descriptions
    private static function generateDescription($action, $studentName, $subject, $month, $date, $oldValues, $newValues)
    {
        switch ($action) {
            case 'update_last_completed_page':
                return "Updated last completed page for {$studentName} ({$subject}) on {$month} {$date} from {$oldValues['last_completed_page']} to {$newValues['last_completed_page']}";
            
            case 'update_level':
                return "Updated level for {$studentName} ({$subject}) on {$month} {$date} from '{$oldValues['level']}' to '{$newValues['level']}'";
            
            case 'update_repeats':
                return "Updated repeats for {$studentName} ({$subject}) on {$month} {$date}, page {$newValues['pages']} from {$oldValues['repeats']} to {$newValues['repeats']} repeats";
            
            case 'delete_student_data':
                return "Deleted all data for {$studentName} ({$subject}) for {$month}";
            
            default:
                return "Performed {$action} for {$studentName} ({$subject}) on {$month} {$date}";
        }
    }

    private static function generateGradeDescription($action, $studentName, $subject, $month, $date, $oldValues, $newValues)
    {
        switch ($action) {
            case 'save_grade':
                $page = $newValues['page'] ?? 'unknown page';
                $grade = $newValues['grade'] ?? 'no grade';
                return "Saved grade '{$grade}' for {$studentName} ({$subject}) on {$month} {$date}, page {$page}";
            
            case 'save_page_override':
                $page = $newValues['page'] ?? 'unknown page';
                $overridePage = $newValues['override_page'] ?? 'unknown override';
                return "Added page override for {$studentName} ({$subject}) on {$month} {$date}: page {$page} → page {$overridePage}";
            
            case 'remove_page_override':
                $page = $oldValues['page'] ?? 'unknown page';
                return "Removed page override for {$studentName} ({$subject}) on {$month} {$date}, page {$page}";
            
            default:
                return "Performed {$action} for {$studentName} ({$subject}) on {$month} {$date}";
        }
    }

    // Scope methods for filtering
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByStudent($query, $studentName)
    {
        return $query->where('student_name', $studentName);
    }

    public function scopeBySubject($query, $subject)
    {
        return $query->where('subject', $subject);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Accessor for formatted timestamp
    public function getFormattedTimestampAttribute()
    {
        return $this->created_at->format('M j, Y g:i A');
    }

    // Accessor for action label
    public function getActionLabelAttribute()
    {
        $labels = [
            'update_last_completed_page' => 'Updated Last Completed Page',
            'update_level' => 'Updated Level',
            'update_repeats' => 'Updated Repeats',
            'delete_student_data' => 'Deleted Student Data',
            'save_grade' => 'Saved Grade',
            'save_page_override' => 'Added Page Override',
            'remove_page_override' => 'Removed Page Override',
            'bulk_save_grades' => 'Bulk Saved Grades',
            'bulk_save_lesson_plan_changes' => 'Bulk Updated Lesson Plans'
        ];

        return $labels[$this->action] ?? ucwords(str_replace('_', ' ', $this->action));
    }
}