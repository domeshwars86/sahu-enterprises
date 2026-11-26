<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EducationalArticle;
use App\Models\EducationalFaq;
use App\Models\EducationalVideo;
use App\Services\Admin\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ContentController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasAdminPermission('content.manage') || $request->user()->hasAdminPermission('videos.manage'), 403);

        return Inertia::render('Admin/Content/Index', [
            'articles' => $request->user()->hasAdminPermission('content.manage') ? EducationalArticle::query()->with('author:id,name')->latest()->paginate(20)->withQueryString()->through(fn (EducationalArticle $article): array => ['id' => $article->id, 'title' => $article->title, 'slug' => $article->slug, 'body' => $article->body, 'status' => $article->status, 'author' => $article->author?->name, 'publishedAt' => $article->published_at?->toIso8601String()]) : [],
            'faqs' => $request->user()->hasAdminPermission('content.manage') ? EducationalFaq::query()->orderBy('display_order')->orderBy('id')->get()->map(fn (EducationalFaq $faq): array => ['id' => $faq->id, 'question' => $faq->question, 'answer' => $faq->answer, 'displayOrder' => $faq->display_order, 'isVisible' => $faq->is_visible]) : [],
            'videos' => $request->user()->hasAdminPermission('videos.manage') ? EducationalVideo::query()->orderBy('display_order')->orderBy('title')->get()->map(fn (EducationalVideo $video): array => ['id' => $video->id, 'title' => $video->title, 'url' => $video->url, 'publisher' => $video->publisher, 'description' => $video->description, 'category' => $video->category, 'displayOrder' => $video->display_order, 'isVisible' => $video->is_visible, 'isFeatured' => $video->is_featured]) : [],
            'canManageArticles' => $request->user()->hasAdminPermission('content.manage'),
            'canManageVideos' => $request->user()->hasAdminPermission('videos.manage'),
        ]);
    }

    public function storeArticle(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()->hasAdminPermission('content.manage'), 403);
        $data = $request->validate(['title' => ['required', 'string', 'max:180'], 'slug' => ['nullable', 'string', 'max:180', 'alpha_dash', 'unique:educational_articles,slug'], 'body' => ['required', 'string', 'max:50000'], 'status' => ['required', 'in:draft,published']]);
        $slug = $data['slug'] ?: Str::slug($data['title']);
        $baseSlug = $slug;
        $suffix = 2;
        while (EducationalArticle::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }
        $article = EducationalArticle::query()->create([...$data, 'slug' => $slug, 'author_id' => $request->user()->id, 'published_at' => $data['status'] === 'published' ? now() : null]);
        $auditLogger->record('content.article_created', $request->user(), 'educational_article', $article->id, ['status' => $article->status]);

        return back()->with('success', 'Educational article saved.');
    }

    public function updateArticle(Request $request, EducationalArticle $article, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()->hasAdminPermission('content.manage'), 403);
        $data = $request->validate(['title' => ['sometimes', 'required', 'string', 'max:180'], 'body' => ['sometimes', 'required', 'string', 'max:50000'], 'status' => ['sometimes', 'required', 'in:draft,published']]);
        if (($data['status'] ?? null) === 'published' && ! $article->published_at) {
            $data['published_at'] = now();
        } elseif (($data['status'] ?? null) === 'draft') {
            $data['published_at'] = null;
        }
        $article->update($data);
        $auditLogger->record('content.article_updated', $request->user(), 'educational_article', $article->id, ['status' => $article->status]);

        return back()->with('success', 'Educational article updated.');
    }

    public function storeVideo(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()->hasAdminPermission('videos.manage'), 403);
        $data = $request->validate(['title' => ['required', 'string', 'max:180'], 'url' => ['required', 'url', 'max:2048', 'starts_with:https://www.youtube.com/,https://youtube.com/,https://youtu.be/', function (string $attribute, mixed $value, \Closure $fail): void {
            if (! in_array(parse_url($value, PHP_URL_HOST), ['www.youtube.com', 'youtube.com', 'youtu.be'], true)) {
                $fail('The video URL must point to YouTube.');
            }
        }], 'publisher' => ['nullable', 'string', 'max:160'], 'description' => ['nullable', 'string', 'max:5000'], 'category' => ['nullable', 'string', 'max:80'], 'display_order' => ['sometimes', 'integer', 'min:0', 'max:10000'], 'is_visible' => ['required', 'boolean'], 'is_featured' => ['required', 'boolean']]);
        $video = EducationalVideo::query()->create($data);
        $auditLogger->record('content.video_created', $request->user(), 'educational_video', $video->id, ['is_visible' => $video->is_visible]);

        return back()->with('success', 'Educational video saved.');
    }

    public function storeFaq(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()->hasAdminPermission('content.manage'), 403);
        $data = $request->validate(['question' => ['required', 'string', 'max:240'], 'answer' => ['required', 'string', 'max:5000'], 'display_order' => ['required', 'integer', 'min:0', 'max:10000'], 'is_visible' => ['required', 'boolean']]);
        $faq = EducationalFaq::query()->create($data);
        $auditLogger->record('content.faq_created', $request->user(), 'educational_faq', $faq->id, ['is_visible' => $faq->is_visible]);

        return back()->with('success', 'FAQ saved.');
    }

    public function updateFaq(Request $request, EducationalFaq $faq, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()->hasAdminPermission('content.manage'), 403);
        $faq->update($request->validate(['question' => ['sometimes', 'required', 'string', 'max:240'], 'answer' => ['sometimes', 'required', 'string', 'max:5000'], 'display_order' => ['sometimes', 'required', 'integer', 'min:0', 'max:10000'], 'is_visible' => ['sometimes', 'required', 'boolean']]));
        $auditLogger->record('content.faq_updated', $request->user(), 'educational_faq', $faq->id, ['is_visible' => $faq->is_visible]);

        return back()->with('success', 'FAQ updated.');
    }

    public function updateVideo(Request $request, EducationalVideo $video, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()->hasAdminPermission('videos.manage'), 403);
        $data = $request->validate(['title' => ['sometimes', 'required', 'string', 'max:180'], 'url' => ['sometimes', 'required', 'url', 'max:2048', 'starts_with:https://www.youtube.com/,https://youtube.com/,https://youtu.be/', function (string $attribute, mixed $value, \Closure $fail): void {
            if (! in_array(parse_url($value, PHP_URL_HOST), ['www.youtube.com', 'youtube.com', 'youtu.be'], true)) {
                $fail('The video URL must point to YouTube.');
            }
        }], 'publisher' => ['nullable', 'string', 'max:160'], 'description' => ['nullable', 'string', 'max:5000'], 'category' => ['nullable', 'string', 'max:80'], 'display_order' => ['sometimes', 'integer', 'min:0', 'max:10000'], 'is_visible' => ['sometimes', 'required', 'boolean'], 'is_featured' => ['sometimes', 'required', 'boolean']]);
        $video->update($data);
        $auditLogger->record('content.video_updated', $request->user(), 'educational_video', $video->id, ['is_visible' => $video->is_visible]);

        return back()->with('success', 'Educational video updated.');
    }
}
