<?php

namespace Tests\Unit;

use App\Support\CvResponsibilityRichText;
use PHPUnit\Framework\TestCase;

class CvResponsibilityRichTextTest extends TestCase
{
    public function test_it_sanitizes_legacy_rich_text_html()
    {
        $html = '<p onclick="alert(1)">Memimpin <strong>tim</strong><script>alert(1)</script></p><img src=x onerror=alert(1)>';

        $this->assertSame(
            '<p>Memimpin <strong>tim</strong></p>',
            CvResponsibilityRichText::sanitize($html)
        );
    }

    public function test_it_converts_textarea_lines_to_bullet_html()
    {
        $stored = CvResponsibilityRichText::toStorage("Merawat mesin\n\nMembuat laporan");

        $this->assertSame(['Merawat mesin', 'Membuat laporan'], $stored);
        $this->assertSame(
            '<ul><li>Merawat mesin</li><li>Membuat laporan</li></ul>',
            CvResponsibilityRichText::toOutputHtml($stored)
        );
    }

    public function test_it_converts_legacy_responsibility_list_to_bullet_html()
    {
        $this->assertSame(
            '<ul><li>Merawat mesin</li><li>Membuat laporan</li></ul>',
            CvResponsibilityRichText::toOutputHtml(['Merawat mesin', 'Membuat laporan'])
        );
    }

    public function test_it_converts_legacy_html_to_textarea_lines()
    {
        $this->assertSame(
            "Memimpin tim\nMembuat laporan",
            CvResponsibilityRichText::toTextareaText([
                'html' => '<p>Memimpin <strong>tim</strong></p><ul><li>Membuat laporan</li></ul>',
            ])
        );
    }

    public function test_it_escapes_textarea_content_on_output()
    {
        $this->assertSame(
            '<ul><li>&lt;script&gt;alert(1)&lt;/script&gt;</li></ul>',
            CvResponsibilityRichText::toOutputHtml(['<script>alert(1)</script>'])
        );
    }
}
