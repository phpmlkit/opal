#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * phpmlkit/opal comparison benchmark — GD vs Imagick vs Opal.
 *
 * Generates test images at four sizes, then measures the full pipeline:
 * load → resize → rotate 45° → sharpen for each library.
 *
 * Usage: php benchmarks/opal-vs-gd-vs-imagick.php
 */

require_once __DIR__.'/../vendor/autoload.php';

use PhpMlKit\NDArray\DType;
use PhpMlKit\Opal\Image as OpalImage;
use PhpMlKit\Opal\Kernel;

use function PhpMlKit\NDArray\linspace;
use function PhpMlKit\NDArray\random;
use function PhpMlKit\NDArray\stack;
use function PhpMlKit\NDArray\zeros;

const IMG_DIR = __DIR__.'/images';
const OUT_DIR = __DIR__.'/output';

// ─── image generation ─────────────────────

function generateImages(): void
{
    @mkdir(IMG_DIR, 0777, true);

    $sizes = [
        'small.jpg' => [640, 480],
        'medium.jpg' => [1920, 1080],
        'large.jpg' => [4000, 2670],
        'xlarge.jpg' => [6000, 4000],
    ];

    foreach ($sizes as $name => [$w, $h]) {
        $path = IMG_DIR."/{$name}";
        if (file_exists($path)) {
            continue;
        }

        $xCoords = linspace(0, $w * 0.01, $w)->reshape([1, $w]);
        $yCoords = linspace(0, $h * 0.008, $h)->reshape([$h, 1]);

        $xGrid = zeros([$h, $w])->add($xCoords);
        $yGrid = zeros([$h, $w])->add($yCoords);

        $noise = random([$h, $w])->multiply(50)->subtract(25);

        $r = $xGrid->add(0.3)->sin()->multiply(127)->add(128)->add($noise);
        $g = $yGrid->add(1.1)->sin()->multiply(127)->add(128)->add($noise);
        $b = $xGrid->add($yGrid)->multiply(0.5)->add(2.7)->sin()->multiply(127)->add(128)->add($noise);

        $imageArray = stack([$r, $g, $b], -1)->clip(0, 255)->astype(DType::UInt8);

        OpalImage::fromArray($imageArray)->toFile($path);
        printf("  Generated %-12s %dx%d\n", $name, $w, $h);
    }
}

// ─── engine interface ──────────────────────────────────────────────────

interface ImageEngine
{
    public function name(): string;

    public function load(string $path): mixed;

    public function resize(mixed $img, int $w, int $h): mixed;

    public function rotate(mixed $img, float $degrees): mixed;

    public function sharpen(mixed $img): mixed;

    public function force(mixed $img): void;

    public function dispose(mixed $img): void;
}

final class GDEngine implements ImageEngine
{
    public function name(): string
    {
        return 'GD';
    }

    public function load(string $path): mixed
    {
        $img = imagecreatefromjpeg($path);
        if (false === $img) {
            throw new RuntimeException("Failed to load: {$path}");
        }

        return $img;
    }

    public function resize(mixed $img, int $w, int $h): mixed
    {
        $resized = imagescale($img, $w, $h, \IMG_BILINEAR_FIXED);
        if (false === $resized) {
            throw new RuntimeException('GD resize failed');
        }

        return $resized;
    }

    public function rotate(mixed $img, float $degrees): mixed
    {
        $rotated = imagerotate($img, $degrees, 0);
        if (false === $rotated) {
            throw new RuntimeException('GD rotate failed');
        }

        return $rotated;
    }

    public function sharpen(mixed $img): mixed
    {
        $kernel = [[0, -1, 0], [-1, 5, -1], [0, -1, 0]];
        imageconvolution($img, $kernel, 1, 0);

        return $img;
    }

    public function force(mixed $img): void
    {
        imagesx($img);
    }

    public function dispose(mixed $img): void
    {
        imagedestroy($img);
    }
}

final class ImagickEngine implements ImageEngine
{
    public function name(): string
    {
        return 'Imagick';
    }

    public function load(string $path): mixed
    {
        return new Imagick($path);
    }

    public function resize(mixed $img, int $w, int $h): mixed
    {
        $img->resizeImage($w, $h, Imagick::FILTER_LANCZOS, 1);

        return $img;
    }

