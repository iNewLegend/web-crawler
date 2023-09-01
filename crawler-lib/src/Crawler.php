<?php

namespace Inewlegend\CrawlerLib;

use DOMDocument;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\TooManyRedirectsException;

const DEFAULT_TEXT_MAX_LENGTH = 100;

const DEFAULT_LOADING_MAX_TIME = 5000;

const DEFAULT_USE_MEMORY_CACHE = true;

const LIBXML_OPTIONS = LIBXML_NOWARNING | LIBXML_NOERROR;

class Crawler
{
    private array $options;

    private DOMDocument $document;

    protected string $target;

    protected bool $targetIsFile = false;

    private static array $cacheLinks = [];
    private static array $cacheConnectionFailed = [];

    public function __construct(string $target, $options = [])
    {
        $this->options = array_merge([
            "text_max_length" => DEFAULT_TEXT_MAX_LENGTH,
            "loading_max_time" => DEFAULT_LOADING_MAX_TIME,
            "use_memory_cache" => DEFAULT_USE_MEMORY_CACHE,
            "debug" => false,
        ], $options);

        $this->target = $target;

        $this->document = new DOMDocument();
    }

    public function crawl($depth = 0, &$accumulator = []): array
    {
        $useMemoryCache = $this->options['use_memory_cache'];

        if ($useMemoryCache && isset(self::$cacheLinks[$this->target])) {
            echo "Using cache for '$this->target'\n";
            $links = self::$cacheLinks[$this->target];
        } else {
            if (!$this->document->textContent) {
                $this->load();
            }

            $links = $this->getLinks();

            // Cache links for this target.
            if ($useMemoryCache) {
                self::$cacheLinks[$this->target] = $links;
            }
        }

        foreach ($links as $key => $link) {
            $accumulator[$key]['href'] = $link['href'];
            $accumulator[$key]['text'] = $link['text'];

            if ($depth) {
                $crawler = new static($link['href'], $this->options);

                $crawler->crawl($depth - 1, $accumulator);
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
        $this->debug("Loading '$this->target'");

        if (!is_file($this->target)) {
            $result = $this->getFromRemote();

            if ($result) {
                $this->document->loadHTML($result, LIBXML_OPTIONS);
            }
        } else {
            $this->targetIsFile = true;

            $result = $this->document->loadHTMLFile($this->target, LIBXML_OPTIONS);
        }

        $this->debug(($result ? "Loaded" : "Failed to load") . " '$this->target'");

        return $result;
    }

    protected function getFromRemote(): string|bool
    {
        static $client;

        $useMemoryCache = $this->options['use_memory_cache'];

        if ($useMemoryCache && isset(self::$cacheConnectionFailed[$this->target])) {
            $this->debug("Failed to load from cache '$this->target'");
            return false;
        }

        if (!$client) {
            $client = new Client([
                'timeout' => $this->options['loading_max_time'],
                // Do not fail on error status code.
                'http_errors' => false,

                'agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',

                'debug' => false,
            ]);
        }

        $response = null;

        try {
            $response = $client->get($this->target);
        } catch (Exception $e) {
            // Cache failed connections.
            if ($useMemoryCache) {
                self::$cacheConnectionFailed[$this->target] = true;
            }

            if ($e instanceof ConnectException || $e instanceof TooManyRedirectsException) {
                $this->debug("Failed to load '$this->target' - " . $e->getMessage());
            } else {
                throw $e;
            }
        }

        if ($response?->getStatusCode() === 200) {
            return $response->getBody()->getContents();
        }

        return false;
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
            $result = $href;
        } else {
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
        }

        // Remove anything after '#'.
        $result = preg_replace('/#.*$/', '', $result);

        // Remove trailing slash.
        return rtrim($result, '/');
    }

    private function debug($message): void
    {
        if ($this->options['debug']) {
            echo $message . "\n";
        }
    }
}
