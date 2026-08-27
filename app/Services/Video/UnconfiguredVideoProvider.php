<?php
namespace App\Services\Video;
use App\Contracts\VideoProviderInterface;
use App\Models\Consultation;
use Illuminate\Support\Str;
class UnconfiguredVideoProvider implements VideoProviderInterface
{
    public function createRoom(string $reference): array { return ['provider_key'=>'unconfigured','room_locator'=>'consultation-'.$reference.'-'.Str::random(24)]; }
    public function participantConfiguration(Consultation $consultation,string $displayName,string $role): array { return ['provider'=>'unconfigured','ready'=>false,'display_name'=>$displayName,'participant_role'=>$role,'message'=>'Live video will become available after a secure video provider is configured.']; }
}
