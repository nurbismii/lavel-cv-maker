<?php

namespace App\Services;

use App\Models\CvEducation;
use App\Models\CvProfile;
use App\Models\User;
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
            'photo_url' => $this->photoUrl($profile),
            'photo_data_uri' => $this->photoDataUri($profile),
            'experiences' => $this->experiences($profile),
            'educations' => $this->educations($profile),
            'certifications' => $this->certifications($profile),
            'languages' => $this->languages($profile),
            'projects' => $this->projects($profile),
            'organizations' => $this->organizations($profile),
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
        $lines = [];
        $address = $this->cleanLabel($profile->address);
        $location = $this->cleanList([
            $profile->village_name,
            $profile->district_name,
            $profile->regency_name,
            $profile->province_name,
        ]);

        if ($address) {
            $lines[] = $address;
        }

        if (count($location)) {
            $lines[] = implode(', ', $location);
        }

        return count($lines) ? implode("\n", $lines) : null;
    }

    private function experiences(CvProfile $profile): array
    {
        return $profile->experiences
            ->sortByDesc(function ($experience) {
                if ($experience->is_current) {
                    return now()->timestamp;
                }

                return optional($experience->end_month ?: $experience->start_month)->timestamp ?: 0;
            })
            ->map(function ($experience) {
                return [
                    'position' => $this->cleanLabel($experience->position),
                    'company' => $this->cleanLabel($experience->company),
                    'department' => $this->cleanLabel($experience->department),
                    'period' => $this->period($experience->start_month, $experience->end_month, $experience->is_current),
                    'responsibilities' => $this->cleanList(array_slice($experience->responsibilities ?: [], 0, 5)),
                ];
            })
            ->filter(function ($experience) {
                return $experience['position'] || $experience['company'] || $experience['department'] || $experience['period'] || count($experience['responsibilities']);
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
