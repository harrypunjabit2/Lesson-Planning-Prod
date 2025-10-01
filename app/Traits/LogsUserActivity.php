<?php

// app/Traits/LogsUserActivity.php

namespace App\Traits;

use App\Models\UserActivityLog;

trait LogsUserActivity
{
    /**
     * Log a lesson plan update activity
     */
    protected function logLessonPlanActivity($action, $studentName, $subject, $month, $date, $oldValues, $newValues, $description = null)
    {
        // Only log if user is authenticated and has a role that can make changes
        if (auth()->check() && (auth()->user()->canEdit() || auth()->user()->canGrade())) {
            UserActivityLog::logLessonPlanUpdate(
                $action,
                $studentName,
                $subject,
                $month,
                $date,
                $oldValues,
                $newValues,
                $description
            );
        }
    }

    /**
     * Log a grading activity
     */
    protected function logGradingActivity($action, $studentName, $subject, $month, $date, $oldValues, $newValues, $description = null)
    {
        // Only log if user is authenticated and can grade
        if (auth()->check() && auth()->user()->canGrade()) {
            UserActivityLog::logGradeUpdate(
                $action,
                $studentName,
                $subject,
                $month,
                $date,
                $oldValues,
                $newValues,
                $description
            );
        }
    }

    /**
     * Log a general activity
     */
    protected function logActivity($action, $entityType = null, $entityId = null, $oldValues = [], $newValues = [], $description = null)
    {
        if (auth()->check()) {
            UserActivityLog::logActivity([
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'description' => $description
            ]);
        }
    }
}