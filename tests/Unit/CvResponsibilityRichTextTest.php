<?php

namespace Tests\Unit;

use App\Support\CvResponsibilityRichText;
use PHPUnit\Framework\TestCase;

class CvResponsibilityRichTextTest extends TestCase
{
    public function test_it_sanitizes_rich_text_html()
    {
        $html = '<p onclick="alert(1)">Memimpin <strong>tim</strong><script>alert(1)</script></p><img src=x onerror=alert(1)>';

        $this->assertSame(
            '<p>Memimpin <strong>tim</strong></p>',
            CvResponsibilityRichText::sanitize($html)
        );
    }

    public function test_it_converts_legacy_responsibility_list_to_html()
    {
        $this->assertSame(
            '<ul><li>Merawat mesin</li><li>Membuat laporan</li></ul>',
            CvResponsibilityRichText::toOutputHtml(['Merawat mesin', 'Membuat laporan'])
        );
    }
}
