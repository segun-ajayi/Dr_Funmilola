<?php

namespace App\Services\Security;

use App\Contracts\FileScannerInterface;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class BasicFileScanner implements FileScannerInterface
{
    public function assertSafe(string $absolutePath): void
    {
        $handle = @fopen($absolutePath, 'rb');
        if (! $handle) {
            throw new RuntimeException('The quarantined file could not be opened for scanning.');
        }

        try {
            $sample = fread($handle, 1024 * 1024);
        } finally {
            fclose($handle);
        }

        if ($sample === false) {
            throw new RuntimeException('The quarantined file could not be read for scanning.');
        }

        $validContainer = match (strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION))) {
            'pdf' => str_starts_with($sample, '%PDF-'),
            'jpg', 'jpeg' => str_starts_with($sample, "\xFF\xD8\xFF"),
            'png' => str_starts_with($sample, "\x89PNG\r\n\x1A\n"),
            'webp' => strlen($sample) >= 12 && str_starts_with($sample, 'RIFF') && substr($sample, 8, 4) === 'WEBP',
            default => false,
        };
        if (! $validContainer) {
            throw ValidationException::withMessages(['document' => 'This file is malformed or does not match its permitted document type.']);
        }

        $lower = strtolower($sample);
        foreach (['<?php', '<script', 'mz'.chr(144), '/javascript', 'eicar-standard-antivirus-test-file'] as $signature) {
            if (str_contains($lower, strtolower($signature))) {
                throw ValidationException::withMessages(['document' => 'This file failed the security scan and was not uploaded.']);
            }
        }
    }
}
