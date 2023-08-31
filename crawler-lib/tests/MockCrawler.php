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

    protected function getFromRemote(): string
    {
        // Check if target exist locally, if so load it.
        $path = __DIR__ . "/assets/mock/" . md5($this->target) . ".html";

        if (file_exists($path)) {
            return file_get_contents($path);
        }

        // If not, try to load from remote.
        $content = parent::getFromRemote();

        // If succeeded, save it locally.
        file_put_contents($path, $content);

        return $content;
    }
}
