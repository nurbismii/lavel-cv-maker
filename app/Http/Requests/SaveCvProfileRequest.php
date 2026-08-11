<?php

namespace App\Http\Requests;

use App\Models\CvEmergencyContact;
use App\Models\CvDocument;
use App\Models\CvAchievement;
use App\Models\CvProfile;
use App\Services\VPeopleOrganizationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCvProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return array_merge([
            'full_name' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date'],
            'ktp_number' => ['nullable', 'digits:16'],
            'family_card_number' => ['nullable', 'digits:16'],
            'bank_account_number' => ['nullable', 'string', 'regex:/^[0-9]{5,34}$/'],
            'npwp_number' => ['nullable', 'digits_between:15,16'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
            'documents' => ['nullable', 'array'],
            'remove_documents' => ['nullable', 'array'],
            'remove_documents.*' => ['nullable'],
            'gender' => ['nullable', 'in:L,P'],
            'height_cm' => ['nullable', 'integer', 'min:50', 'max:250'],
            'weight_kg' => ['nullable', 'numeric', 'min:20', 'max:300'],
            'blood_type' => ['nullable', 'string', Rule::in(CvProfile::BLOOD_TYPES)],
            'religion' => ['nullable', 'string', Rule::in(CvProfile::RELIGIONS)],
            'marital_status' => ['nullable', 'string', 'max:64'],
            'marriage_date' => ['nullable', 'date'],
            'spouse_name' => ['nullable', 'string', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'has_children' => ['nullable', 'boolean'],
            'children_names' => ['nullable', 'array', 'max:3'],
            'children_names.*' => ['nullable', 'string', 'max:255'],
            'province_id' => ['required', 'string', 'max:32'],
            'regency_id' => ['required', 'string', 'max:32'],
            'district_id' => ['required', 'string', 'max:32'],
            'village_id' => ['required', 'string', 'max:32'],
            'ktp_address' => ['required', 'string', 'max:2000'],
            'domicile_same_as_ktp' => ['nullable', 'boolean'],
            'address' => ['required_unless:domicile_same_as_ktp,1', 'nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:64'],
            'email' => ['required', 'email', 'max:255'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'linkedin' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'work_area' => ['required', 'string', Rule::in(VPeopleOrganizationService::supportedWorkAreaCodes())],
            'department' => ['required', 'string', 'max:255'],
            'department_custom' => ['nullable', 'string', 'max:255'],
            'division' => ['required', 'string', 'max:255'],
            'division_custom' => ['nullable', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'position_custom' => ['nullable', 'string', 'max:255'],
            'profile_summary' => ['nullable', 'string', 'max:300'],
            'technical_skills' => ['nullable', 'string', 'max:1000'],
            'non_technical_skills' => ['nullable', 'string', 'max:1000'],
            'hobbies' => ['nullable', 'array', 'max:' . count(CvProfile::INTEREST_OPTIONS)],
            'hobbies.*' => ['nullable', 'string', 'max:255'],
            'other_hobby' => ['nullable', 'string', 'max:255'],
            'talents' => ['nullable', 'array', 'max:' . count(CvProfile::INTEREST_OPTIONS)],
            'talents.*' => ['nullable', 'string', 'max:255'],
            'other_talent' => ['nullable', 'string', 'max:255'],

            'achievements' => ['nullable', 'array', 'max:20'],
            'achievements.*.field' => ['nullable', 'string', Rule::in(array_keys(CvAchievement::FIELD_OPTIONS))],
            'achievements.*.other_field' => ['nullable', 'string', 'max:255'],
            'achievements.*.achievement_type' => ['nullable', 'string', 'max:255'],
            'achievements.*.rank' => ['nullable', 'string', 'max:100'],
            'achievements.*.level' => ['nullable', 'string', Rule::in(array_keys(CvAchievement::LEVEL_OPTIONS))],
            'achievements.*.other_level' => ['nullable', 'string', 'max:255'],
            'achievements.*.period' => ['nullable', 'date_format:Y-m'],

            'emergency_contacts' => ['required', 'array', 'min:1'],
            'emergency_contacts.*.phone' => ['required', 'digits_between:10,13'],
            'emergency_contacts.*.name' => ['required', 'string', 'max:255'],
            'emergency_contacts.*.relationship' => ['required', Rule::in(CvEmergencyContact::RELATIONSHIPS)],

            'experiences' => ['nullable', 'array'],
            'experiences.*.position' => ['nullable', 'string', 'max:255'],
            'experiences.*.company' => ['nullable', 'string', 'max:255'],
            'experiences.*.department' => ['nullable', 'string', 'max:255'],
            'experiences.*.division' => ['nullable', 'string', 'max:255'],
            'experiences.*.start_month' => ['nullable', 'date_format:Y-m'],
            'experiences.*.end_month' => ['nullable', 'date_format:Y-m'],
            'experiences.*.is_current' => ['nullable', 'boolean'],
            'experiences.*.responsibilities' => ['nullable', 'string', 'max:4000'],

            'educations' => ['nullable', 'array'],
            'educations.*.level' => ['nullable', 'string', 'max:16'],
            'educations.*.institution' => ['nullable', 'string', 'max:255'],
            'educations.*.major' => ['nullable', 'string', 'max:255'],
            'educations.*.graduation_year' => ['nullable', 'integer', 'min:1900', 'max:' . (((int) date('Y')) + 1)],

            'certifications' => ['nullable', 'array'],
            'certifications.*.name' => ['nullable', 'string', 'max:255'],
            'certifications.*.issuer' => ['nullable', 'string', 'max:255'],
            'certifications.*.year' => ['nullable', 'integer', 'min:1900', 'max:' . (((int) date('Y')) + 1)],
            'certifications.*.valid_until_year' => ['nullable', 'integer', 'min:1900', 'max:' . (((int) date('Y')) + 30)],
            'certifications.*.is_lifetime' => ['nullable', 'boolean'],
            'certifications.*.type' => ['nullable', 'in:Sertifikasi,Pelatihan'],

            'languages' => ['nullable', 'array'],
            'languages.*.language' => ['nullable', 'string', 'max:100'],
            'languages.*.level' => ['nullable', 'string', 'max:32'],

            'projects' => ['nullable', 'array'],
            'projects.*.name' => ['nullable', 'string', 'max:255'],
            'projects.*.year' => ['nullable', 'integer', 'min:1900', 'max:' . (((int) date('Y')) + 1)],

            'organizations' => ['nullable', 'array'],
            'organizations.*.organization_name' => ['nullable', 'string', 'max:255'],
            'organizations.*.role' => ['nullable', 'string', 'max:255'],
            'organizations.*.start_year' => ['nullable', 'integer', 'min:1900', 'max:' . (((int) date('Y')) + 1)],
            'organizations.*.end_year' => ['nullable', 'integer', 'min:1900', 'max:' . (((int) date('Y')) + 1)],
        ], $this->documentRules());
    }

    public function messages()
    {
        return [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'birth_date.required' => 'Tanggal lahir wajib diisi.',
            'birth_date.date' => 'Format tanggal lahir tidak valid.',
            'ktp_address.required' => 'Alamat sesuai KTP wajib diisi.',
            'province_id.required' => 'Provinsi wajib dipilih.',
            'regency_id.required' => 'Kabupaten/kota wajib dipilih.',
            'district_id.required' => 'Kecamatan wajib dipilih.',
            'village_id.required' => 'Kelurahan/desa wajib dipilih.',
            'address.required_unless' => 'Alamat domisili wajib diisi jika berbeda dari alamat KTP.',
            'work_area.required' => 'Area kerja wajib dipilih.',
            'department.required' => 'Departemen wajib dipilih.',
            'division.required' => 'Divisi wajib dipilih.',
            'position.required' => 'Jabatan/posisi wajib dipilih.',
            'emergency_contacts.required' => 'Minimal satu kontak darurat wajib diisi.',
            'emergency_contacts.min' => 'Minimal satu kontak darurat wajib diisi.',
            'emergency_contacts.*.phone.required' => 'Nomor kontak darurat wajib diisi.',
            'emergency_contacts.*.name.required' => 'Nama kontak darurat wajib diisi.',
            'emergency_contacts.*.relationship.required' => 'Hubungan kontak darurat wajib dipilih.',
            'ktp_number.digits' => 'No. KTP harus berisi 16 digit angka.',
            'family_card_number.digits' => 'No. KK harus berisi 16 digit angka.',
            'bank_account_number.regex' => 'No. rekening harus berisi 5 sampai 34 digit angka.',
            'npwp_number.digits_between' => 'No. NPWP harus berisi 15 digit, atau 16 digit jika menggunakan No. KTP.',
            'religion.in' => 'Agama yang dipilih tidak valid.',
            'marriage_date.date' => 'Format tanggal pernikahan tidak valid.',
            'children_names.max' => 'Nama anak maksimal 3 orang.',
            'emergency_contacts.*.phone.digits_between' => 'Nomor kontak darurat harus berisi 10 sampai 13 digit angka.',
            'emergency_contacts.*.relationship.in' => 'Hubungan kontak darurat tidak valid.',
            'photo.image' => 'File foto harus berupa gambar.',
            'photo.mimes' => 'Foto hanya boleh JPG atau PNG.',
            'photo.max' => 'Ukuran foto maksimal 2MB.',
            'documents.*.file' => 'Dokumen karyawan harus berupa file.',
            'documents.*.mimes' => 'Dokumen karyawan hanya boleh PDF, JPG, JPEG, atau PNG.',
            'documents.*.max' => 'Ukuran dokumen karyawan maksimal 5MB per file.',
            'height_cm.integer' => 'Tinggi badan harus berupa angka bulat dalam cm.',
            'height_cm.min' => 'Tinggi badan minimal 50 cm.',
            'height_cm.max' => 'Tinggi badan maksimal 250 cm.',
            'weight_kg.numeric' => 'Berat badan harus berupa angka dalam kg.',
            'weight_kg.min' => 'Berat badan minimal 20 kg.',
            'weight_kg.max' => 'Berat badan maksimal 300 kg.',
            'blood_type.in' => 'Golongan darah yang dipilih tidak valid.',
            'profile_summary.max' => 'Ringkasan profil maksimal 300 karakter.',
            'achievements.max' => 'Prestasi maksimal 20 entri.',
            'achievements.*.field.in' => 'Bidang prestasi tidak valid.',
            'achievements.*.level.in' => 'Tingkat prestasi tidak valid.',
            'achievements.*.period.date_format' => 'Periode prestasi harus menggunakan format bulan dan tahun.',
            '*.date_format' => 'Format bulan/tahun tidak valid.',
            '*.max' => 'Input melebihi batas karakter yang diperbolehkan.',
        ];
    }

    protected function documentRules(): array
    {
        $rules = [];

        foreach (CvDocument::allowedTypes() as $type) {
            if (CvDocument::acceptsMultipleFiles($type)) {
                $rules['documents.' . $type] = ['nullable', 'array', 'max:10'];
                $rules['documents.' . $type . '.*'] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
                $rules['remove_documents.' . $type] = ['nullable', 'array'];
                $rules['remove_documents.' . $type . '.*'] = ['nullable', 'boolean'];
            } else {
                $rules['documents.' . $type] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
                $rules['remove_documents.' . $type] = ['nullable', 'boolean'];
            }
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $npwpNumber = (string) $this->input('npwp_number');
            $ktpNumber = preg_replace('/\D+/', '', (string) $this->input('ktp_number'));

            if (strlen($npwpNumber) === 16 && $npwpNumber !== $ktpNumber) {
                $validator->errors()->add('npwp_number', 'No. NPWP 16 digit hanya dapat digunakan jika sama dengan No. KTP.');
            }

            foreach ((array) $this->file('documents', []) as $type => $file) {
                if (!CvDocument::isAllowedType((string) $type)) {
                    $validator->errors()->add("documents.{$type}", 'Jenis dokumen karyawan tidak valid.');
                }
            }

            foreach (array_keys((array) $this->input('remove_documents', [])) as $type) {
                if (!CvDocument::isAllowedType((string) $type)) {
                    $validator->errors()->add("remove_documents.{$type}", 'Jenis dokumen yang akan dihapus tidak valid.');
                }
            }

            foreach ((array) $this->input('emergency_contacts', []) as $index => $contact) {
                $phone = trim((string) ($contact['phone'] ?? ''));
                $name = trim((string) ($contact['name'] ?? ''));
                $relationship = trim((string) ($contact['relationship'] ?? ''));

                if ($phone === '' && $name === '' && $relationship === '') {
                    continue;
                }

                if ($phone === '') {
                    $validator->errors()->add("emergency_contacts.{$index}.phone", 'Nomor kontak darurat wajib diisi.');
                }

                if ($name === '') {
                    $validator->errors()->add("emergency_contacts.{$index}.name", 'Nama kontak darurat wajib diisi.');
                }

                if ($relationship === '') {
                    $validator->errors()->add("emergency_contacts.{$index}.relationship", 'Hubungan kontak darurat wajib dipilih.');
                }
            }

            $this->validateInterestKeys($validator, 'hobbies', 'Hobi');
            $this->validateInterestKeys($validator, 'talents', 'Bakat');

            foreach ((array) $this->input('achievements', []) as $index => $achievement) {
                if (!$this->achievementHasValue($achievement)) {
                    continue;
                }

                $requiredFields = [
                    'field' => 'Bidang prestasi',
                    'achievement_type' => 'Nama/jenis prestasi',
                    'rank' => 'Peringkat prestasi',
                    'level' => 'Tingkat prestasi',
                    'period' => 'Periode prestasi',
                ];

                foreach ($requiredFields as $field => $label) {
                    if (trim((string) ($achievement[$field] ?? '')) === '') {
                        $validator->errors()->add("achievements.{$index}.{$field}", "{$label} wajib diisi.");
                    }
                }

                if (($achievement['field'] ?? null) === 'other' && trim((string) ($achievement['other_field'] ?? '')) === '') {
                    $validator->errors()->add("achievements.{$index}.other_field", 'Bidang prestasi lainnya wajib diisi.');
                }

                if (($achievement['level'] ?? null) === 'other' && trim((string) ($achievement['other_level'] ?? '')) === '') {
                    $validator->errors()->add("achievements.{$index}.other_level", 'Tingkat prestasi lainnya wajib diisi.');
                }
            }
        });
    }

    private function validateInterestKeys($validator, string $field, string $label): void
    {
        $details = $this->input($field, []);

        if (!is_array($details)) {
            return;
        }

        foreach (array_keys($details) as $key) {
            if (!array_key_exists((string) $key, CvProfile::INTEREST_OPTIONS)) {
                $validator->errors()->add($field, "Kategori {$label} tidak valid.");
                break;
            }
        }
    }

    private function achievementHasValue($achievement): bool
    {
        if (!is_array($achievement)) {
            return false;
        }

        foreach (['field', 'other_field', 'achievement_type', 'rank', 'level', 'other_level', 'period'] as $field) {
            if (trim((string) ($achievement[$field] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    protected function prepareForValidation()
    {
        $npwpNumber = preg_replace('/\D+/', '', (string) $this->input('npwp_number'));

        $this->merge([
            'npwp_number' => $npwpNumber ?: null,
        ]);
    }
}
