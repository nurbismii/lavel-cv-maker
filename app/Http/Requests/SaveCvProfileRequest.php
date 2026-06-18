<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'birth_place' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
            'gender' => ['nullable', 'in:L,P'],
            'marital_status' => ['nullable', 'string', 'max:64'],
            'province_id' => ['nullable', 'string', 'max:32'],
            'regency_id' => ['nullable', 'string', 'max:32'],
            'district_id' => ['nullable', 'string', 'max:32'],
            'village_id' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:64'],
            'email' => ['required', 'email', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'profile_summary' => ['nullable', 'string', 'max:300'],
            'technical_skills' => ['nullable', 'string', 'max:1000'],
            'non_technical_skills' => ['nullable', 'string', 'max:1000'],

            'experiences' => ['nullable', 'array'],
            'experiences.*.position' => ['nullable', 'string', 'max:255'],
            'experiences.*.company' => ['nullable', 'string', 'max:255'],
            'experiences.*.department' => ['nullable', 'string', 'max:255'],
            'experiences.*.start_month' => ['nullable', 'date_format:Y-m'],
            'experiences.*.end_month' => ['nullable', 'date_format:Y-m'],
            'experiences.*.is_current' => ['nullable', 'boolean'],
            'experiences.*.responsibilities' => ['nullable', 'string', 'max:1500'],

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
            'photo.image' => 'File foto harus berupa gambar.',
            'photo.mimes' => 'Foto hanya boleh JPG atau PNG.',
            'photo.max' => 'Ukuran foto maksimal 2MB.',
            'profile_summary.max' => 'Ringkasan profil maksimal 300 karakter.',
            '*.date_format' => 'Format bulan/tahun tidak valid.',
            '*.max' => 'Input melebihi batas karakter yang diperbolehkan.',
        ];
    }
}
