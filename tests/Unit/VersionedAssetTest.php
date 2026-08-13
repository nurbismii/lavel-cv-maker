<?php

namespace Tests\Unit;

use App\Support\VersionedAsset;
use Tests\TestCase;

class VersionedAssetTest extends TestCase
{
    public function test_existing_asset_url_contains_its_modification_version()
    {
        $path = 'js/app.js';
        $url = VersionedAsset::url($path);

        $this->assertStringContainsString('v=' . filemtime(public_path($path)), $url);
    }

    public function test_missing_asset_uses_regular_url_without_throwing_an_error()
    {
        $url = VersionedAsset::url('js/asset-that-does-not-exist.js');

        $this->assertSame(asset('js/asset-that-does-not-exist.js'), $url);
    }

    public function test_local_styles_and_scripts_use_the_shared_versioning_helper()
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $cvEdit = file_get_contents(resource_path('views/cv/edit.blade.php'));

        $this->assertStringContainsString("VersionedAsset::url('css/app.css')", $layout);
        $this->assertStringContainsString("VersionedAsset::url('js/app.js')", $layout);
        $this->assertStringContainsString("VersionedAsset::url('js/cv-form.js')", $cvEdit);
        $this->assertStringNotContainsString('filemtime(public_path(', $layout . $cvEdit);
    }
}
