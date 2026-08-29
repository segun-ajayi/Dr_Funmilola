<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use App\Models\EducationArticle;
use App\Models\Publication;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect([
            ['path' => '/', 'updated_at' => null],
            ['path' => '/about', 'updated_at' => null],
            ['path' => '/services', 'updated_at' => null],
            ['path' => '/research', 'updated_at' => null],
            ['path' => '/academic', 'updated_at' => null],
            ['path' => '/education', 'updated_at' => null],
            ['path' => '/contact', 'updated_at' => null],
            ['path' => '/book', 'updated_at' => null],
        ]);

        CmsPage::query()->whereNotNull('published_snapshot')->get(['slug', 'updated_at'])->each(function (CmsPage $page) use ($urls) {
            $path = in_array($page->slug, ['home', 'about', 'services', 'research'], true)
                ? ($page->slug === 'home' ? '/' : '/'.$page->slug)
                : '/p/'.$page->slug;
            if (! $urls->contains('path', $path)) {
                $urls->push(['path' => $path, 'updated_at' => $page->updated_at]);
            }
        });
        EducationArticle::query()->where('status', 'published')->get(['slug', 'updated_at'])
            ->each(fn (EducationArticle $article) => $urls->push(['path' => '/education/'.$article->slug, 'updated_at' => $article->updated_at]));
        Publication::query()->where('verification_status', 'published')->get(['id', 'updated_at'])
            ->each(fn (Publication $publication) => $urls->push(['path' => '/academic/publications/'.$publication->id, 'updated_at' => $publication->updated_at]));

        $body = view('sitemap', ['urls' => $urls->unique('path')->values()])->render();

        return response($body, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
