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
    'windows-64' => 'windows-64',
];

$platformDirectory = Platform::findBestMatch($platformDirectories);

if (false !== $platformDirectory) {
    $libDir = __DIR__.'/../lib/'.$platformDirectory;

    if (Platform::isWindows()) {
        $msvcrt = \FFI::cdef(
            'size_t mbstowcs(void *wcstr, const char *mbstr, size_t count);',
            'msvcrt.dll'
        );

        $k32 = \FFI::cdef(
            '
            int SetDefaultDllDirectories(unsigned long DirectoryFlags);
            void* AddDllDirectory(const void* NewDirectory);
            int RemoveDllDirectory(void* Cookie);',
            'kernel32.dll'
        );

        $k32->SetDefaultDllDirectories(0x1000); // LOAD_LIBRARY_SEARCH_DEFAULT_DIRS

        $pathLen = \strlen($libDir);
        $wide = $msvcrt->new('char['.($pathLen * 2 + 2).']');
        $msvcrt->mbstowcs($wide, $libDir, $pathLen + 1);

        $k32->AddDllDirectory($wide);
    }

    if (file_exists($libDir)) {
        VipsFFI::addLibraryPath($libDir);
    }
}
