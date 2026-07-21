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
    public const TYPE_NPWP = 'npwp';
    public const TYPE_VACCINATION_CERTIFICATE = 'vaccination_certificate';
    public const TYPE_BIRTH_CERTIFICATE = 'birth_certificate';
    public const TYPE_MARRIAGE_BOOK = 'marriage_book';
    public const TYPE_DIVORCE_CERTIFICATE = 'divorce_certificate';
    public const TYPE_SIM_B2_UMUM = 'sim_b2_umum';
    public const TYPE_SIO = 'sio';
    public const TYPE_K3_CERTIFICATE = 'k3_certificate';
    public const TYPE_SECURITY_KTA = 'security_kta';

    public const TYPES = [
        self::TYPE_KTP => 'KTP',
        self::TYPE_FAMILY_CARD => 'Kartu Keluarga',
        self::TYPE_DIPLOMA => 'Ijazah',
        self::TYPE_CERTIFICATE => 'Sertifikat / Pelatihan',
        self::TYPE_WORK_EXPERIENCE => 'Pengalaman Kerja / Paklaring',
        self::TYPE_OTHER => 'Dokumen Lainnya',
        self::TYPE_NPWP => 'NPWP',
        self::TYPE_VACCINATION_CERTIFICATE => 'Sertifikat Vaksin',
        self::TYPE_BIRTH_CERTIFICATE => 'Akta Kelahiran',
        self::TYPE_MARRIAGE_BOOK => 'Buku Nikah',
        self::TYPE_DIVORCE_CERTIFICATE => 'Surat Cerai',
        self::TYPE_SIM_B2_UMUM => 'SIM B2 Umum',
        self::TYPE_SIO => 'SIO',
        self::TYPE_K3_CERTIFICATE => 'Sertifikat K3',
        self::TYPE_SECURITY_KTA => 'KTA Security',
    ];

    public const MULTIPLE_FILE_TYPES = [
        self::TYPE_DIPLOMA,
        self::TYPE_WORK_EXPERIENCE,
        self::TYPE_CERTIFICATE,
        self::TYPE_K3_CERTIFICATE,
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

    public static function acceptsMultipleFiles(string $type): bool
    {
        return in_array($type, self::MULTIPLE_FILE_TYPES, true);
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
                'group' => 'administrative',
            ],
            self::TYPE_FAMILY_CARD => [
                'label' => self::TYPES[self::TYPE_FAMILY_CARD],
                'description' => 'Upload KK terbaru untuk kebutuhan administrasi HR.',
                'required' => true,
                'group' => 'administrative',
            ],
            self::TYPE_DIPLOMA => [
                'label' => self::TYPES[self::TYPE_DIPLOMA],
                'description' => 'Upload ijazah pendidikan terakhir.',
                'required' => true,
                'group' => 'administrative',
            ],
            self::TYPE_CERTIFICATE => [
                'label' => self::TYPES[self::TYPE_CERTIFICATE],
                'description' => 'Upload sertifikat atau pelatihan pendukung. Gabungkan dalam satu PDF jika lebih dari satu.',
                'required' => false,
                'group' => 'skills',
            ],
            self::TYPE_WORK_EXPERIENCE => [
                'label' => self::TYPES[self::TYPE_WORK_EXPERIENCE],
                'description' => 'Upload paklaring atau surat pengalaman kerja jika tersedia.',
                'required' => false,
                'group' => 'administrative',
            ],
            self::TYPE_OTHER => [
                'label' => self::TYPES[self::TYPE_OTHER],
                'description' => 'Upload dokumen tambahan jika diminta HR.',
                'required' => false,
                'group' => 'administrative',
            ],
            self::TYPE_NPWP => [
                'label' => self::TYPES[self::TYPE_NPWP],
                'description' => 'Upload kartu atau dokumen NPWP untuk administrasi perpajakan.',
                'required' => true,
                'group' => 'administrative',
            ],
            self::TYPE_VACCINATION_CERTIFICATE => [
                'label' => self::TYPES[self::TYPE_VACCINATION_CERTIFICATE],
                'description' => 'Upload sertifikat vaksin yang masih dapat diverifikasi.',
                'required' => true,
                'group' => 'administrative',
            ],
            self::TYPE_BIRTH_CERTIFICATE => [
                'label' => self::TYPES[self::TYPE_BIRTH_CERTIFICATE],
                'description' => 'Upload scan atau foto akta kelahiran yang terbaca jelas.',
                'required' => true,
                'group' => 'administrative',
            ],
            self::TYPE_MARRIAGE_BOOK => [
                'label' => self::TYPES[self::TYPE_MARRIAGE_BOOK],
                'description' => 'Upload buku nikah jika diperlukan untuk administrasi keluarga.',
                'required' => false,
                'group' => 'administrative',
            ],
            self::TYPE_DIVORCE_CERTIFICATE => [
                'label' => self::TYPES[self::TYPE_DIVORCE_CERTIFICATE],
                'description' => 'Upload surat cerai jika diperlukan untuk administrasi keluarga.',
                'required' => false,
                'group' => 'administrative',
            ],
            self::TYPE_SIM_B2_UMUM => [
                'label' => self::TYPES[self::TYPE_SIM_B2_UMUM],
                'description' => 'Upload SIM B2 Umum yang masih berlaku jika dimiliki.',
                'required' => false,
                'group' => 'skills',
            ],
            self::TYPE_SIO => [
                'label' => self::TYPES[self::TYPE_SIO],
                'description' => 'Upload Surat Izin Operator (SIO) yang masih berlaku jika dimiliki.',
                'required' => false,
                'group' => 'skills',
            ],
            self::TYPE_K3_CERTIFICATE => [
                'label' => self::TYPES[self::TYPE_K3_CERTIFICATE],
                'description' => 'Upload sertifikat K3 yang masih berlaku jika dimiliki.',
                'required' => false,
                'group' => 'skills',
            ],
            self::TYPE_SECURITY_KTA => [
                'label' => self::TYPES[self::TYPE_SECURITY_KTA],
                'description' => 'Upload KTA Security yang masih berlaku jika dimiliki.',
                'required' => false,
                'group' => 'skills',
            ],
        ];
    }

    public function cvProfile()
    {
        return $this->belongsTo(CvProfile::class);
    }
}
