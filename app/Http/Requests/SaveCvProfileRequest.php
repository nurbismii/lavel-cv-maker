<?php

namespace App\Http\Requests;

use App\Models\CvEmergencyContact;
use App\Models\CvDocument;
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
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date'],
            'ktp_number' => ['nullable', 'digits:16'],
            'family_card_number' => ['nullable', 'digits:16'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'remove_documents' => ['nullable', 'array'],
            'remove_documents.*' => ['nullable', 'boolean'],
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
            'province_id' => ['nullable', 'string', 'max:32'],
            'regency_id' => ['nullable', 'string', 'max:32'],
            'district_id' => ['nullable', 'string', 'max:32'],
            'village_id' => ['nullable', 'string', 'max:32'],
            'ktp_address' => ['nullable', 'string', 'max:2000'],
            'domicile_same_as_ktp' => ['nullable', 'boolean'],
            'address' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:64'],
            'email' => ['required', 'email', 'max:255'],
            'work_area' => ['nullable', 'string', Rule::in(VPeopleOrganizationService::supportedWorkAreaCodes())],
            'department' => ['nullable', 'string', 'max:255'],
            'department_custom' => ['nullable', 'string', 'max:255'],
            'division' => ['nullable', 'string', 'max:255'],
            'division_custom' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'position_custom' => ['nullable', 'string', 'max:255'],
            'profile_summary' => ['nullable', 'string', 'max:300'],
            'technical_skills' => ['nullable', 'string', 'max:1000'],
            'non_technical_skills' => ['nullable', 'string', 'max:1000'],

            'emergency_contacts' => ['nullable', 'array'],
            'emergency_contacts.*.phone' => ['nullable', 'digits_between:10,13'],
            'emergency_contacts.*.name' => ['nullable', 'string', 'max:255'],
            'emergency_contacts.*.relationship' => ['nullable', Rule::in(CvEmergencyContact::RELATIONSHIPS)],

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
        ];
    }

    public function messages()
    {
        return [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'birth_date.required' => 'Tanggal lahir wajib diisi.',
            'birth_date.date' => 'Format tanggal lahir tidak valid.',
            'ktp_number.digits' => 'No. KTP harus berisi 16 digit angka.',
            'family_card_number.digits' => 'No. KK harus berisi 16 digit angka.',
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
            '*.date_format' => 'Format bulan/tahun tidak valid.',
            '*.max' => 'Input melebihi batas karakter yang diperbolehkan.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
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
        });
    }
}
