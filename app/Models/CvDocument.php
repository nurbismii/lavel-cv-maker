<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CvDocument extends Model
{
    use HasFactory;

    public const TYPE_KTP = 'ktp';
    public const TYPE_FAMILY_CARD = 'family_card';
    public const TYPE_DIPLOMA = 'diploma';
    public const TYPE_CERTIFICATE = 'certificate';
    public const TYPE_WORK_EXPERIENCE = 'work_experience';
    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_KTP => 'KTP',
        self::TYPE_FAMILY_CARD => 'Kartu Keluarga',
        self::TYPE_DIPLOMA => 'Ijazah',
        self::TYPE_CERTIFICATE => 'Sertifikat / Pelatihan',
        self::TYPE_WORK_EXPERIENCE => 'Pengalaman Kerja / Paklaring',
        self::TYPE_OTHER => 'Dokumen Lainnya',
    ];

    protected $fillable = [
        'cv_profile_id',
        'type',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public static function allowedTypes(): array
    {
        return array_keys(self::TYPES);
    }

    public static function isAllowedType(string $type): bool
    {
        return array_key_exists($type, self::TYPES);
    }

    public static function labelFor(?string $type): string
    {
        return self::TYPES[$type] ?? 'Dokumen';
    }

    public static function documentOptions(): array
    {
        return [
            self::TYPE_KTP => [
                'label' => self::TYPES[self::TYPE_KTP],
                'description' => 'Upload scan/foto KTP yang jelas untuk verifikasi data pribadi.',
                'required' => true,
            ],
            self::TYPE_FAMILY_CARD => [
                'label' => self::TYPES[self::TYPE_FAMILY_CARD],
                'description' => 'Upload KK terbaru untuk kebutuhan administrasi HR.',
                'required' => true,
            ],
            self::TYPE_DIPLOMA => [
                'label' => self::TYPES[self::TYPE_DIPLOMA],
                'description' => 'Upload ijazah pendidikan terakhir.',
                'required' => true,
            ],
            self::TYPE_CERTIFICATE => [
                'label' => self::TYPES[self::TYPE_CERTIFICATE],
                'description' => 'Upload sertifikat atau pelatihan pendukung. Gabungkan dalam satu PDF jika lebih dari satu.',
                'required' => false,
            ],
            self::TYPE_WORK_EXPERIENCE => [
                'label' => self::TYPES[self::TYPE_WORK_EXPERIENCE],
                'description' => 'Upload paklaring atau surat pengalaman kerja jika tersedia.',
                'required' => false,
            ],
            self::TYPE_OTHER => [
                'label' => self::TYPES[self::TYPE_OTHER],
                'description' => 'Upload dokumen tambahan jika diminta HR.',
                'required' => false,
            ],
        ];
    }

    public function cvProfile()
    {
        return $this->belongsTo(CvProfile::class);
    }
}

