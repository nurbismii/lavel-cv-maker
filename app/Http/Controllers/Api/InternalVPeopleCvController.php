<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InternalVPeopleCvController extends Controller
{
    private const PROFILE_COLUMNS = [
        'status', 'full_name', 'birth_place', 'birth_date', 'ktp_number',
        'family_card_number', 'gender', 'height_cm', 'weight_kg', 'blood_type',
        'religion', 'marital_status', 'marriage_date', 'spouse_name', 'mother_name',
        'bank_account_number', 'npwp_number', 'photo_path', 'ktp_address', 'rt', 'rw',
        'domicile_same_as_ktp', 'has_children', 'children_names',
        'province_id', 'province_name', 'regency_id', 'regency_name', 'district_id',
        'district_name', 'village_id', 'village_name', 'address', 'phone', 'email',
        'instagram', 'linkedin', 'facebook',
        'work_area', 'department', 'division', 'position', 'current_job_entry_date',
        'profile_summary', 'technical_skills', 'non_technical_skills',
        'hobbies', 'other_hobby', 'talents', 'other_talent',
        'last_generated_at', 'updated_at',
    ];

    private const RELATED_TABLES = [
        'educations' => ['cv_educations', ['id', 'cv_profile_id', 'level', 'institution', 'major', 'graduation_year', 'sort_order', 'updated_at']],
        'experiences' => ['cv_experiences', ['id', 'cv_profile_id', 'position', 'company', 'department', 'division', 'start_month', 'end_month', 'is_current', 'responsibilities', 'sort_order', 'updated_at']],
        'organizations' => ['cv_organizations', ['id', 'cv_profile_id', 'organization_name', 'role', 'start_year', 'end_year', 'sort_order', 'updated_at']],
        'certifications' => ['cv_certifications', ['id', 'cv_profile_id', 'name', 'issuer', 'year', 'valid_until_year', 'is_lifetime', 'type', 'sort_order', 'updated_at']],
        'languages' => ['cv_languages', ['id', 'cv_profile_id', 'language', 'level', 'sort_order', 'updated_at']],
        'projects' => ['cv_projects', ['id', 'cv_profile_id', 'name', 'year', 'sort_order', 'updated_at']],
        'achievements' => ['cv_achievements', ['id', 'cv_profile_id', 'field', 'other_field', 'achievement_type', 'rank', 'level', 'other_level', 'period', 'sort_order', 'updated_at']],
        'emergency_contacts' => ['cv_emergency_contacts', ['id', 'cv_profile_id', 'phone', 'name', 'relationship', 'sort_order', 'updated_at']],
        'documents' => ['cv_documents', ['id', 'cv_profile_id', 'type', 'original_name', 'mime_type', 'file_size', 'uploaded_at', 'updated_at']],
    ];

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hashes' => ['required', 'array', 'min:1', 'max:100'],
            'hashes.*' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
        ]);

        $hashes = array_values(array_unique($validated['hashes']));
        $profiles = DB::table('users')
            ->leftJoin('cv_profiles', 'cv_profiles.user_id', '=', 'users.id')
            ->whereIn('users.vpeople_nik_hash', $hashes)
            ->select(array_merge([
                'users.id as user_id',
                'users.email as account_email',
                'users.vpeople_nik_hash',
                'users.vpeople_last_synced_at',
                'cv_profiles.id as profile_id',
            ], array_map(function ($column) {
                return 'cv_profiles.' . $column;
            }, self::PROFILE_COLUMNS)))
            ->get();

        $profileIds = $profiles->pluck('profile_id')->filter()->map(function ($id) {
            return (int) $id;
        })->values()->all();
        $related = $this->relatedRows($profileIds);

        return response()->json([
            'success' => true,
            'data' => [
                'profiles' => $profiles->map(function ($profile) use ($related) {
                    $item = (array) $profile;
                    $profileId = (int) ($item['profile_id'] ?? 0);
                    $item['photo_available'] = !empty($item['photo_path']);
                    unset($item['photo_path']);
                    $item['related'] = $profileId ? ($related[$profileId] ?? $this->emptyRelated()) : $this->emptyRelated();

                    return $item;
                })->values(),
            ],
        ]);
    }

    private function relatedRows(array $profileIds): array
    {
        $result = [];

        foreach ($profileIds as $profileId) {
            $result[$profileId] = $this->emptyRelated();
        }

        if (empty($profileIds)) {
            return $result;
        }

        foreach (self::RELATED_TABLES as $key => $definition) {
            list($table, $columns) = $definition;
            $rows = DB::table($table)
                ->whereIn('cv_profile_id', $profileIds)
                ->select($columns)
                ->orderBy('cv_profile_id')
                ->orderBy(in_array('sort_order', $columns, true) ? 'sort_order' : 'id')
                ->limit(count($profileIds) * 50)
                ->get();

            foreach ($rows as $row) {
                $item = (array) $row;
                $profileId = (int) $item['cv_profile_id'];

                if (isset($result[$profileId])) {
                    $result[$profileId][$key][] = $item;
                }
            }
        }

        return $result;
    }

    private function emptyRelated(): array
    {
        return array_fill_keys(array_keys(self::RELATED_TABLES), []);
    }
}
