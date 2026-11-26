<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EnquiryController as AdminEnquiryController;
use App\Http\Controllers\Admin\FollowUpController;
use App\Http\Controllers\Admin\LendingPartnerController;
use App\Http\Controllers\Admin\LoanCategoryController;
use App\Http\Controllers\Admin\LoanApplicationController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\TaskController;
use App\Http\Controllers\Admin\TwoFactorController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\LoanEnquiryController;
use App\Models\EducationalArticle;
use App\Models\EducationalFaq;
use App\Models\EducationalVideo;
use App\Models\LoanCategory;
use App\Models\LoanProduct;
use App\Models\NewsArticle;
use App\Services\BusinessProfile;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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
    $educationPaths = Schema::hasTable('educational_articles')
        ? EducationalArticle::query()->where('status', 'published')->pluck('slug')->map(fn (string $slug): string => '/education/'.$slug)->all()
        : [];
    $configuredProductPaths = collect(config('loan_catalogue.products', []))->map(fn (array $product): string => '/'.trim((string) ($product['slug'] ?? 'loans/'.Str::slug($product['name'])), '/'))->all();
    $hasManagedProducts = Schema::hasTable('loan_products') && LoanProduct::query()->exists();
    $enabledProductPaths = $hasManagedProducts
        ? LoanProduct::query()->where('is_enabled', true)->pluck('slug')->map(fn (string $slug): string => '/'.$slug)->all()
        : collect(config('loan_catalogue.products', []))->where('enabled', true)->pluck('slug')->all();
    $paths = [...$paths, ...$enabledProductPaths];
    $paths = array_values(array_filter($paths, fn (string $path): bool => ! in_array($path, $configuredProductPaths, true) || in_array($path, $enabledProductPaths, true)));

    return response()->view('sitemap', [
        'urls' => array_map(fn (string $path): string => url($path), [...$paths, ...$newsPaths, ...$educationPaths]),
    ], 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
});

