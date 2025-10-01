<?php

// app/Models/SubjectLevel.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectLevel extends Model
{
    use HasFactory;

    protected $table = 'subject_levels';

    protected $fillable = [
        'subject',
        'level'
    ];

    public $timestamps = true;

    /**
     * Get the next level for a given subject and current level
     * 
     * @param string $subject
     * @param string $currentLevel
     * @return string|null
     */
    public static function getNextLevel(string $subject, string $currentLevel)
    {
        // Get all levels for this subject ordered by level
        $levels = self::where('subject', $subject)
            ->orderBy('level')
            ->pluck('level')
            ->toArray();

        $currentIndex = array_search($currentLevel, $levels);
        
        if ($currentIndex === false) {
            return null; // Current level not found
        }

        // Return next level if it exists
        if (isset($levels[$currentIndex + 1])) {
            return $levels[$currentIndex + 1];
        }

        return null; // Already at highest level
    }

    /**
     * Check if a level exists for a subject
     * 
     * @param string $subject
     * @param string $level
     * @return bool
     */
    public static function levelExists(string $subject, string $level): bool
    {
        return self::where('subject', $subject)
            ->where('level', $level)
            ->exists();
    }

    /**
     * Get all levels for a subject
     * 
     * @param string $subject
     * @return array
     */
    public static function getLevelsForSubject(string $subject): array
    {
        return self::where('subject', $subject)
            ->orderBy('level')
            ->pluck('level')
            ->toArray();
    }
}