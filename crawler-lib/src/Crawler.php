<?php

namespace Inewlegend\CrawlerLib;

use DOMDocument;
use GuzzleHttp\Client;

const DEFAULT_TEXT_MAX_LENGTH = 100;

const DEFAULT_LOADING_MAX_TIME = 5000;

class Crawler
{
    private array $options;

    private DOMDocument $document;

    protected string $target;

    protected bool $targetIsFile = false;

    public function __construct(string $target, $options = [])
    {
        $this->options = array_merge([
            "text_max_length" => DEFAULT_TEXT_MAX_LENGTH,
            "loading_max_time" => DEFAULT_LOADING_MAX_TIME,
            "debug" => false,
        ], $options);

        $this->target = $target;

        $this->document = new DOMDocument();
    }

    public function crawl($depth = 0, &$accumulator = []): array
    {
        if (!$this->document->textContent) {
            $this->load();
        }

        $links = $this->getLinks();

        foreach ($links as $key => $link) {
            $accumulator[$key]['href'] = $link['href'];
            $accumulator[$key]['text'] = $link['text'];

            if ($depth) {
                $crawler = new Crawler($link['href'], $this->options);

                $crawler->crawl($depth - 1, $accumulator );
            }
        }

        return $accumulator;
    }

    public function getDocument(): DOMDocument
    {
        return $this->document;
    }

    public function getLinks(): array
    {
        $result = [];

        $links = $this->document->getElementsByTagName('a');

        foreach ($links as $link) {
            $href = $link->getAttribute('href');

            // Remove only \t and \n.
            $href = $this->normalizeHref($href);

            if (!$href) {
                continue;
            }

            $text = $link->textContent;

            $id = hash('sha256', $href);

            $text = substr($text, 0, $this->options['text_max_length']);

            // Remove characters that are not part of any language.
            $text = preg_replace('/[^\p{L}\p{N}\p{P}\p{Zs}]/u', '', $text);

            // Replace multiple spaces, tabs, new lines with single space.
            $text = trim(preg_replace('/\s+/', ' ', $text));

            $result[$id] = [
                'href' => $href,
                "text" => $text,
            ];
        }

        return $result;
    }

    protected function load(): bool
    {
        $client = new Client();

        $isDebug = $this->options['debug'];

        if ($isDebug) {
            echo "Loading '$this->target'\n";
        }

        if (!is_file($this->target)) {
            $response = $client->get($this->target, [
                'timeout' => $this->options['loading_max_time'],
                // Do not fail on error status code.
                'http_errors' => false,
            ]);

            // Load content only with http 200 status code.
            $result = $response->getStatusCode() === 200 &&
                $this->document->loadHTML($response->getBody()->getContents());
        } else {
            $this->targetIsFile = true;

            $result = $this->document->loadHTMLFile($this->target);
        }

        if ($isDebug) {
            echo ($result ? "Loaded" : "Failed to load") . " '$this->target'\n";
        }

        return $result;
    }

    private function normalizeHref($href): string
    {
        // Remove only \t and \n.
        $href = trim(preg_replace('/[\t\n]/', '', $href));

        if (!$href) {
            return '';
        }

        // If the URL already starts with http or https, return it as is.
        if (preg_match('/^https?:\/\//', $href)) {
            return $href;
        }

        // Get the base URL components.
        $baseUrlScheme = parse_url($this->target, PHP_URL_SCHEME);
        $baseUrlHost = parse_url($this->target, PHP_URL_HOST);
        $baseUrlPath = dirname($this->target);

        $result = '';

        if ($this->targetIsFile && !preg_match('/^\//', $href)) {
            // If file and not a full path, append to base path.
            $result = $baseUrlPath . '/' . $href;
        } else if (preg_match('/^www\./', $href)) {
            // If starts with 'www', add 'https://' - Not the best solution, but it's enough for this project.
            $result = 'https://' . $href;
        } else if (preg_match('/^\/\//', $href)) {
            // If starts with '//' use current scheme.
            $result = $baseUrlScheme . ':' . $href;
        } else if (preg_match('/^\//', $href)) {
            // If starts with '/', append to current host.
            $result = $baseUrlScheme . '://' . $baseUrlHost . $href;
        } else if (!preg_match('/^https?:\/\//', $href) && !str_contains($href, ':')) {
            // If not remote URL and doesn't contain ':' eg `tel:`, append to base URL.
            $result = $baseUrlScheme . '://' . $baseUrlHost . '/' . $href;
        }

        return $result;
    }

}
