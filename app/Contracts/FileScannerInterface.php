<?php

namespace App\Contracts;

use Illuminate\Validation\ValidationException;

interface FileScannerInterface
{
    /**
     * @throws ValidationException when unsafe
     * @throws \Throwable when the scanner cannot provide a definitive result
     */
    public function assertSafe(string $absolutePath): void;
}
