<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $setting = DB::table('cms_settings')->where('key', 'navigation')->first();
        if ($setting) {
            $updates = [];
            foreach (['draft_value', 'published_value'] as $column) {
                if ($setting->{$column} === null) {
                    continue;
                }
                $navigation = json_decode($setting->{$column}, true) ?: [];
                $updates[$column] = json_encode($this->navigation($navigation), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            if ($updates) {
                DB::table('cms_settings')->where('id', $setting->id)->update($updates);
            }
        }

        foreach ([
            'home' => ['Breast oncology care in Ile-Ife', 'Specialist breast oncology and breast surgery care in Ile-Ife, Nigeria.'],
            'about' => ['About the practice', 'Learn about the practice’s evidence-first approach to specialist breast care and professional profile publishing.'],
            'services' => ['Breast care services', 'Explore active breast oncology consultation services and request an appointment.'],
            'research' => ['Research and academic work', 'Explore source-verified breast cancer publications, academic work and patient education.'],
        ] as $slug => [$title, $description]) {
            $page = DB::table('cms_pages')->where('slug', $slug)->first();
            if (! $page) {
                continue;
            }
            $updates = [];
            if (! $page->seo_title) {
                $updates['seo_title'] = $title;
            }
            if (! $page->seo_description) {
                $updates['seo_description'] = $description;
            }
            if ($page->published_snapshot) {
                $snapshot = json_decode($page->published_snapshot, true) ?: [];
                $snapshot['seo']['title'] = $snapshot['seo']['title'] ?? $title;
                $snapshot['seo']['description'] = $snapshot['seo']['description'] ?? $description;
                $updates['published_snapshot'] = json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            if ($updates) {
                DB::table('cms_pages')->where('id', $page->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        // Published navigation and metadata may have been edited after migration; never erase user content on rollback.
    }

    private function navigation(array $navigation): array
    {
        $researchIndex = collect($navigation)->search(fn (array $item) => ($item['path'] ?? null) === '/research');
        if ($researchIndex !== false) {
            $children = $navigation[$researchIndex]['children'] ?? [];
            foreach ([['label' => 'Academic portfolio', 'path' => '/academic'], ['label' => 'Patient education', 'path' => '/education']] as $child) {
                if (! collect($children)->contains('path', $child['path'])) {
                    $children[] = $child;
                }
            }
            $navigation[$researchIndex]['children'] = $children;
        }
        if (! collect($navigation)->contains('path', '/contact')) {
            $portalIndex = collect($navigation)->search(fn (array $item) => ($item['path'] ?? null) === '/portal');
            array_splice($navigation, $portalIndex === false ? count($navigation) : $portalIndex, 0, [[
                'label' => 'Contact', 'path' => '/contact',
            ]]);
        }

        return array_values($navigation);
    }
};
