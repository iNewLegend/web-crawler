<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CrawlerNormalizer, normalize the response for the client.
 */
class CrawlerNormalizer
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->isSuccessful() && $response->original) {
            /**
             * @var \Illuminate\Support\Collection|\App\Models\Url $context
             */
            $context = $response->getOriginalContent();

            if (!$context instanceof \Illuminate\Support\Collection) {
                $stringify = json_encode($this->mapper($context));

                return new Response($stringify, $response->getStatusCode(), $response->headers->all());
            }

            // Map the collection to a new collection
            $result = $context->map(function ($item) {
                return $this->mapper($item);
            });

            return new Response($result, $response->getStatusCode(), $response->headers->all());
        }

        return $response;
    }

    private function mapper($item): array
    {
        $result = [
            'id' => $item->id,
            'url' => $item->url,

            'updatedAt' => $item->updated_at,
            'createdAt' => $item->created_at,
        ];

        static $optional = [
            'text' => 'text',
            'url_hash' => 'urlHash',
            'depth' => 'depth',
            'owner_ids' => 'ownerIds',

            // Partial.
            'children' => 'children',

            // Not from db.
            'children_count' => 'childrenCount',
        ];

        foreach ($optional as $key => $value) {
            if (isset($item->$key)) {
                $result[$value] = $item->$key;
            }
        }

        // Normalize children.
        if (isset($item->children)) {
            $result['children'] = $item->children->map(function ($item) {
                return $this->mapper($item);
            });
        }

        return $result;
    }
}
