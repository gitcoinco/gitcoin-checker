<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @for ($i = 0; $i < $sitemapCount; $i++)
        <sitemap>
            <loc>{{ url("public/projects/sitemap-{$i}.xml") }}</loc>
        </sitemap>
    @endfor
</sitemapindex>
