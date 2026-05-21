<?php

declare(strict_types=1);

namespace PhpMlKit\Opal;

use Codewithkyrian\PlatformPackageInstaller\Platform;
use Jcupitt\Vips\FFI as VipsFFI;

/**
 * Bootstrap file for the Image library.
 * Sets up the libvips library path and initializes the library.
 */
$platformDirectories = [
    'linux-x86_64' => 'linux-x86_64',
    'linux-arm64' => 'linux-arm64',
    'darwin-arm64' => 'darwin-arm64',
    'darwin-x86_64' => 'darwin-x86_64',
    'windows-x64' => 'windows-x64',
];

$platformDirectory = Platform::findBestMatch($platformDirectories);

if (false !== $platformDirectory) {
    $libDir = __DIR__.'/../lib/'.$platformDirectory;

    if (file_exists($libDir)) {
        VipsFFI::addLibraryPath($libDir);
    }
}
