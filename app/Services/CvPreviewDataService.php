<?php

namespace App\Services;

use App\Models\CvEducation;
use App\Models\CvAchievement;
use App\Models\CvProfile;
use App\Models\User;
use App\Support\CvResponsibilityRichText;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class CvPreviewDataService
{
    private const EDUCATION_RANKS = [
        'SD' => 10,
        'SMP' => 20,
        'SMA' => 30,
        'SMK' => 30,
        'D1' => 40,
        'D2' => 50,
        'D3' => 60,
        'D4' => 70,
        'S1' => 80,
        'S2' => 90,
        'S3' => 100,
    ];

    private const MONTHS = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public function build(CvProfile $profile, User $user): array
    {
        return [
            'nik' => $this->vpeopleNik($user),
            'birth_date' => $this->formatDate($profile->birth_date),
            'gender' => $this->genderLabel($profile->gender),
            'position' => $this->cleanLabel($profile->position),
            'department' => $this->cleanLabel($profile->department),
            'division' => $this->cleanLabel($profile->division),
            'address' => $this->address($profile),
            'addresses' => $this->addresses($profile),
            'photo_url' => $this->photoUrl($profile),
            'photo_data_uri' => $this->photoDataUri($profile),
            'experiences' => $this->experiences($profile),
            'educations' => $this->educations($profile),
            'certifications' => $this->certifications($profile),
            'languages' => $this->languages($profile),
            'projects' => $this->projects($profile),
            'organizations' => $this->organizations($profile),
            'hobbies' => $this->interests($profile->hobbies ?: [], $profile->other_hobby),
            'talents' => $this->interests($profile->talents ?: [], $profile->other_talent),
            'achievements' => $this->achievements($profile),
            'technical_skills' => $this->cleanList($profile->technical_skills ?: []),
            'non_technical_skills' => $this->cleanList($profile->non_technical_skills ?: []),
        ];
    }

    private function photoUrl(CvProfile $profile): ?string
    {
        if (!$profile->photo_path || !Storage::disk('local')->exists($profile->photo_path)) {
            return null;
        }

        return route('cv.photo.show') . '?v=' . optional($profile->updated_at)->timestamp;
    }

    private function photoDataUri(CvProfile $profile): ?string
    {
        if (!$profile->photo_path || !Storage::disk('local')->exists($profile->photo_path)) {
            return null;
        }

        $mimeType = Storage::disk('local')->mimeType($profile->photo_path) ?: 'image/jpeg';
        $contents = Storage::disk('local')->get($profile->photo_path);

        return 'data:' . $mimeType . ';base64,' . base64_encode($contents);
    }

    private function address(CvProfile $profile): ?string
    {
        return $this->displayAddress($profile->address);
    }

    private function location(CvProfile $profile): ?string
    {
        $location = $this->cleanList([
            $profile->village_name,
            $profile->district_name,
            $profile->regency_name,
            $profile->province_name,
        ]);

        return count($location) ? implode(', ', $location) : null;
    }

    private function addresses(CvProfile $profile): array
    {
        $domicileAddress = $this->displayAddress($profile->address);
        $location = $this->location($profile);
        $ktp = $this->displayAddress($profile->ktp_address);
        $addresses = [];

        if ($domicileAddress && $ktp && !$profile->domicile_same_as_ktp && $this->normalizedAddress($ktp) !== $this->normalizedAddress($domicileAddress)) {
            $addresses[] = [
                'label' => 'Alamat KTP',
                'value' => $location ? $ktp . "\n" . $location : $ktp,
            ];
        }

        if ($domicileAddress) {
            $addresses[] = [
                'label' => 'Alamat Domisili',
                'value' => $domicileAddress,
            ];
        }

        return $addresses;
    }

    private function displayAddress($value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = preg_replace('/[\x{3400}-\x{9FFF}\x{F900}-\x{FAFF}]+/u', '', (string) $value);
        $lines = preg_split('/\R/u', $value);
        $lines = array_values(array_filter(array_map(function ($line) {
            return trim(preg_replace('/\s+/', ' ', $line));
        }, $lines)));

        return count($lines) ? implode("\n", $lines) : null;
    }

    private function normalizedAddress(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        return strtolower(preg_replace('/\s+/', ' ', trim($value)));
    }

    private function experiences(CvProfile $profile): array
    {
        $profileDepartment = $this->cleanLabel($profile->department);
        $profileDivision = $this->cleanLabel($profile->division);

        return $profile->experiences
            ->sortByDesc(function ($experience) {
                if ($experience->is_current) {
                    return now()->timestamp;
                }

                return optional($experience->end_month ?: $experience->start_month)->timestamp ?: 0;
            })
            ->map(function ($experience) use ($profileDepartment, $profileDivision) {
                $responsibilitiesHtml = CvResponsibilityRichText::toOutputHtml($experience->responsibilities ?: []);

                return [
                    'position' => $this->cleanLabel($experience->position),
                    'company' => $this->cleanLabel($experience->company),
                    'department' => $this->cleanLabel($experience->department) ?: $profileDepartment,
                    'division' => $this->cleanLabel($experience->division) ?: $profileDivision,
                    'period' => $this->period($experience->start_month, $experience->end_month, $experience->is_current),
                    'responsibilities_html' => $responsibilitiesHtml,
                ];
            })
            ->filter(function ($experience) {
                return $experience['position'] || $experience['company'] || $experience['period'] || $experience['responsibilities_html'];
            })
            ->values()
            ->toArray();
    }

    private function educations(CvProfile $profile): array
    {
        return $profile->educations
            ->sortByDesc(function (CvEducation $education) {
                return ($this->educationRank($education->level) * 10000) + ((int) $education->graduation_year);
            })
            ->take(2)
            ->map(function ($education) {
                return [
                    'level' => $this->cleanLabel($education->level),
                    'institution' => $this->cleanLabel($education->institution),
                    'major' => $this->cleanLabel($education->major),
                    'graduation_year' => $education->graduation_year,
                ];
            })
            ->values()
            ->toArray();
    }

    private function certifications(CvProfile $profile): array
    {
        return $profile->certifications
            ->map(function ($certification) {
                return [
                    'name' => $this->cleanLabel($certification->name),
                    'issuer' => $this->cleanLabel($certification->issuer),
                    'year' => $certification->year,
                    'valid_until' => $certification->is_lifetime
                        ? 'Seumur hidup'
                        : ($certification->valid_until_year ?: '-'),
                    'type' => $certification->type,
                ];
            })
            ->filter(function ($certification) {
                return $certification['name'] || $certification['issuer'] || $certification['year'];
            })
            ->values()
            ->toArray();
    }

    private function languages(CvProfile $profile): array
    {
        return $profile->languages
            ->map(function ($language) {
                return [
                    'language' => $this->cleanLabel($language->language),
                    'level' => $this->cleanLabel($language->level),
                ];
            })
            ->filter(function ($language) {
                return $language['language'];
            })
            ->values()
            ->toArray();
    }

    private function projects(CvProfile $profile): array
    {
        return $profile->projects
            ->map(function ($project) {
                return [
                    'name' => $this->cleanLabel($project->name),
                    'year' => $project->year,
                ];
            })
            ->filter(function ($project) {
                return $project['name'];
            })
            ->values()
            ->toArray();
    }

    private function organizations(CvProfile $profile): array
    {
        return $profile->organizations
            ->map(function ($organization) {
                return [
                    'organization_name' => $this->cleanLabel($organization->organization_name),
                    'role' => $this->cleanLabel($organization->role),
                    'period' => $this->yearPeriod($organization->start_year, $organization->end_year),
                ];
            })
            ->filter(function ($organization) {
                return $organization['organization_name'];
            })
            ->values()
            ->toArray();
    }

    private function interests(array $items, ?string $other): array
    {
        $isList = count($items) > 0 && array_keys($items) === range(0, count($items) - 1);

        if (!$isList) {
            return collect($items)
                ->map(function ($detail, $key) {
                    $label = CvProfile::INTEREST_OPTIONS[$key] ?? null;
                    $detail = $this->cleanLabel($detail);

                    return $label && $detail ? $label . ': ' . $detail : null;
                })
                ->filter()
                ->values()
                ->toArray();
        }

        return collect($items)
            ->map(function ($item) use ($other) {
                if ($item === 'other') {
                    $other = $this->cleanLabel($other);

                    return $other ? 'Lainnya: ' . $other : null;
                }

                return CvProfile::INTEREST_OPTIONS[$item] ?? null;
            })
            ->filter()
            ->values()
            ->toArray();
    }

    private function achievements(CvProfile $profile): array
    {
        return $profile->achievements
            ->map(function (CvAchievement $achievement) {
                return [
                    'field' => $achievement->field === 'other'
                        ? ($this->cleanLabel($achievement->other_field) ?: 'Lainnya')
                        : CvAchievement::fieldLabel($achievement->field),
                    'type' => $this->cleanLabel($achievement->achievement_type),
                    'rank' => $this->cleanLabel($achievement->rank),
                    'level' => $achievement->level === 'other'
                        ? ($this->cleanLabel($achievement->other_level) ?: 'Lainnya')
                        : CvAchievement::levelLabel($achievement->level),
                    'period' => $this->formatMonth($achievement->period),
                ];
            })
            ->filter(function ($achievement) {
                return $achievement['field'] || $achievement['type'] || $achievement['rank'] || $achievement['level'] || $achievement['period'];
            })
            ->values()
            ->toArray();
    }

    private function vpeopleNik(User $user): ?string
    {
        if (!$user->vpeople_nik_encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($user->vpeople_nik_encrypted);
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function period($start, $end, bool $isCurrent): ?string
    {
        $startText = $this->formatMonth($start);

        if (!$startText && !$end) {
            return null;
        }

        return trim(($startText ?: '-') . ' - ' . ($isCurrent ? 'Sekarang' : ($this->formatMonth($end) ?: '-')));
    }

    private function yearPeriod($start, $end): ?string
    {
        if (!$start && !$end) {
            return null;
        }

        return trim(($start ?: '-') . ' - ' . ($end ?: 'Sekarang'));
    }

    private function formatDate($date): ?string
    {
        if (!$date) {
            return null;
        }

        return Carbon::parse($date)->format('d/m/Y');
    }

    private function formatMonth($date): ?string
    {
        if (!$date) {
            return null;
        }

        $date = Carbon::parse($date);

        return self::MONTHS[(int) $date->format('n')] . ' ' . $date->format('Y');
    }

    private function genderLabel(?string $gender): ?string
    {
        if ($gender === 'L') {
            return 'Laki-laki';
        }

        if ($gender === 'P') {
            return 'Perempuan';
        }

        return $gender;
    }

    private function educationRank(?string $level): int
    {
        $level = $this->cleanLabel($level);

        foreach (self::EDUCATION_RANKS as $key => $rank) {
            if ($level && stripos($level, $key) === 0) {
                return $rank;
            }
        }

        return 0;
    }

    private function cleanList(array $items): array
    {
        return array_values(array_filter(array_map(function ($item) {
            return $this->cleanLabel($item);
        }, $items)));
    }

    private function cleanLabel($value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = preg_replace('/[\x{3400}-\x{9FFF}\x{F900}-\x{FAFF}]+/u', '', (string) $value);
        $value = trim(preg_replace('/\s+/', ' ', $value));

        return $value ?: null;
    }
}
