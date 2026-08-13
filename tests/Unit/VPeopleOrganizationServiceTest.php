<?php

namespace Tests\Unit;

use App\Services\VPeopleOrganizationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VPeopleOrganizationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.vpeople', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('vpeople');

        Schema::connection('vpeople')->create('employees', function (Blueprint $table) {
            $table->increments('id');
            $table->string('status_resign');
            $table->string('jabatan')->nullable();
            $table->string('posisi')->nullable();
            $table->unsignedInteger('departemen_id')->nullable();
            $table->unsignedInteger('divisi_id')->nullable();
        });

        Schema::connection('vpeople')->create('job_titles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('name_zh')->nullable();
            $table->boolean('is_active')->default(true);
        });
    }

    protected function tearDown(): void
    {
        DB::purge('vpeople');

        parent::tearDown();
    }

    public function test_job_titles_return_all_active_master_titles_without_organization_filter(): void
    {
        DB::connection('vpeople')->table('job_titles')->insert([
            ['id' => 14, 'name' => 'ADMIN', 'name_zh' => '文员', 'is_active' => true],
            ['id' => 11, 'name' => 'PENGAWAS', 'name_zh' => '班长', 'is_active' => true],
            ['id' => 13, 'name' => 'STAFF', 'name_zh' => '职员', 'is_active' => true],
            ['id' => 4, 'name' => 'SUPERVISOR', 'name_zh' => '调度', 'is_active' => true],
            ['id' => 99, 'name' => 'TIDAK AKTIF', 'name_zh' => null, 'is_active' => false],
        ]);

        $options = (new VPeopleOrganizationService())->jobTitles('1', '1');

        $this->assertSame([
            ['id' => '14', 'name' => 'ADMIN 文员'],
            ['id' => '11', 'name' => 'PENGAWAS 班长'],
            ['id' => '13', 'name' => 'STAFF 职员'],
            ['id' => '4', 'name' => 'SUPERVISOR 调度'],
        ], $options);
    }

    public function test_positions_use_position_column_and_respect_selected_division(): void
    {
        DB::connection('vpeople')->table('employees')->insert([
            ['status_resign' => 'AKTIF', 'jabatan' => 'Supervisor', 'posisi' => 'Produksi', 'departemen_id' => 1, 'divisi_id' => 1],
            ['status_resign' => 'AKTIF', 'jabatan' => '', 'posisi' => 'Operator', 'departemen_id' => 1, 'divisi_id' => 1],
            ['status_resign' => 'AKTIF', 'jabatan' => 'Manager', 'posisi' => 'Keuangan', 'departemen_id' => 2, 'divisi_id' => 2],
        ]);

        $options = (new VPeopleOrganizationService())->positions('1', '1');

        $this->assertSame([
            ['id' => 'Operator', 'name' => 'Operator'],
            ['id' => 'Produksi', 'name' => 'Produksi'],
        ], $options);
    }
}
