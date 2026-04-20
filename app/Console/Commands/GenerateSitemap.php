<?php

namespace App\Console\Commands;

use App\Models\Program;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Signature('sitemap:generate')]
#[Description('Generate public/sitemap.xml from static public routes and active programs.')]
class GenerateSitemap extends Command
{
    public function handle(): int
    {
        $urls = $this->buildUrls();

        File::put(public_path('sitemap.xml'), $this->renderXml($urls));

        $this->info(sprintf('Wrote %d URLs to public/sitemap.xml', count($urls)));

        return self::SUCCESS;
    }

    /**
     * @return list<array{loc: string, lastmod: ?string, changefreq: string, priority: string}>
     */
    protected function buildUrls(): array
    {
        $urls = [
            ['loc' => route('home'), 'lastmod' => null, 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['loc' => route('public.programs'), 'lastmod' => null, 'changefreq' => 'weekly', 'priority' => '0.9'],
            ['loc' => route('about'), 'lastmod' => null, 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => route('contact'), 'lastmod' => null, 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['loc' => route('privacy'), 'lastmod' => null, 'changefreq' => 'yearly', 'priority' => '0.3'],
            ['loc' => route('terms'), 'lastmod' => null, 'changefreq' => 'yearly', 'priority' => '0.3'],
        ];

        Program::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->each(function (Program $program) use (&$urls): void {
                $urls[] = [
                    'loc' => route('public.program', $program),
                    'lastmod' => $program->updated_at?->toIso8601String(),
                    'changefreq' => 'monthly',
                    'priority' => '0.8',
                ];
            });

        return $urls;
    }

    /**
     * @param  list<array{loc: string, lastmod: ?string, changefreq: string, priority: string}>  $urls
     */
    protected function renderXml(array $urls): string
    {
        $lines = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $lines[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $url) {
            $lines[] = '    <url>';
            $lines[] = '        <loc>'.htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc>';

            if (filled($url['lastmod'])) {
                $lines[] = '        <lastmod>'.$url['lastmod'].'</lastmod>';
            }

            $lines[] = '        <changefreq>'.$url['changefreq'].'</changefreq>';
            $lines[] = '        <priority>'.$url['priority'].'</priority>';
            $lines[] = '    </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines)."\n";
    }
}
