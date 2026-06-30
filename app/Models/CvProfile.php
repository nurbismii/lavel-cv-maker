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

    public const BLOOD_TYPES = ['A 型', 'B 型', 'AB 型', 'O 型'];

    public const RELIGIONS = [
        'ISLAM 伊斯兰教',
        'KRISTEN PROTESTAN 基督教新教',
        'BUDHA 佛教',
        'KRISTEN KATHOLIK 天主教徒',
        'HINDU 印度教',
        'KHONGHUCU 儒教',
    ];

    protected $fillable = [
        'user_id',
        'status',
        'full_name',
        'photo_path',
        'birth_place',
        'birth_date',
        'ktp_number',
        'family_card_number',
        'gender',
        'height_cm',
        'weight_kg',
        'blood_type',
        'religion',
        'marital_status',
        'marriage_date',
        'spouse_name',
        'mother_name',
        'has_children',
        'children_names',
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
        'current_job_entry_date',
        'profile_summary',
        'technical_skills',
        'non_technical_skills',
        'last_generated_at',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'marriage_date' => 'date',
        'current_job_entry_date' => 'date',
        'height_cm' => 'integer',
        'weight_kg' => 'decimal:2',
        'has_children' => 'boolean',
        'children_names' => 'array',
        'technical_skills' => 'array',
        'non_technical_skills' => 'array',
        'last_generated_at' => 'datetime',
    ];

    public static function normalizeReligion($value): ?string
    {
        $value = trim(preg_replace('/\s+/', ' ', (string) $value));

        if ($value === '') {
            return null;
        }

        foreach (self::RELIGIONS as $religion) {
            if (strcasecmp($value, $religion) === 0) {
                return $religion;
            }
        }

        $upperValue = strtoupper($value);

        if (strpos($upperValue, 'ISLAM') !== false) {
            return 'ISLAM 伊斯兰教';
        }

        if (strpos($upperValue, 'KATHOLIK') !== false || strpos($upperValue, 'KATOLIK') !== false) {
            return 'KRISTEN KATHOLIK 天主教徒';
        }

        if (strpos($upperValue, 'PROTESTAN') !== false || strpos($upperValue, 'KRISTEN') !== false) {
            return 'KRISTEN PROTESTAN 基督教新教';
        }

        if (strpos($upperValue, 'BUDDHA') !== false || strpos($upperValue, 'BUDHA') !== false) {
            return 'BUDHA 佛教';
        }

        if (strpos($upperValue, 'HINDU') !== false) {
            return 'HINDU 印度教';
        }

        if (strpos($upperValue, 'KHONGHUCU') !== false || strpos($upperValue, 'KONGHUCU') !== false) {
            return 'KHONGHUCU 儒教';
        }

        return null;
    }

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

    public function emergencyContacts()
    {
        return $this->hasMany(CvEmergencyContact::class)->orderBy('sort_order');
    }

    public function documents()
    {
        return $this->hasMany(CvDocument::class)->orderBy('type');
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
