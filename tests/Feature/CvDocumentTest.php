<?php

namespace Tests\Feature;

use App\Models\CvProfile;
use App\Models\User;
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

        Storage::disk('local')->put('cv-documents/' . $user->id . '/kk.pdf', 'dummy-pdf-content');
        DB::table('cv_documents')->insert([
            'cv_profile_id' => $profileId,
            'type' => 'family_card',
            'original_name' => 'kk.pdf',
            'file_path' => 'cv-documents/' . $user->id . '/kk.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 17,
            'uploaded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/cv/draft', $this->validCvPayload([
            'remove_documents' => [
                'family_card' => '1',
            ],
        ]));

        $response->assertRedirect(route('cv.edit'));

        $this->assertDatabaseMissing('cv_documents', [
            'cv_profile_id' => $profileId,
            'type' => 'family_card',
        ]);
        Storage::disk('local')->assertMissing('cv-documents/' . $user->id . '/kk.pdf');
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

    private function validCvPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Budi Santoso',
            'birth_date' => '1990-01-01',
            'birth_place' => 'Kendari',
            'gender' => 'L',
            'marital_status' => 'Belum Kawin',
            'address' => 'Jl. Industri No. 1',
            'phone' => '081234567890',
            'email' => 'budi@example.com',
            'profile_summary' => 'Operator produksi berpengalaman.',
            'technical_skills' => 'Microsoft Excel',
        ], $overrides);
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
            $table->boolean('domicile_same_as_ktp')->default(false);
            $table->text('address')->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('email')->nullable();
            $table->string('work_area')->nullable();
            $table->string('department')->nullable();
            $table->string('division')->nullable();
            $table->string('position')->nullable();
            $table->date('current_job_entry_date')->nullable();
            $table->text('profile_summary')->nullable();
            $table->text('technical_skills')->nullable();
            $table->text('non_technical_skills')->nullable();
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
    }
}
