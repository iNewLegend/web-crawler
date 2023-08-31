<?php

namespace Inewlegend\CrawlerLib\Tests;

use PHPUnit\Framework\TestCase;

class TestCrawler extends TestCase
{
    public function test_load__ensure_non_exist_target()
    {
        // Arrange.
        $crawler = new MockCrawler("https://unknown-address.not-exist");

        // Assert.
        $this->expectExceptionMessage("Could not resolve host: unknown-address.not-exist");

        // Act.
        $crawler->doLoad();
    }

    public function test_load__ensure_exist_target()
    {
        // Arrange.
        $crawler = new MockCrawler("https://google.com");

        // Act.
        $result = $crawler->doLoad();

        // Assert.
        $this->assertTrue($result);
    }

    public function test_getLinks()
    {
        // Arrange.
        $excepted = array (
            '72dd00e3fa944efc2f9506fcd86896247c26995c474616d60f40e618bfd561db' =>
                array (
                    'href' => '/Users/inewlegend/Desktop/web-crawler/crawler-lib/tests/assets/custom/index_1.html',
                    'text' => 'index_1',
                ),
            '6403e4013da43783cdeccce6d10794b0cfbe1c921b9b67a8bea9301622e0dd75' =>
                array (
                    'href' => '/Users/inewlegend/Desktop/web-crawler/crawler-lib/tests/assets/custom/index_2.html',
                    'text' => 'index_2',
                ),
            '39057fbd541b3248f150ac27ccda95a3cf0a099565ecd01aef4da193f6fd83a9' =>
                array (
                    'href' => '/Users/inewlegend/Desktop/web-crawler/crawler-lib/tests/assets/custom/index_3.html',
                    'text' => 'index_3',
                ),
        );

        $crawler = new MockCrawler(__DIR__ . "/assets/custom/index.html");

        $crawler->doLoad();

        // Act.
        $result = $crawler->getLinks();

        // Assert.
        $this->assertEquals( $excepted, $result);
    }

    public function test_crawl__ensure_no_duplicates() {
        // Arrange.
        $excepted = array (
            '72dd00e3fa944efc2f9506fcd86896247c26995c474616d60f40e618bfd561db' =>
                array (
                    'href' => '/Users/inewlegend/Desktop/web-crawler/crawler-lib/tests/assets/custom/index_1.html',
                    'text' => 'to index_1 in index_3',
                ),
            '6403e4013da43783cdeccce6d10794b0cfbe1c921b9b67a8bea9301622e0dd75' =>
                array (
                    'href' => '/Users/inewlegend/Desktop/web-crawler/crawler-lib/tests/assets/custom/index_2.html',
                    'text' => 'to index_2 in index_3',
                ),
            '39057fbd541b3248f150ac27ccda95a3cf0a099565ecd01aef4da193f6fd83a9' =>
                array (
                    'href' => '/Users/inewlegend/Desktop/web-crawler/crawler-lib/tests/assets/custom/index_3.html',
                    'text' => 'to index_3 in index_3',
                ),
        );

        $crawler = new MockCrawler(__DIR__ . "/assets/custom/index.html");

        // Act.
        $result = $crawler->crawl(5 );

        // Assert.
        $this->assertEquals($excepted, $result);
    }

    public function test_crawl__ensure_depth_0()
    {
        // Arrange.
        $crawler = new MockCrawler("https://www.index.co.il/");

        // Load `crawl_result_google_html_depth_1.php` asset.
        $expected = require __DIR__ . "/assets/results/index.co.il_0_depth.php";

        // Act.
        $result = $crawler->crawl(0);

        // Assert.
        $this->assertEquals($expected, $result);
    }

    public function test_crawl__ensure_depth_1()
    {
        // Arrange.
        $crawler = new MockCrawler("https://www.index.co.il/");

        // Load `crawl_result_google_html_depth_1.php` asset.
        $expected = require __DIR__ . "/assets/results/index.co.il_1_depth.php";

        // Act.
        $result = $crawler->crawl(1);

        $this->assertEquals($expected, $result);
    }
}
