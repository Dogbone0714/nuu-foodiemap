<?php
// Place.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Place extends Model
{
    /**
     * 可批量賦值的屬性
     */
    protected $fillable = [
        'name',
        'description',
        'lat',
        'lng',
        'rating',
        'category_id'
    ];

    /**
     * 應該被轉換為原生類型的屬性
     */
    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'rating' => 'float',
        'category_id' => 'integer'
    ];

    /**
     * 與類別的關聯
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * 獲取格式化後的坐標
     */
    public function getCoordinatesAttribute(): array
    {
        return [
            'lat' => $this->lat,
            'lng' => $this->lng
        ];
    }

    /**
     * 獲取格式化後的評分
     */
    public function getFormattedRatingAttribute(): string
    {
        return number_format($this->rating, 1);
    }

    /**
     * 獲取完整的地點信息
     */
    public function getFullInfoAttribute(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'rating' => $this->rating,
            'formatted_rating' => $this->formatted_rating,
            'category_id' => $this->category_id,
            'category_name' => $this->category->name,
            'category_icon' => $this->category->icon,
            'coordinates' => $this->coordinates
        ];
    }

    /**
     * 計算與指定坐標的距離
     */
    public function calculateDistance(float $lat, float $lng): float
    {
        $earthRadius = 6371; // 地球半徑（公里）

        $latDiff = deg2rad($lat - $this->lat);
        $lngDiff = deg2rad($lng - $this->lng);

        $a = sin($latDiff/2) * sin($latDiff/2) +
            cos(deg2rad($this->lat)) * cos(deg2rad($lat)) *
            sin($lngDiff/2) * sin($lngDiff/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
    }

    /**
     * 獲取指定範圍內的地點
     */
    public static function getNearby(float $lat, float $lng, float $radius): array
    {
        return static::select([
            'id', 'name', 'description', 'lat', 'lng', 'rating', 'category_id',
            \DB::raw("(6371 * acos(cos(radians($lat)) * cos(radians(lat)) * cos(radians(lng) - radians($lng)) + sin(radians($lat)) * sin(radians(lat)))) AS distance")
        ])
        ->having('distance', '<=', $radius)
        ->orderBy('distance')
        ->get()
        ->map(function ($place) {
            return $place->full_info;
        })
        ->toArray();
    }

    /**
     * 搜索地點
     */
    public static function search(string $query): array
    {
        return static::with(['category:id,name,icon'])
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->get()
            ->map(function ($place) {
                return $place->full_info;
            })
            ->toArray();
    }
}
