<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CvAchievement extends Model
{
    use HasFactory;

    public const FIELD_OPTIONS = [
        'academic' => 'Akademik',
        'non_academic' => 'Non-akademik',
        'sports' => 'Olahraga',
        'arts' => 'Seni',
        'other' => 'Lainnya',
    ];

    public const LEVEL_OPTIONS = [
        'internal' => 'Internal Perusahaan/Sekolah',
        'district' => 'Kecamatan',
        'city' => 'Kabupaten/Kota',
        'province' => 'Provinsi',
        'national' => 'Nasional',
        'international' => 'Internasional',
        'other' => 'Lainnya',
    ];

    protected $fillable = [
        'cv_profile_id',
        'field',
        'other_field',
        'achievement_type',
        'rank',
        'level',
        'other_level',
        'period',
        'sort_order',
    ];

    public static function fieldLabel(?string $value): ?string
    {
        return self::FIELD_OPTIONS[$value] ?? null;
    }

    public static function levelLabel(?string $value): ?string
    {
        return self::LEVEL_OPTIONS[$value] ?? null;
    }

    public function cvProfile()
    {
        return $this->belongsTo(CvProfile::class);
    }
}
