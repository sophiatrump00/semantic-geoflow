<?php

namespace App\Services\Commerce;

use App\Models\CommerceContentDraft;
use App\Models\CommerceInventorySnapshot;
use App\Models\CommerceProduct;
use App\Models\CommerceVariant;
use App\Models\KnowledgeBase;
use App\Services\GeoFlow\KnowledgeChunkSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommerceConnectorService
{
    public function __construct(private readonly KnowledgeChunkSyncService $chunkSyncService) {}

    /**
     * @return array{imported:int,updated:int,skipped:int,errors:list<string>}
     */
    public function importCsv(string $csv): array
    {
        $rows = $this->parseCsv($csv);
        if ($rows === []) {
            return ['imported' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => ['No CSV rows found.']];
        }

        $header = array_map(fn (string $value): string => $this->normalizeHeader($value), array_shift($rows));
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            if ($this->isEmptyRow($row)) {
                $skipped++;
                continue;
            }

            $payload = $this->mapRow($header, $row);
            $sku = trim((string) ($payload['sku'] ?? ''));
            $title = trim((string) ($payload['title'] ?? $payload['name'] ?? ''));

            if ($sku === '' || $title === '') {
                $skipped++;
                $errors[] = 'Row '.($index + 2).': sku and title are required.';
                continue;
            }

            DB::transaction(function () use ($payload, $sku, $title, &$imported, &$updated): void {
                $product = CommerceProduct::query()->firstOrNew(['sku' => $sku]);
                $isNew = ! $product->exists;

                $product->fill([
                    'source' => trim((string) ($payload['source'] ?? 'csv')) ?: 'csv',
                    'external_id' => $this->nullableString($payload['external_id'] ?? $payload['shopify_product_id'] ?? null),
                    'title' => $title,
                    'description' => $this->nullableString($payload['description'] ?? null),
                    'vendor' => $this->nullableString($payload['vendor'] ?? $payload['brand'] ?? null),
                    'product_type' => $this->nullableString($payload['product_type'] ?? $payload['category'] ?? null),
                    'material' => $this->nullableString($payload['material'] ?? null),
                    'origin_country' => $this->nullableString($payload['origin_country'] ?? $payload['origin'] ?? null),
                    'certifications' => $this->nullableString($payload['certifications'] ?? $payload['certification'] ?? null),
                    'faq' => $this->nullableString($payload['faq'] ?? null),
                    'support_policy' => $this->nullableString($payload['support_policy'] ?? $payload['returns'] ?? null),
                    'language' => trim((string) ($payload['language'] ?? 'en')) ?: 'en',
                    'status' => trim((string) ($payload['status'] ?? 'active')) ?: 'active',
                    'raw_payload' => $payload,
                    'synced_at' => now(),
                ]);
                $product->save();

                $variantSku = trim((string) ($payload['variant_sku'] ?? $payload['sku'] ?? $sku));
                $variant = CommerceVariant::query()->firstOrNew([
                    'commerce_product_id' => $product->id,
                    'sku' => $variantSku,
                ]);
                $variant->fill([
                    'external_id' => $this->nullableString($payload['variant_external_id'] ?? $payload['shopify_variant_id'] ?? null),
                    'option_1' => $this->nullableString($payload['option_1'] ?? $payload['color'] ?? null),
                    'option_2' => $this->nullableString($payload['option_2'] ?? $payload['size'] ?? null),
                    'option_3' => $this->nullableString($payload['option_3'] ?? null),
                    'price' => $this->nullableDecimal($payload['price'] ?? null),
                    'currency' => trim((string) ($payload['currency'] ?? 'USD')) ?: 'USD',
                    'inventory_quantity' => $this->integer($payload['inventory_quantity'] ?? $payload['inventory'] ?? $payload['stock'] ?? 0),
                    'inventory_policy' => trim((string) ($payload['inventory_policy'] ?? 'deny')) ?: 'deny',
                    'weight' => $this->nullableDecimal($payload['weight'] ?? null),
                    'weight_unit' => $this->nullableString($payload['weight_unit'] ?? null),
                    'raw_payload' => $payload,
                ]);
                $variant->save();

                CommerceInventorySnapshot::query()->create([
                    'commerce_product_id' => $product->id,
                    'commerce_variant_id' => $variant->id,
                    'quantity' => (int) $variant->inventory_quantity,
                    'location_name' => $this->nullableString($payload['location_name'] ?? $payload['warehouse'] ?? null),
                    'captured_at' => now(),
                ]);

                $isNew ? $imported++ : $updated++;
            });
        }

        return ['imported' => $imported, 'updated' => $updated, 'skipped' => $skipped, 'errors' => $errors];
    }

    public function createKnowledgeBase(CommerceProduct $product): KnowledgeBase
    {
        $product->loadMissing('variants');
        $content = $this->buildKnowledgeMarkdown($product);

        $knowledgeBase = KnowledgeBase::query()->updateOrCreate(
            [
                'source_type' => 'commerce_sku',
                'source_name' => 'SKU '.$product->sku,
            ],
            [
                'name' => 'Commerce SKU Knowledge — '.$product->sku,
                'description' => 'Structured product knowledge generated from ERP/Shopify connector data.',
                'content' => $content,
                'character_count' => mb_strlen($content, 'UTF-8'),
                'word_count' => mb_strlen(strip_tags($content), 'UTF-8'),
                'file_type' => 'markdown',
                'business_line' => 'commerce',
                'risk_level' => 'medium',
                'review_status' => 'unreviewed',
            ]
        );

        $this->chunkSyncService->sync((int) $knowledgeBase->id, $content, false);
        $product->update(['knowledge_base_id' => $knowledgeBase->id]);

        return $knowledgeBase;
    }

    public function generateContentDraft(CommerceProduct $product, string $language = 'en'): CommerceContentDraft
    {
        $product->loadMissing('variants');
        $language = in_array($language, ['en', 'zh', 'es'], true) ? $language : 'en';
        $mainVariant = $product->variants->first();
        $facts = array_filter([
            $product->material ? 'Material: '.$product->material : null,
            $product->origin_country ? 'Origin: '.$product->origin_country : null,
            $product->certifications ? 'Certifications: '.$product->certifications : null,
            $mainVariant ? 'Inventory: '.$mainVariant->inventory_quantity.' units' : null,
        ]);

        $title = match ($language) {
            'zh' => $product->title.'｜跨境商品说明',
            'es' => $product->title.' — ficha comercial',
            default => $product->title.' — Product Brief',
        };

        $description = match ($language) {
            'zh' => $this->zhDescription($product, $facts),
            'es' => $this->esDescription($product, $facts),
            default => $this->enDescription($product, $facts),
        };

        $bullets = implode("\n", array_map(
            static fn (string $fact): string => '- '.$fact,
            $facts !== [] ? $facts : ['SKU: '.$product->sku, 'Product type: '.($product->product_type ?: 'General merchandise')]
        ));

        $faq = $this->buildDraftFaq($product, $language);
        $prompt = $this->buildPrompt($product, $language);

        return CommerceContentDraft::query()->create([
            'commerce_product_id' => $product->id,
            'language' => $language,
            'channel' => 'marketplace',
            'title' => $title,
            'description' => $description,
            'bullets' => $bullets,
            'faq' => $faq,
            'prompt' => $prompt,
            'generation_mode' => 'template',
        ]);
    }

    /**
     * @return array{products:int,variants:int,with_knowledge:int,with_drafts:int,missing_faq:int,missing_description:int,low_inventory:int}
     */
    public function stats(): array
    {
        return [
            'products' => CommerceProduct::query()->count(),
            'variants' => CommerceVariant::query()->count(),
            'with_knowledge' => CommerceProduct::query()->whereNotNull('knowledge_base_id')->count(),
            'with_drafts' => CommerceProduct::query()->whereHas('contentDrafts')->count(),
            'missing_faq' => CommerceProduct::query()->where(fn ($query) => $query->whereNull('faq')->orWhere('faq', ''))->count(),
            'missing_description' => CommerceProduct::query()->where(fn ($query) => $query->whereNull('description')->orWhere('description', ''))->count(),
            'low_inventory' => CommerceVariant::query()->where('inventory_quantity', '<=', 5)->distinct('commerce_product_id')->count('commerce_product_id'),
        ];
    }

    public function sampleCsv(): string
    {
        return implode("\n", [
            'sku,title,description,vendor,product_type,material,origin_country,certifications,faq,support_policy,price,currency,inventory_quantity,variant_sku,color,size',
            'SF-BAG-001,Waterproof Travel Backpack,Lightweight waterproof backpack for cross-border travel,Semantic Goods,Bags,Recycled nylon,Vietnam,GRS; REACH,"Q: Is it waterproof? A: Yes, splash-resistant for daily commuting.","30-day return for unused items.",49.90,USD,18,SF-BAG-001-BLK,Black,20L',
            'SF-LAMP-002,Portable LED Desk Lamp,Foldable USB-C desk lamp for home office use,Semantic Goods,Lighting,ABS + aluminum,China,CE; RoHS,"Q: Does it include a battery? A: Yes, rechargeable battery included.","One-year limited warranty.",29.50,USD,4,SF-LAMP-002-WHT,White,Standard',
        ])."\n";
    }

    private function buildKnowledgeMarkdown(CommerceProduct $product): string
    {
        $variantRows = $product->variants->map(function (CommerceVariant $variant): string {
            return '- Variant SKU: '.$variant->sku
                .'; Options: '.implode(' / ', array_filter([(string) $variant->option_1, (string) $variant->option_2, (string) $variant->option_3]))
                .'; Price: '.($variant->price ?? 'N/A').' '.$variant->currency
                .'; Inventory: '.$variant->inventory_quantity;
        })->implode("\n");

        return trim(implode("\n\n", [
            '# SKU Knowledge: '.$product->sku,
            '## Product Identity'."\n"
                .'- Title: '.$product->title."\n"
                .'- SKU: '.$product->sku."\n"
                .'- Vendor: '.($product->vendor ?: 'N/A')."\n"
                .'- Product Type: '.($product->product_type ?: 'N/A'),
            '## Product Facts'."\n"
                .'- Material: '.($product->material ?: 'N/A')."\n"
                .'- Origin Country: '.($product->origin_country ?: 'N/A')."\n"
                .'- Certifications: '.($product->certifications ?: 'N/A'),
            '## Description'."\n".($product->description ?: 'No product description provided yet.'),
            '## Variants and Inventory'."\n".($variantRows !== '' ? $variantRows : 'No variants imported yet.'),
            '## FAQ'."\n".($product->faq ?: 'No FAQ provided yet.'),
            '## Support Policy'."\n".($product->support_policy ?: 'No support policy provided yet.'),
            '## AI Usage Notes'."\n"
                .'Use this knowledge to generate accurate marketplace descriptions, product FAQ, customer support replies, and content gap analysis. Do not invent unsupported certification, material, warranty, or inventory claims.',
        ]));
    }

    private function enDescription(CommerceProduct $product, array $facts): string
    {
        return $product->title.' is a commerce-ready product record for SKU '.$product->sku.'. '
            .'It is designed for marketplace, product detail page, and support knowledge workflows. '
            .($facts !== [] ? 'Key facts include '.implode('; ', $facts).'. ' : '')
            .'Use the verified SKU data as the source of truth before publishing customer-facing copy.';
    }

    private function zhDescription(CommerceProduct $product, array $facts): string
    {
        return $product->title.' 是 SKU '.$product->sku.' 的商品资料草稿，可用于跨境商品页、客服知识库和运营内容。'
            .($facts !== [] ? '已知事实包括：'.implode('；', $facts).'。' : '')
            .'发布前应以 ERP/Shopify 商品资料为准，避免补充未经验证的认证、库存或售后承诺。';
    }

    private function esDescription(CommerceProduct $product, array $facts): string
    {
        return $product->title.' es una ficha comercial para el SKU '.$product->sku.'. '
            .($facts !== [] ? 'Datos clave: '.implode('; ', $facts).'. ' : '')
            .'Revise los datos del ERP o Shopify antes de publicar descripciones orientadas al cliente.';
    }

    private function buildDraftFaq(CommerceProduct $product, string $language): string
    {
        if (trim((string) $product->faq) !== '') {
            return (string) $product->faq;
        }

        return match ($language) {
            'zh' => "Q: 这个商品适合什么场景？\nA: 适合与 {$product->product_type} 相关的跨境销售和客服场景。\n\nQ: 售后政策是什么？\nA: ".($product->support_policy ?: '请以店铺实际售后政策为准。'),
            'es' => "Q: ¿Para qué tipo de uso es adecuado?\nA: Es adecuado para escenarios de comercio relacionados con {$product->product_type}.\n\nQ: ¿Cuál es la política de soporte?\nA: ".($product->support_policy ?: 'Revise la política oficial de la tienda antes de publicar.'),
            default => "Q: What is this product best suited for?\nA: It is suited for commerce scenarios related to ".($product->product_type ?: 'general merchandise').".\n\nQ: What support policy should be shown?\nA: ".($product->support_policy ?: 'Confirm the official store policy before publishing.'),
        };
    }

    private function buildPrompt(CommerceProduct $product, string $language): string
    {
        return "Generate marketplace-ready product title, description, bullets, and FAQ in {$language} using only verified facts for SKU {$product->sku}. Avoid unsupported claims.";
    }

    /**
     * @return list<list<string>>
     */
    private function parseCsv(string $csv): array
    {
        $csv = trim($csv);
        if ($csv === '') {
            return [];
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $csv);
        rewind($handle);
        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = array_map(static fn ($value): string => trim((string) $value), $row);
        }
        fclose($handle);

        return $rows;
    }

    private function normalizeHeader(string $value): string
    {
        return Str::of($value)->trim()->lower()->replace([' ', '-'], '_')->toString();
    }

    /**
     * @param  list<string>  $header
     * @param  list<string>  $row
     * @return array<string, string>
     */
    private function mapRow(array $header, array $row): array
    {
        $payload = [];
        foreach ($header as $index => $name) {
            if ($name !== '') {
                $payload[$name] = $row[$index] ?? '';
            }
        }

        return $payload;
    }

    /**
     * @param  list<string>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        return trim(implode('', $row)) === '';
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableDecimal(mixed $value): ?float
    {
        $value = trim((string) $value);

        return is_numeric($value) ? (float) $value : null;
    }

    private function integer(mixed $value): int
    {
        $value = trim((string) $value);

        return is_numeric($value) ? (int) $value : 0;
    }
}
