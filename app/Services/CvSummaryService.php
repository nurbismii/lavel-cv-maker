<?php

namespace App\Services;

use App\Models\CvProfile;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CvSummaryService
{
    public function generate(CvProfile $profile): string
    {
        $profile->loadMissing(['experiences', 'certifications']);

        $position = $this->position($profile);
        $context = $this->context($profile);
        $years = $this->experienceYears($profile);
        $skills = array_slice(array_filter(array_map(function ($skill) {
            return $this->cleanLabel($skill);
        }, $profile->technical_skills ?: [])), 0, 3);
        $certification = $this->cleanLabel(
            optional($profile->certifications->firstWhere('type', 'Sertifikasi'))->name
                ?: optional($profile->certifications->first())->name
        );

        $sentences = [];

        if ($position && $years && $context) {
            $sentences[] = "{$position} dengan pengalaman {$years} tahun di {$context}.";
        } elseif ($position && $context) {
            $sentences[] = "{$position} dengan latar belakang kerja di {$context}.";
        } elseif ($position && $years) {
            $sentences[] = "{$position} dengan pengalaman {$years} tahun.";
        } elseif ($position) {
            $sentences[] = "{$position} dengan kompetensi yang relevan untuk mendukung kebutuhan operasional perusahaan.";
        } else {
            $sentences[] = 'Karyawan dengan pengalaman dan kompetensi yang relevan untuk mendukung kebutuhan operasional perusahaan.';
        }

        if ($skills) {
            $sentences[] = 'Kompeten dalam ' . $this->humanList($skills) . '.';
        }

        if ($certification) {
            $sentences[] = 'Memiliki sertifikasi/pelatihan ' . $certification . '.';
        }

        return $this->limit(implode(' ', $sentences), 300);
    }

    private function position(CvProfile $profile): ?string
    {
        if ($profile->position) {
            return $this->cleanLabel($profile->position);
        }

        $experience = $profile->experiences
            ->filter(function ($item) {
                return (bool) $item->position;
            })
            ->sortByDesc(function ($item) {
                return $item->is_current ? now()->timestamp : optional($item->end_month)->timestamp;
            })
            ->first();

        return $this->cleanLabel(optional($experience)->position);
    }

    private function context(CvProfile $profile): ?string
    {
        if ($profile->department) {
            return 'Departemen ' . $this->cleanLabel($profile->department);
        }

        if ($profile->work_area) {
            return 'area kerja ' . $this->cleanLabel($profile->work_area);
        }

        $company = $this->cleanLabel(optional($profile->experiences->first())->company);

        return $company ?: null;
    }

    private function experienceYears(CvProfile $profile): ?int
    {
        $months = 0;

        foreach ($profile->experiences as $experience) {
            if (!$experience->start_month) {
                continue;
            }

            $start = Carbon::parse($experience->start_month)->startOfMonth();
            $end = $experience->is_current || !$experience->end_month
                ? now()->startOfMonth()
                : Carbon::parse($experience->end_month)->startOfMonth();

            if ($end->lessThan($start)) {
                continue;
            }

            $months += $start->diffInMonths($end) + 1;
        }

        if ($months <= 0) {
            return null;
        }

        return max(1, (int) floor($months / 12));
    }

    private function humanList(array $items): string
    {
        $items = array_values(array_filter(array_map('trim', $items)));

        if (count($items) <= 1) {
            return $items[0] ?? '';
        }

        if (count($items) === 2) {
            return $items[0] . ' dan ' . $items[1];
        }

        $last = array_pop($items);

        return implode(', ', $items) . ', dan ' . $last;
    }

    private function limit(string $summary, int $limit): string
    {
        $summary = trim(preg_replace('/\s+/', ' ', $summary));

        if (Str::length($summary) <= $limit) {
            return $summary;
        }

        return rtrim(Str::limit($summary, $limit, ''), " \t\n\r\0\x0B.,;") . '.';
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
