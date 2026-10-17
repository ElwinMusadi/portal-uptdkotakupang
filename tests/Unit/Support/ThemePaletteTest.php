<?php

namespace Tests\Unit\Support;

use App\Support\ThemePalette;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class ThemePaletteTest extends TestCase
{
    #[TestWith(['#2B63F6'])]
    #[TestWith(['#F7F6F2'])]
    #[TestWith(['#0A0A0C'])]
    #[TestWith(['#C2410C'])]
    #[TestWith(['#808080'])]
    public function test_converts_a_colour_to_oklch_and_back(string $hex): void
    {
        $this->assertSame($hex, ThemePalette::hex(...ThemePalette::oklch($hex)));
    }

    public function test_gives_pintu_blue_its_oklch_coordinates(): void
    {
        [$lightness, $chroma, $hue] = ThemePalette::oklch('#2B63F6');

        $this->assertEqualsWithDelta(0.557, $lightness, 0.001);
        $this->assertEqualsWithDelta(0.228, $chroma, 0.001);
        $this->assertEqualsWithDelta(264.2, $hue, 0.1);
    }

    public function test_brings_a_colour_outside_srgb_into_it_by_lowering_its_chroma(): void
    {
        $hex = ThemePalette::hex(0.9, 0.4, 264);

        [$lightness, $chroma, $hue] = ThemePalette::oklch($hex);
        $this->assertEqualsWithDelta(0.9, $lightness, 0.01);
        $this->assertLessThan(0.4, $chroma);
        $this->assertEqualsWithDelta(264, $hue, 2);
    }

    public function test_measures_the_wcag_contrast_ratio(): void
    {
        $this->assertEqualsWithDelta(21.0, ThemePalette::contrast('#000000', '#FFFFFF'), 0.001);
        $this->assertEqualsWithDelta(21.0, ThemePalette::contrast('#FFFFFF', '#000000'), 0.001);
        $this->assertEqualsWithDelta(1.0, ThemePalette::contrast('#2B63F6', '#2B63F6'), 0.001);
        $this->assertEqualsWithDelta(4.98, ThemePalette::contrast('#FFFFFF', '#2B63F6'), 0.01);
    }

    public function test_leaves_a_colour_that_is_readable_already(): void
    {
        $this->assertSame('#2B63F6', ThemePalette::darkenedFor('#2b63f6', ['#FFFFFF']));
        $this->assertSame('#7FA4FF', ThemePalette::lightenedFor('#7FA4FF', ['#17181B']));
    }

    public function test_darkens_a_light_colour_just_enough_to_be_readable_on_light_backgrounds(): void
    {
        $against = ['#FFFFFF', '#F7F6F2'];

        $darkened = ThemePalette::darkenedFor('#F2C46B', $against);

        foreach ($against as $background) {
            $this->assertGreaterThanOrEqual(ThemePalette::MIN_CONTRAST, ThemePalette::contrast($darkened, $background));
        }
        [$lightness, , $hue] = ThemePalette::oklch($darkened);
        $this->assertLessThan(ThemePalette::MIN_CONTRAST, ThemePalette::contrast(ThemePalette::tone($darkened, $lightness + 0.01, 1, 1), '#F7F6F2'));
        $this->assertEqualsWithDelta(ThemePalette::oklch('#F2C46B')[2], $hue, 2);
    }

    public function test_lightens_a_dark_colour_just_enough_to_be_readable_on_dark_backgrounds(): void
    {
        $lightened = ThemePalette::lightenedFor('#1A3FA8', ['#0A0A0C', '#17181B']);

        $this->assertGreaterThanOrEqual(ThemePalette::MIN_CONTRAST, ThemePalette::contrast($lightened, '#17181B'));
        [$lightness] = ThemePalette::oklch($lightened);
        $this->assertLessThan(ThemePalette::MIN_CONTRAST, ThemePalette::contrast(ThemePalette::tone($lightened, $lightness - 0.01, 1, 1), '#17181B'));
    }

    public function test_makes_a_tone_with_a_share_of_the_chroma_up_to_a_cap(): void
    {
        [$lightness, $chroma, $hue] = ThemePalette::oklch(ThemePalette::tone('#2B63F6', 0.8, 0.5, 0.06));
        $this->assertEqualsWithDelta(0.8, $lightness, 0.005);
        $this->assertEqualsWithDelta(0.06, $chroma, 0.005);
        $this->assertEqualsWithDelta(264.2, $hue, 1);

        $this->assertEqualsWithDelta(0.114, ThemePalette::oklch(ThemePalette::tone('#2B63F6', 0.6, 0.5, 0.2))[1], 0.005);
        $this->assertEqualsWithDelta(0.0, ThemePalette::oklch(ThemePalette::tone('#808080', 0.5, 1, 0.2))[1], 0.001);
    }

    public function test_blends_a_colour_over_another_like_a_browser(): void
    {
        $this->assertSame('#BCBDBF', ThemePalette::blend('#FFFFFF', '#0F1319', 0.72));
        $this->assertSame('#0F1319', ThemePalette::blend('#0F1319', '#FFFFFF', 1));
        $this->assertSame('#FFFFFF', ThemePalette::blend('#0F1319', '#FFFFFF', 0));
    }

    public function test_writes_a_transparent_colour_like_the_portal_tokens(): void
    {
        $this->assertSame('rgba(43, 99, 246, .18)', ThemePalette::rgba('#2B63F6', 0.18));
        $this->assertSame('rgba(127, 164, 255, .1)', ThemePalette::rgba('#7FA4FF', 0.1));
        $this->assertSame('rgba(127, 164, 255, .35)', ThemePalette::rgba('#7FA4FF', 0.35));
    }
}
