<?php

namespace Tests\Unit;

use Picqer\Barcode\BarcodeGeneratorHTML;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Tests\TestCase;

class BarcodeGeneratorCompatibilityTest extends TestCase
{
    public function test_html_generator_builds_code128_markup(): void
    {
        $generator = new BarcodeGeneratorHTML;

        $html = $generator->getBarcode('ABC123', $generator::TYPE_CODE_128);

        $this->assertIsString($html);
        $this->assertNotSame('', trim($html));
        $this->assertStringContainsString('<div style="font-size:0;position:relative;', $html);
        $this->assertStringContainsString('background-color:rgb(0,0,0);', $html);
    }

    public function test_svg_generator_builds_svg_and_escapes_special_chars_in_description(): void
    {
        $generator = new BarcodeGeneratorSVG;

        $svg = $generator->getBarcode('A&B<12>', $generator::TYPE_CODE_128);

        $this->assertIsString($svg);
        $this->assertNotSame('', trim($svg));
        $this->assertStringContainsString('<svg width="', $svg);
        $this->assertStringContainsString('<desc>A&amp;B&lt;12&gt;</desc>', $svg);
        $this->assertStringContainsString('<rect x="', $svg);
    }
}
