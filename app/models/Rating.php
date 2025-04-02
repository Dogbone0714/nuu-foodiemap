<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Rating extends Model
{
    /**
     * 可批量賦值的屬性
     */
    protected $fillable = [
        'place_id',
        'user_id',
        'ip_address',
        'rating',
        'comment',
        'tags',
        'is_verified'
    ];

    /**
     * 應該被轉換為原生類型的屬性
     */
    protected $casts = [
        'rating' => 'float',
        'tags' => 'array',
        'is_verified' => 'boolean'
    ];

    /**
     * 與地點的關聯
     */
    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    /**
     * 與用戶的關聯
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 獲取地點的評分統計
     */
    public static function getPlaceStats(int $placeId): array
    {
        return static::query()
            ->where('place_id', $placeId)
            ->where('is_verified', true)
            ->select([
                DB::raw('COUNT(*) as total_ratings'),
                DB::raw('AVG(rating) as average_rating'),
                DB::raw('COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_ratings'),
                DB::raw('COUNT(CASE WHEN rating <= 2 THEN 1 END) as negative_ratings')
            ])
            ->first()
            ->toArray();
    }

    /**
     * 獲取地點的標籤統計
     */
    public static function getPlaceTags(int $placeId): array
    {
        return static::query()
            ->where('place_id', $placeId)
            ->where('is_verified', true)
            ->whereNotNull('tags')
            ->select('tags')
            ->get()
            ->pluck('tags')
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(10)
            ->toArray();
    }

    /**
     * 記錄評分
     */
    public static function record(array $data): Rating
    {
        $rating = static::create([
            'place_id' => $data['place_id'],
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'tags' => $data['tags'] ?? null,
            'is_verified' => false
        ]);

        // 更新地點的平均評分
        $stats = static::getPlaceStats($data['place_id']);
        $rating->place->update([
            'rating' => $stats['average_rating']
        ]);

        return $rating;
    }

    /**
     * 驗證評分
     */
    public function verify(): bool
    {
        $this->is_verified = true;
        $this->save();

        // 更新地點的平均評分
        $stats = static::getPlaceStats($this->place_id);
        $this->place->update([
            'rating' => $stats['average_rating']
        ]);

        return true;
    }

    /**
     * 獲取用戶的評分歷史
     */
    public static function getUserRatings(int $userId, int $limit = 10): array
    {
        return static::with(['place:id,name,description'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($rating) {
                return [
                    'id' => $rating->id,
                    'place_id' => $rating->place_id,
                    'place_name' => $rating->place->name,
                    'rating' => $rating->rating,
                    'comment' => $rating->comment,
                    'tags' => $rating->tags,
                    'created_at' => $rating->created_at->format('Y-m-d H:i:s'),
                    'is_verified' => $rating->is_verified
                ];
            })
            ->toArray();
    }

    /**
     * 獲取地點的評分列表（帶篩選）
     */
    public static function getPlaceRatings(int $placeId, array $filters = [], int $perPage = 10): array
    {
        $query = static::with(['user:id,name'])
            ->where('place_id', $placeId)
            ->where('is_verified', true);

        // 評分範圍篩選
        if (isset($filters['min_rating'])) {
            $query->where('rating', '>=', $filters['min_rating']);
        }
        if (isset($filters['max_rating'])) {
            $query->where('rating', '<=', $filters['max_rating']);
        }

        // 標籤篩選
        if (!empty($filters['tags'])) {
            $tags = (array) $filters['tags'];
            foreach ($tags as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        // 時間範圍篩選
        if (isset($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        // 排序
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // 分頁
        $ratings = $query->paginate($perPage);

        return [
            'ratings' => $ratings->map(function ($rating) {
                return [
                    'id' => $rating->id,
                    'user_id' => $rating->user_id,
                    'user_name' => $rating->user->name,
                    'rating' => $rating->rating,
                    'comment' => $rating->comment,
                    'tags' => $rating->tags,
                    'created_at' => $rating->created_at->format('Y-m-d H:i:s')
                ];
            }),
            'pagination' => [
                'total' => $ratings->total(),
                'per_page' => $ratings->perPage(),
                'current_page' => $ratings->currentPage(),
                'last_page' => $ratings->lastPage()
            ]
        ];
    }

    /**
     * 獲取評分標籤統計
     */
    public static function getRatingTags(int $placeId): array
    {
        return static::query()
            ->where('place_id', $placeId)
            ->where('is_verified', true)
            ->whereNotNull('tags')
            ->select('tags')
            ->get()
            ->pluck('tags')
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(20)
            ->toArray();
    }

    /**
     * 獲取評分時間分布
     */
    public static function getRatingTimeDistribution(int $placeId): array
    {
        return static::query()
            ->where('place_id', $placeId)
            ->where('is_verified', true)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(rating) as average_rating')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get()
            ->toArray();
    }

    /**
     * 獲取評分等級
     */
    public function getRatingLevel(): string
    {
        return match(true) {
            $this->rating >= 4.5 => 'excellent',
            $this->rating >= 4.0 => 'very_good',
            $this->rating >= 3.5 => 'good',
            $this->rating >= 3.0 => 'fair',
            $this->rating >= 2.0 => 'poor',
            default => 'very_poor'
        };
    }

    /**
     * 獲取評分等級文字
     */
    public function getRatingLevelText(): string
    {
        return match($this->getRatingLevel()) {
            'excellent' => '極好',
            'very_good' => '很好',
            'good' => '好',
            'fair' => '一般',
            'poor' => '差',
            'very_poor' => '很差',
            default => '未知'
        };
    }

    /**
     * 獲取評分等級顏色
     */
    public function getRatingLevelColor(): string
    {
        return match($this->getRatingLevel()) {
            'excellent' => '#4CAF50',
            'very_good' => '#8BC34A',
            'good' => '#CDDC39',
            'fair' => '#FFC107',
            'poor' => '#FF9800',
            'very_poor' => '#F44336',
            default => '#9E9E9E'
        };
    }

    /**
     * 獲取評分星星數量
     */
    public function getRatingStars(): array
    {
        $stars = [];
        $rating = round($this->rating * 2) / 2; // 四捨五入到0.5
        $fullStars = floor($rating);
        $hasHalfStar = $rating - $fullStars >= 0.5;

        for ($i = 0; $i < 5; $i++) {
            if ($i < $fullStars) {
                $stars[] = 'full';
            } elseif ($i == $fullStars && $hasHalfStar) {
                $stars[] = 'half';
            } else {
                $stars[] = 'empty';
            }
        }

        return $stars;
    }

    /**
     * 獲取評分統計摘要
     */
    public static function getRatingSummary(int $placeId): array
    {
        $stats = static::getPlaceStats($placeId);
        $tags = static::getPlaceTags($placeId);
        $timeDistribution = static::getRatingTimeDistribution($placeId);

        return [
            'stats' => [
                'total_ratings' => $stats['total_ratings'],
                'average_rating' => round($stats['average_rating'], 1),
                'rating_level' => match(true) {
                    $stats['average_rating'] >= 4.5 => 'excellent',
                    $stats['average_rating'] >= 4.0 => 'very_good',
                    $stats['average_rating'] >= 3.5 => 'good',
                    $stats['average_rating'] >= 3.0 => 'fair',
                    $stats['average_rating'] >= 2.0 => 'poor',
                    default => 'very_poor'
                },
                'rating_level_text' => match(true) {
                    $stats['average_rating'] >= 4.5 => '極好',
                    $stats['average_rating'] >= 4.0 => '很好',
                    $stats['average_rating'] >= 3.5 => '好',
                    $stats['average_rating'] >= 3.0 => '一般',
                    $stats['average_rating'] >= 2.0 => '差',
                    default => '很差'
                },
                'positive_ratio' => round($stats['positive_ratings'] / $stats['total_ratings'] * 100, 1),
                'negative_ratio' => round($stats['negative_ratings'] / $stats['total_ratings'] * 100, 1)
            ],
            'tags' => $tags,
            'time_distribution' => $timeDistribution
        ];
    }
} 