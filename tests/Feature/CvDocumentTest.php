<?php

namespace Tests\Feature;

use App\Models\CvProfile;
use App\Models\User;
use App\Services\VPeopleLocationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CvDocumentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createSchema();

        $locationService = \Mockery::mock(VPeopleLocationService::class);
        $locationService->shouldReceive('resolveSelection')->andReturnUsing(function (array $input) {
            return [
                'province_id' => $input['province_id'] ?? null,
                'province_name' => 'Sulawesi Tenggara',
                'regency_id' => $input['regency_id'] ?? null,
                'regency_name' => 'Konawe Selatan',
                'district_id' => $input['district_id'] ?? null,
                'district_name' => 'Moramo',
                'village_id' => $input['village_id'] ?? null,
                'village_name' => 'Wawatu',
            ];
        });
        $this->app->instance(VPeopleLocationService::class, $locationService);
    }

    public function test_employee_can_upload_private_document_from_cv_draft_form()
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/cv/draft', $this->validCvPayload([
            'documents' => [
                'ktp' => UploadedFile::fake()->create('ktp-karyawan.pdf', 120, 'application/pdf'),
            ],
        ]));

        $response->assertRedirect(route('cv.edit'));

        $profileId = DB::table('cv_profiles')->where('user_id', $user->id)->value('id');
        $document = DB::table('cv_documents')
            ->where('cv_profile_id', $profileId)
            ->where('type', 'ktp')
            ->first();

        $this->assertNotNull($document);
        $this->assertSame('ktp-karyawan.pdf', $document->original_name);
        $this->assertStringStartsWith('cv-documents/' . $user->id . '/', $document->file_path);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_employee_can_save_incomplete_draft_with_document_upload()
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/cv/draft', [
            'full_name' => 'Draft Belum Lengkap',
            'documents' => [
                'certificate' => [
                    UploadedFile::fake()->create('sertifikat-draft.pdf', 120, 'application/pdf'),
                ],
            ],
        ]);

        $response->assertRedirect(route('cv.edit'));
        $response->assertSessionHasNoErrors();

        $profileId = DB::table('cv_profiles')->where('user_id', $user->id)->value('id');

        $this->assertDatabaseHas('cv_profiles', [
            'id' => $profileId,
            'full_name' => 'Draft Belum Lengkap',
            'status' => 'draft',
        ]);
        $this->assertDatabaseHas('cv_documents', [
            'cv_profile_id' => $profileId,
            'type' => 'certificate',
            'original_name' => 'sertifikat-draft.pdf',
        ]);
    }

    public function test_employee_can_upload_multiple_files_for_certificate_document_types()
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/cv/draft', $this->validCvPayload([
            'documents' => [
                'certificate' => [
                    UploadedFile::fake()->create('pelatihan-a.pdf', 120, 'application/pdf'),
                    UploadedFile::fake()->create('pelatihan-b.pdf', 120, 'application/pdf'),
                ],
                'k3_certificate' => [
                    UploadedFile::fake()->create('k3-a.pdf', 120, 'application/pdf'),
                    UploadedFile::fake()->create('k3-b.pdf', 120, 'application/pdf'),
                ],
            ],
        ]));

        $response->assertRedirect(route('cv.edit'));

        $profileId = DB::table('cv_profiles')->where('user_id', $user->id)->value('id');

        $this->assertSame(2, DB::table('cv_documents')->where('cv_profile_id', $profileId)->where('type', 'certificate')->count());
        $this->assertSame(2, DB::table('cv_documents')->where('cv_profile_id', $profileId)->where('type', 'k3_certificate')->count());
    }

    public function test_employee_can_save_bank_account_number_and_upload_skill_certificate()
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/cv/draft', $this->validCvPayload([
            'bank_account_number' => '001234567890',
            'npwp_number' => '12.345.678.9-012.345',
            'documents' => [
                'sim_b2_umum' => UploadedFile::fake()->create('sim-b2.pdf', 120, 'application/pdf'),
            ],
        ]));

        $response->assertRedirect(route('cv.edit'));

        $profileId = DB::table('cv_profiles')->where('user_id', $user->id)->value('id');
        $this->assertDatabaseHas('cv_profiles', [
            'id' => $profileId,
            'bank_account_number' => '001234567890',
            'npwp_number' => '123456789012345',
        ]);
        $this->assertDatabaseHas('cv_documents', [
            'cv_profile_id' => $profileId,
            'type' => 'sim_b2_umum',
            'original_name' => 'sim-b2.pdf',
        ]);
    }

    public function test_employee_can_use_matching_16_digit_ktp_number_as_npwp_number()
    {
        $user = User::factory()->create();
        $identityNumber = '1234567890123456';

        $response = $this->actingAs($user)->post('/cv/draft', $this->validCvPayload([
            'ktp_number' => $identityNumber,
            'npwp_number' => '1234 5678 9012 3456',
        ]));

        $response->assertRedirect(route('cv.edit'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('cv_profiles', [
            'user_id' => $user->id,
            'ktp_number' => $identityNumber,
            'npwp_number' => $identityNumber,
        ]);
    }

    public function test_employee_cannot_use_a_different_16_digit_number_as_npwp_number()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from(route('cv.edit'))->post('/cv/draft', $this->validCvPayload([
            'ktp_number' => '1234567890123456',
            'npwp_number' => '9876543210987654',
        ]));

        $response->assertRedirect(route('cv.edit'));
        $response->assertSessionHasErrors('npwp_number');
    }

    public function test_employee_can_view_own_document_inline()
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $profileId = $this->createProfileFor($user);

        Storage::disk('local')->put('cv-documents/' . $user->id . '/ktp.pdf', 'dummy-pdf-content');
        $documentId = DB::table('cv_documents')->insertGetId([
            'cv_profile_id' => $profileId,
            'type' => 'ktp',
            'original_name' => 'ktp.pdf',
            'file_path' => 'cv-documents/' . $user->id . '/ktp.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 17,
            'uploaded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/cv/documents/' . $documentId . '/download');

        $response->assertOk();
        $contentDisposition = $response->headers->get('content-disposition');

        $this->assertStringContainsString('inline', strtolower($contentDisposition));
        $this->assertStringContainsString('ktp.pdf', $contentDisposition);
        $this->assertStringNotContainsString('attachment', strtolower($contentDisposition));
    }

    public function test_employee_can_remove_document_from_cv_draft_form()
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $profileId = $this->createProfileFor($user);

        Storage::disk('local')->put('cv-documents/' . $user->id . '/certificate.pdf', 'dummy-pdf-content');
        $documentId = DB::table('cv_documents')->insertGetId([
            'cv_profile_id' => $profileId,
            'type' => 'certificate',
            'original_name' => 'certificate.pdf',
            'file_path' => 'cv-documents/' . $user->id . '/certificate.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 17,
            'uploaded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/cv/draft', $this->validCvPayload([
            'remove_documents' => [
                'certificate' => [$documentId => '1'],
            ],
        ]));

        $response->assertRedirect(route('cv.edit'));

        $this->assertDatabaseMissing('cv_documents', [
            'cv_profile_id' => $profileId,
            'type' => 'certificate',
        ]);
        Storage::disk('local')->assertMissing('cv-documents/' . $user->id . '/certificate.pdf');
    }

    public function test_employee_can_save_physical_profile_fields_from_cv_draft_form()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/cv/draft', $this->validCvPayload([
            'height_cm' => '172',
            'weight_kg' => '68.5',
            'blood_type' => CvProfile::BLOOD_TYPES[3],
        ]));

        $response->assertRedirect(route('cv.edit'));

        $this->assertDatabaseHas('cv_profiles', [
            'user_id' => $user->id,
            'height_cm' => 172,
            'weight_kg' => 68.5,
            'blood_type' => CvProfile::BLOOD_TYPES[3],
        ]);
    }

    public function test_employee_can_use_ktp_address_as_domicile_address()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/cv/draft', $this->validCvPayload([
            'ktp_address' => 'Jl. KTP No. 10',
            'domicile_same_as_ktp' => '1',
            'address' => '',
        ]));

        $response->assertRedirect(route('cv.edit'));

        $this->assertDatabaseHas('cv_profiles', [
            'user_id' => $user->id,
            'ktp_address' => 'Jl. KTP No. 10',
            'domicile_same_as_ktp' => 1,
            'address' => 'Jl. KTP No. 10',
        ]);
    }

    public function test_employee_can_save_different_domicile_address()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/cv/draft', $this->validCvPayload([
            'ktp_address' => 'Jl. KTP No. 10',
            'domicile_same_as_ktp' => '0',
            'address' => 'Jl. Domisili No. 22',
        ]));

        $response->assertRedirect(route('cv.edit'));

        $this->assertDatabaseHas('cv_profiles', [
            'user_id' => $user->id,
            'ktp_address' => 'Jl. KTP No. 10',
            'domicile_same_as_ktp' => 0,
            'address' => 'Jl. Domisili No. 22',
        ]);
    }

    public function test_employee_can_save_optional_social_media_fields_from_cv_draft_form()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/cv/draft', $this->validCvPayload([
            'instagram' => '@budi.santoso',
            'linkedin' => 'https://linkedin.com/in/budi-santoso',
            'facebook' => 'Budi Santoso',
        ]));

        $response->assertRedirect(route('cv.edit'));

        $this->assertDatabaseHas('cv_profiles', [
            'user_id' => $user->id,
            'instagram' => '@budi.santoso',
            'linkedin' => 'https://linkedin.com/in/budi-santoso',
            'facebook' => 'Budi Santoso',
        ]);
    }

    public function test_step_autosave_normalizes_phone_and_rt_rw(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $payload = $this->validCvPayload([
            'phone' => '+62 857-9731-0490',
            'rt' => '7',
            'rw' => '12',
        ]);

        unset($payload['profile_summary'], $payload['technical_skills'], $payload['experiences'], $payload['educations']);

        $response = $this->actingAs($user)->postJson('/cv/autosave', $payload);

        $response->assertOk()->assertJsonStructure(['message', 'saved_at']);
        $this->assertDatabaseHas('cv_profiles', [
            'user_id' => $user->id,
            'phone' => '085797310490',
            'rt' => '007',
            'rw' => '012',
        ]);
    }

    public function test_personal_step_rejects_missing_address_job_and_emergency_contact_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/cv/save-preview', $this->validCvPayload([
            'ktp_address' => '',
            'province_id' => '',
            'regency_id' => '',
            'district_id' => '',
            'village_id' => '',
            'address' => '',
            'work_area' => '',
            'department' => '',
            'division' => '',
            'position' => '',
            'emergency_contacts' => [],
        ]));

        $response->assertSessionHasErrors([
            'ktp_address',
            'province_id',
            'regency_id',
            'district_id',
            'village_id',
            'address',
            'work_area',
            'department',
            'division',
            'position',
            'emergency_contacts',
        ]);
    }

    public function test_employee_can_save_interests_and_repeatable_achievements(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/cv/draft', $this->validCvPayload([
            'hobbies' => ['sports' => 'Futsal', 'other' => 'Berkebun'],
            'talents' => ['arts' => 'Melukis'],
            'achievements' => [[
                'field' => 'sports',
                'achievement_type' => 'Turnamen Futsal',
                'rank' => 'Juara 1',
                'level' => 'province',
                'period' => '2025-05',
            ]],
        ]));

        $response->assertRedirect(route('cv.edit'));

        $profile = $user->cvProfile()->firstOrFail();
        $this->assertSame(['sports' => 'Futsal', 'other' => 'Berkebun'], $profile->hobbies);
        $this->assertSame('Berkebun', $profile->other_hobby);
        $this->assertSame(['arts' => 'Melukis'], $profile->talents);
        $this->assertDatabaseHas('cv_achievements', [
            'cv_profile_id' => $profile->id,
            'field' => 'sports',
            'achievement_type' => 'Turnamen Futsal',
            'rank' => 'Juara 1',
            'level' => 'province',
            'period' => '2025-05',
        ]);
    }

    public function test_starred_fields_from_step_two_onwards_are_required(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $response = $this->actingAs($user)->from('/cv/edit')->post('/cv/save-preview', $this->validCvPayload([
            'profile_summary' => '',
            'technical_skills' => '',
            'experiences' => [[
                'position' => 'HR Staff',
            ]],
            'educations' => [[
                'level' => 'S1',
            ]],
            'documents' => [
                'diploma' => [],
            ],
        ]));

        $response->assertRedirect('/cv/edit');
        $response->assertSessionHasErrors([
            'profile_summary',
            'technical_skills',
            'experiences.0.company',
            'experiences.0.department',
            'experiences.0.division',
            'experiences.0.start_month',
            'experiences.0.end_month',
            'experiences.0.responsibilities',
            'educations.0.institution',
            'educations.0.major',
            'educations.0.graduation_year',
            'documents.diploma',
        ]);
    }

    public function test_required_diploma_cannot_be_removed_without_a_replacement(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $profileId = $this->createProfileFor($user);
        $documentId = DB::table('cv_documents')->insertGetId([
            'cv_profile_id' => $profileId,
            'type' => 'diploma',
            'original_name' => 'ijazah-lama.pdf',
            'file_path' => 'cv-documents/' . $user->id . '/ijazah-lama.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'uploaded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->from('/cv/edit')->post('/cv/save-preview', $this->validCvPayload([
            'documents' => ['diploma' => []],
            'remove_documents' => [
                'diploma' => [$documentId => '1'],
            ],
        ]));

        $response->assertRedirect('/cv/edit');
        $response->assertSessionHasErrors('documents.diploma');
        $this->assertDatabaseHas('cv_documents', ['id' => $documentId]);
    }

    private function validCvPayload(array $overrides = []): array
    {
        $requiredDocuments = [
            'ktp' => UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf'),
            'family_card' => UploadedFile::fake()->create('kk.pdf', 100, 'application/pdf'),
            'npwp' => UploadedFile::fake()->create('npwp.pdf', 100, 'application/pdf'),
            'diploma' => [UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf')],
        ];
        $payload = [
            'full_name' => 'Budi Santoso',
            'birth_date' => '1990-01-01',
            'birth_place' => 'Kendari',
            'ktp_number' => '7401010101010101',
            'family_card_number' => '7401010101010102',
            'bank_account_number' => '1234567890',
            'npwp_number' => '123456789012345',
            'gender' => 'L',
            'height_cm' => '170',
            'weight_kg' => '65',
            'blood_type' => CvProfile::BLOOD_TYPES[0],
            'religion' => CvProfile::RELIGIONS[0],
            'marital_status' => 'Belum Kawin',
            'mother_name' => 'Siti Aminah',
            'photo' => UploadedFile::fake()->image('photo.jpg', 300, 400),
            'documents' => $requiredDocuments,
            'address' => 'Jl. Industri No. 1',
            'phone' => '081234567890',
            'email' => 'budi@example.com',
            'ktp_address' => 'Jl. KTP No. 10',
            'rt' => '007',
            'rw' => '012',
            'province_id' => '74',
            'regency_id' => '7401',
            'district_id' => '7401010',
            'village_id' => '7401010001',
            'address' => 'Jl. Domisili No. 10',
            'work_area' => 'VDNI',
            'department' => 'Human Resources',
            'division' => 'HR Operations',
            'position' => 'HR Staff',
            'experiences' => [[
                'position' => 'HR Staff',
                'company' => 'PT VDNI',
                'department' => 'Human Resources',
                'division' => 'HR Operations',
                'start_month' => '2020-01',
                'is_current' => '1',
                'responsibilities' => 'Mengelola administrasi karyawan',
            ]],
            'educations' => [[
                'level' => 'S1',
                'institution' => 'Universitas Test',
                'major' => 'Manajemen',
                'graduation_year' => '2019',
            ]],
            'emergency_contacts' => [[
                'phone' => '081234567890',
                'name' => 'Siti Santoso',
                'relationship' => 'Orang Tua',
            ]],
            'profile_summary' => 'Operator produksi berpengalaman.',
            'technical_skills' => 'Microsoft Excel',
        ];

        if (isset($overrides['documents'])) {
            $overrides['documents'] = array_merge($requiredDocuments, $overrides['documents']);
        }

        return array_replace($payload, $overrides);
    }

    private function createProfileFor(User $user): int
    {
        return DB::table('cv_profiles')->insertGetId([
            'user_id' => $user->id,
            'status' => 'draft',
            'full_name' => $user->name,
            'email' => $user->email,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->text('vpeople_nik_encrypted')->nullable();
            $table->string('vpeople_nik_hash', 64)->nullable()->unique();
            $table->timestamp('vpeople_last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cv_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('status', 32)->default('draft');
            $table->string('full_name');
            $table->string('photo_path')->nullable();
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('ktp_number')->nullable();
            $table->string('family_card_number')->nullable();
            $table->string('bank_account_number', 34)->nullable();
            $table->string('npwp_number', 16)->nullable();
            $table->string('gender', 8)->nullable();
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->string('blood_type', 3)->nullable();
            $table->string('religion')->nullable();
            $table->string('marital_status', 64)->nullable();
            $table->date('marriage_date')->nullable();
            $table->string('spouse_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->boolean('has_children')->default(false);
            $table->text('children_names')->nullable();
            $table->string('province_id', 32)->nullable();
            $table->string('province_name')->nullable();
            $table->string('regency_id', 32)->nullable();
            $table->string('regency_name')->nullable();
            $table->string('district_id', 32)->nullable();
            $table->string('district_name')->nullable();
            $table->string('village_id', 32)->nullable();
            $table->string('village_name')->nullable();
            $table->text('ktp_address')->nullable();
            $table->string('rt', 3)->nullable();
            $table->string('rw', 3)->nullable();
            $table->boolean('domicile_same_as_ktp')->default(false);
            $table->text('address')->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('email')->nullable();
            $table->string('instagram')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('facebook')->nullable();
            $table->string('work_area')->nullable();
            $table->string('department')->nullable();
            $table->string('division')->nullable();
            $table->string('position')->nullable();
            $table->string('job_title')->nullable();
            $table->unsignedBigInteger('job_title_id')->nullable();
            $table->date('current_job_entry_date')->nullable();
            $table->text('profile_summary')->nullable();
            $table->text('technical_skills')->nullable();
            $table->text('non_technical_skills')->nullable();
            $table->text('hobbies')->nullable();
            $table->string('other_hobby')->nullable();
            $table->text('talents')->nullable();
            $table->string('other_talent')->nullable();
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cv_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cv_profile_id');
            $table->string('type', 64);
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cv_experiences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cv_profile_id');
            $table->string('position')->nullable();
            $table->string('company')->nullable();
            $table->string('department')->nullable();
            $table->string('division')->nullable();
            $table->date('start_month')->nullable();
            $table->date('end_month')->nullable();
            $table->boolean('is_current')->default(false);
            $table->text('responsibilities')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('cv_emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cv_profile_id');
            $table->string('phone')->nullable();
            $table->string('name')->nullable();
            $table->string('relationship')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('cv_educations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cv_profile_id');
            $table->string('level')->nullable();
            $table->string('institution')->nullable();
            $table->string('major')->nullable();
            $table->unsignedInteger('graduation_year')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('cv_certifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cv_profile_id');
            $table->string('name')->nullable();
            $table->string('issuer')->nullable();
            $table->unsignedInteger('year')->nullable();
            $table->unsignedInteger('valid_until_year')->nullable();
            $table->boolean('is_lifetime')->default(false);
            $table->string('type')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('cv_languages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cv_profile_id');
            $table->string('language')->nullable();
            $table->string('level')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('cv_projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cv_profile_id');
            $table->string('name')->nullable();
            $table->unsignedInteger('year')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('cv_organizations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cv_profile_id');
            $table->string('organization_name')->nullable();
            $table->string('role')->nullable();
            $table->unsignedInteger('start_year')->nullable();
            $table->unsignedInteger('end_year')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('cv_achievements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cv_profile_id');
            $table->string('field');
            $table->string('other_field')->nullable();
            $table->string('achievement_type');
            $table->string('rank');
            $table->string('level');
            $table->string('other_level')->nullable();
            $table->string('period', 7);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }
}
