<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#6f1838" />
    @php
        $seo = $seo ?? ['title' => 'Dr. Funmilola Olanike Wuraola', 'description' => 'Specialist breast oncology and breast surgery care in Ile-Ife, Nigeria.'];
        $fullTitle = str_contains($seo['title'], 'Dr. Funmilola Olanike Wuraola') ? $seo['title'] : $seo['title'].' | Dr. Funmilola Olanike Wuraola';
    @endphp
    <meta name="description" content="{{ $seo['description'] }}" />
    <meta name="robots" content="{{ ($seo['noindex'] ?? false) ? 'noindex, nofollow' : 'index, follow' }}" />
    <meta property="og:title" content="{{ $fullTitle }}" />
    <meta property="og:description" content="{{ $seo['description'] }}" />
    <meta property="og:type" content="{{ $seo['type'] ?? 'website' }}" />
    @if ($seo['canonical'] ?? null)
        <link rel="canonical" href="{{ $seo['canonical'] }}" />
        <meta property="og:url" content="{{ $seo['canonical'] }}" />
    @endif
    <title>{{ $fullTitle }}</title>
    @viteReactRefresh
    @vite(['resources/js/main.tsx'])
</head>
<body><div id="app"></div></body>
</html>
