<?php
namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConsultationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_confirmed_or_rescheduled_online_appointment_can_receive_one_room():void
    {
        $staff=User::factory()->create(['role'=>UserRole::Moderator]);$patient=User::factory()->create();$online=$this->appointment($patient,now()->addMinutes(10));Sanctum::actingAs($staff);
        $response=$this->postJson("/api/staff/appointments/{$online->id}/consultation")->assertCreated()->assertJsonMissingPath('data.room_locator');
        $this->postJson("/api/staff/appointments/{$online->id}/consultation")->assertCreated();$this->assertDatabaseCount('consultations',1);
        $rescheduled=$this->appointment($patient,now()->addHours(2));$rescheduled->update(['status'=>'rescheduled']);$this->postJson("/api/staff/appointments/{$rescheduled->id}/consultation")->assertCreated();
        $inPerson=$this->appointment($patient,now()->addDay(),'in_person');$this->postJson("/api/staff/appointments/{$inPerson->id}/consultation")->assertUnprocessable();
        $this->assertNotNull($response->json('data.public_id'));
    }

    public function test_patient_isolation_and_consent_are_enforced_before_waiting():void
    {
        [$patient,$staff,$consultation]=$this->consultation();$other=User::factory()->create();Sanctum::actingAs($other);$this->getJson("/api/consultations/{$consultation->id}")->assertForbidden();
        Sanctum::actingAs($patient);$this->postJson("/api/consultations/{$consultation->id}/waiting-room")->assertStatus(422);
        $this->postJson("/api/consultations/{$consultation->id}/consent",['accepted'=>true])->assertCreated();$this->postJson("/api/consultations/{$consultation->id}/waiting-room")->assertOk()->assertJsonPath('data.status','waiting');
        $this->assertDatabaseHas('consultation_consents',['consultation_id'=>$consultation->id,'patient_id'=>$patient->id,'consent_version'=>'v1']);
    }

    public function test_staff_admits_starts_and_ends_with_validated_transitions():void
    {
        [$patient,$staff,$consultation]=$this->consultation();Sanctum::actingAs($patient);$this->postJson("/api/consultations/{$consultation->id}/consent",['accepted'=>true]);$this->postJson("/api/consultations/{$consultation->id}/waiting-room");
        Sanctum::actingAs($staff);$this->patchJson("/api/staff/consultations/{$consultation->id}/status",['status'=>'ready'])->assertOk();$this->patchJson("/api/staff/consultations/{$consultation->id}/status",['status'=>'in_progress'])->assertOk();$this->patchJson("/api/staff/consultations/{$consultation->id}/status",['status'=>'ended'])->assertOk();$this->patchJson("/api/staff/consultations/{$consultation->id}/status",['status'=>'ready'])->assertUnprocessable();
        $this->assertDatabaseHas('audit_logs',['action'=>'consultation.status_changed','subject_id'=>$consultation->id]);
    }

    public function test_join_link_is_signed_short_lived_and_records_attendance():void
    {
        [$patient,$staff,$consultation]=$this->consultation();Sanctum::actingAs($patient);$this->postJson("/api/consultations/{$consultation->id}/consent",['accepted'=>true]);$this->postJson("/api/consultations/{$consultation->id}/waiting-room");Sanctum::actingAs($staff);$this->patchJson("/api/staff/consultations/{$consultation->id}/status",['status'=>'ready']);
        Sanctum::actingAs($patient);$join=$this->postJson("/api/consultations/{$consultation->id}/join-authorization")->assertOk()->json('join_url');$path=parse_url($join,PHP_URL_PATH).'?'.parse_url($join,PHP_URL_QUERY);$room=$this->getJson($path)->assertOk()->assertJsonPath('configuration.provider','unconfigured');$this->postJson("/api/consultations/{$consultation->id}/leave")->assertOk();
        $this->assertDatabaseHas('consultation_attendances',['id'=>$room->json('attendance_id'),'participant_role'=>'patient']);$this->assertNotNull(\App\Models\ConsultationAttendance::find($room->json('attendance_id'))->left_at);
        $this->getJson(parse_url($join,PHP_URL_PATH).'?signature=invalid')->assertForbidden();
    }

    public function test_waiting_room_is_closed_outside_the_join_window():void
    {
        $patient=User::factory()->create();$staff=User::factory()->create(['role'=>UserRole::Admin]);$appointment=$this->appointment($patient,now()->addDays(2));Sanctum::actingAs($staff);$consultationId=$this->postJson("/api/staff/appointments/{$appointment->id}/consultation")->json('data.id');Sanctum::actingAs($patient);$this->postJson("/api/consultations/{$consultationId}/consent",['accepted'=>true]);$this->postJson("/api/consultations/{$consultationId}/waiting-room")->assertForbidden();
    }

    private function consultation():array{$patient=User::factory()->create();$staff=User::factory()->create(['role'=>UserRole::Moderator]);$appointment=$this->appointment($patient,now()->addMinutes(10));Sanctum::actingAs($staff);$id=$this->postJson("/api/staff/appointments/{$appointment->id}/consultation")->json('data.id');return[$patient,$staff,\App\Models\Consultation::findOrFail($id)];}
    private function appointment(User $patient,$start,string $method='online'):Appointment{$service=Service::create(['name'=>'Online consultation','slug'=>'consult-'.Str::random(6),'summary'=>'Secure consultation']);return Appointment::create(['public_id'=>Str::uuid(),'patient_id'=>$patient->id,'service_id'=>$service->id,'starts_at'=>$start,'ends_at'=>$start->copy()->addMinutes(45),'status'=>'confirmed','consultation_method'=>$method,'reason'=>'Consultation']);}
}
