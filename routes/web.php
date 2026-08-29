<?php

use App\Http\Controllers\SitemapController;
use App\Models\CmsPage;
use App\Models\CmsPreviewToken;
use App\Models\EducationArticle;
use App\Models\Publication;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::view('/reset-password', 'app', ['seo' => ['title' => 'Reset password', 'description' => 'Reset secure account access.', 'noindex' => true]])->name('password.reset');
Route::get('/sitemap.xml', SitemapController::class);
Route::get('/robots.txt', fn () => response("User-agent: *\nDisallow:\nSitemap: ".url('/sitemap.xml')."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']));
Route::get('/{path?}', function (?string $path = null) {
    $path = trim((string) $path, '/');
    $metadata = [
        '' => ['Breast oncology care in Ile-Ife', 'Specialist breast oncology and breast surgery care in Ile-Ife, Nigeria.'],
        'about' => ['About the practice', 'Learn about the practice’s evidence-first approach to specialist breast care and professional profile publishing.'],
        'services' => ['Breast care services', 'Explore active breast oncology consultation services and request an appointment.'],
        'research' => ['Research and academic work', 'Explore source-verified breast cancer publications, academic work and patient education.'],
        'academic' => ['Academic portfolio', 'Source-verified publications, career entries and achievements from Dr. Funmilola Olanike Wuraola.'],
        'education' => ['Patient education', 'Medically reviewed breast-health education for informed conversations with your care team.'],
        'contact' => ['Contact the practice', 'Request an appointment or contact the practice securely through the patient portal.'],
        'book' => ['Book an appointment', 'Request a breast oncology consultation using current practice availability.'],
        'privacy' => ['Privacy notice — approval pending', 'Publication status for the practice privacy notice.'],
        'terms' => ['Website terms — approval pending', 'Publication status for the practice website terms.'],
        'accessibility' => ['Accessibility statement — approval pending', 'Publication status for the practice accessibility statement.'],
    ];
    $known = ['', 'about', 'services', 'research', 'academic', 'education', 'contact', 'book', 'portal',
        'portal/reminders', 'portal/consultations', 'sign-in', 'register', 'forgot-password',
        'privacy', 'terms', 'accessibility', 'staff', 'staff/inbox', 'staff/calendar',
        'staff/consultations', 'staff/cms', 'staff/research-review', 'staff/education', 'staff/audit'];
    $dynamic = false;
    $seo = ['title' => $metadata[$path][0] ?? 'Dr. Funmilola Olanike Wuraola', 'description' => $metadata[$path][1] ?? 'Specialist breast oncology care, academic work and patient education.'];
    if (preg_match('#^education/([a-z0-9]+(?:-[a-z0-9]+)*)$#', $path, $matches)) {
        $record = Schema::hasTable('education_articles') ? EducationArticle::query()->where('slug', $matches[1])->where('status', 'published')->first() : null;
        $dynamic = $record !== null;
        if ($record) {
            $seo = ['title' => $record->title, 'description' => $record->summary, 'type' => 'article'];
        }
    } elseif (preg_match('#^academic/publications/(\d+)$#', $path, $matches)) {
        $record = Schema::hasTable('publications') ? Publication::query()->whereKey($matches[1])->where('verification_status', 'published')->first() : null;
        $dynamic = $record !== null;
        if ($record) {
            $seo = ['title' => $record->title, 'description' => trim($record->authors.'. '.$record->journal), 'type' => 'article'];
        }
    } elseif (preg_match('#^preview/([^/]+)$#', $path, $matches)) {
        $dynamic = Schema::hasTable('cms_preview_tokens') && CmsPreviewToken::query()->where('token_hash', hash('sha256', $matches[1]))->where('expires_at', '>', now())->exists();
        $seo['noindex'] = true;
    }
    if (str_starts_with($path, 'p/')) {
        $slug = substr($path, 2);
        $page = Schema::hasTable('cms_pages') && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1
            ? CmsPage::query()->where('slug', $slug)->whereNotNull('published_snapshot')->first() : null;
        $dynamic = $page !== null;
        if ($page) {
            $seo = ['title' => $page->seo_title ?: $page->title, 'description' => $page->seo_description ?: $seo['description']];
        }
    } elseif (Schema::hasTable('cms_pages') && in_array($path, ['', 'about', 'services', 'research'], true)) {
        $slug = $path === '' ? 'home' : $path;
        $page = CmsPage::query()->where('slug', $slug)->whereNotNull('published_snapshot')->first();
        if ($page) {
            $seo = ['title' => $page->seo_title ?: $page->title, 'description' => $page->seo_description ?: $seo['description']];
        }
    }

    $status = in_array($path, $known, true) || $dynamic ? 200 : 404;
    $private = preg_match('#^(staff|portal|sign-in|register|forgot-password)(/|$)#', $path) === 1
        || in_array($path, ['privacy', 'terms', 'accessibility'], true);
    $seo['noindex'] = ($seo['noindex'] ?? false) || $private || $status === 404;
    $seo['canonical'] = $seo['noindex'] ? null : url($path === '' ? '/' : '/'.$path);

    return response()->view('app', ['seo' => $seo], $status);
})->where('path', '^(?!api).*$');
