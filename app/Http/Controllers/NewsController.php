<?php

namespace App\Http\Controllers;

use App\Http\Requests\NewsSearchRequest;
use App\Services\NewsScraperService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class NewsController extends Controller
{
    public function __construct(
        private readonly NewsScraperService $scraper,
    ) {}

    /**
     * Return the initial news list for today (or a given date).
     * Used when the page first loads.
     *
     * GET /api/news?date=YYYY-MM-DD
     */
    public function index(NewsSearchRequest $request): JsonResponse
    {
        $date = $request->validated('date') ?? Carbon::today()->toDateString();
        $news = $this->scraper->getNews($date);

        return response()->json([
            'date'  => $date,
            'total' => $news->count(),
            'data'  => $news->map(fn($item) => $item->toArray())->values(),
        ]);
    }

    /**
     * Search news by title keyword for a given date (backend-filtered).
     *
     * GET /api/news/search?date=YYYY-MM-DD&search=keyword
     */
    public function search(NewsSearchRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $date   = $validated['date'];
        $search = $validated['search'] ?? null;

        $news = $this->scraper->getNews($date, $search);

        return response()->json([
            'date'   => $date,
            'search' => $search,
            'total'  => $news->count(),
            'data'   => $news->map(fn($item) => $item->toArray())->values(),
        ]);
    }
}
