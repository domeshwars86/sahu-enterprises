<?php

namespace App\Services\News;

use App\Models\BusinessSetting;
use App\Models\NewsArticle;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;
use Throwable;

class PibRssIngestor
{
    public function ingest(): int
    {
        if (! config('news.pib.enabled') || (Schema::hasTable('business_settings') && BusinessSetting::query()->where('key', 'news_sync_enabled')->value('value') === '0')) {
            return 0;
        }

        $response = Http::connectTimeout(3)
            ->timeout(10)
            ->withoutRedirecting()
            ->accept('application/rss+xml, application/xml, text/xml')
            ->get(config('news.pib.feed_url'))
            ->throw();

        $xmlContent = $response->body();

        if (strlen($xmlContent) > config('news.pib.max_response_bytes')) {
            throw new RuntimeException('The PIB RSS response exceeded the configured size limit.');
        }

        $previousErrorsMode = libxml_use_internal_errors(true);

        try {
            $feed = simplexml_load_string($xmlContent, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorsMode);
        }

        if (! $feed instanceof SimpleXMLElement || ! isset($feed->channel->item)) {
            throw new RuntimeException('The PIB RSS response did not contain a valid RSS channel.');
        }

        $processed = 0;

        $itemCount = 0;

        foreach ($feed->channel->item as $item) {
            if ($itemCount++ >= config('news.pib.max_items')) {
                break;
            }

            $title = (string) Str::of(strip_tags((string) $item->title))->squish();
            $sourceUrl = $this->approvedArticleUrl((string) $item->link);
            $category = $this->classify($title);

            if ($title === '' || $sourceUrl === null || $category === null) {
                continue;
            }

            $publishedAt = null;
            $publicationDate = trim((string) $item->pubDate);

            if ($publicationDate !== '') {
                try {
                    $publishedAt = CarbonImmutable::parse($publicationDate)->utc();
                } catch (Throwable) {
                    continue;
                }
            }

            $externalId = hash('sha256', $sourceUrl);
            $slug = Str::limit(Str::slug($title), 120, '').'-'.substr($externalId, 0, 12);

            NewsArticle::query()->updateOrCreate(
                ['external_id' => $externalId],
                [
                    'slug' => $slug,
                    'title' => Str::limit($title, 500, ''),
                    'source_url' => $sourceUrl,
                    'source_name' => config('news.pib.source_name'),
                    'category' => $category,
                    'published_at' => $publishedAt,
                    'fetched_at' => now(),
                    'is_published' => true,
                ],
            );

            $processed++;
        }

        Cache::forever('news.articles.version.v1', Str::uuid()->toString());

        NewsArticle::query()
            ->where('source_name', config('news.pib.source_name'))
            ->where('is_published', true)
            ->where('fetched_at', '<', now()->subDays(60))
            ->update(['is_published' => false]);

        return $processed;
    }

    private function approvedArticleUrl(string $url): ?string
    {
        $parts = parse_url(trim($url));

        if (! is_array($parts)
            || ($parts['scheme'] ?? null) !== 'https'
            || ! in_array(strtolower($parts['host'] ?? ''), config('news.pib.allowed_hosts'), true)
            || isset($parts['user'])
            || isset($parts['pass'])
            || (isset($parts['port']) && $parts['port'] !== 443)) {
            return null;
        }

        return $url;
    }

    private function classify(string $title): ?string
    {
        if (preg_match('/\b(dsa|direct selling agent|loan sourcing agent|dsa industry)\b/i', $title) === 1) {
            return 'dsa';
        }

        if (preg_match('/\b(vehicle loan|vehicle finance|car loan|auto loan|commercial vehicle finance)\b/i', $title) === 1) {
            return 'vehicle_finance';
        }

        if (preg_match('/\b(banking|bank|nbfc|non-banking financial|digital lending|credit bureau)\b/i', $title) === 1) {
            return 'banking';
        }

        if (preg_match('/\b(finance|financial inclusion|loan|lending|credit|fintech|microfinance)\b/i', $title) === 1) {
            return 'finance';
        }

        return null;
    }
}
