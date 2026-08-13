<?php

namespace Tests\Unit;

use App\Services\CvUserProvisioningService;
use Tests\TestCase;

class CvUserProvisioningServiceTest extends TestCase
{
    public function test_dedicated_key_is_used_for_new_nik_hash(): void
    {
        config()->set('app.key', 'legacy-app-key');
        config()->set('services.vpeople.nik_hash_key', 'dedicated-integration-key');

        $service = app(CvUserProvisioningService::class);

        $this->assertSame(
            hash_hmac('sha256', '123456', 'dedicated-integration-key'),
            $service->hashNik('123456')
        );
        $this->assertSame(
            hash_hmac('sha256', '123456', 'legacy-app-key'),
            $service->legacyHashNik('123456')
        );
        $this->assertCount(2, $service->hashCandidates('123456'));
    }

    public function test_app_key_remains_fallback_until_dedicated_key_is_configured(): void
    {
        config()->set('app.key', 'legacy-app-key');
        config()->set('services.vpeople.nik_hash_key', null);

        $service = app(CvUserProvisioningService::class);

        $this->assertFalse($service->usesDedicatedNikHashKey());
        $this->assertSame($service->legacyHashNik('123456'), $service->hashNik('123456'));
        $this->assertCount(1, $service->hashCandidates('123456'));
    }
}
