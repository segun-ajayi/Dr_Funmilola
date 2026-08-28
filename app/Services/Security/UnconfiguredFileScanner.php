<?php

namespace App\Services\Security;

use App\Contracts\FileScannerInterface;
use RuntimeException;

class UnconfiguredFileScanner implements FileScannerInterface
{
    public function assertSafe(string $absolutePath): void
    {
        throw new RuntimeException('No approved malware scanner is configured.');
    }
}
