<?php

namespace App\Services;

use App\Models\CvEducation;
use App\Models\CvProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class CvUserProvisioningService
{
    public function createFromVPeopleEmployee(array $employee, string $email, ?string $password = null): User
    {
        $email = strtolower(trim($email));

        $user = User::create([
            'name' => $employee['name'],
            'email' => $email,
            'password' => Hash::make($password ?: Str::random(40)),
            'vpeople_nik_encrypted' => Crypt::encryptString($employee['nik']),
            'vpeople_nik_hash' => $this->hashNik($employee['nik']),
            'vpeople_last_synced_at' => now(),
        ]);

        $profile = CvProfile::create([
            'user_id' => $user->id,
            'status' => CvProfile::STATUS_DRAFT,
            'full_name' => $employee['name'],
            'birth_date' => $employee['birth_date'],
            'gender' => $employee['gender'],
            'marital_status' => $employee['marital_status'],
            'address' => $employee['address'],
            'phone' => $employee['phone'],
            'email' => $email,
            'work_area' => $employee['work_area'],
            'department' => $employee['department'],
            'division' => $employee['division'],
            'position' => $employee['position'],
        ]);

        $this->createEducationPrefill($profile, $employee);

        return $user;
    }

    public function hashNik(string $nik): string
    {
        return hash_hmac('sha256', $nik, config('app.key'));
    }

    private function createEducationPrefill(CvProfile $profile, array $employee): void
    {
        if (!$employee['education_level'] || !$employee['education_institution']) {
            return;
        }

        CvEducation::create([
            'cv_profile_id' => $profile->id,
            'level' => $employee['education_level'],
            'institution' => $employee['education_institution'],
            'major' => $employee['education_major'],
            'graduation_year' => $this->parseGraduationYear($employee['graduation_date']),
            'sort_order' => 0,
        ]);
    }

    private function parseGraduationYear($graduationDate): ?int
    {
        if (!$graduationDate || $graduationDate === '0000-00-00') {
            return null;
        }

        try {
            $year = (int) Carbon::parse($graduationDate)->format('Y');
        } catch (Throwable $exception) {
            return null;
        }

        if ($year < 1900 || $year > ((int) now()->format('Y') + 1)) {
            return null;
        }

        return $year;
    }
}
