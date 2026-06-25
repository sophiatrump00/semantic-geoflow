@extends('admin.layouts.app')

@section('content')
    @php
        $lanes = [
            [
                'label' => 'Shopify Sync',
                'title' => 'Products, variants, and inventory',
                'desc' => 'Prepare a connector lane for Shopify product feeds, variant attributes, inventory status, and collection metadata.',
                'icon' => 'shopping-bag',
                'tone' => 'bg-violet-50 text-violet-700 border-violet-100',
                'items' => ['Product catalog import', 'Variant option mapping', 'Inventory freshness checks'],
            ],
            [
                'label' => 'ERP SKU Knowledge',
                'title' => 'Specs, materials, origin, certificates, FAQ',
                'desc' => 'Turn ERP SKU facts into structured knowledge bases for sales, product, support, and multilingual content generation.',
                'icon' => 'boxes',
                'tone' => 'bg-sky-50 text-sky-700 border-sky-100',
                'items' => ['SKU fact cards', 'Compliance and certificate notes', 'Reusable FAQ knowledge'],
            ],
            [
                'label' => 'Multilingual Copy',
                'title' => 'EN / ZH / ES product content',
                'desc' => 'Generate product titles, descriptions, bullets, FAQ, and support answers from trusted commerce facts.',
                'icon' => 'languages',
                'tone' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                'items' => ['Localized product descriptions', 'Marketplace-ready bullet points', 'Support response drafts'],
            ],
            [
                'label' => 'Operations Analysis',
                'title' => 'Content gaps and support readiness',
                'desc' => 'Find products missing FAQ, weak descriptions, outdated support policies, or inventory-sensitive publishing risks.',
                'icon' => 'bar-chart-3',
                'tone' => 'bg-amber-50 text-amber-700 border-amber-100',
                'items' => ['Missing FAQ detection', 'Product content gap queue', 'Support policy coverage'],
            ],
        ];
    @endphp

    <div class="space-y-8">
        <section class="overflow-hidden rounded-[2rem] bg-slate-950 text-white shadow-xl shadow-slate-200">
            <div class="grid gap-8 p-8 lg:grid-cols-[1.05fr_0.95fr] lg:p-10">
                <div>
                    <p class="mb-4 inline-flex rounded-full bg-violet-400/15 px-4 py-2 text-sm font-semibold text-violet-100">
                        ERP / Shopify lane
                    </p>
                    <h1 class="text-4xl font-black tracking-tight sm:text-5xl">Commerce operations, separated from SEO.</h1>
                    <p class="mt-5 max-w-3xl text-base leading-8 text-slate-300">
                        This workspace is for product data, SKU knowledge, inventory-aware content, and after-sales knowledge. SEO/GEO publishing remains in the content lane; ERP data becomes a trusted source layer for commerce automation.
                    </p>
                    <div class="mt-7 flex flex-wrap gap-3">
                        <a href="{{ route('admin.knowledge-bases.index') }}" class="rounded-full bg-violet-300 px-5 py-3 text-sm font-semibold text-slate-950 hover:bg-violet-200">
                            Create SKU Knowledge Base
                        </a>
                        <a href="{{ route('admin.tasks.create') }}" class="rounded-full border border-white/15 px-5 py-3 text-sm font-semibold text-white hover:bg-white/10">
                            Generate Product Content
                        </a>
                    </div>
                </div>

                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6">
                    <div class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">Current integration stance</div>
                    <div class="mt-5 space-y-4">
                        <div class="rounded-2xl bg-white/10 p-4">
                            <div class="text-sm text-slate-400">Now</div>
                            <div class="mt-1 font-semibold">Independent ERP workspace and reusable knowledge workflow</div>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-4">
                            <div class="text-sm text-slate-400">Next</div>
                            <div class="mt-1 font-semibold">Shopify product / variant / inventory connector</div>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-4">
                            <div class="text-sm text-slate-400">Boundary</div>
                            <div class="mt-1 font-semibold">Commerce data feeds AI operations; it does not replace a full ERP.</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-5 lg:grid-cols-2">
            @foreach ($lanes as $lane)
                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border {{ $lane['tone'] }}">
                            <i data-lucide="{{ $lane['icon'] }}" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <div class="text-xs font-bold uppercase tracking-[0.22em] text-slate-400">{{ $lane['label'] }}</div>
                            <h2 class="mt-2 text-xl font-black text-slate-950">{{ $lane['title'] }}</h2>
                            <p class="mt-3 text-sm leading-6 text-slate-600">{{ $lane['desc'] }}</p>
                        </div>
                    </div>
                    <ul class="mt-5 grid gap-2 text-sm text-slate-700">
                        @foreach ($lane['items'] as $item)
                            <li class="flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-2">
                                <i data-lucide="check" class="h-4 w-4 text-emerald-500"></i>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </article>
            @endforeach
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-2xl font-black text-slate-950">Recommended demo flow</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        For interviews, present this as a connector-ready commerce lane: ERP/Shopify data enters the knowledge base, AI generates multilingual product/support content, and operations analysis highlights missing facts.
                    </p>
                </div>
                <a href="{{ route('admin.materials.index') }}" class="inline-flex rounded-full bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                    Open Source Data Layer
                </a>
            </div>
        </section>
    </div>
@endsection