    public function rotate(mixed $img, float $degrees): mixed
    {
        $img->rotateImage(new ImagickPixel('black'), $degrees);

        return $img;
    }

    public function sharpen(mixed $img): mixed
    {
        $img->sharpenImage(0, 2.0);

        return $img;
    }

    public function force(mixed $img): void
    {
        $img->getImageWidth();
    }

    public function dispose(mixed $img): void
    {
        $img->destroy();
    }
}

final class OpalEngine implements ImageEngine
{
    public function name(): string
    {
        return 'Opal';
    }

    public function load(string $path): mixed
    {
        return OpalImage::fromFile($path);
    }

    public function resize(mixed $img, int $w, int $h): mixed
    {
        return $img->resize($w, $h, Kernel::Lanczos3);
    }

    public function rotate(mixed $img, float $degrees): mixed
    {
        return $img->rotate($degrees);
    }

    public function sharpen(mixed $img): mixed
    {
        return $img->sharpen(sigma: 2.0);
    }

    public function force(mixed $img): void
    {
        $img->toMemory();
    }

    public function dispose(mixed $img): void {}
}

// ─── benchmark runner ──────────────────────────────────────────────────

function runPipeline(
    ImageEngine $engine,
    string $imagePath,
    int $targetW,
    int $targetH,
    int $iters,
): array {
    $img = $engine->load($imagePath);
    $img = $engine->resize($img, $targetW, $targetH);
    $img = $engine->rotate($img, 45.0);
    $engine->sharpen($img);
    $engine->force($img);
    $engine->dispose($img);

    gc_collect_cycles();
    $memBefore = memory_get_peak_usage(true);

    $t0 = hrtime(true);
    for ($i = 0; $i < $iters; ++$i) {
        $img = $engine->load($imagePath);
        $img = $engine->resize($img, $targetW, $targetH);
        $img = $engine->rotate($img, 45.0);
        $engine->sharpen($img);
        $engine->force($img);
        $engine->dispose($img);
    }
    $wall = (hrtime(true) - $t0) / 1_000_000;

    return [
        'engine' => $engine->name(),
        'avg_ms' => round($wall / $iters, 2),
        'total_ms' => round($wall, 1),
        'iters' => $iters,
    ];
}

// ─── main ──────────────────────────────────────────────────────────────

echo "\n=== phpmlkit/opal — Library Comparison Benchmarks ===\n";
echo "Pipeline: load → resize → rotate 45° → sharpen\n\n";

echo "Generating test images...\n";
generateImages();

@mkdir(OUT_DIR, 0777, true);

$images = [
    'Small  (640×480)' => ['file' => 'small.jpg', 'out' => [300, 225], 'iters' => 30],
    'Medium (1920×1080)' => ['file' => 'medium.jpg', 'out' => [800, 450], 'iters' => 20],
    'Large  (4000×2670)' => ['file' => 'large.jpg', 'out' => [1200, 800], 'iters' => 10],
    'X-Large (6000×4000)' => ['file' => 'xlarge.jpg', 'out' => [1600, 1067], 'iters' => 5],
];

$engines = [
    new GDEngine(),
    new ImagickEngine(),
    new OpalEngine(),
];

$allResults = [];

foreach ($images as $label => $cfg) {
    $path = IMG_DIR.'/'.$cfg['file'];
    $fileSizeMb = round(filesize($path) / 1_048_576, 1);

    echo "\n── {$label} ({$fileSizeMb} MB) ──\n";

    foreach ($engines as $engine) {
        $r = runPipeline($engine, $path, $cfg['out'][0], $cfg['out'][1], $cfg['iters']);
        $r['image_label'] = $label;
        $allResults[] = $r;

        printf(
            "  %-10s  %8.2f ms  (%d iters)\n",
            $engine->name(),
            $r['avg_ms'],
            $r['iters'],
        );
    }
}

$jsonPath = OUT_DIR.'/results.json';
file_put_contents($jsonPath, json_encode($allResults, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES));
echo "\nResults saved to {$jsonPath}\n";

echo "\n=== Summary ===\n\n";
printf("%-10s | %-22s | %10s\n", 'Engine', 'Image', 'Avg (ms)');
echo str_repeat('-', 48)."\n";
foreach ($allResults as $r) {
    printf("%-10s | %-22s | %8.2f ms\n", $r['engine'], $r['image_label'], $r['avg_ms']);
}
echo "\n";
