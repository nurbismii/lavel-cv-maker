<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CvProfile extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_GENERATED = 'generated';

    protected $fillable = [
        'user_id',
        'status',
        'full_name',
        'photo_path',
        'birth_place',
        'birth_date',
        'gender',
        'marital_status',
        'province_id',
        'province_name',
        'regency_id',
        'regency_name',
        'district_id',
        'district_name',
        'village_id',
        'village_name',
        'address',
        'phone',
        'email',
        'work_area',
        'department',
        'division',
        'position',
        'profile_summary',
        'technical_skills',
        'non_technical_skills',
        'last_generated_at',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'technical_skills' => 'array',
        'non_technical_skills' => 'array',
        'last_generated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function experiences()
    {
        return $this->hasMany(CvExperience::class)->orderBy('sort_order');
    }

    public function educations()
    {
        return $this->hasMany(CvEducation::class);
    }

    public function certifications()
    {
        return $this->hasMany(CvCertification::class)->orderBy('sort_order');
    }

    public function languages()
    {
        return $this->hasMany(CvLanguage::class)->orderBy('sort_order');
    }

    public function projects()
    {
        return $this->hasMany(CvProject::class)->orderBy('sort_order');
    }

    public function organizations()
    {
        return $this->hasMany(CvOrganization::class)->orderBy('sort_order');
    }
}
