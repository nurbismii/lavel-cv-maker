<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CvExperience extends Model
{
    use HasFactory;

    protected $fillable = [
        'cv_profile_id',
        'position',
        'company',
        'department',
        'division',
        'start_month',
        'end_month',
        'is_current',
        'responsibilities',
        'sort_order',
    ];

    protected $casts = [
        'start_month' => 'date',
        'end_month' => 'date',
        'is_current' => 'boolean',
        'responsibilities' => 'array',
    ];

    public function cvProfile()
    {
        return $this->belongsTo(CvProfile::class);
    }
}
