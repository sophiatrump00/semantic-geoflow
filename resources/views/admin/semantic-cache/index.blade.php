@extends('admin.layouts.app')

@section('content')
    <div class="px-4 sm:px-0">
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center space-x-4">
                <a href="{{ route('admin.ai.configurator') }}" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.semantic_cache.page_title') }}</h1>
                    <p class="mt-1 text-sm text-gray-600">{{ __('admin.semantic_cache.page_subtitle') }}</p>
                </div>
            </div>
        </div>

        {{-- 统计卡片 --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i data-lucide="database" class="w-8 h-8 text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">{{ __('admin.semantic_cache.stats.total_entries') }}</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ number_format($statistics['total_entries']) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i data-lucide="zap" class="w-8 h-8 text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">{{ __('admin.semantic_cache.stats.total_hits') }}</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ number_format($statistics['total_hits']) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i data-lucide="trending-up" class="w-8 h-8 text-purple-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">{{ __('admin.semantic_cache.stats.hit_rate') }}</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ number_format($statistics['hit_rate'] * 100, 1) }}%</p>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i data-lucide="coins" class="w-8 h-8 text-yellow-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">{{ __('admin.semantic_cache.stats.tokens_saved') }}</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ number_format($statistics['total_tokens_saved']) }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- 配置信息 --}}
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i data-lucide="info" class="w-5 h-5 text-blue-600"></i>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm text-blue-800">
                        <span class="font-medium">{{ __('admin.semantic_cache.config_title') }}:</span>
                        {{ __('admin.semantic_cache.config_status', ['status' => $config['enabled'] ? __('admin.semantic_cache.enabled') : __('admin.semantic_cache.disabled')]) }} |
                        {{ __('admin.semantic_cache.config_threshold', ['threshold' => $config['similarity_threshold']]) }} |
                        {{ __('admin.semantic_cache.config_ttl', ['days' => $config['ttl_days']]) }} |
                        {{ __('admin.semantic_cache.config_max', ['max' => $config['max_entries'] > 0 ? number_format($config['max_entries']) : __('admin.semantic_cache.unlimited')]) }}
                    </p>
                </div>
            </div>
        </div>

        {{-- 操作按钮 --}}
        <div class="flex justify-between items-center mb-4">
            <form method="GET" action="{{ route('admin.semantic-cache.index') }}" class="flex items-center space-x-2">
                <label class="flex items-center">
                    <input type="checkbox" name="show_expired" value="1" {{ $showExpired ? 'checked' : '' }}
                           onchange="this.form.submit()"
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <span class="ml-2 text-sm text-gray-700">{{ __('admin.semantic_cache.show_expired') }}</span>
                </label>
            </form>

            <div class="flex space-x-2">
                <form method="POST" action="{{ route('admin.semantic-cache.prune-expired') }}" onsubmit="return confirm('{{ __('admin.semantic_cache.confirm_prune') }}')">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <i data-lucide="trash-2" class="w-4 h-4 mr-2"></i>
                        {{ __('admin.semantic_cache.prune_expired') }}
                    </button>
                </form>

                <button type="button" onclick="confirmTruncate()" class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50">
                    <i data-lucide="x-circle" class="w-4 h-4 mr-2"></i>
                    {{ __('admin.semantic_cache.clear_all') }}
                </button>
            </div>
        </div>

        {{-- __CONTINUE_HERE__ --}}

        {{-- 缓存列表 --}}
        <div class="bg-white shadow rounded-lg overflow-hidden">
            @if($caches->isEmpty())
                <div class="px-6 py-12 text-center">
                    <i data-lucide="inbox" class="w-12 h-12 mx-auto text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-1">{{ __('admin.semantic_cache.no_caches') }}</h3>
                    <p class="text-sm text-gray-500">{{ __('admin.semantic_cache.no_caches_desc') }}</p>
                </div>
            @else
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.semantic_cache.table.prompt') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.semantic_cache.table.model') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.semantic_cache.table.tokens') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.semantic_cache.table.hits') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.semantic_cache.table.saved') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.semantic_cache.table.expires') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.semantic_cache.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($caches as $cache)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 font-mono truncate max-w-md" title="{{ $cache->prompt_text }}">
                                        {{ Str::limit($cache->prompt_text, 80) }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ __('admin.semantic_cache.dimensions', ['dims' => $cache->embedding_dimensions]) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ $cache->aiModel->name ?? __('admin.semantic_cache.unknown_model') }}</div>
                                    <div class="text-xs text-gray-500">{{ $cache->model_identifier }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div>{{ number_format($cache->prompt_token_count + $cache->response_token_count) }}</div>
                                    <div class="text-xs text-gray-500">
                                        ({{ number_format($cache->prompt_token_count) }}+{{ number_format($cache->response_token_count) }})
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $cache->hit_count > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ number_format($cache->hit_count) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ number_format($cache->tokens_saved) }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if($cache->isExpired())
                                        <span class="text-red-600">{{ __('admin.semantic_cache.expired') }}</span>
                                    @elseif($cache->expires_at)
                                        <span class="text-gray-500" title="{{ $cache->expires_at->toDateTimeString() }}">
                                            {{ $cache->expires_at->diffForHumans() }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">{{ __('admin.semantic_cache.never') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium space-x-2">
                                    <a href="{{ route('admin.semantic-cache.show', $cache->id) }}" class="text-blue-600 hover:text-blue-900">
                                        {{ __('admin.semantic_cache.view') }}
                                    </a>
                                    <form method="POST" action="{{ route('admin.semantic-cache.delete', $cache->id) }}" class="inline"
                                          onsubmit="return confirm('{{ __('admin.semantic_cache.confirm_delete') }}')">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                            {{ __('admin.semantic_cache.delete') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $caches->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- 清空确认模态框 --}}
    <form id="truncateForm" method="POST" action="{{ route('admin.semantic-cache.truncate') }}">
        @csrf
        <input type="hidden" name="confirmed" value="1">
    </form>

    <script>
        function confirmTruncate() {
            if (confirm('{{ __('admin.semantic_cache.confirm_truncate') }}')) {
                document.getElementById('truncateForm').submit();
            }
        }
    </script>
@endsection

