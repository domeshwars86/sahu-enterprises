<?php

namespace Tests\Feature;

use App\Models\NewsArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class IngestPibNewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_only_relevant_articles_from_approved_pib_urls_and_is_idempotent(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'www.pib.gov.in/RssMain.aspx*' => Http::response($this->feed([
                ['RBI announces banking sector update', 'https://www.pib.gov.in/PressReleasePage.aspx?PRID=123', 'Wed, 18 Nov 2026 10:00:00 GMT'],
                ['Vehicle finance policy update', 'https://example.com/story', 'Wed, 18 Nov 2026 11:00:00 GMT'],
                ['Annual agriculture programme announced', 'https://pib.gov.in/PressReleasePage.aspx?PRID=456', 'Wed, 18 Nov 2026 12:00:00 GMT'],
            ]), 200, ['Content-Type' => 'application/rss+xml']),
        ]);

        $this->artisan('news:ingest-pib')->expectsOutput('Imported or refreshed 1 PIB headlines.')->assertSuccessful();
        $this->artisan('news:ingest-pib')->assertSuccessful();

        $this->assertDatabaseCount('news_articles', 1);
        $article = NewsArticle::query()->sole();
        $this->assertSame('banking', $article->category);
        $this->assertSame('Press Information Bureau', $article->source_name);
        $this->assertSame('https://www.pib.gov.in/PressReleasePage.aspx?PRID=123', $article->source_url);
        $this->assertSame('RBI announces banking sector update', $article->title);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://www.pib.gov.in/RssMain.aspx?ModId=6&Lang=1&Regid=1');
    }

    public function test_ingest_command_reports_a_safe_failure_when_the_feed_is_unavailable(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'www.pib.gov.in/RssMain.aspx*' => Http::failedConnection(),
        ]);

        $this->artisan('news:ingest-pib')
            ->expectsOutput('PIB news import failed. Check the application log for details.')
            ->assertFailed();

        $this->assertDatabaseCount('news_articles', 0);
    }

    public function test_news_pages_only_share_published_articles_for_the_requested_category(): void
    {
        NewsArticle::query()->create($this->articleAttributes('Visible DSA headline', 'dsa', true));
        NewsArticle::query()->create($this->articleAttributes('Visible banking headline', 'banking', true));
        NewsArticle::query()->create($this->articleAttributes('Hidden DSA headline', 'dsa', false));

        $this->get('/news/dsa')->assertInertia(fn (Assert $page) => $page
            ->component('Site')
            ->where('path', '/news/dsa')
            ->has('newsArticles', 1)
            ->where('newsArticles.0.title', 'Visible DSA headline')
            ->where('newsArticles.0.category', 'dsa')
            ->where('newsFixtureMode', false));

        $this->get('/news/visible-dsa-headline')
            ->assertInertia(fn (Assert $page) => $page
                ->where('newsArticle.title', 'Visible DSA headline')
                ->where('newsArticle.source_url', 'https://www.pib.gov.in/PressReleasePage.aspx?PRID=123'));
    }

    public function test_homepage_shares_only_published_finance_headlines(): void
    {
        NewsArticle::query()->create($this->articleAttributes('Published finance headline', 'finance', true));
        NewsArticle::query()->create($this->articleAttributes('Unpublished finance headline', 'finance', false));

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->component('Site')
            ->has('homeNews', 1)
            ->where('homeNews.0.title', 'Published finance headline'));
    }

    /** @param array<int, array{0: string, 1: string, 2: string}> $items */
    private function feed(array $items): string
    {
        $xmlItems = collect($items)
            ->map(fn (array $item): string => '<item><title>'.e($item[0]).'</title><link>'.e($item[1]).'</link><pubDate>'.e($item[2]).'</pubDate></item>')
            ->implode('');

        return '<?xml version="1.0"?><rss version="2.0"><channel><title>PIB</title>'.$xmlItems.'</channel></rss>';
    }

    /** @return array<string, mixed> */
    private function articleAttributes(string $title, string $category, bool $published): array
    {
        static $id = 0;
        $id++;

        return [
            'external_id' => hash('sha256', (string) $id),
            'slug' => str($title)->slug(),
            'title' => $title,
            'source_url' => 'https://www.pib.gov.in/PressReleasePage.aspx?PRID=123',
            'source_name' => 'Press Information Bureau',
            'category' => $category,
            'published_at' => now(),
            'fetched_at' => now(),
            'is_published' => $published,
        ];
    }
}