Route::post('/apply', [LoanEnquiryController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('loan-enquiries.store');

Route::post('/contact', [ContactMessageController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('contact-messages.store');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
    Route::get('/forgot-password', [AdminAuthController::class, 'forgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AdminAuthController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [AdminAuthController::class, 'resetPassword'])->name('password.reset');
    Route::post('/reset-password', [AdminAuthController::class, 'updatePassword'])->middleware('throttle:5,1')->name('password.update');

    Route::middleware(['auth', 'admin.access'])->group(function (): void {
        Route::get('/security/two-factor/setup', [TwoFactorController::class, 'setup'])->name('security.setup');
        Route::post('/security/two-factor/setup', [TwoFactorController::class, 'enable'])->middleware('throttle:5,1')->name('security.setup.store');
        Route::get('/security/two-factor/recovery-codes', [TwoFactorController::class, 'recoveryCodes'])->name('security.recovery-codes');
        Route::get('/security/two-factor/challenge', [TwoFactorController::class, 'challenge'])->name('security.challenge');
        Route::post('/security/two-factor/challenge', [TwoFactorController::class, 'verify'])->middleware('throttle:5,1')->name('security.verify');
        Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
        Route::delete('/profile/sessions', [ProfileController::class, 'revokeOtherSessions'])->name('profile.sessions.revoke');
        Route::get('/', AdminDashboardController::class)->middleware('can:dashboard.view')->name('dashboard');
        Route::get('/follow-ups', [FollowUpController::class, 'index'])->name('followups.index');
        Route::patch('/follow-ups/{followUp}', [FollowUpController::class, 'update'])->name('followups.update');
        Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
        Route::post('/tasks', [TaskController::class, 'storeGeneral'])->name('tasks.store');
        Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::get('/applications', [LoanApplicationController::class, 'index'])->name('applications.index');
        Route::post('/applications', [LoanApplicationController::class, 'store'])->name('applications.store');
        Route::patch('/applications/{application}/stage', [LoanApplicationController::class, 'updateStage'])->name('applications.stage');
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::patch('/customers/{customer}/communication-preference', [CustomerController::class, 'updatePreference'])->name('customers.preference');
        Route::post('/customers/{customer}/privacy-requests', [CustomerController::class, 'recordPrivacyRequest'])->name('customers.privacy-requests.store');
        Route::get('/news', [NewsController::class, 'index'])->name('news.index');
        Route::post('/news/sync', [NewsController::class, 'sync'])->middleware('throttle:1,1')->name('news.sync');
        Route::patch('/news/{article}', [NewsController::class, 'update'])->name('news.update');
        Route::get('/content', [ContentController::class, 'index'])->name('content.index');
        Route::get('/contact-messages', [AdminContactMessageController::class, 'index'])->name('contact-messages.index');
        Route::post('/content/articles', [ContentController::class, 'storeArticle'])->name('content.articles.store');
        Route::patch('/content/articles/{article}', [ContentController::class, 'updateArticle'])->name('content.articles.update');
        Route::post('/content/faqs', [ContentController::class, 'storeFaq'])->name('content.faqs.store');
        Route::patch('/content/faqs/{faq}', [ContentController::class, 'updateFaq'])->name('content.faqs.update');
        Route::post('/content/videos', [ContentController::class, 'storeVideo'])->name('content.videos.store');
        Route::patch('/content/videos/{video}', [ContentController::class, 'updateVideo'])->name('content.videos.update');
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/enquiries.csv', [ReportController::class, 'export'])->name('reports.export');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
        Route::get('/lending-partners', [LendingPartnerController::class, 'index'])->name('partners.index');
        Route::post('/lending-partners', [LendingPartnerController::class, 'store'])->name('partners.store');
        Route::patch('/lending-partners/{partner}', [LendingPartnerController::class, 'update'])->name('partners.update');
        Route::post('/enquiries/{enquiry}/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::get('/users', [StaffController::class, 'index'])->name('users.index');
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::patch('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
        Route::post('/users', [StaffController::class, 'store'])->name('users.store');
        Route::patch('/users/{staff}', [StaffController::class, 'update'])->name('users.update');
        Route::post('/users/{staff}/password-reset', [StaffController::class, 'sendPasswordReset'])->middleware('throttle:5,1')->name('users.password-reset');
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::patch('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::get('/loan-products', [ProductController::class, 'index'])->name('loan-products.index');
        Route::get('/loan-categories', [LoanCategoryController::class, 'index'])->name('loan-categories.index');
        Route::post('/loan-categories', [LoanCategoryController::class, 'store'])->name('loan-categories.store');
        Route::patch('/loan-categories/{category}', [LoanCategoryController::class, 'update'])->name('loan-categories.update');
        Route::patch('/applications/{application}/disbursement', [LoanApplicationController::class, 'recordDisbursement'])->name('applications.disbursement');
        Route::get('/enquiries', [AdminEnquiryController::class, 'index'])->name('enquiries.index');
        Route::get('/enquiries/{enquiry}', [AdminEnquiryController::class, 'show'])->name('enquiries.show');
        Route::patch('/enquiries/{enquiry}/status', [AdminEnquiryController::class, 'updateStatus'])->name('enquiries.status');
        Route::patch('/enquiries/{enquiry}/assignment', [AdminEnquiryController::class, 'assign'])->name('enquiries.assign');
        Route::post('/enquiries/{enquiry}/notes', [AdminEnquiryController::class, 'storeNote'])->name('enquiries.notes.store');
        Route::post('/enquiries/{enquiry}/follow-ups', [AdminEnquiryController::class, 'storeFollowUp'])->name('enquiries.followups.store');
    });
});

Route::get('/{path?}', function (?string $path = null): Response {
    $path = '/'.ltrim($path ?? '', '/');
    $productRecords = Schema::hasTable('loan_products') ? LoanProduct::query()->with(['categories', 'faqs', 'relatedProducts'])->orderBy('display_order')->get() : collect();
    $configuredProducts = collect(config('loan_catalogue.products', []))->map(function (array $definition): array {
        $slug = trim((string) ($definition['slug'] ?? 'loans/'.Str::slug($definition['name'])), '/');
        $categorySlug = $definition['category'];

        return [
            'id' => null,
            'title' => $definition['name'],
            'slug' => '/'.$slug,
            'icon' => config('loan_catalogue.categories.'.$categorySlug.'.icon', '↗'),
            'desc' => $definition['description'] ?? 'Availability depends on current service arrangements and the applicable lender.',
            'tag' => ($definition['featured'] ?? false) ? 'Popular' : null,
            'category' => $categorySlug,
            'categorySlug' => $categorySlug,
            'enabled' => (bool) ($definition['enabled'] ?? false),
            'enquiryFields' => config('loan_catalogue.categories.'.$categorySlug.'.fields', []),
        ];
    });
    $publicProducts = $productRecords->isEmpty()
        ? $configuredProducts->where('enabled', true)->values()
        : $productRecords->where('is_enabled', true)->map(fn (LoanProduct $product): array => [
            'id' => $product->id,
            'title' => $product->name,
            'slug' => '/'.$product->slug,
            'icon' => $product->categories->first()?->icon ?? '↗',
            'desc' => $product->description,
            'tag' => $product->is_featured ? 'Popular' : null,
            'category' => $product->categories->first()?->name ?? $product->category,
            'categorySlug' => $product->categories->first()?->slug ?? $product->category,
            'enquiryFields' => $product->enquiry_fields ?: ($product->categories->first()?->enquiry_fields ?? []),
        ])->values();
    $loanCatalogCategories = Schema::hasTable('loan_categories')
        ? LoanCategory::query()->where('is_enabled', true)->whereHas('products', fn ($query) => $query->where('is_enabled', true))
            ->with(['products' => fn ($query) => $query->where('is_enabled', true)->orderBy('display_order')->orderBy('name')])
            ->orderBy('display_order')->get()->map(fn (LoanCategory $category): array => [
                'id' => $category->id,
                'slug' => $category->slug,
                'name' => $category->name,
                'description' => $category->description,
                'icon' => $category->icon,
                'products' => $category->products->map(fn (LoanProduct $product): array => [
                    'title' => $product->name,
                    'slug' => '/'.$product->slug,
                    'icon' => $category->icon,
                    'desc' => $product->description,
                    'tag' => $product->is_featured ? 'Popular' : null,
                ])->all(),
            ])->values()->all()
        : [];
    $loanProductModel = $productRecords->first(fn (LoanProduct $product): bool => $product->is_enabled && '/'.$product->slug === $path);
    if ($loanProductModel) {
        $loanProductModel->load(['lendingPartners' => fn ($query) => $query
            ->where('lending_partners.is_active', true)->where('lending_partners.is_verified', true)
            ->wherePivot('partnership_status', 'active')->wherePivot('is_available', true)]);
    }
    $relatedProducts = $loanProductModel?->relatedProducts->where('is_enabled', true)->values() ?? collect();
    if ($loanProductModel && $relatedProducts->isEmpty()) {
        $relatedProducts = $loanProductModel->categories()->first()?->products()
            ->where('is_enabled', true)->where('loan_products.id', '<>', $loanProductModel->id)->orderBy('display_order')->limit(3)->get() ?? collect();
    }
    $productNews = $loanProductModel
        ? NewsArticle::query()->where('is_published', true)->whereIn('category', ['vehicle_finance', 'dsa', 'banking'])->orderByDesc('published_at')->limit(3)->get(['slug', 'title', 'source_name', 'category', 'published_at'])->map(fn (NewsArticle $article): array => [
            'slug' => $article->slug,
            'title' => $article->title,
            'source_name' => $article->source_name,
            'category' => $article->category,
            'published_at' => $article->published_at?->toIso8601String(),
        ])->all()
        : [];
    $loanProductDetail = $loanProductModel ? [
        'id' => $loanProductModel->id,
        'title' => $loanProductModel->name,
        'slug' => '/'.$loanProductModel->slug,
        'description' => $loanProductModel->description,
        'eligibility' => $loanProductModel->eligibility,
        'documentation' => $loanProductModel->documentation ?? [],
        'seoTitle' => $loanProductModel->seo_title,
        'seoDescription' => $loanProductModel->seo_description,
        'category' => $loanProductModel->categories->first()?->name ?? $loanProductModel->category,
        'categorySlug' => $loanProductModel->categories->first()?->slug ?? $loanProductModel->category,
        'enquiryFields' => $loanProductModel->enquiry_fields ?: ($loanProductModel->categories->first()?->enquiry_fields ?? []),
        'faqs' => $loanProductModel->faqs->map(fn ($faq): array => ['question' => $faq->question, 'answer' => $faq->answer])->all(),
        'relatedProducts' => $relatedProducts->map(fn (LoanProduct $product): array => ['title' => $product->name, 'slug' => '/'.$product->slug])->values()->all(),
        'lendingPartners' => $loanProductModel->lendingPartners->pluck('name')->values()->all(),
    ] : null;
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
    $configuredProductPaths = $configuredProducts->pluck('slug')->all();
    $enabledProductPaths = $publicProducts->pluck('slug')->all();
    $availabilityCheckRoutes = [
        '/loans/personal-loan', '/loans/business-loan', '/loans/home-loan',
        '/loans/loan-against-property', '/loans/balance-transfer',
    ];
    $publicRoutes = [...$publicRoutes, ...$enabledProductPaths];
    $publicRoutes = array_values(array_filter($publicRoutes, fn (string $route): bool => ! in_array($route, $configuredProductPaths, true) || in_array($route, $enabledProductPaths, true) || in_array($route, $availabilityCheckRoutes, true)));
    $knownArticle = str_starts_with($path, '/news/') && ! in_array($path, ['/news/dsa', '/news/banking', '/news/vehicle-finance'], true)
        ? NewsArticle::query()->where('is_published', true)->where('slug', substr($path, 6))->exists()
        : false;
    $knownGuide = str_starts_with($path, '/resources/') && in_array(substr($path, 11), [
        'vehicle-loan-emi', 'dsa-guide', 'vehicle-finance-guide', 'used-car-finance', 'loan-tenure-guide',
        'vehicle-finance-documents', 'credit-score-guide', 'loan-processing-fees', 'vehicle-refinancing-guide', 'borrower-rights-guide',
    ], true);
    $managedArticle = str_starts_with($path, '/education/')
        ? EducationalArticle::query()->where('slug', substr($path, strlen('/education/')))->where('status', 'published')->first(['slug', 'title', 'body', 'published_at'])
        : null;
    if (! in_array($path, $publicRoutes, true) && ! $knownArticle && ! $knownGuide && ! $managedArticle) {
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
            ->orderByDesc('is_featured')
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

    $businessProfile = app(BusinessProfile::class);
    $setting = fn (string $key): mixed => $businessProfile->get($key);
    $serviceAreasValue = $setting('service_areas');
    $serviceAreas = is_array($serviceAreasValue)
        ? $serviceAreasValue
        : array_values(array_filter(array_map(trim(...), explode(',', (string) $serviceAreasValue))));
    $educationalVideos = Schema::hasTable('educational_videos')
        ? EducationalVideo::query()->where('is_visible', true)->orderBy('display_order')->orderBy('title')->get()->map(fn (EducationalVideo $video): array => ['title' => $video->title, 'url' => $video->url, 'publisher' => $video->publisher, 'description' => $video->description, 'category' => $video->category])->all()
        : [];
    $publishedEducation = $path === '/resources' && Schema::hasTable('educational_articles')
        ? EducationalArticle::query()->where('status', 'published')->latest('published_at')->limit(30)->get(['slug', 'title', 'body', 'published_at'])->map(fn (EducationalArticle $article): array => ['slug' => $article->slug, 'title' => $article->title, 'excerpt' => mb_substr(preg_replace('/\\s+/', ' ', $article->body) ?? '', 0, 180), 'published_at' => $article->published_at?->toIso8601String()])->all()
        : [];
    $publishedFaqs = $path === '/faq' && Schema::hasTable('educational_faqs')
        ? EducationalFaq::query()->where('is_visible', true)->orderBy('display_order')->orderBy('id')->get(['question', 'answer'])->map(fn (EducationalFaq $faq): array => ['question' => $faq->question, 'answer' => $faq->answer])->all()
        : [];

    return Inertia::render('Site', [
        'path' => $path,
        'newsArticles' => $newsArticles,
        'homeNews' => $homeNews,
        'newsPagination' => $newsPagination,
        'newsArticle' => $newsArticle,
        'loanProductDetail' => $loanProductDetail,
        'loanCatalogCategories' => $loanCatalogCategories,
        'productNews' => $productNews,
        'managedArticle' => $managedArticle?->only(['slug', 'title', 'body', 'published_at']),
        'educationalVideos' => $educationalVideos,
        'publishedEducation' => $publishedEducation,
        'publishedFaqs' => $publishedFaqs,
        'newsFixtureMode' => app()->environment('local'),
        'loanProducts' => $publicProducts->map(fn (array $product): array => collect($product)->except(['enabled'])->all())->values()->all(),
        'businessConfig' => $businessProfile->publicDetails(),
    ]);
})->where('path', '.*')->name('site');
