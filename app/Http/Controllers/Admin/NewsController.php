<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\NewsArticle;
use App\Services\Admin\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasAdminPermission('news.manage'), 403);
        $query = NewsArticle::query();
        $query->when($request->query('category'), fn ($builder, $category) => $builder->where('category', $category));

        return Inertia::render('Admin/News/Index', [
            'articles' => $query->orderByDesc('published_at')->paginate(25)->withQueryString()->through(fn (NewsArticle $article): array => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'sourceName' => $article->source_name,
                'sourceUrl' => $article->source_url,
                'category' => $article->category,
                'publishedAt' => $article->published_at?->toIso8601String(),
                'isPublished' => $article->is_published,
                'isFeatured' => $article->is_featured,
            ]),
            'categories' => NewsArticle::query()->distinct()->orderBy('category')->pluck('category'),
            'categoryFilter' => $request->query('category', ''),
            'sync' => [
                'enabled' => config('news.pib.enabled') && BusinessSetting::query()->where('key', 'news_sync_enabled')->value('value') !== '0',
                'status' => BusinessSetting::query()->where('key', 'news_last_sync_status')->value('value'),
                'lastAttemptAt' => BusinessSetting::query()->where('key', 'news_last_attempt_at')->value('value'),
                'lastSuccessAt' => BusinessSetting::query()->where('key', 'news_last_success_at')->value('value'),
                'lastCount' => (int) (BusinessSetting::query()->where('key', 'news_last_sync_count')->value('value') ?? 0),
            ],
        ]);
    }

    public function sync(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()->hasAdminPermission('news.manage'), 403);
        $auditLogger->record('news.sync_requested', $request->user());
        $exitCode = Artisan::call('news:ingest-pib');

        return back()->with($exitCode === 0 ? 'success' : 'error', $exitCode === 0 ? 'News synchronization completed.' : 'News synchronization failed. Check the server log.');
    }

    public function update(Request $request, NewsArticle $article, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()->hasAdminPermission('news.manage'), 403);
        $data = $request->validate(['is_published' => ['required', 'boolean'], 'is_featured' => ['required', 'boolean']]);
        $article->update($data);
        $auditLogger->record('news.visibility_changed', $request->user(), 'news_article', $article->id, ['is_published' => (bool) $data['is_published'], 'is_featured' => (bool) $data['is_featured']]);
        Cache::forever('news.articles.version.v1', (string) Str::uuid());

        return back()->with('success', 'News visibility settings saved.');
    }
}
