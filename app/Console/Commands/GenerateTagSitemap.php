<?php

namespace App\Console\Commands;

use App\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateTagSitemap extends Command
{
    protected $signature = 'sitemap:tags 
                            {--entity-type= : Filter by entity type (product, post)}
                            {--output= : Output file path (default: public/sitemap-tags.xml)}';

    protected $description = 'Generate sitemap XML for tags';

    public function handle(): int
    {
        $entityType = $this->option('entity-type');
        $outputPath = $this->option('output') ?? 'public/sitemap-tags.xml';

        $this->info('Generating tag sitemap...');

        $query = Tag::active();
        
        if ($entityType) {
            // Normalize entity type
            if ($entityType === 'product') {
                $query->where(function($q) {
                    $q->where('entity_type', \App\Models\Product::class)
                      ->orWhere('entity_type', 'product');
                });
            } elseif ($entityType === 'post') {
                $query->where(function($q) {
                    $q->where('entity_type', \App\Models\Post::class)
                      ->orWhere('entity_type', 'post');
                });
            } else {
                $query->where('entity_type', $entityType);
            }
        }

        $tags = $query->orderBy('slug')->get();
        $baseUrl = config('app.url');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($tags as $tag) {
            $url = route('client.tags.show', $tag->slug);
            $lastmod = $tag->updated_at->format('Y-m-d');
            $priority = $tag->usage_count > 10 ? '0.8' : '0.6';
            $changefreq = $tag->usage_count > 10 ? 'weekly' : 'monthly';

            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url, ENT_XML1) . "</loc>\n";
            $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
            $xml .= "    <changefreq>{$changefreq}</changefreq>\n";
            $xml .= "    <priority>{$priority}</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        // Ensure directory exists
        $directory = dirname($outputPath);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($outputPath, $xml);

        $this->info("✅ Sitemap generated successfully: {$outputPath}");
        $this->info("   Total tags: {$tags->count()}");

        // Generate separate sitemaps by entity type if not filtered
        if (!$entityType) {
            $this->generateByEntityType('product', 'public/sitemap-tags-product.xml');
            $this->generateByEntityType('post', 'public/sitemap-tags-post.xml');
        }

        return Command::SUCCESS;
    }

    protected function generateByEntityType(string $entityType, string $outputPath): void
    {
        $this->call('sitemap:tags', [
            '--entity-type' => $entityType,
            '--output' => $outputPath,
        ]);
    }
}
