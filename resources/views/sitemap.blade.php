{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($urls as $url)
    <url>
        <loc>{{ url($url['path']) }}</loc>
@if ($url['updated_at'])
        <lastmod>{{ $url['updated_at']->toAtomString() }}</lastmod>
@endif
    </url>
@endforeach
</urlset>
