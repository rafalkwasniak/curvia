<?php

namespace App\Services;

use GdImage;
use RuntimeException;

/**
 * Draws the branded overlay on top of a generated photo and returns a 1200x630
 * WebP. A left-side dark scrim is laid down first so the text is legible on any
 * crop, then the CURVIA logo, the post title (auto-fitted, wrapped), a yellow
 * accent rule and a fixed kicker line. All text is drawn here with a bundled
 * Polish-capable font - the image model never renders letters.
 */
class ImageComposer
{
    private const WIDTH = 1200;

    private const HEIGHT = 630;

    private const PAD_LEFT = 70;

    private const SCRIM_FADE = 0.62;   // scrim reaches transparent at 62% width

    private const SCRIM_OPACITY = 0.9; // black opacity at the far left edge

    private const TITLE_MAX_WIDTH = 560;

    private const TITLE_SIZE_MAX = 52;

    private const TITLE_SIZE_MIN = 30;

    private const TITLE_MAX_LINES = 3;

    public function compose(string $imageBinary, string $title): string
    {
        $canvas = $this->coverResize($imageBinary);

        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);

        $this->drawScrim($canvas);
        $this->drawBlock($canvas, trim($title));

        return $this->toWebp($canvas);
    }

    /**
     * Scale the source to fully cover 1200x630 and centre-crop the overflow.
     */
    private function coverResize(string $imageBinary): GdImage
    {
        $src = @imagecreatefromstring($imageBinary);

        if ($src === false) {
            throw new RuntimeException('Nie udało się odczytać wygenerowanego obrazu.');
        }

        $sw = imagesx($src);
        $sh = imagesy($src);
        $scale = max(self::WIDTH / $sw, self::HEIGHT / $sh);
        $rw = (int) round($sw * $scale);
        $rh = (int) round($sh * $scale);

        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagecopyresampled(
            $canvas, $src,
            (int) round((self::WIDTH - $rw) / 2), (int) round((self::HEIGHT - $rh) / 2),
            0, 0,
            $rw, $rh, $sw, $sh,
        );

        imagedestroy($src);

        return $canvas;
    }

    /**
     * Black gradient from the left edge fading to transparent, so left-aligned
     * text stays readable regardless of what the photo shows there.
     */
    private function drawScrim(GdImage $canvas): void
    {
        $fadeEnd = (int) round(self::WIDTH * self::SCRIM_FADE);

        for ($x = 0; $x < $fadeEnd; $x++) {
            $opacity = self::SCRIM_OPACITY * (1 - $x / $fadeEnd);
            $gdAlpha = 127 - (int) round($opacity * 127);
            $color = imagecolorallocatealpha($canvas, 0, 0, 0, $gdAlpha);
            imagefilledrectangle($canvas, $x, 0, $x, self::HEIGHT, $color);
        }
    }

    private function drawBlock(GdImage $canvas, string $title): void
    {
        [$ar, $ag, $ab] = $this->hexToRgb((string) config('curvia.image.overlay.accent_color', '#EAB227'));
        $accent = imagecolorallocate($canvas, $ar, $ag, $ab);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $muted = imagecolorallocate($canvas, 210, 210, 210);

        $fontTitle = base_path((string) config('curvia.image.overlay.font_title'));
        $fontKicker = base_path((string) config('curvia.image.overlay.font_kicker'));

        // Logo.
        $logo = $this->loadLogo();
        $logoW = 300;
        $logoH = (int) round(imagesy($logo) * ($logoW / imagesx($logo)));
        $y = 165;
        imagecopyresampled($canvas, $logo, self::PAD_LEFT, $y, 0, 0, $logoW, $logoH, imagesx($logo), imagesy($logo));
        imagedestroy($logo);
        $y += $logoH + 46;

        // Title - largest size that wraps within the width and line budget.
        [$size, $lines] = $this->fitTitle($title, $fontTitle);
        $lineHeight = (int) round($size * 1.34);

        foreach ($lines as $line) {
            imagettftext($canvas, $size, 0, self::PAD_LEFT, $y + $size, $white, $fontTitle, $line);
            $y += $lineHeight;
        }

        // Yellow accent rule.
        $y += 18;
        imagefilledrectangle($canvas, self::PAD_LEFT, $y, self::PAD_LEFT + 64, $y + 5, $accent);
        $y += 5 + 34;

        // Kicker.
        $kicker = (string) config('curvia.image.overlay.kicker', '');
        if ($kicker !== '') {
            imagettftext($canvas, 19, 0, self::PAD_LEFT, $y, $muted, $fontKicker, $kicker);
        }
    }

    /**
     * Find the biggest title size that wraps into at most TITLE_MAX_LINES lines
     * within TITLE_MAX_WIDTH; fall back to the minimum size if nothing fits.
     *
     * @return array{0: int, 1: array<int, string>}
     */
    private function fitTitle(string $title, string $font): array
    {
        for ($size = self::TITLE_SIZE_MAX; $size >= self::TITLE_SIZE_MIN; $size -= 2) {
            $lines = $this->wrap($title, $font, $size, self::TITLE_MAX_WIDTH);

            if (count($lines) <= self::TITLE_MAX_LINES) {
                return [$size, $lines];
            }
        }

        return [self::TITLE_SIZE_MIN, $this->wrap($title, $font, self::TITLE_SIZE_MIN, self::TITLE_MAX_WIDTH)];
    }

    /**
     * @return array<int, string>
     */
    private function wrap(string $text, string $font, int $size, int $maxWidth): array
    {
        $words = preg_split('/\s+/', $text) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;
            $box = imagettfbbox($size, 0, $font, $candidate);
            $width = abs($box[2] - $box[0]);

            if ($width <= $maxWidth || $current === '') {
                $current = $candidate;
            } else {
                $lines[] = $current;
                $current = $word;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    private function loadLogo(): GdImage
    {
        $path = public_path((string) config('curvia.image.overlay.logo_path'));
        $logo = @imagecreatefrompng($path);

        if ($logo === false) {
            throw new RuntimeException('Nie udało się wczytać logo nakładki.');
        }

        imagealphablending($logo, false);
        imagesavealpha($logo, true);

        return $logo;
    }

    private function toWebp(GdImage $canvas): string
    {
        $quality = (int) config('curvia.image.output_quality', 80);

        ob_start();
        imagewebp($canvas, null, $quality);
        $binary = (string) ob_get_clean();
        imagedestroy($canvas);

        return $binary;
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}
