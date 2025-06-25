<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Dynamic title from Inertia props or fallback -->
    <title>PKKI ITERA</title>
    
    <!-- Meta tags -->
    <meta name="description" content="{{ $page['props']['meta']['description'] ?? config('app.description', 'PKKI ITERA Application') }}">
    <meta name="keywords" content="{{ $page['props']['meta']['keywords'] ?? config('app.keywords', 'laravel,inertia,react,filament,itera') }}">
    <meta name="author" content="{{ config('app.author', 'PKKI ITERA Team') }}">
    <meta name="robots" content="{{ $page['props']['meta']['robots'] ?? 'index, follow' }}">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $page['props']['meta']['og_title'] ?? config('app.name', 'PKKI ITERA') }}">
    <meta property="og:description" content="{{ $page['props']['meta']['og_description'] ?? config('app.description', 'PKKI ITERA Application') }}">
    <meta property="og:image" content="{{ $page['props']['meta']['og_image'] ?? asset('images/og-image.jpg') }}">
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="{{ $page['props']['meta']['twitter_title'] ?? config('app.name', 'PKKI ITERA') }}">
    <meta name="twitter:description" content="{{ $page['props']['meta']['twitter_description'] ?? config('app.description', 'PKKI ITERA Application') }}">
    <meta name="twitter:image" content="{{ $page['props']['meta']['twitter_image'] ?? asset('images/twitter-image.jpg') }}">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="{{ url()->current() }}">
    
    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="mask-icon" href="{{ asset('safari-pinned-tab.svg') }}" color="#5bbad5">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="theme-color" content="#ffffff">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Vite & Inertia -->
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/css/index.css', 'resources/js/app.jsx'])
    
    <!-- Styles -->
    <style>
        [x-cloak] { display: none !important; }
        html { scroll-behavior: smooth; }
    </style>

    <!-- Inertia Head - Dynamically inject additional head elements from your React components -->
    {{-- @routes --}}
    @inertiaHead
</head>
<body class="font-sans antialiased bg-gray-100 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
    @inertia
    
    <!-- Structured data for SEO (optional - customize based on your content) -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "{{ config('app.name', 'PKKI ITERA') }}",
            "url": "{{ config('app.url') }}",
            "logo": "{{ asset('images/logo.png') }}",
            "contactPoint": {
                "@type": "ContactPoint",
                "telephone": "+62-123-4567",
                "contactType": "customer service"
            },
            "sameAs": [
                "https://facebook.com/pkki.itera",
                "https://twitter.com/pkki_itera",
                "https://instagram.com/pkki_itera"
            ]
        }
    </script>
    
    <!-- Optional Scripts (add as needed) -->
    @stack('scripts')
</body>
</html>