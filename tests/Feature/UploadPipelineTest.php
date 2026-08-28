<?php

namespace Tests\Feature;

use App\Contracts\FileScannerInterface;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class UploadPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_clean_file_is_quarantined_scanned_released_privately_and_audited(): void
    {
        Storage::fake('local');
        $patient = User::factory()->create();
        Sanctum::actingAs($patient);

        $response = $this->post('/api/me/documents', [
            'label' => 'Referral letter',
            'document' => UploadedFile::fake()->createWithContent('referral.pdf', "%PDF-1.4\nbenign referral fixture"),
        ], ['Accept' => 'application/json'])->assertCreated()->assertJsonMissingPath('data.storage_path');

        $document = $patient->documents()->findOrFail($response->json('data.id'));
        Storage::disk('local')->assertExists($document->storage_path);
        $this->assertSame([], Storage::disk('local')->allFiles(config('upload-security.quarantine_directory')));
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $patient->id, 'action' => 'document.uploaded']);

        $this->get('/api/documents/'.$document->id.'/download')->assertOk();
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $patient->id, 'action' => 'document.downloaded']);
        $this->get('/storage/'.$document->storage_path)->assertDontSee('benign referral fixture');
    }

    public function test_test_signature_is_rejected_and_quarantine_is_removed(): void
    {
        Storage::fake('local');
        $patient = User::factory()->create();
        Sanctum::actingAs($patient);
        $file = UploadedFile::fake()->createWithContent('scanner-test.pdf', "%PDF-1.4\nEICAR-STANDARD-ANTIVIRUS-TEST-FILE");

        $this->post('/api/me/documents', ['label' => 'Scanner fixture', 'document' => $file], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('document');

        $this->assertDatabaseCount('patient_documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $patient->id, 'action' => 'document.upload_rejected']);
    }

    public function test_clean_pdf_jpeg_and_png_containers_are_accepted(): void
    {
        Storage::fake('local');
        $patient = User::factory()->create();
        Sanctum::actingAs($patient);

        foreach ([
            UploadedFile::fake()->createWithContent('referral.pdf', "%PDF-1.4\nbenign"),
            UploadedFile::fake()->image('scan.jpg'),
            UploadedFile::fake()->image('scan.png'),
        ] as $index => $file) {
            $this->post('/api/me/documents', ['label' => 'Clean file '.$index, 'document' => $file], ['Accept' => 'application/json'])->assertCreated();
        }

        $this->assertDatabaseCount('patient_documents', 3);
        $this->assertSame([], Storage::disk('local')->allFiles(config('upload-security.quarantine_directory')));
    }

    public function test_malformed_allowed_container_is_rejected(): void
    {
        Storage::fake('local');
        $patient = User::factory()->create();
        Sanctum::actingAs($patient);

        $this->post('/api/me/documents', [
            'label' => 'Malformed PDF',
            'document' => UploadedFile::fake()->create('malformed.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('document');

        $this->assertDatabaseCount('patient_documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_unavailable_scanner_fails_closed_without_file_or_metadata(): void
    {
        Storage::fake('local');
        $this->app->instance(FileScannerInterface::class, new class implements FileScannerInterface
        {
            public function assertSafe(string $absolutePath): void
            {
                throw new RuntimeException('Provider timeout');
            }
        });
        $patient = User::factory()->create();
        Sanctum::actingAs($patient);

        $this->post('/api/me/documents', [
            'label' => 'Referral',
            'document' => UploadedFile::fake()->createWithContent('referral.pdf', "%PDF-1.4\nbenign"),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('document');

        $this->assertDatabaseCount('patient_documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $patient->id, 'action' => 'document.scan_failed']);
    }

    public function test_unsafe_names_and_unsupported_or_oversized_files_are_rejected(): void
    {
        Storage::fake('local');
        Sanctum::actingAs(User::factory()->create());

        $this->post('/api/me/documents', ['label' => 'Double extension', 'document' => UploadedFile::fake()->create('referral.pdf.exe', 10, 'application/pdf')], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->post('/api/me/documents', ['label' => 'Executable', 'document' => UploadedFile::fake()->create('referral.exe', 10, 'application/octet-stream')], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->post('/api/me/documents', ['label' => 'Oversized', 'document' => UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf')], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->assertDatabaseCount('patient_documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_patient_isolation_and_authorized_staff_download_are_audited(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $staff = User::factory()->create(['role' => UserRole::Moderator]);
        Sanctum::actingAs($owner);
        $documentId = $this->post('/api/me/documents', [
            'label' => 'Private referral',
            'document' => UploadedFile::fake()->createWithContent('referral.pdf', "%PDF-1.4\nbenign"),
        ], ['Accept' => 'application/json'])->assertCreated()->json('data.id');

        Sanctum::actingAs($other);
        $this->get('/api/documents/'.$documentId.'/download')->assertForbidden();
        Sanctum::actingAs($staff);
        $this->get('/api/documents/'.$documentId.'/download')->assertOk();
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $staff->id, 'action' => 'document.downloaded']);
    }
}
