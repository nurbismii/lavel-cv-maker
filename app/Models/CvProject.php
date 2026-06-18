<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CvProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'cv_profile_id',
        'name',
        'year',
        'sort_order',
    ];

    public function cvProfile()
    {
        return $this->belongsTo(CvProfile::class);
    }
}
