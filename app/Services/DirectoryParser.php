<?php

namespace App\Services;

use Symfony\Component\DomCrawler\Crawler;

class DirectoryParser
{
    /**
     * Parse the given HTML and return a JSON object.
     *
     * @param  string  $html
     * @return string
     */
    public function parse(string $html): string
    {
        $crawler = new Crawler($html);



        $directories = $crawler->filter('#files > li:not(.header) > a')->each(function (Crawler $node) {
            $name = $node->filter('.name')->text();
            $size = $node->filter('.size')->text();
            $date = $node->filter('.date')->text();
            $href = $node->attr('href');

            return compact('name', 'size', 'date', 'href');
        });


        return json_encode(['directories' => $directories], JSON_PRETTY_PRINT);
    }
}
