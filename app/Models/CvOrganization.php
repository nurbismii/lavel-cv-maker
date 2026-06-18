<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CvOrganization extends Model
{
    use HasFactory;

    protected $fillable = [
        'cv_profile_id',
        'organization_name',
        'role',
        'start_year',
        'end_year',
        'sort_order',
    ];

    public function cvProfile()
    {
        return $this->belongsTo(CvProfile::class);
    }
}
