<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class SearchHistory extends Model
{
    /**
     * 可批量賦值的屬性
     */
    protected $fillable = [
        'query',
        'user_id',
        'ip_address',
        'search_params',
        'result_count'
    ];

    /**
     * 應該被轉換為原生類型的屬性
     */
    protected $casts = [
        'search_params' => 'array',
        'result_count' => 'integer'
    ];

    /**
     * 與用戶的關聯
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 獲取最近的搜索歷史
     */
    public static function getRecent(int $limit = 10, ?int $userId = null): array
    {
        $query = static::query()
            ->select(['query', 'created_at', 'result_count'])
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('ip_address', request()->ip());
        }

        return $query->get()
            ->map(function ($history) {
                return [
                    'query' => $history->query,
                    'created_at' => $history->created_at->format('Y-m-d H:i:s'),
                    'result_count' => $history->result_count
                ];
            })
            ->toArray();
    }

    /**
     * 記錄搜索歷史
     */
    public static function record(string $query, array $params = [], int $resultCount = 0): void
    {
        static::create([
            'query' => $query,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'search_params' => $params,
            'result_count' => $resultCount
        ]);
    }

    /**
     * 清除搜索歷史
     */
    public static function clear(?int $userId = null): void
    {
        $query = static::query();

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('ip_address', request()->ip());
        }

        $query->delete();
    }

    /**
     * 獲取熱門搜索
     */
    public static function getPopular(int $limit = 10, ?int $days = 7): array
    {
        $query = static::query()
            ->select([
                'query',
                DB::raw('COUNT(*) as search_count'),
                DB::raw('MAX(created_at) as last_searched')
            ])
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('query')
            ->orderBy('search_count', 'desc')
            ->orderBy('last_searched', 'desc')
            ->limit($limit);

        return $query->get()
            ->map(function ($history) {
                return [
                    'query' => $history->query,
                    'search_count' => $history->search_count,
                    'last_searched' => $history->last_searched->format('Y-m-d H:i:s')
                ];
            })
            ->toArray();
    }

    /**
     * 獲取相關搜索
     */
    public static function getRelated(string $query, int $limit = 5): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        return static::query()
            ->select([
                'query',
                DB::raw('COUNT(*) as search_count')
            ])
            ->where('query', 'like', "%{$query}%")
            ->where('query', '!=', $query)
            ->groupBy('query')
            ->orderBy('search_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($history) {
                return [
                    'query' => $history->query,
                    'search_count' => $history->search_count
                ];
            })
            ->toArray();
    }
} 