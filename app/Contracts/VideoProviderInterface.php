<?php
namespace App\Contracts;
use App\Models\Consultation;
interface VideoProviderInterface
{
    public function createRoom(string $reference): array;
    public function participantConfiguration(Consultation $consultation,string $displayName,string $role): array;
}
