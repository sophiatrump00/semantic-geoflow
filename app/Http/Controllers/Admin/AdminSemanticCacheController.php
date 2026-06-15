<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiSemanticCache;
use App\Services\GeoFlow\SemanticCacheService;
use App\Support\AdminWeb;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AI 语义缓存管理控制器
 *
 * 提供后台语义缓存的查看、统计和管理功能。
 */
class AdminSemanticCacheController extends Controller
{
    public function __construct(
        private readonly SemanticCacheService $semanticCacheService
    ) {}

    /**
     * 语义缓存列表页
     */
    public function index(Request $request): View
    {
        $perPage = 20;
        $query = AiSemanticCache::query()
            ->with('aiModel:id,name,model_id')
            ->orderByDesc('hit_count')
            ->orderByDesc('created_at');

        // 筛选：按模型
        if ($request->filled('model_id')) {
            $query->where('ai_model_id', (int) $request->input('model_id'));
        }

        // 筛选：是否过期
        $showExpired = $request->boolean('show_expired', false);
        if (!$showExpired) {
            $query->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
        }

        $caches = $query->paginate($perPage);
        $statistics = $this->semanticCacheService->getStatistics();

        return view('admin.semantic-cache.index', [
            'pageTitle' => __('admin.semantic_cache.page_title'),
            'activeMenu' => 'ai_config',
            'adminSiteName' => AdminWeb::siteName(),
            'caches' => $caches,
            'statistics' => $statistics,
            'showExpired' => $showExpired,
            'config' => [
                'enabled' => config('geoflow.semantic_cache_enabled', true),
                'similarity_threshold' => config('geoflow.semantic_cache_similarity_threshold', 0.92),
                'ttl_days' => round(config('geoflow.semantic_cache_ttl_seconds', 604800) / 86400, 1),
                'max_entries' => config('geoflow.semantic_cache_max_entries', 10000),
            ],
        ]);
    }

    /**
     * 查看单条缓存详情
     */
    public function show(int $id): View
    {
        $cache = AiSemanticCache::with('aiModel:id,name,model_id')->findOrFail($id);

        return view('admin.semantic-cache.show', [
            'pageTitle' => __('admin.semantic_cache.detail_title'),
            'activeMenu' => 'ai_config',
            'adminSiteName' => AdminWeb::siteName(),
            'cache' => $cache,
        ]);
    }

    /**
     * 删除单条缓存
     */
    public function destroy(int $id): RedirectResponse
    {
        $cache = AiSemanticCache::findOrFail($id);
        $cache->delete();

        return back()->with('success', __('admin.semantic_cache.messages.delete_success'));
    }

    /**
     * 批量删除过期缓存
     */
    public function pruneExpired(): RedirectResponse
    {
        $deleted = $this->semanticCacheService->pruneExpired();

        return back()->with('success', __('admin.semantic_cache.messages.prune_success', ['count' => $deleted]));
    }

    /**
     * 清空所有缓存
     */
    public function truncate(Request $request): RedirectResponse
    {
        // 需要用户确认
        if (!$request->boolean('confirmed')) {
            return back()->withErrors(__('admin.semantic_cache.error.confirmation_required'));
        }

        $count = AiSemanticCache::query()->count();
        AiSemanticCache::query()->truncate();

        return redirect()->route('admin.semantic-cache.index')
            ->with('success', __('admin.semantic_cache.messages.truncate_success', ['count' => $count]));
    }

    /**
     * 获取缓存统计数据（AJAX）
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->semanticCacheService->getStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
