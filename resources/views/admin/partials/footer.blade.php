@php
    $projectGithubUrl = 'https://github.com/sophiatrump00/semantic-geoflow';
    $appVersion = (string) config('geoflow.app_version', '2.0');
@endphp
<footer class="mt-12 border-t border-slate-200 bg-slate-950 text-slate-300">
    <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-6 text-sm sm:px-6 lg:px-8 md:flex-row md:items-center md:justify-between">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <span class="font-semibold text-white">SemanticFlow</span>
            <span class="text-slate-500">© 2026</span>
            <span class="text-slate-600">/</span>
            <span>{{ __('admin.footer.version', ['version' => $appVersion]) }}</span>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ $projectGithubUrl }}" target="_blank" rel="noopener noreferrer" class="text-sky-300 hover:text-white">
                GitHub
            </a>
            <button type="button" data-open-admin-welcome class="text-sky-300 hover:text-white">
                Overview
            </button>
        </div>
    </div>
</footer>
<script>
    window.ADMIN_BASE_PATH = @json('/'.\App\Support\AdminWeb::basePath());
    window.adminUrl = function (path) {
        const base = window.ADMIN_BASE_PATH || '';
        if (!path) return base + '/';
        return base + '/' + String(path).replace(/^\/+/, '');
    };
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
