<?php

namespace Inewlegend\CrawlerLib\Tests;

class MockCrawler extends \Inewlegend\CrawlerLib\Crawler
{
    public function __construct(string $target, $options = [])
    {
        parent::__construct($target, array_merge([
            "loading_max_time" => 500,
            "debug" => true,
        ], $options));
    }

    public function doLoad()
    {
        return $this->load();
    }
}
