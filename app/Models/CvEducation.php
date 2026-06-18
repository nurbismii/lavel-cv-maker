<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CvEducation extends Model
{
    use HasFactory;

    protected $table = 'cv_educations';

    protected $fillable = [
        'cv_profile_id',
        'level',
        'institution',
        'major',
        'graduation_year',
        'sort_order',
    ];

    public function cvProfile()
    {
        return $this->belongsTo(CvProfile::class);
    }
}
