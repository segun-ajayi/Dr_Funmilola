<?php
namespace App\Services\Security;
use App\Contracts\FileScannerInterface;use Illuminate\Http\UploadedFile;use Illuminate\Validation\ValidationException;
class BasicFileScanner implements FileScannerInterface{public function assertSafe(UploadedFile $file):void{$handle=fopen($file->getRealPath(),'rb');$sample=$handle?fread($handle,8192):'';if($handle)fclose($handle);$lower=strtolower($sample?:'');foreach(['<?php','<script','mz'.chr(144),'/javascript'] as $signature)if(str_contains($lower,strtolower($signature)))throw ValidationException::withMessages(['document'=>'This file failed the security scan and was not uploaded.']);}}
