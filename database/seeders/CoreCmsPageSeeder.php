<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use App\Models\CmsSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CoreCmsPageSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->where('role', 'power_admin')->orderBy('id')->first();
        if (! $owner) {
            $this->command?->warn('Core CMS pages were not seeded because no Power Admin account exists.');
            return;
        }

        foreach ($this->pages() as $slug => $definition) {
            if (CmsPage::query()->where('slug', $slug)->exists()) {
                continue;
            }

            $page = CmsPage::create([
                'title' => $definition['title'], 'slug' => $slug, 'template' => 'landing',
                'status' => 'published', 'created_by' => $owner->id, 'published_by' => $owner->id,
                'published_at' => now(),
            ]);
            foreach ($definition['sections'] as $order => $section) {
                $page->sections()->create($section + [
                    'section_key' => Str::uuid(), 'sort_order' => $order, 'is_visible' => true,
                ]);
            }
            $snapshot = app(\App\Services\CmsService::class)->snapshot($page);
            $page->update(['published_snapshot' => $snapshot]);
            $page->versions()->create([
                'version' => 1, 'reason' => 'Core website page seeded',
                'snapshot' => $snapshot, 'created_by' => $owner->id,
            ]);
        }

        foreach ([
            'navigation' => [
                ['label' => 'Home', 'path' => '/'], ['label' => 'About', 'path' => '/about'],
                ['label' => 'Services', 'path' => '/services'], ['label' => 'Research', 'path' => '/research'],
                ['label' => 'Patient portal', 'path' => '/portal'], ['label' => 'Book appointment', 'path' => '/book'],
            ],
            'theme' => ['palette' => 'wine', 'density' => 'comfortable', 'heading_style' => 'editorial'],
        ] as $key => $value) {
            CmsSetting::firstOrCreate(['key' => $key], [
                'draft_value' => $value, 'published_value' => $value, 'updated_by' => $owner->id,
            ]);
        }
    }

    private function pages(): array
    {
        $section = fn (string $type, array $content, string $background = 'ivory', string $alignment = 'left') => [
            'type' => $type, 'content' => $content,
            'presentation' => ['background' => $background, 'alignment' => $alignment, 'width' => 'normal', 'spacing' => 'normal'],
        ];
        return [
            'home' => ['title' => 'Home', 'sections' => [
                $section('hero', ['eyebrow'=>'Specialist breast care · Ile-Ife, Nigeria','heading'=>'Care that sees the whole woman, not only the diagnosis.','text'=>'Specialist breast oncology and surgical care, grounded in evidence and shaped around each patient’s questions, priorities and life.','primary_label'=>'Book an appointment','primary_url'=>'/book','secondary_label'=>'Meet Dr. Funmilola','secondary_url'=>'/about']),
                $section('text', ['eyebrow'=>'A considered approach','heading'=>'Serious expertise can still feel human.','body'=>'Breast concerns often arrive with uncertainty. Our practice is designed to replace that uncertainty with careful assessment, understandable choices and a coordinated next step.'], 'white'),
                $section('stats', ['heading'=>'Focused expertise. Personal care.','items'=>[['value'=>'01','label'=>'Listen first'],['value'=>'02','label'=>'Explain clearly'],['value'=>'03','label'=>'Decide together'],['value'=>'04','label'=>'Stay connected']]], 'blush'),
                $section('cta', ['eyebrow'=>'Ready when you are','heading'=>'Take the next step with confidence.','text'=>'Request a specialist consultation and the practice team will guide the next step.','button_label'=>'Request an appointment','button_url'=>'/book'], 'wine', 'center'),
            ]],
            'about' => ['title' => 'About', 'sections' => [
                $section('hero', ['eyebrow'=>'About','heading'=>'A specialist practice built around clarity and compassion.','text'=>'Dr. Funmilola Olanike Wuraola is a surgeon and academic clinician associated with Obafemi Awolowo University and OAUTHC in Ile-Ife, Nigeria.','primary_label'=>'Request an appointment','primary_url'=>'/book']),
                $section('text', ['eyebrow'=>'Professional profile','heading'=>'Evidence before publication.','body'=>'Qualifications, appointments, fellowships and achievements are published only after approval against authoritative sources.'], 'white'),
                $section('text', ['eyebrow'=>'Areas of inquiry','heading'=>'Care, research and health systems.','body'=>'The practice’s academic work spans breast cancer care, breast surgery, screening, genetics, survivorship and equitable access.'], 'blush'),
            ]],
            'services' => ['title' => 'Services', 'sections' => [
                $section('hero', ['eyebrow'=>'Clinical services','heading'=>'Breast care for the questions that cannot wait.','text'=>'Choose the consultation that best matches your concern. The practice team reviews every request and will guide you if a different service is more appropriate.','primary_label'=>'Book an appointment','primary_url'=>'/book']),
                $section('text', ['eyebrow'=>'Specialist consultations','heading'=>'Assessment, treatment planning and follow-up.','body'=>'Appointments include breast-concern assessment, breast cancer and surgery consultation, independent second opinions, follow-up care and personalised screening guidance.'], 'white'),
                $section('cta', ['eyebrow'=>'Not sure which service?','heading'=>'Start with your concern.','text'=>'Share the reason for your visit and the practice team will help match the right consultation.','button_label'=>'Request a consultation','button_url'=>'/book'], 'blush'),
            ]],
            'research' => ['title' => 'Research', 'sections' => [
                $section('hero', ['eyebrow'=>'Research & academic work','heading'=>'Improving breast cancer care through inquiry and collaboration.','text'=>'This portfolio uses a verification-first publishing process. Draft research records stay private until a Power Admin approves their sources.','primary_label'=>'View academic portfolio','primary_url'=>'/academic']),
                $section('text', ['eyebrow'=>'Evidence first','heading'=>'Publications are source verified.','body'=>'Research records are released publicly only after bibliographic details and authoritative sources have passed the practice review workflow.'], 'white'),
                $section('cta', ['eyebrow'=>'Patient education','heading'=>'Research translated into understandable guidance.','text'=>'Read medically reviewed education from the practice.','button_label'=>'Explore education','button_url'=>'/education'], 'wine'),
            ]],
        ];
    }
}
