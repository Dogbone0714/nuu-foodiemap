<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    private const CACHE_TTL = 3600; // 1小時
    private const CACHE_KEY_CATEGORIES = 'categories.all';

    /**
     * 獲取所有類別
     */
    public function index()
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
     * 創建新類別
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories',
                'icon' => 'nullable|string|max:255'
            ]);

            $category = Category::create($validated);
            Cache::forget(self::CACHE_KEY_CATEGORIES);

            return response()->json([
                'message' => '類別創建成功',
                'category' => $category
            ], 201);
        } catch (\Exception $e) {
            Log::error('創建類別失敗', [
                'data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '創建類別失敗'], 500);
        }
    }

    /**
     * 更新類別
     */
    public function update(Request $request, Category $category)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
                'icon' => 'nullable|string|max:255'
            ]);

            $category->update($validated);
            Cache::forget(self::CACHE_KEY_CATEGORIES);

            return response()->json([
                'message' => '類別更新成功',
                'category' => $category
            ]);
        } catch (\Exception $e) {
            Log::error('更新類別失敗', [
                'category_id' => $category->id,
                'data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '更新類別失敗'], 500);
        }
    }

    /**
     * 刪除類別
     */
    public function destroy(Category $category)
    {
        try {
            $category->delete();
            Cache::forget(self::CACHE_KEY_CATEGORIES);

            return response()->json(['message' => '類別刪除成功']);
        } catch (\Exception $e) {
            Log::error('刪除類別失敗', [
                'category_id' => $category->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '刪除類別失敗'], 500);
        }
    }

    /**
     * 清除緩存
     */
    public function clearCache()
    {
        try {
            Cache::forget(self::CACHE_KEY_CATEGORIES);
            return response()->json(['message' => '緩存清除成功']);
        } catch (\Exception $e) {
            Log::error('清除緩存失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => '清除緩存失敗'], 500);
        }
    }
} 