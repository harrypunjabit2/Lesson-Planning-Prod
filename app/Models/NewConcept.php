<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NewConcept extends Model
{
    use HasFactory;

    protected $fillable = [
        'level',
        'subject',
        'worksheet',
        'is_new_concept'
    ];

    protected $casts = [
        'worksheet' => 'integer',
    ];

    public function getIsNewConceptBooleanAttribute()
    {
        return $this->is_new_concept === 'Y';
    }

    public function scopeForLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeForSubject($query, $subject)
    {
        return $query->where('subject', $subject);
    }

    public function scopeNewConcepts($query)
    {
        return $query->where('is_new_concept', 'Y');
    }
}
