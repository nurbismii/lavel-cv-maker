<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CvEmergencyContact extends Model
{
    use HasFactory;

    public const RELATIONSHIPS = [
        'Orang Tua',
        'Suami/Istri',
        'Anak',
        'Saudara',
        'Kerabat',
        'Teman Dekat',
        'Rekan Kerja',
        'Tetangga',
        'Lainnya',
    ];

    protected $fillable = [
        'cv_profile_id',
        'phone',
        'name',
        'relationship',
        'sort_order',
    ];

    public function cvProfile()
    {
        return $this->belongsTo(CvProfile::class);
    }
}
