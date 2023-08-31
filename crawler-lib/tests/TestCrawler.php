<?php

namespace Inewlegend\CrawlerLib\Tests;

use PHPUnit\Framework\TestCase;

class TestCrawler extends TestCase
{
    public function test_load__ensure_non_exist_target()
    {
        // Arrange.
        $crawler = new MockCrawler("https://unknown-address.not-exist");

        // Act.
        $result = $crawler->doLoad();

        // Assert.
        $this->assertFalse($result);
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

    public function test_crawl__ensure_no_duplicates()
    {
        $crawler = new MockCrawler(__DIR__ . "/assets/custom/index.html");

        // Act.
        $result = $crawler->crawl(5);

        // Assert.
        $this->assertCount(3, $result);
    }

    public function test_crawl__ensure_depth_0()
    {
        // Arrange.
        $crawler = new MockCrawler("https://www.index.co.il/");

        $expected = require __DIR__ . "/assets/results/index.co.il_0_depth.php";

        // Act.
        $result = $crawler->crawl(0);
        arsort($result);

        // Assert.
        $this->assertEquals($expected, $result);
    }

    public function test_crawl__ensure_depth_1()
    {
        // Arrange.
        $crawler = new MockCrawler("https://www.index.co.il/");

        $expected = require __DIR__ . "/assets/results/index.co.il_1_depth.php";

        // Act.
        $result = $crawler->crawl(1);
        arsort($result);

        // Assert.
        $this->assertEquals($expected, $result);
    }

    public function test_crawl__ensure_depth_2()
    {
        // Arrange.
        $crawler = new MockCrawler("https://www.sitemaps.org");

        $expected = require __DIR__ . "/assets/results/sitemaps.org_3_depth.php";

        // Act.
        $result = $crawler->crawl(2);
        arsort($result);

        // Assert.
        $this->assertEquals($expected, $result);
    }
}
