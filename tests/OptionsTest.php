<?php

declare(strict_types=1);

namespace PhpMlKit\Opal\Tests;

use PhpMlKit\Opal\LoadOptions;
use PhpMlKit\Opal\SaveOptions;
use PhpMlKit\Opal\TextOptions;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class OptionsTest extends TestCase
{
    // -------------------------------------------------------------------------
    // LoadOptions
    // -------------------------------------------------------------------------

    public function testLoadOptionsDefault(): void
    {
        $options = LoadOptions::default();
        $this->assertTrue($options->autoRotate);
        $this->assertNull($options->page);
        $this->assertNull($options->n);
        $this->assertNull($options->shrink);
        $this->assertNull($options->scale);
    }

    public function testLoadOptionsWithPage(): void
    {
        $options = LoadOptions::default()->withPage(2);
        $this->assertSame(2, $options->page);
    }

    public function testLoadOptionsWithN(): void
    {
        $options = LoadOptions::default()->withN(5);
        $this->assertSame(5, $options->n);
    }

    public function testLoadOptionsWithAutoRotateFalse(): void
    {
        $options = LoadOptions::default()->withAutoRotate(false);
        $this->assertFalse($options->autoRotate);
    }

    public function testLoadOptionsWithShrink(): void
    {
        $options = LoadOptions::default()->withShrink(2);
        $this->assertSame(2, $options->shrink);
    }

    public function testLoadOptionsWithScale(): void
    {
        $options = LoadOptions::default()->withScale(0.5);
        $this->assertSame(0.5, $options->scale);
    }

    public function testLoadOptionsToVipsOptionsDefaults(): void
    {
        $vips = LoadOptions::default()->toVipsOptions();
        $this->assertSame([], $vips);
    }

    public function testLoadOptionsToVipsOptionsAutoRotateFalse(): void
    {
        $vips = LoadOptions::default()->withAutoRotate(false)->toVipsOptions();
        $this->assertFalse($vips['autorotate']);
    }

    public function testLoadOptionsToVipsOptionsWithPage(): void
    {
        $vips = LoadOptions::default()->withPage(1)->toVipsOptions();
        $this->assertSame(1, $vips['page']);
    }

    // -------------------------------------------------------------------------
    // SaveOptions
    // -------------------------------------------------------------------------

    public function testSaveOptionsJpegDefaults(): void
    {
        $options = SaveOptions::jpeg();
        $vips = $options->toVipsOptions();
        $this->assertSame(85, $vips['Q']);
        $this->assertTrue($vips['strip']);
    }

    public function testSaveOptionsJpegCustom(): void
    {
        $options = SaveOptions::jpeg(70, false, true);
        $vips = $options->toVipsOptions();
        $this->assertSame(70, $vips['Q']);
        $this->assertFalse($vips['strip']);
        $this->assertTrue($vips['interlace']);
    }

    public function testSaveOptionsPngDefaults(): void
    {
        $options = SaveOptions::png();
        $vips = $options->toVipsOptions();
        $this->assertSame(6, $vips['compression']);
        $this->assertTrue($vips['strip']);
    }

    public function testSaveOptionsPngCustom(): void
    {
        $options = SaveOptions::png(9, false, true);
        $vips = $options->toVipsOptions();
        $this->assertSame(9, $vips['compression']);
        $this->assertFalse($vips['strip']);
        $this->assertTrue($vips['interlace']);
    }

    public function testSaveOptionsWebpDefaults(): void
    {
        $options = SaveOptions::webp();
        $vips = $options->toVipsOptions();
        $this->assertSame(80, $vips['Q']);
        $this->assertTrue($vips['strip']);
    }

    public function testSaveOptionsWebpLossless(): void
    {
        $options = SaveOptions::webp(90, true, false);
        $vips = $options->toVipsOptions();
        $this->assertSame(90, $vips['Q']);
        $this->assertTrue($vips['lossless']);
    }

    public function testSaveOptionsTiffDefaults(): void
    {
        $options = SaveOptions::tiff();
        $vips = $options->toVipsOptions();
        $this->assertSame(75, $vips['Q']);
        $this->assertFalse($vips['strip']);
    }

    public function testSaveOptionsAvifDefaults(): void
    {
        $options = SaveOptions::avif();
        $vips = $options->toVipsOptions();
        $this->assertSame(50, $vips['Q']);
        $this->assertSame(5, $vips['speed']);
    }

    public function testSaveOptionsHeifDefaults(): void
    {
        $options = SaveOptions::heif();
        $vips = $options->toVipsOptions();
        $this->assertSame(50, $vips['Q']);
    }

    public function testSaveOptionsWithQuality(): void
    {
        $options = SaveOptions::jpeg()->withQuality(95);
        $this->assertSame(95, $options->toVipsOptions()['Q']);
    }

    public function testSaveOptionsWithStrip(): void
    {
        $options = SaveOptions::jpeg()->withStrip(false);
        $this->assertFalse($options->toVipsOptions()['strip']);
    }

    // -------------------------------------------------------------------------
    // TextOptions
    // -------------------------------------------------------------------------

    public function testTextOptionsDefault(): void
    {
        $options = TextOptions::default();
        $vips = $options->toVipsOptions();
        $this->assertSame([], $vips);
    }

    public function testTextOptionsWithFontAndSize(): void
    {
        $options = TextOptions::default()->withFont('sans-serif')->withFontSize(24);
        $vips = $options->toVipsOptions();
        $this->assertSame('sans-serif 24', $vips['font']);
    }

    public function testTextOptionsWithFontSizeAloneUsesDefaultFamily(): void
    {
        $options = TextOptions::default()->withFontSize(48);
        $vips = $options->toVipsOptions();
        $this->assertSame(TextOptions::DEFAULT_FONT_FAMILY.' 48', $vips['font']);
    }

    public function testTextOptionsWithFontAloneUsesDefaultSize(): void
    {
        $options = TextOptions::default()->withFont('Helvetica');
        $vips = $options->toVipsOptions();
        $this->assertSame('Helvetica '.TextOptions::DEFAULT_FONT_SIZE, $vips['font']);
    }

    public function testTextOptionsWithFontAndFile(): void
    {
        $options = TextOptions::default()->withFont('Inter', '/path/to/font.ttf');
        $vips = $options->toVipsOptions();
        $this->assertSame('/path/to/font.ttf', $vips['fontfile']);
        $this->assertSame('Inter '.TextOptions::DEFAULT_FONT_SIZE, $vips['font']);
    }

    public function testTextOptionsWithFontFileAndCustomSize(): void
    {
        $options = TextOptions::default()->withFont('Inter', '/path/to/font.ttf')->withFontSize(24);
        $vips = $options->toVipsOptions();
        $this->assertSame('/path/to/font.ttf', $vips['fontfile']);
        $this->assertSame('Inter 24', $vips['font']);
    }

    public function testTextOptionsWithFontClearsPriorFile(): void
    {
        // Setting a file with one family, then changing to a different
        // family without a file, should clear the file (file is tied to
        // the family, not a separate setting).
        $withFile = TextOptions::default()->withFont('Caveat', '/path/to/caveat.ttf');
        $this->assertSame('/path/to/caveat.ttf', $withFile->fontFile);

        $withoutFile = $withFile->withFont('Helvetica');
        $this->assertSame('Helvetica', $withoutFile->fontFamily);
        $this->assertNull($withoutFile->fontFile);
    }

    public function testTextOptionsWithWidth(): void
    {
        $options = TextOptions::default()->withWidth(300);
        $this->assertSame(300, $options->toVipsOptions()['width']);
    }

    public function testTextOptionsWithHeight(): void
    {
        $options = TextOptions::default()->withHeight(200);
        $this->assertSame(200, $options->toVipsOptions()['height']);
    }

    public function testTextOptionsWithAlign(): void
    {
        $options = TextOptions::default()->withAlign('centre');
        $this->assertSame('centre', $options->toVipsOptions()['align']);
    }

    public function testTextOptionsWithJustify(): void
    {
        $options = TextOptions::default()->withJustify(true);
        $this->assertTrue($options->toVipsOptions()['justify']);
    }

    public function testTextOptionsWithDpi(): void
    {
        $options = TextOptions::default()->withDpi(300);
        $this->assertSame(300, $options->toVipsOptions()['dpi']);
    }

    public function testTextOptionsWithRgba(): void
    {
        $options = TextOptions::default()->withRgba();
        $this->assertTrue($options->toVipsOptions()['rgba']);
    }

    public function testTextOptionsWithSpacing(): void
    {
        $options = TextOptions::default()->withSpacing(4);
        $this->assertSame(4, $options->toVipsOptions()['spacing']);
    }

    public function testTextOptionsWithWrap(): void
    {
        $options = TextOptions::default()->withWrap('word');
        $this->assertSame('word', $options->toVipsOptions()['wrap']);
    }

    public function testTextOptionsImmutability(): void
    {
        $original = TextOptions::default();
        $modified = $original->withFont('Arial');
        $this->assertNull($original->fontFamily);
        $this->assertSame('Arial', $modified->fontFamily);
    }
}
