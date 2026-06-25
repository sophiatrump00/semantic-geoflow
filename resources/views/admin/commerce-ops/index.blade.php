@extends('admin.layouts.app')

@section('content')
    @php
        $stats = $stats ?? [];
        $products = $products ?? collect();
        $latestDrafts = $latestDrafts ?? collect();
        $statCards = [
            ['label' => 'Products', 'value' => (int) ($stats['products'] ?? 0), 'tone' => 'bg-violet-50 text-violet-700'],
            ['label' => 'Variants', 'value' => (int) ($stats['variants'] ?? 0), 'tone' => 'bg-sky-50 text-sky-700'],
            ['label' => 'SKU knowledge', 'value' => (int) ($stats['with_knowledge'] ?? 0), 'tone' => 'bg-emerald-50 text-emerald-700'],
            ['label' => 'Content drafts', 'value' => (int) ($stats['with_drafts'] ?? 0), 'tone' => 'bg-indigo-50 text-indigo-700'],
            ['label' => 'Missing FAQ', 'value' => (int) ($stats['missing_faq'] ?? 0), 'tone' => 'bg-amber-50 text-amber-700'],
            ['label' => 'Low inventory', 'value' => (int) ($stats['low_inventory'] ?? 0), 'tone' => 'bg-red-50 text-red-700'],
        ];
    @endphp

    <div class="space-y-8">
        <section class="overflow-hidden rounded-[2rem] bg-slate-950 text-white shadow-xl shadow-slate-200">
            <div class="grid gap-8 p-8 lg:grid-cols-[1.05fr_0.95fr] lg:p-10">
                <div>
                    <p class="mb-4 inline-flex rounded-full bg-violet-400/15 px-4 py-2 text-sm font-semibold text-violet-100">
                        ERP / Shopify connector layer
                    </p>
                    <h1 class="text-4xl font-black tracking-tight sm:text-5xl">Commerce data into AI-ready SKU knowledge.</h1>
                    <p class="mt-5 max-w-3xl text-base leading-8 text-slate-300">
                        Import SKU/product facts, convert them into knowledge bases, generate multilingual product drafts, and inspect operational gaps such as missing FAQ, weak descriptions, or low inventory.
                    </p>
                    <div class="mt-7 flex flex-wrap gap-3">
                        <a href="{{ route('admin.commerce-ops.sample-csv') }}" class="rounded-full bg-violet-300 px-5 py-3 text-sm font-semibold text-slate-950 hover:bg-violet-200">
                            Download CSV Template
                        </a>
                        <a href="#commerce-products" class="rounded-full border border-white/15 px-5 py-3 text-sm font-semibold text-white hover:bg-white/10">
                            View Imported SKUs
                        </a>
                    </div>
                </div>

                <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.06] p-6">
                    <div class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">MVP capability</div>
                    <div class="mt-5 space-y-4">
                        <div class="rounded-2xl bg-white/10 p-4">
                            <div class="text-sm text-slate-400">Input</div>
                            <div class="mt-1 font-semibold">CSV product, variant, inventory and support data</div>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-4">
                            <div class="text-sm text-slate-400">Transformation</div>
                            <div class="mt-1 font-semibold">SKU facts → structured Markdown knowledge base + chunks</div>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-4">
                            <div class="text-sm text-slate-400">Output</div>
                            <div class="mt-1 font-semibold">EN / ZH / ES title, description, bullets, and FAQ drafts</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
            @foreach ($statCards as $card)
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-sm font-medium text-slate-500">{{ $card['label'] }}</div>
                    <div class="mt-3 inline-flex rounded-2xl px-4 py-2 text-3xl font-black {{ $card['tone'] }}">
                        {{ $card['value'] }}
                    </div>
                </div>
            @endforeach
        </section>

        <section class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-black text-slate-950">Import commerce CSV</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Paste product/SKU rows or upload a CSV. Required fields: <code>sku</code> and <code>title</code>.
                        </p>
                    </div>
                    <i data-lucide="upload-cloud" class="h-6 w-6 text-violet-500"></i>
                </div>

                <form method="POST" action="{{ route('admin.commerce-ops.import-csv') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">CSV file</label>
                        <input type="file" name="commerce_file" accept=".csv,.txt" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Or paste CSV</label>
                        <textarea name="commerce_csv" rows="9" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-mono text-xs" placeholder="sku,title,description,vendor,product_type,material,origin_country,certifications,faq,support_policy,price,currency,inventory_quantity">{{ old('commerce_csv') }}</textarea>
                    </div>
                    <button type="submit" class="inline-flex rounded-full bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                        Import products
                    </button>
                </form>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-black text-slate-950">Latest content drafts</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Drafts are generated from imported SKU facts. They are deliberately conservative and avoid unsupported claims.
                </p>
                <div class="mt-5 space-y-4">
                    @forelse ($latestDrafts as $draft)
                        <article class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                                <span>{{ strtoupper($draft->language) }}</span>
                                <span>·</span>
                                <span>{{ $draft->product?->sku }}</span>
                            </div>
                            <h3 class="mt-2 font-black text-slate-950">{{ $draft->title }}</h3>
                            <p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-600">{{ $draft->description }}</p>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 p-6 text-sm text-slate-500">
                            No product content drafts yet. Import products, then click “Generate draft”.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section id="commerce-products" class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-6">
                <h2 class="text-2xl font-black text-slate-950">Imported SKUs</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Each row can become a real knowledge base and produce marketplace-ready content drafts.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-500">
                        <tr>
                            <th class="px-6 py-4">SKU</th>
                            <th class="px-6 py-4">Product</th>
                            <th class="px-6 py-4">Inventory</th>
                            <th class="px-6 py-4">Gaps</th>
                            <th class="px-6 py-4">Knowledge</th>
                            <th class="px-6 py-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($products as $product)
                            @php
                                $inventory = (int) $product->variants->sum('inventory_quantity');
                                $gaps = [];
                                if (trim((string) $product->description) === '') $gaps[] = 'description';
                                if (trim((string) $product->faq) === '') $gaps[] = 'FAQ';
                                if ($inventory <= 5) $gaps[] = 'low inventory';
                            @endphp
                            <tr class="align-top">
                                <td class="px-6 py-4 font-mono font-semibold text-slate-900">{{ $product->sku }}</td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-950">{{ $product->title }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $product->vendor ?: 'No vendor' }} · {{ $product->product_type ?: 'No type' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $inventory <= 5 ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }}">
                                        {{ $inventory }} units
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($gaps === [])
                                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">ready</span>
                                    @else
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($gaps as $gap)
                                                <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">{{ $gap }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if ($product->knowledgeBase)
                                        <a href="{{ route('admin.knowledge-bases.detail', ['knowledgeBaseId' => $product->knowledgeBase->id]) }}" class="font-semibold text-sky-600 hover:text-sky-700">
                                            #{{ $product->knowledgeBase->id }}
                                        </a>
                                    @else
                                        <span class="text-slate-400">Not created</span>
                                    @endif
                                    <div class="mt-1 text-xs text-slate-500">{{ $product->content_drafts_count }} drafts</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <form method="POST" action="{{ route('admin.commerce-ops.products.knowledge', ['productId' => $product->id]) }}">
                                            @csrf
                                            <button type="submit" class="rounded-full bg-sky-50 px-3 py-2 text-xs font-semibold text-sky-700 hover:bg-sky-100">
                                                Create knowledge
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.commerce-ops.products.draft', ['productId' => $product->id]) }}" class="flex gap-1">
                                            @csrf
                                            <select name="language" class="rounded-full border border-slate-200 px-2 text-xs">
                                                <option value="en">EN</option>
                                                <option value="zh">ZH</option>
                                                <option value="es">ES</option>
                                            </select>
                                            <button type="submit" class="rounded-full bg-violet-50 px-3 py-2 text-xs font-semibold text-violet-700 hover:bg-violet-100">
                                                Generate draft
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-slate-500">
                                    No commerce products yet. Download the CSV template, import two rows, then generate SKU knowledge and drafts.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
