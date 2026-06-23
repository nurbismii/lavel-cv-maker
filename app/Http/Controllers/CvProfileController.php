<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveCvProfileRequest;
use App\Models\CvCertification;
use App\Models\CvEducation;
use App\Models\CvExperience;
use App\Models\CvLanguage;
use App\Models\CvOrganization;
use App\Models\CvProfile;
use App\Models\CvProject;
use App\Services\CvSummaryService;
use App\Services\VPeopleService;
use App\Services\VPeopleLocationService;
use App\Services\VPeopleOrganizationService;
use App\Support\CvResponsibilityRichText;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CvProfileController extends Controller
{
    public function edit(
        Request $request,
        VPeopleLocationService $locationService,
        VPeopleOrganizationService $organizationService,
        VPeopleService $vpeopleService
    )
    {
        $profile = $this->profileFor($request);
        $this->syncProfileOrganizationFromVPeople($request, $profile, $vpeopleService);

        $profile->load([
            'experiences',
            'educations',
            'certifications',
            'languages',
            'projects',
            'organizations',
        ]);

        [$locationOptions, $locationMasterError] = $this->locationOptions($request, $profile, $locationService);
        [$organizationOptions, $organizationMasterError] = $this->organizationOptions($request, $profile, $organizationService);

        return view('cv.edit', [
            'profile' => $profile,
            'completion' => $this->completion($profile),
            'vpeopleNik' => $this->vpeopleNik($request),
            'locationOptions' => $locationOptions,
            'locationMasterError' => $locationMasterError,
            'organizationOptions' => $organizationOptions,
            'organizationMasterError' => $organizationMasterError,
        ]);
    }

    public function saveDraft(SaveCvProfileRequest $request, VPeopleLocationService $locationService, VPeopleService $vpeopleService)
    {
        $profile = $this->profileFor($request);
        $this->syncProfileOrganizationFromVPeople($request, $profile, $vpeopleService);

        $this->persistDraft($request, $profile, $locationService);

        return redirect()
            ->route('cv.edit')
            ->with('success', 'Draft CV berhasil disimpan.');
    }

    public function generateSummary(
        SaveCvProfileRequest $request,
        CvSummaryService $summaryService,
        VPeopleLocationService $locationService,
        VPeopleService $vpeopleService
    )
    {
        $profile = $this->profileFor($request);
        $this->syncProfileOrganizationFromVPeople($request, $profile, $vpeopleService);

        $this->persistDraft($request, $profile, $locationService);

        $profile->load([
            'experiences',
            'educations',
            'certifications',
            'languages',
            'projects',
            'organizations',
        ]);

        $profile->update([
            'profile_summary' => $summaryService->generate($profile),
        ]);

        return redirect()
            ->route('cv.edit', ['step' => 'summary'])
            ->with('success', 'Ringkasan profil berhasil dibuat. Silakan cek dan edit jika perlu.');
    }

    public function saveAndPreview(SaveCvProfileRequest $request, VPeopleLocationService $locationService, VPeopleService $vpeopleService)
    {
        $profile = $this->profileFor($request);
        $this->syncProfileOrganizationFromVPeople($request, $profile, $vpeopleService);

        $this->persistDraft($request, $profile, $locationService);

        return redirect()->route('cv.preview');
    }

    private function profileFor(Request $request): CvProfile
    {
        $user = $request->user();

        return $user->cvProfile ?: CvProfile::create([
            'user_id' => $user->id,
            'status' => CvProfile::STATUS_DRAFT,
            'full_name' => $user->name,
            'email' => $user->email,
        ]);
    }

    private function vpeopleNik(Request $request): ?string
    {
        if (!$request->user()->vpeople_nik_encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($request->user()->vpeople_nik_encrypted);
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function syncProfileOrganizationFromVPeople(
        Request $request,
        CvProfile $profile,
        VPeopleService $vpeopleService
    ): void {
        $nik = $this->vpeopleNik($request);

        if (!$nik) {
            return;
        }

        try {
            $employee = $vpeopleService->findActiveEmployeeByNik($nik);

            if (!$employee) {
                return;
            }

            $profile->forceFill([
                'department' => $profile->department ?: $employee['department'],
                'division' => $profile->division ?: $employee['division'],
                'position' => $profile->position ?: $employee['position'],
            ])->save();

            $request->user()->forceFill([
                'vpeople_last_synced_at' => now(),
            ])->save();
        } catch (\Throwable $exception) {
            Log::warning('V-People organization sync failed.', [
                'exception' => get_class($exception),
            ]);
        }
    }

    private function persistDraft(
        SaveCvProfileRequest $request,
        CvProfile $profile,
        VPeopleLocationService $locationService
    ): void
    {
        $locationSelection = $this->resolveLocationSelection($request, $locationService);
        [$photoPath, $oldPhotoPath, $newPhotoPath] = $this->preparePhotoUpdate($request, $profile);

        try {
            DB::transaction(function () use ($request, $profile, $locationSelection, $photoPath) {
                $profile->update(array_merge([
                    'status' => CvProfile::STATUS_DRAFT,
                    'photo_path' => $photoPath,
                    'birth_place' => $request->input('birth_place'),
                    'gender' => $request->input('gender'),
                    'marital_status' => $request->input('marital_status'),
                    'address' => $request->input('address'),
                    'phone' => $request->input('phone'),
                    'email' => $request->input('email'),
                    'department' => $this->organizationValue($request, 'department'),
                    'division' => $this->organizationValue($request, 'division'),
                    'position' => $this->organizationValue($request, 'position'),
                    'profile_summary' => $request->input('profile_summary'),
                    'technical_skills' => $this->splitList($request->input('technical_skills')),
                    'non_technical_skills' => $this->splitList($request->input('non_technical_skills')),
                ], $locationSelection));

                $this->syncExperiences($profile, $request->input('experiences', []));
                $this->syncEducations($profile, $request->input('educations', []));
                $this->syncCertifications($profile, $request->input('certifications', []));
                $this->syncLanguages($profile, $request->input('languages', []));
                $this->syncProjects($profile, $request->input('projects', []));
                $this->syncOrganizations($profile, $request->input('organizations', []));
            });
        } catch (\Throwable $exception) {
            if ($newPhotoPath) {
                Storage::disk('local')->delete($newPhotoPath);
            }

            throw $exception;
        }

        if ($oldPhotoPath && $oldPhotoPath !== $photoPath) {
            Storage::disk('local')->delete($oldPhotoPath);
        }
    }

    private function preparePhotoUpdate(SaveCvProfileRequest $request, CvProfile $profile): array
    {
        $photoPath = $profile->photo_path;
        $oldPhotoPath = null;
        $newPhotoPath = null;

        if ($request->boolean('remove_photo') && $photoPath) {
            $oldPhotoPath = $photoPath;
            $photoPath = null;
        }

        if ($request->hasFile('photo')) {
            $oldPhotoPath = $profile->photo_path;
            $newPhotoPath = $request->file('photo')->store('cv-photos/' . $profile->user_id, 'local');
            $photoPath = $newPhotoPath;
        }

        return [$photoPath, $oldPhotoPath, $newPhotoPath];
    }

    private function locationOptions(
        Request $request,
        CvProfile $profile,
        VPeopleLocationService $locationService
    ): array {
        $locationMasterError = null;
        $locationOptions = $this->emptyLocationOptions();

        try {
            $provinceId = $request->old('province_id', $profile->province_id);
            $regencyId = $request->old('regency_id', $profile->regency_id);
            $districtId = $request->old('district_id', $profile->district_id);

            $locationOptions = [
                'provinces' => $locationService->provinces(),
                'regencies' => $provinceId ? $locationService->regencies($provinceId) : [],
                'districts' => $regencyId ? $locationService->districts($regencyId) : [],
                'villages' => $districtId ? $locationService->villages($districtId) : [],
            ];
        } catch (\Throwable $exception) {
            Log::warning('V-People location options failed to load.', [
                'exception' => get_class($exception),
            ]);

            $locationMasterError = 'Master wilayah V-People sedang tidak tersedia. Dropdown wilayah belum bisa dimuat.';
        }

        return [$locationOptions, $locationMasterError];
    }

    private function emptyLocationOptions(): array
    {
        return [
            'provinces' => [],
            'regencies' => [],
            'districts' => [],
            'villages' => [],
        ];
    }

    private function organizationOptions(
        Request $request,
        CvProfile $profile,
        VPeopleOrganizationService $organizationService
    ): array {
        $organizationMasterError = null;
        $organizationOptions = $this->emptyOrganizationOptions();

        try {
            $department = $request->old('department', $profile->department);
            $division = $request->old('division', $profile->division);

            $departmentId = $organizationService->findDepartmentIdByName($department);
            $divisionId = $organizationService->findDivisionIdByName($departmentId, $division);

            $organizationOptions = [
                'departments' => $organizationService->departments(),
                'divisions' => $departmentId ? $organizationService->divisions($departmentId) : [],
                'positions' => $organizationService->positions($departmentId, $divisionId),
                'selected_department_id' => $departmentId,
                'selected_division_id' => $divisionId,
            ];
        } catch (\Throwable $exception) {
            Log::warning('V-People organization options failed to load.', [
                'exception' => get_class($exception),
            ]);

            $organizationMasterError = 'Master organisasi V-People sedang tidak tersedia. Dropdown departemen, divisi, dan posisi belum bisa dimuat.';
        }

        return [$organizationOptions, $organizationMasterError];
    }

    private function emptyOrganizationOptions(): array
    {
        return [
            'departments' => [],
            'divisions' => [],
            'positions' => [],
            'selected_department_id' => null,
            'selected_division_id' => null,
        ];
    }

    private function resolveLocationSelection(
        SaveCvProfileRequest $request,
        VPeopleLocationService $locationService
    ): array {
        try {
            return $locationService->resolveSelection($request->only([
                'province_id',
                'regency_id',
                'district_id',
                'village_id',
            ]));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::warning('V-People location selection failed to resolve.', [
                'exception' => get_class($exception),
            ]);

            throw ValidationException::withMessages([
                'province_id' => 'Master wilayah V-People sedang tidak tersedia. Silakan coba lagi.',
            ]);
        }
    }

    private function organizationValue(SaveCvProfileRequest $request, string $field): ?string
    {
        $customValue = trim((string) $request->input($field . '_custom'));
        $selectedValue = trim((string) $request->input($field));

        if ($customValue !== '') {
            return $customValue;
        }

        return $selectedValue === '' || $selectedValue === '__custom__' ? null : $selectedValue;
    }

    private function syncExperiences(CvProfile $profile, array $items): void
    {
        $profile->experiences()->delete();
        $sortOrder = 0;

        foreach ($items as $item) {
            $responsibilities = CvResponsibilityRichText::toStorage($item['responsibilities'] ?? null);
            $itemForCheck = $item;
            $itemForCheck['responsibilities'] = CvResponsibilityRichText::toPlainText($responsibilities) ?: '';

            if (!$this->rowHasValue($itemForCheck, ['position', 'company', 'start_month', 'end_month', 'responsibilities'])) {
                continue;
            }

            CvExperience::create([
                'cv_profile_id' => $profile->id,
                'position' => $item['position'] ?: null,
                'company' => $item['company'] ?: 'PT VDNI',
                'department' => $item['department'] ?: null,
                'division' => $item['division'] ?: null,
                'start_month' => $this->monthDate($item['start_month'] ?? null),
                'end_month' => !empty($item['is_current']) ? null : $this->monthDate($item['end_month'] ?? null),
                'is_current' => !empty($item['is_current']),
                'responsibilities' => $responsibilities,
                'sort_order' => $sortOrder++,
            ]);
        }
    }

    private function syncEducations(CvProfile $profile, array $items): void
    {
        $profile->educations()->delete();
        $sortOrder = 0;

        foreach ($items as $item) {
            if (!$this->rowHasValue($item, ['level', 'institution', 'major', 'graduation_year'])) {
                continue;
            }

            CvEducation::create([
                'cv_profile_id' => $profile->id,
                'level' => $item['level'] ?: null,
                'institution' => $item['institution'] ?: null,
                'major' => $item['major'] ?: null,
                'graduation_year' => $item['graduation_year'] ?: null,
                'sort_order' => $sortOrder++,
            ]);
        }
    }

    private function syncCertifications(CvProfile $profile, array $items): void
    {
        $profile->certifications()->delete();
        $sortOrder = 0;

        foreach ($items as $item) {
            if (!$this->rowHasValue($item, ['name', 'issuer', 'year', 'valid_until_year'])) {
                continue;
            }

            CvCertification::create([
                'cv_profile_id' => $profile->id,
                'name' => $item['name'] ?: null,
                'issuer' => $item['issuer'] ?: null,
                'year' => $item['year'] ?: null,
                'valid_until_year' => !empty($item['is_lifetime']) ? null : ($item['valid_until_year'] ?: null),
                'is_lifetime' => !empty($item['is_lifetime']),
                'type' => $item['type'] ?: CvCertification::TYPE_CERTIFICATION,
                'sort_order' => $sortOrder++,
            ]);
        }
    }

    private function syncLanguages(CvProfile $profile, array $items): void
    {
        $profile->languages()->delete();
        $sortOrder = 0;

        foreach ($items as $item) {
            if (!$this->rowHasValue($item, ['language', 'level'])) {
                continue;
            }

            CvLanguage::create([
                'cv_profile_id' => $profile->id,
                'language' => $item['language'] ?: null,
                'level' => $item['level'] ?: null,
                'sort_order' => $sortOrder++,
            ]);
        }
    }

    private function syncProjects(CvProfile $profile, array $items): void
    {
        $profile->projects()->delete();
        $sortOrder = 0;

        foreach ($items as $item) {
            if (!$this->rowHasValue($item, ['name', 'year'])) {
                continue;
            }

            CvProject::create([
                'cv_profile_id' => $profile->id,
                'name' => $item['name'] ?: null,
                'year' => $item['year'] ?: null,
                'sort_order' => $sortOrder++,
            ]);
        }
    }

    private function syncOrganizations(CvProfile $profile, array $items): void
    {
        $profile->organizations()->delete();
        $sortOrder = 0;

        foreach ($items as $item) {
            if (!$this->rowHasValue($item, ['organization_name', 'role', 'start_year', 'end_year'])) {
                continue;
            }

            CvOrganization::create([
                'cv_profile_id' => $profile->id,
                'organization_name' => $item['organization_name'] ?: null,
                'role' => $item['role'] ?: null,
                'start_year' => $item['start_year'] ?: null,
                'end_year' => $item['end_year'] ?: null,
                'sort_order' => $sortOrder++,
            ]);
        }
    }

    private function completion(CvProfile $profile): int
    {
        $checks = [
            (bool) $profile->full_name,
            (bool) $profile->birth_place,
            (bool) $profile->birth_date,
            (bool) $profile->address,
            (bool) $profile->phone,
            (bool) $profile->email,
            (bool) $profile->profile_summary,
            count($profile->technical_skills ?: []) > 0,
            $profile->experiences()->exists(),
            $profile->educations()->exists(),
        ];

        return (int) round((count(array_filter($checks)) / count($checks)) * 100);
    }

    private function splitList(?string $value): array
    {
        if (!$value) {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/[,;\n]+/', $value))));
    }

    private function rowHasValue(array $row, array $keys): bool
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && trim((string) $row[$key]) !== '') {
                return true;
            }
        }

        return false;
    }

    private function monthDate(?string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value . '-01')->startOfMonth();
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
