<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CvCertification extends Model
{
    use HasFactory;

    public const TYPE_CERTIFICATION = 'Sertifikasi';
    public const TYPE_TRAINING = 'Pelatihan';

    protected $fillable = [
        'cv_profile_id',
        'name',
        'issuer',
        'year',
        'valid_until_year',
        'is_lifetime',
        'type',
        'sort_order',
    ];

    protected $casts = [
        'is_lifetime' => 'boolean',
    ];

    public function cvProfile()
    {
        return $this->belongsTo(CvProfile::class);
    }
}
