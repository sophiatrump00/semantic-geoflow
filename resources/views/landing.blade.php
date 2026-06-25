<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SemanticFlow — AI Operations Gateway</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .mesh {
            background:
                radial-gradient(circle at top left, rgba(56, 189, 248, .26), transparent 32rem),
                radial-gradient(circle at bottom right, rgba(168, 85, 247, .24), transparent 34rem),
                linear-gradient(135deg, #020617 0%, #0f172a 45%, #111827 100%);
        }
    </style>
</head>
<body class="min-h-screen bg-slate-950 text-white">
    <main class="mesh min-h-screen">
        <div class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-8 lg:px-8">
            <header class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-400 text-xl font-black text-slate-950 shadow-lg shadow-sky-400/30">S</div>
                    <div>
                        <div class="text-lg font-semibold tracking-tight">SemanticFlow</div>
                        <div class="text-xs uppercase tracking-[0.28em] text-slate-400">AI Operations Gateway</div>
                    </div>
                </div>
                <a href="{{ route('admin.entry') }}" class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/15">
                    Open Admin
                </a>
            </header>

            <section class="grid flex-1 items-center gap-10 py-16 lg:grid-cols-[0.9fr_1.1fr]">
                <div>
                    <p class="mb-5 inline-flex rounded-full border border-sky-300/30 bg-sky-300/10 px-4 py-2 text-sm text-sky-100">
                        Built for cross-border content, product knowledge, and operations automation.
                    </p>
                    <h1 class="max-w-3xl text-5xl font-black leading-tight tracking-tight sm:text-6xl">
                        Split AI work into two clear lanes.
                    </h1>
                    <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">
                        SemanticFlow separates public content operations from commerce operations. Use the left lane for SEO/GEO publishing workflows, and the right lane for ERP, Shopify, SKU knowledge, support, and inventory-aware content planning.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('admin.login') }}" class="rounded-full bg-sky-400 px-5 py-3 text-sm font-semibold text-slate-950 shadow-lg shadow-sky-400/30 transition hover:bg-sky-300">
                            Login to Console
                        </a>
                        <a href="https://github.com/sophiatrump00/semantic-geoflow" target="_blank" rel="noopener noreferrer" class="rounded-full border border-white/15 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                            View Repository
                        </a>
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <section class="rounded-[2rem] border border-white/10 bg-white/[0.07] p-6 shadow-2xl shadow-black/30 backdrop-blur">
                        <div class="mb-5 inline-flex rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-emerald-200">Left Lane</div>
                        <h2 class="text-2xl font-black">SEO / GEO Content</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-300">
                            Plan, generate, review, publish, and distribute knowledge-backed content for AI search visibility and multi-site content operations.
                        </p>
                        <ul class="mt-6 space-y-3 text-sm text-slate-200">
                            <li>• Knowledge base + RAG content drafting</li>
                            <li>• Title, keyword, author, and image libraries</li>
                            <li>• Task queues, review flow, and publishing scope</li>
                            <li>• WordPress / HTTP API / site package distribution</li>
                        </ul>
                        <a href="{{ route('admin.dashboard') }}" class="mt-7 inline-flex rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-slate-200">
                            Enter Content Console
                        </a>
                    </section>

                    <section class="rounded-[2rem] border border-white/10 bg-white/[0.07] p-6 shadow-2xl shadow-black/30 backdrop-blur">
                        <div class="mb-5 inline-flex rounded-full bg-violet-400/15 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-violet-200">Right Lane</div>
                        <h2 class="text-2xl font-black">ERP / Shopify Ops</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-300">
                            A separate commerce operations lane for product data, SKU facts, support policies, and inventory-aware AI workflows.
                        </p>
                        <ul class="mt-6 space-y-3 text-sm text-slate-200">
                            <li>• Shopify product / variant / inventory sync plan</li>
                            <li>• ERP SKU knowledge base from specs and FAQ</li>
                            <li>• Multilingual product copy: EN / ZH / ES</li>
                            <li>• Support knowledge and content gap analysis</li>
                        </ul>
                        <a href="{{ route('admin.knowledge-bases.index') }}" class="mt-7 inline-flex rounded-full bg-violet-300 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-violet-200">
                            Build Commerce Knowledge
                        </a>
                    </section>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
