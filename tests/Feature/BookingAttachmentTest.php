<?php

namespace Tests\Feature;

use App\Contracts\FileScannerInterface;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\AvailabilityRule;
use App\Models\Service;
use App\Models\User;
use App\Services\Security\BasicFileScanner;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class BookingAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_remains_available_without_an_attachment(): void
    {
        [$service, $start] = $this->bookableSlot();

        $this->postJson('/api/appointment-requests', $this->payload($service, $start))
            ->assertCreated()
            ->assertJsonStructure(['message', 'reference']);

        $this->assertDatabaseCount('appointments', 1);
        $this->assertDatabaseCount('patient_documents', 0);
    }

    public function test_clean_booking_attachment_is_scanned_linked_privately_and_authorized(): void
    {
        Storage::fake('local');
        [$service, $start] = $this->bookableSlot();
        $payload = $this->payload($service, $start) + [
            'attachment' => UploadedFile::fake()->createWithContent('referral.pdf', "%PDF-1.4\nbenign referral"),
        ];

        $this->post('/api/appointment-requests', $payload, ['Accept' => 'application/json'])
            ->assertCreated();

        $appointment = Appointment::with(['patient', 'documents'])->sole();
        $document = $appointment->documents->sole();
        $this->assertSame($appointment->patient_id, $document->patient_id);
        Storage::disk('local')->assertExists($document->storage_path);
        $this->assertSame([], Storage::disk('local')->allFiles(config('upload-security.quarantine_directory')));
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $appointment->patient_id, 'action' => 'document.uploaded']);

        $appointment->patient->forceFill(['email_verified_at' => now()])->save();
        Sanctum::actingAs($appointment->patient->fresh());
        $this->get('/api/documents/'.$document->id.'/download')->assertOk();
        Sanctum::actingAs(User::factory()->create());
        $this->get('/api/documents/'.$document->id.'/download')->assertForbidden();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Moderator]));
        $this->get('/api/documents/'.$document->id.'/download')->assertOk();
    }

    public function test_rejected_attachment_rolls_back_booking_and_leaves_no_file(): void
    {
        Storage::fake('local');
        [$service, $start] = $this->bookableSlot();
        $payload = $this->payload($service, $start) + [
            'attachment' => UploadedFile::fake()->createWithContent('referral.pdf', "%PDF-1.4\nEICAR-STANDARD-ANTIVIRUS-TEST-FILE"),
        ];

        $this->post('/api/appointment-requests', $payload, ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attachment');

        $this->assertDatabaseCount('appointments', 0);
        $this->assertDatabaseCount('patient_documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_scanner_outage_rolls_back_and_same_request_can_recover_cleanly(): void
    {
        Storage::fake('local');
        [$service, $start] = $this->bookableSlot();
        $requestId = (string) Str::uuid();
        $this->app->instance(FileScannerInterface::class, new class implements FileScannerInterface
        {
            public function assertSafe(string $absolutePath): void
            {
                throw new RuntimeException('Scanner unavailable');
            }
        });

        $this->post('/api/appointment-requests', $this->payload($service, $start, $requestId) + [
            'attachment' => UploadedFile::fake()->createWithContent('referral.pdf', "%PDF-1.4\nbenign"),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('attachment');

        $this->assertDatabaseCount('appointments', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());

        $this->app->instance(FileScannerInterface::class, new BasicFileScanner);
        $this->post('/api/appointment-requests', $this->payload($service, $start, $requestId) + [
            'attachment' => UploadedFile::fake()->createWithContent('referral.pdf', "%PDF-1.4\nbenign"),
        ], ['Accept' => 'application/json'])->assertCreated();

        $this->assertDatabaseCount('appointments', 1);
        $this->assertDatabaseCount('patient_documents', 1);
    }

    public function test_duplicate_retry_returns_original_reference_without_duplicate_attachment(): void
    {
        Storage::fake('local');
        [$service, $start] = $this->bookableSlot();
        $requestId = (string) Str::uuid();

        $first = $this->post('/api/appointment-requests', $this->payload($service, $start, $requestId) + [
            'attachment' => UploadedFile::fake()->createWithContent('referral.pdf', "%PDF-1.4\nfirst"),
        ], ['Accept' => 'application/json'])->assertCreated();
        $retry = $this->post('/api/appointment-requests', $this->payload($service, $start, $requestId) + [
            'attachment' => UploadedFile::fake()->createWithContent('referral.pdf', "%PDF-1.4\nretry"),
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertSame($first->json('reference'), $retry->json('reference'));
        $this->assertDatabaseCount('appointments', 1);
        $this->assertDatabaseCount('patient_documents', 1);
        $this->assertStringContainsString('No duplicate was created', $retry->json('message'));
    }

    public function test_unsupported_and_oversized_booking_attachments_are_rejected_before_creation(): void
    {
        Storage::fake('local');
        [$service, $start] = $this->bookableSlot();

        $this->post('/api/appointment-requests', $this->payload($service, $start) + [
            'attachment' => UploadedFile::fake()->create('notes.exe', 10, 'application/octet-stream'),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('attachment');
        $this->post('/api/appointment-requests', $this->payload($service, $start) + [
            'attachment' => UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('attachment');

        $this->assertDatabaseCount('appointments', 0);
        $this->assertDatabaseCount('patient_documents', 0);
    }

    private function bookableSlot(): array
    {
        $service = Service::create(['name' => 'Consultation', 'slug' => 'consultation', 'summary' => 'Specialist review', 'duration_minutes' => 45, 'online_available' => true]);
        $start = CarbonImmutable::now('Africa/Lagos')->next('Monday')->setTime(9, 0);
        AvailabilityRule::create(['weekday' => $start->dayOfWeekIso, 'start_time' => '09:00', 'end_time' => '12:00', 'slot_minutes' => 45, 'buffer_minutes' => 15, 'consultation_method' => 'both', 'is_active' => true]);

        return [$service, $start];
    }

    private function payload(Service $service, CarbonImmutable $start, ?string $requestId = null): array
    {
        return [
            'name' => 'Booking Patient',
            'email' => 'booking@example.test',
            'phone' => '+2348000000000',
            'service_id' => $service->id,
            'starts_at' => $start->toIso8601String(),
            'consultation_method' => 'online',
            'reason' => 'I would like a specialist review of a recent breast concern.',
            'client_request_id' => $requestId ?? (string) Str::uuid(),
        ];
    }
}
