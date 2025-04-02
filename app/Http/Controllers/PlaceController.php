<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Category;
use App\Models\Rating;
use App\Models\SearchHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PlaceController extends Controller
{
    private const CACHE_TTL = 3600; // 1小時
    private const CACHE_KEY_PLACES = 'places.all';
    private const CACHE_KEY_CATEGORIES = 'categories.all';
    private const DEFAULT_PER_PAGE = 10; // 默認每頁顯示數量
    private const SUGGESTION_LIMIT = 5; // 搜索建議數量限制
    private const HISTORY_LIMIT = 10; // 歷史記錄數量限制

    /**
     * 獲取所有地點
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', self::DEFAULT_PER_PAGE);
            $page = $request->input('page', 1);
            $cacheKey = "places.all.page{$page}.per{$perPage}";

            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($perPage) {
                return Place::with(['category:id,name,icon'])
                    ->select(['id', 'name', 'description', 'lat', 'lng', 'rating', 'category_id'])
                    ->orderBy('name')
                    ->paginate($perPage)
                    ->through(function ($place) {
                        return [
                            'id' => $place->id,
                            'name' => $place->name,
                            'description' => $place->description,
                            'lat' => (float) $place->lat,
                            'lng' => (float) $place->lng,
                            'rating' => (float) $place->rating,
                            'category_id' => $place->category_id,
                            'category_name' => $place->category->name,
                            'category_icon' => $place->category->icon
                        ];
                    });
            });
        } catch (\Exception $e) {
            Log::error('獲取地點列表失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '獲取地點列表失敗'], 500);
        }
    }

    /**
     * 獲取所有類別
     */
    public function categories()
    {
        try {
            return Cache::remember(self::CACHE_KEY_CATEGORIES, self::CACHE_TTL, function () {
                return Category::select(['id', 'name', 'icon'])
                    ->orderBy('name')
                    ->get();
            });
        } catch (\Exception $e) {
            Log::error('獲取類別列表失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '獲取類別列表失敗'], 500);
        }
    }

    /**
     * 根據類別獲取地點
     */
    public function getByCategory($categoryId)
    {
        try {
            $cacheKey = "places.category.{$categoryId}";
            
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($categoryId) {
                return Place::with(['category:id,name,icon'])
                    ->where('category_id', $categoryId)
                    ->select(['id', 'name', 'description', 'lat', 'lng', 'rating', 'category_id'])
                    ->get()
                    ->map(function ($place) {
                        return [
                            'id' => $place->id,
                            'name' => $place->name,
                            'description' => $place->description,
                            'lat' => (float) $place->lat,
                            'lng' => (float) $place->lng,
                            'rating' => (float) $place->rating,
                            'category_id' => $place->category_id,
                            'category_name' => $place->category->name,
                            'category_icon' => $place->category->icon
                        ];
                    });
            });
        } catch (\Exception $e) {
            Log::error('獲取類別地點失敗', [
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '獲取類別地點失敗'], 500);
        }
    }

    /**
     * 搜索地點
     */
    public function search(Request $request)
    {
        try {
            $query = $request->input('query', '');
            $categoryId = $request->input('category_id');
            $minRating = $request->input('min_rating');
            $maxRating = $request->input('max_rating');
            $lat = $request->input('lat');
            $lng = $request->input('lng');
            $radius = $request->input('radius', 5);
            $sortBy = $request->input('sort_by', 'name');
            $sortOrder = $request->input('sort_order', 'asc');
            $perPage = $request->input('per_page', self::DEFAULT_PER_PAGE);
            $page = $request->input('page', 1);

            $cacheKey = "places.search." . md5(json_encode($request->all()));

            $result = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($query, $categoryId, $minRating, $maxRating, $lat, $lng, $radius, $sortBy, $sortOrder, $perPage) {
                $places = Place::with(['category:id,name,icon'])
                    ->select([
                        'id', 'name', 'description', 'lat', 'lng', 'rating', 'category_id',
                        DB::raw("(6371 * acos(cos(radians($lat)) * cos(radians(lat)) * cos(radians(lng) - radians($lng)) + sin(radians($lat)) * sin(radians(lat)))) AS distance")
                    ]);

                // 應用搜索條件
                if ($query) {
                    $places->where(function ($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%")
                          ->orWhere('description', 'like', "%{$query}%");
                    });
                }

                if ($categoryId) {
                    $places->where('category_id', $categoryId);
                }

                if ($minRating !== null) {
                    $places->where('rating', '>=', $minRating);
                }

                if ($maxRating !== null) {
                    $places->where('rating', '<=', $maxRating);
                }

                if ($lat && $lng) {
                    $places->having('distance', '<=', $radius);
                }

                // 應用排序
                switch ($sortBy) {
                    case 'rating':
                        $places->orderBy('rating', $sortOrder);
                        break;
                    case 'distance':
                        if ($lat && $lng) {
                            $places->orderBy('distance', $sortOrder);
                        }
                        break;
                    case 'name':
                    default:
                        $places->orderBy('name', $sortOrder);
                }

                return $places->paginate($perPage)
                    ->through(function ($place) {
                        return [
                            'id' => $place->id,
                            'name' => $place->name,
                            'description' => $place->description,
                            'lat' => (float) $place->lat,
                            'lng' => (float) $place->lng,
                            'rating' => (float) $place->rating,
                            'category_id' => $place->category_id,
                            'category_name' => $place->category->name,
                            'category_icon' => $place->category->icon,
                            'distance' => isset($place->distance) ? round($place->distance, 2) : null
                        ];
                    });
            });

            // 記錄搜索歷史
            if ($query) {
                SearchHistory::record($query, $request->all(), $result->total());
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('搜索地點失敗', [
                'params' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '搜索地點失敗'], 500);
        }
    }

    /**
     * 獲取搜索歷史
     */
    public function getSearchHistory(Request $request)
    {
        try {
            $limit = $request->input('limit', self::HISTORY_LIMIT);
            $userId = auth()->id();

            return response()->json([
                'history' => SearchHistory::getRecent($limit, $userId)
            ]);
        } catch (\Exception $e) {
            Log::error('獲取搜索歷史失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '獲取搜索歷史失敗'], 500);
        }
    }

    /**
     * 清除搜索歷史
     */
    public function clearSearchHistory()
    {
        try {
            SearchHistory::clear(auth()->id());
            return response()->json(['message' => '搜索歷史清除成功']);
        } catch (\Exception $e) {
            Log::error('清除搜索歷史失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '清除搜索歷史失敗'], 500);
        }
    }

    /**
     * 獲取附近地點
     */
    public function getNearby(Request $request)
    {
        try {
            $lat = $request->input('lat');
            $lng = $request->input('lng');
            $radius = $request->input('radius', 5); // 默認5公里
            $perPage = $request->input('per_page', self::DEFAULT_PER_PAGE);
            $page = $request->input('page', 1);
            $cacheKey = "places.nearby.{$lat}.{$lng}.{$radius}.page{$page}.per{$perPage}";

            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($lat, $lng, $radius, $perPage) {
                return Place::with(['category:id,name,icon'])
                    ->select([
                        'id', 'name', 'description', 'lat', 'lng', 'rating', 'category_id',
                        DB::raw("(6371 * acos(cos(radians($lat)) * cos(radians(lat)) * cos(radians(lng) - radians($lng)) + sin(radians($lat)) * sin(radians(lat)))) AS distance")
                    ])
                    ->having('distance', '<=', $radius)
                    ->orderBy('distance')
                    ->paginate($perPage)
                    ->through(function ($place) {
                        return [
                            'id' => $place->id,
                            'name' => $place->name,
                            'description' => $place->description,
                            'lat' => (float) $place->lat,
                            'lng' => (float) $place->lng,
                            'rating' => (float) $place->rating,
                            'category_id' => $place->category_id,
                            'category_name' => $place->category->name,
                            'category_icon' => $place->category->icon,
                            'distance' => round($place->distance, 2)
                        ];
                    });
            });
        } catch (\Exception $e) {
            Log::error('獲取附近地點失敗', [
                'lat' => $lat,
                'lng' => $lng,
                'radius' => $radius,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '獲取附近地點失敗'], 500);
        }
    }

    /**
     * 獲取搜索建議
     */
    public function getSuggestions(Request $request)
    {
        try {
            $query = $request->input('query', '');
            $categoryId = $request->input('category_id');
            $lat = $request->input('lat');
            $lng = $request->input('lng');
            $radius = $request->input('radius', 5);

            if (strlen($query) < 2) {
                return response()->json(['suggestions' => []]);
            }

            $cacheKey = "places.suggestions." . md5(json_encode([
                'query' => $query,
                'category_id' => $categoryId,
                'lat' => $lat,
                'lng' => $lng,
                'radius' => $radius
            ]));

            return Cache::remember($cacheKey, 300, function () use ($query, $categoryId, $lat, $lng, $radius) {
                $places = Place::with(['category:id,name,icon'])
                    ->select([
                        'id', 'name', 'description', 'lat', 'lng', 'rating', 'category_id',
                        DB::raw("(6371 * acos(cos(radians($lat)) * cos(radians(lat)) * cos(radians(lng) - radians($lng)) + sin(radians($lat)) * sin(radians(lat)))) AS distance")
                    ])
                    ->where(function ($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%")
                          ->orWhere('description', 'like', "%{$query}%");
                    });

                if ($categoryId) {
                    $places->where('category_id', $categoryId);
                }

                if ($lat && $lng) {
                    $places->having('distance', '<=', $radius);
                }

                $suggestions = $places->orderBy('rating', 'desc')
                    ->limit(self::SUGGESTION_LIMIT)
                    ->get()
                    ->map(function ($place) {
                        return [
                            'id' => $place->id,
                            'name' => $place->name,
                            'description' => $this->highlightText($place->description, request('query')),
                            'rating' => (float) $place->rating,
                            'category_name' => $place->category->name,
                            'category_icon' => $place->category->icon,
                            'distance' => isset($place->distance) ? round($place->distance, 2) : null,
                            'url' => route('places.show', $place->id)
                        ];
                    });

                return [
                    'suggestions' => $suggestions,
                    'total' => $places->count(),
                    'query' => $query
                ];
            });
        } catch (\Exception $e) {
            Log::error('獲取搜索建議失敗', [
                'params' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '獲取搜索建議失敗'], 500);
        }
    }

    /**
     * 高亮搜索文本
     */
    private function highlightText(string $text, string $query): string
    {
        if (empty($query)) {
            return $text;
        }

        $pattern = '/(' . preg_quote($query, '/') . ')/i';
        return preg_replace($pattern, '<mark>$1</mark>', $text);
    }

    /**
     * 清除緩存
     */
    public function clearCache()
    {
        try {
            Cache::forget(self::CACHE_KEY_PLACES);
            Cache::forget(self::CACHE_KEY_CATEGORIES);
            Cache::tags(['suggestions'])->flush();
            return response()->json(['message' => '緩存清除成功']);
        } catch (\Exception $e) {
            Log::error('清除緩存失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '清除緩存失敗'], 500);
        }
    }

    /**
     * 獲取地點的評分統計
     */
    public function getPlaceStats(int $id)
    {
        try {
            $cacheKey = "place.{$id}.stats";

            return Cache::remember($cacheKey, 3600, function () use ($id) {
                $place = Place::findOrFail($id);
                $stats = Rating::getPlaceStats($id);
                $tags = Rating::getPlaceTags($id);

                return response()->json([
                    'place' => [
                        'id' => $place->id,
                        'name' => $place->name,
                        'rating' => $place->rating
                    ],
                    'stats' => $stats,
                    'tags' => $tags
                ]);
            });
        } catch (\Exception $e) {
            Log::error('獲取地點評分統計失敗', [
                'place_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '獲取地點評分統計失敗'], 500);
        }
    }

    /**
     * 提交評分
     */
    public function submitRating(Request $request, int $id)
    {
        try {
            $request->validate([
                'rating' => 'required|numeric|min:1|max:5',
                'comment' => 'nullable|string|max:1000',
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:20'
            ]);

            $place = Place::findOrFail($id);
            $rating = Rating::record([
                'place_id' => $id,
                'rating' => $request->input('rating'),
                'comment' => $request->input('comment'),
                'tags' => $request->input('tags')
            ]);

            // 清除相關緩存
            Cache::forget("place.{$id}.stats");

            return response()->json([
                'message' => '評分提交成功',
                'rating' => $rating
            ]);
        } catch (\Exception $e) {
            Log::error('提交評分失敗', [
                'place_id' => $id,
                'params' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '提交評分失敗'], 500);
        }
    }

    /**
     * 獲取用戶的評分歷史
     */
    public function getUserRatings(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            $userId = auth()->id();

            return response()->json([
                'ratings' => Rating::getUserRatings($userId, $limit)
            ]);
        } catch (\Exception $e) {
            Log::error('獲取用戶評分歷史失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '獲取用戶評分歷史失敗'], 500);
        }
    }

    /**
     * 驗證評分
     */
    public function verifyRating(int $id)
    {
        try {
            $rating = Rating::findOrFail($id);
            $rating->verify();

            // 清除相關緩存
            Cache::forget("place.{$rating->place_id}.stats");

            return response()->json([
                'message' => '評分驗證成功',
                'rating' => $rating
            ]);
        } catch (\Exception $e) {
            Log::error('驗證評分失敗', [
                'rating_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '驗證評分失敗'], 500);
        }
    }

    /**
     * 獲取地點的評分時間分布
     */
    public function getPlaceRatingTimeDistribution(int $id)
    {
        try {
            $cacheKey = "place.{$id}.rating_time_distribution";

            return Cache::remember($cacheKey, 3600, function () use ($id) {
                return response()->json([
                    'distribution' => Rating::getRatingTimeDistribution($id)
                ]);
            });
        } catch (\Exception $e) {
            Log::error('獲取地點評分時間分布失敗', [
                'place_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '獲取地點評分時間分布失敗'], 500);
        }
    }

    /**
     * 獲取地點的評分摘要
     */
    public function getPlaceRatingSummary(int $id)
    {
        try {
            $cacheKey = "place.{$id}.rating_summary";

            return Cache::remember($cacheKey, 3600, function () use ($id) {
                return response()->json(Rating::getRatingSummary($id));
            });
        } catch (\Exception $e) {
            Log::error('獲取地點評分摘要失敗', [
                'place_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '獲取地點評分摘要失敗'], 500);
        }
    }

    /**
     * 獲取優化後的評分列表
     */
    public function getEnhancedPlaceRatings(Request $request, int $id)
    {
        try {
            $request->validate([
                'min_rating' => 'nullable|numeric|min:1|max:5',
                'max_rating' => 'nullable|numeric|min:1|max:5',
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:20',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'sort_by' => 'nullable|in:rating,created_at',
                'sort_order' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:50'
            ]);

            $filters = $request->only([
                'min_rating',
                'max_rating',
                'tags',
                'start_date',
                'end_date',
                'sort_by',
                'sort_order'
            ]);

            $perPage = $request->input('per_page', 10);
            $cacheKey = "place.{$id}.enhanced_ratings." . md5(json_encode($filters));

            return Cache::remember($cacheKey, 300, function () use ($id, $filters, $perPage) {
                $ratings = Rating::getPlaceRatings($id, $filters, $perPage);
                
                // 優化評分展示
                $ratings['ratings'] = collect($ratings['ratings'])->map(function ($rating) {
                    $ratingModel = Rating::find($rating['id']);
                    return array_merge($rating, [
                        'rating_level' => $ratingModel->getRatingLevel(),
                        'rating_level_text' => $ratingModel->getRatingLevelText(),
                        'rating_level_color' => $ratingModel->getRatingLevelColor(),
                        'rating_stars' => $ratingModel->getRatingStars(),
                        'formatted_date' => Carbon::parse($rating['created_at'])->diffForHumans()
                    ]);
                });

                return response()->json($ratings);
            });
        } catch (\Exception $e) {
            Log::error('獲取優化後的評分列表失敗', [
                'place_id' => $id,
                'filters' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '獲取優化後的評分列表失敗'], 500);
        }
    }
} 