<?php

namespace App\Services;

use App\DTO\News;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class NewsScraperService
{
    private const BASE_URL = 'https://kaktus.media';

    /**
     * Cache scraped results for 15 minutes per date.
     */
    private const CACHE_TTL = 60 * 15;

    /**
     * Fetch news for a given date, optionally filtered by a search keyword.
     *
     * @param  string      $date    Date in Y-m-d format.
     * @param  string|null $search  Optional keyword to filter by title.
     * @return Collection<int, News>
     */
    public function getNews(string $date, ?string $search = null): Collection
    {
        $cacheKey = "news:{$date}";

        /** @var Collection<int, News> $news */
        $news = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($date) {
            return $this->scrape($date);
        });

        if ($search && mb_strlen(trim($search)) > 0) {
            $keyword = mb_strtolower(trim($search));

            $news = $news->filter(
                fn(News $item) => str_contains(mb_strtolower($item->title), $keyword)
            )->values();
        }

        return $news;
    }

    /**
     * Scrape the kaktus.media archive for the given date.
     * URL pattern: https://kaktus.media/?lable=8&date=YYYY-MM-DD&order=time
     *
     * @param  string $date  Date in Y-m-d format.
     * @return Collection<int, News>
     */
    private function scrape(string $date): Collection
    {
        $url = sprintf(
            '%s/?lable=8&date=%s&order=time',
            self::BASE_URL,
            $date,
        );

        $response = Http::timeout(15)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; NewsAggregator/1.0)',
                'Accept-Language' => 'ru-RU,ru;q=0.9',
            ])
            ->get($url);

        if ($response->failed()) {
            return collect();
        }

        return $this->parseHtml($response->body(), $date);
    }

    /**
     * Parse HTML and extract news articles into News DTOs.
     *
     *
     * @param  string $html  Raw HTML response body.
     * @param  string $date  Date string to attach to each DTO.
     * @return Collection<int, News>
     */
    private function parseHtml(string $html, string $date): Collection
    {
        $crawler = new Crawler($html);
        $news    = collect();

        $crawler->filter('.Tag--article .ArticleItem')->each(
            function (Crawler $node) use ($news, $date) {
                $nameLink = $node->filter('a.ArticleItem--name')->first();
                if (!$nameLink->count()) {
                    return;
                }

                $title = trim($nameLink->text(''));
                $url   = trim($nameLink->attr('href') ?? '');

                if (empty($title) || empty($url)) {
                    return;
                }

                if (!str_starts_with($url, 'http')) {
                    $url = self::BASE_URL . '/' . ltrim($url, '/');
                }

                $image   = null;
                $imgNode = $node->filter('a.ArticleItem--image img')->first();
                if ($imgNode->count()) {
                    $image = $imgNode->attr('data-src') ?: $imgNode->attr('src');

                    if ($image && !str_starts_with($image, 'http')) {
                        $image = self::BASE_URL . '/' . ltrim($image, '/');
                    }

                    if (empty($image)) {
                        $image = null;
                    }
                }

                $news->push(News::fromArray([
                    'date'  => $date,
                    'title' => $title,
                    'url'   => $url,
                    'image' => $image,
                ]));
            }
        );

        return $news->unique('url')->values();
    }
}
