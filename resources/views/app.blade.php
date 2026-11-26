<!doctype html>
<html lang="en-IN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b1730">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ config('sahu.name') }} — {{ config('sahu.tagline') }} and loan assistance in {{ config('sahu.city') }}, {{ config('sahu.state') }}.">
    <meta property="og:title" content="{{ config('sahu.name') }} — {{ config('sahu.tagline') }}">
    <meta property="og:description" content="Clear information and thoughtful support for your financing journey.">
    <meta property="og:type" content="website">
    <link rel="canonical" href="{{ url()->current() }}">
    <title>{{ config('sahu.name') }} — {{ config('sahu.tagline') }}</title>
    @vite(['resources/css/app.css', 'resources/js/site.tsx'])
</head>
<body>
    @inertia
</body>
</html>
