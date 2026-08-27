<?php
namespace App\Contracts;use Illuminate\Http\UploadedFile;interface FileScannerInterface{public function assertSafe(UploadedFile $file):void;}
