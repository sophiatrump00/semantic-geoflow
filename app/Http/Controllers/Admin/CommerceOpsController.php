<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommerceContentDraft;
use App\Models\CommerceProduct;
use App\Services\Commerce\CommerceConnectorService;
use App\Support\AdminWeb;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\View\View;

class CommerceOpsController extends Controller
{
    public function __construct(private readonly CommerceConnectorService $commerceConnector) {}

    public function index(): View
    {
        return view('admin.commerce-ops.index', [
            'adminSiteName' => AdminWeb::siteName(),
            'pageTitle' => __('admin.commerce_ops.page_title'),
            'activeMenu' => 'commerce_ops',
            'stats' => $this->commerceConnector->stats(),
            'products' => CommerceProduct::query()
                ->with(['variants', 'knowledgeBase'])
                ->withCount('contentDrafts')
                ->latest()
                ->limit(20)
                ->get(),
            'latestDrafts' => CommerceContentDraft::query()
                ->with('product')
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }

    public function sampleCsv(): Response
    {
        return response($this->commerceConnector->sampleCsv(), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="semanticflow-commerce-sample.csv"',
        ]);
    }

    public function importCsv(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'commerce_csv' => ['nullable', 'string'],
            'commerce_file' => ['nullable', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $csv = trim((string) ($payload['commerce_csv'] ?? ''));
        if ($csv === '' && $request->hasFile('commerce_file')) {
            $csv = (string) file_get_contents($request->file('commerce_file')->getRealPath());
        }

        if (trim($csv) === '') {
            return back()->withErrors(['commerce_csv' => 'Paste CSV data or upload a CSV file.']);
        }

        $result = $this->commerceConnector->importCsv($csv);
        $message = "Commerce import complete: {$result['imported']} imported, {$result['updated']} updated, {$result['skipped']} skipped.";

        if ($result['errors'] !== []) {
            return redirect()
                ->route('admin.commerce-ops.index')
                ->with('message', $message)
                ->withErrors(['commerce_import' => implode(' ', array_slice($result['errors'], 0, 3))]);
        }

        return redirect()->route('admin.commerce-ops.index')->with('message', $message);
    }

    public function createKnowledge(int $productId): RedirectResponse
    {
        $product = CommerceProduct::query()->with('variants')->findOrFail($productId);
        $knowledgeBase = $this->commerceConnector->createKnowledgeBase($product);

        return redirect()
            ->route('admin.commerce-ops.index')
            ->with('message', 'SKU knowledge base created: '.$knowledgeBase->name);
    }

    public function generateDraft(Request $request, int $productId): RedirectResponse
    {
        $payload = $request->validate([
            'language' => ['nullable', 'in:en,zh,es'],
        ]);

        $product = CommerceProduct::query()->with('variants')->findOrFail($productId);
        $draft = $this->commerceConnector->generateContentDraft($product, (string) ($payload['language'] ?? 'en'));

        return redirect()
            ->route('admin.commerce-ops.index')
            ->with('message', 'Product content draft generated: '.$draft->title);
    }
}
