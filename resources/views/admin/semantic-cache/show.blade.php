@extends('admin.layouts.app')

@section('content')
    <div class="px-4 sm:px-0">
        <div class="flex items-center space-x-4 mb-8">
            <a href="{{ route('admin.semantic-cache.index') }}" class="text-gray-400 hover:text-gray-600">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.semantic_cache.detail_title') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ __('admin.semantic_cache.detail_subtitle', ['id' => $cache->id]) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <div class="bg-white shadow rounded-lg p-6">
                <p class="text-sm font-medium text-gray-500">{{ __('admin.semantic_cache.table.hits') }}</p>
                <p class="text-3xl font-semibold text-gray-900 mt-2">{{ number_format($cache->hit_count) }}</p>
                @if($cache->last_hit_at)
                    <p class="text-xs text-gray-500 mt-1">{{ __('admin.semantic_cache.last_hit', ['time' => $cache->last_hit_at->diffForHumans()]) }}</p>
                @endif
            </div>
            <div class="bg-white shadow rounded-lg p-6">
                <p class="text-sm font-medium text-gray-500">{{ __('admin.semantic_cache.table.saved') }}</p>
                <p class="text-3xl font-semibold text-gray-900 mt-2">{{ number_format($cache->tokens_saved) }}</p>
                <p class="text-xs text-gray-500 mt-1">tokens</p>
            </div>
            <div class="bg-white shadow rounded-lg p-6">
                <p class="text-sm font-medium text-gray-500">{{ __('admin.semantic_cache.table.model') }}</p>
                <p class="text-lg font-semibold text-gray-900 mt-2">{{ $cache->aiModel->name ?? __('admin.semantic_cache.unknown_model') }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $cache->model_identifier }}</p>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">{{ __('admin.semantic_cache.prompt_content') }}</h3>
            </div>
            <div class="px-6 py-4">
                <pre class="text-sm text-gray-700 whitespace-pre-wrap font-mono bg-gray-50 p-4 rounded-md overflow-x-auto">{{ $cache->prompt_text }}</pre>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">{{ __('admin.semantic_cache.response_content') }}</h3>
            </div>
            <div class="px-6 py-4">
                <pre class="text-sm text-gray-700 whitespace-pre-wrap font-mono bg-gray-50 p-4 rounded-md overflow-x-auto max-h-96">{{ $cache->response_content }}</pre>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">{{ __('admin.semantic_cache.metadata') }}</h3>
            </div>
            <dl class="divide-y divide-gray-200">
                <div class="px-6 py-3 grid grid-cols-3 gap-4">
                    <dt class="text-sm font-medium text-gray-500">{{ __('admin.semantic_cache.dimensions_label') }}</dt>
                    <dd class="text-sm text-gray-900 col-span-2">{{ $cache->embedding_dimensions }}</dd>
                </div>
                <div class="px-6 py-3 grid grid-cols-3 gap-4">
                    <dt class="text-sm font-medium text-gray-500">{{ __('admin.semantic_cache.prompt_tokens') }}</dt>
                    <dd class="text-sm text-gray-900 col-span-2">{{ number_format($cache->prompt_token_count) }}</dd>
                </div>
                <div class="px-6 py-3 grid grid-cols-3 gap-4">
                    <dt class="text-sm font-medium text-gray-500">{{ __('admin.semantic_cache.response_tokens') }}</dt>
                    <dd class="text-sm text-gray-900 col-span-2">{{ number_format($cache->response_token_count) }}</dd>
                </div>
                <div class="px-6 py-3 grid grid-cols-3 gap-4">
                    <dt class="text-sm font-medium text-gray-500">{{ __('admin.semantic_cache.created_at') }}</dt>
                    <dd class="text-sm text-gray-900 col-span-2">{{ $cache->created_at?->toDateTimeString() }}</dd>
                </div>
                <div class="px-6 py-3 grid grid-cols-3 gap-4">
                    <dt class="text-sm font-medium text-gray-500">{{ __('admin.semantic_cache.expires_at_label') }}</dt>
                    <dd class="text-sm text-gray-900 col-span-2">
                        {{ $cache->expires_at?->toDateTimeString() ?? __('admin.semantic_cache.never') }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>
@endsection
