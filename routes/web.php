<?php

use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\LoanEnquiryController;
use App\Models\NewsArticle;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

Route::get('/robots.txt', function (): HttpResponse {
    return response("User-agent: *\nAllow: /\nSitemap: ".url('/sitemap.xml')."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
});

Route::get('/sitemap.xml', function (): HttpResponse {
    $paths = [
        '/', '/loans', '/loans/car-loan', '/loans/used-vehicle-loan', '/loans/two-wheeler-loan',
        '/loans/commercial-vehicle-loan', '/loans/vehicle-refinance', '/vehicle-finance', '/emi-calculator',
        '/compare-loans', '/calculators', '/calculators/personal-loan-emi', '/calculators/home-loan-emi',
        '/calculators/business-loan-emi', '/calculators/car-loan-emi', '/calculators/two-wheeler-loan',
        '/calculators/loan-against-property', '/calculators/balance-transfer', '/calculators/down-payment',
        '/calculators/tenure-comparison', '/calculators/amortization', '/apply', '/news', '/news/dsa', '/news/banking', '/news/vehicle-finance',
        '/resources', '/videos', '/financial-resources', '/resources/vehicle-loan-emi', '/resources/dsa-guide', '/resources/vehicle-finance-guide',
        '/resources/used-car-finance', '/resources/loan-tenure-guide', '/resources/vehicle-finance-documents',
        '/resources/credit-score-guide', '/resources/loan-processing-fees', '/resources/vehicle-refinancing-guide',
        '/resources/borrower-rights-guide', '/about', '/contact', '/faq',
        '/privacy-policy', '/terms', '/disclaimer', '/grievance-redressal',
    ];
    $newsPaths = NewsArticle::query()
        ->where('is_published', true)
        ->orderByDesc('published_at')
        ->limit(500)
        ->pluck('slug')
        ->map(fn (string $slug): string => '/news/'.$slug)
        ->all();

    return response()->view('sitemap', [
        'urls' => array_map(fn (string $path): string => url($path), [...$paths, ...$newsPaths]),
    ], 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
});

Route::post('/apply', [LoanEnquiryController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('loan-enquiries.store');

Route::post('/contact', [ContactMessageController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('contact-messages.store');

Route::get('/{path?}', function (?string $path = null): Response {
    $path = '/'.ltrim($path ?? '', '/');
    $publicRoutes = [
        '/', '/loans', '/loans/car-loan', '/loans/used-vehicle-loan', '/loans/two-wheeler-loan',
        '/loans/commercial-vehicle-loan', '/loans/vehicle-refinance', '/vehicle-finance', '/emi-calculator',
        '/compare-loans', '/calculators', '/calculators/personal-loan-emi', '/calculators/home-loan-emi',
        '/calculators/business-loan-emi', '/calculators/car-loan-emi', '/calculators/two-wheeler-loan',
        '/calculators/loan-against-property', '/calculators/balance-transfer', '/calculators/down-payment',
        '/calculators/tenure-comparison', '/calculators/amortization', '/apply', '/news', '/news/dsa', '/news/banking', '/news/vehicle-finance',
        '/resources', '/resources/vehicle-loan-emi', '/resources/dsa-guide', '/resources/vehicle-finance-guide',
        '/resources/used-car-finance', '/resources/loan-tenure-guide', '/resources/vehicle-finance-documents',
        '/resources/credit-score-guide', '/resources/loan-processing-fees', '/resources/vehicle-refinancing-guide',
        '/resources/borrower-rights-guide', '/about', '/contact', '/faq', '/privacy-policy', '/terms',
        '/disclaimer', '/grievance-redressal', '/videos', '/financial-resources',
        '/loans/personal-loan', '/loans/business-loan', '/loans/home-loan', '/loans/loan-against-property', '/loans/balance-transfer',
    ];
    $knownArticle = str_starts_with($path, '/news/') && ! in_array($path, ['/news/dsa', '/news/banking', '/news/vehicle-finance'], true)
        ? NewsArticle::query()->where('is_published', true)->where('slug', substr($path, 6))->exists()
        : false;
    $knownGuide = str_starts_with($path, '/resources/') && in_array(substr($path, 11), [
        'vehicle-loan-emi', 'dsa-guide', 'vehicle-finance-guide', 'used-car-finance', 'loan-tenure-guide',
        'vehicle-finance-documents', 'credit-score-guide', 'loan-processing-fees', 'vehicle-refinancing-guide', 'borrower-rights-guide',
    ], true);
    if (! in_array($path, $publicRoutes, true) && ! $knownArticle && ! $knownGuide) {
        abort(404);
    }
    $category = match ($path) {
        '/news/dsa' => 'dsa',
        '/news/banking' => 'banking',
        '/news/vehicle-finance' => 'vehicle_finance',
        default => null,
    };
    $newsArticles = [];
    $homeNews = [];
    $newsPagination = ['previous_page_url' => null, 'next_page_url' => null];
    $newsArticle = null;

    if ($path === '/news' || in_array($path, ['/news/dsa', '/news/banking', '/news/vehicle-finance'], true)) {
        $cacheVersion = Cache::rememberForever('news.articles.version.v1', fn (): string => 'initial');
        $pageNumber = max(1, (int) request()->query('page', 1));
        $cacheKey = 'news.articles.'.$cacheVersion.'.'.($category ?? 'all').'.page.'.$pageNumber;
        $cachedPage = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($category): array {
            $articles = NewsArticle::query()
                ->where('is_published', true)
                ->when($category, fn ($query) => $query->where('category', $category))
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->simplePaginate(15)
                ->withQueryString();

            return [
                'data' => $articles->getCollection()->map(fn (NewsArticle $article): array => [
                    'slug' => $article->slug,
                    'title' => $article->title,
                    'source_url' => $article->source_url,
                    'source_name' => $article->source_name,
                    'category' => $article->category,
                    'published_at' => $article->published_at?->toIso8601String(),
                ])->all(),
                'pagination' => [
                    'previous_page_url' => $articles->previousPageUrl(),
                    'next_page_url' => $articles->nextPageUrl(),
                ],
            ];
        });
        $newsArticles = $cachedPage['data'];
        $newsPagination = $cachedPage['pagination'];
    }

    if ($path === '/') {
        $cacheVersion = Cache::rememberForever('news.articles.version.v1', fn (): string => 'initial');
        $homeNews = Cache::remember('news.home.'.$cacheVersion, now()->addMinutes(5), fn (): array => NewsArticle::query()
            ->where('is_published', true)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get(['slug', 'title', 'source_url', 'source_name', 'category', 'published_at'])
            ->map(fn (NewsArticle $article): array => [
                'slug' => $article->slug,
                'title' => $article->title,
                'source_url' => $article->source_url,
                'source_name' => $article->source_name,
                'category' => $article->category,
                'published_at' => $article->published_at?->toIso8601String(),
            ])->all());
    }

    if (str_starts_with($path, '/news/')) {
        $slug = substr($path, strlen('/news/'));

        if (! str_contains($slug, '/')) {
            $newsArticle = NewsArticle::query()
                ->where('is_published', true)
                ->where('slug', $slug)
                ->first([
                    'slug', 'title', 'source_url', 'source_name', 'category', 'published_at',
                ])
                ?->only(['slug', 'title', 'source_url', 'source_name', 'category', 'published_at']);
        }
    }

    return Inertia::render('Site', [
        'path' => $path,
        'newsArticles' => $newsArticles,
        'homeNews' => $homeNews,
        'newsPagination' => $newsPagination,
        'newsArticle' => $newsArticle,
        'newsFixtureMode' => app()->environment('local'),
        'loanProducts' => collect(config('loan_catalogue.products', []))->where('enabled', true)->map(fn (array $product): array => collect($product)->except(['category', 'enabled'])->all())->values()->all(),
        'businessConfig' => [
            'name' => config('sahu.name'), 'tagline' => config('sahu.tagline'), 'phone' => config('sahu.phone'),
            'whatsapp' => config('sahu.whatsapp'), 'email' => config('sahu.email'), 'address' => config('sahu.address'),
            'city' => config('sahu.city'), 'state' => config('sahu.state'), 'serviceAreas' => config('sahu.service_areas', []),
            'businessHours' => config('sahu.business_hours'), 'grievanceEmail' => config('sahu.grievance_email'),
        ],
    ]);
})->where('path', '.*')->name('site');
