<?php

namespace App\Services;

use App\Models\Url;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Inewlegend\CrawlerLib\Crawler;
use Jenssegers\Mongodb\Eloquent\Builder;

class CrawlerService
{
    private Url $url;

    public function __construct(Url $url)
    {
        $this->url = $url;
    }

    public function getFromRemote($url, $depth)
    {
        return (new Crawler($url))->crawl($depth);
    }

    public function getIndex()
    {
        // TODO: Find better way to determine index links.
        $indexLinks = $this->url->where("depth", ">=", 0)->get();

        // Add the count of links for each index link.
        foreach ($indexLinks as $indexLink) {
            $indexLink->children_count = $this->getChildrenOf($indexLink->_id)->count();
        }

        return $indexLinks;
    }

    public function getById(string $id)
    {
        $urlModel = $this->url->where('_id', $id)->first();

        if (!$urlModel) {
            // Return 404.
            return new Response('Not found', 404);
        }

        $urlModel->children = $this->getChildrenOf($id)->get();

        return $urlModel;
    }

    public function getByHash(string $hash)
    {
        $urlModel = $this->url->where('url_hash', $hash)->first();

        if (!$urlModel) {
            // Return 404.
            return new Response('Not found', 404);
        }

        return $this->getById($urlModel->_id);
    }

    public function getByUrl(string $url)
    {
        $urlModel = $this->url->where('url', $url)->first();

        if (!$urlModel) {
            return false;
        }

        return $urlModel;
    }

    public function create(string $url, int $depth, array $content)
    {
        $urlModel = $this->url->create([
            'url' => $url,
            'url_hash' => hash('sha256', $url),
            'depth' => $depth,
        ]);

        // New children collection.
        $children = new Collection();

        foreach ($content as $key => $value) {
            $model = $urlModel->create([
                'url' => $value['href'],
                'url_hash' => $key,

                'text' => $value['text'],

                // Works as array in mongodb.
                'owner_ids' => [$urlModel->id],
            ]);

            $children->push($model);
        }

        $urlModel['children'] = $children;

        return $urlModel;
    }

    public function update($id, $depth)
    {
        $urlModel = $this->url->where('_id', $id)->first();

        if (!$urlModel) {
            // Return 404.
            return new Response('Not found', 404);
        }

        $children = new Collection();

        $content = $this->getFromRemote($urlModel->url, $depth);

        foreach ($content as $key => $value) {
            $oldChildren = $this->url->where('url_hash', $key)->first();
            $oldOwnerIds = $oldChildren?->toArray() ? ['owner_ids'] : [];

            // Merge old `owner_ids` with new `owner_ids`.
            $ownerIds = array_merge($oldOwnerIds, [$urlModel->id]);

            // TODO - "This database engine does not support upserts."
            $result = $this->url->updateOrCreate([
                'url' => $value['href'],
                'url_hash' => $key,
            ], [
                'text' => $value['text'],

                // Works as array in mongodb.
                'owner_ids' => $ownerIds,
            ]);

            $children->push($result);
        }

        if (!isset($urlModel->depth)) {
            $urlModel->depth = 0;
        } // Update depth only if it is higher.
        else if ($depth > $urlModel->depth) {
            $urlModel->depth = intval($depth);
        }

        // Trigger update, anyway - to update `updated_at`.
        $urlModel->touch();
        $urlModel->save();

        $urlModel->children = $children;

        return $urlModel;
    }

    /**
     * Delete a URL and its associated child URLs.
     *
     * Deletes a URL by its ID and, if it has child URLs, removes them as well.
     * If the URL is not found, it returns a 404 Response.
     *
     * @param string $id The ID of the URL to delete.
     *
     * @return array|Response An array containing the deleted URL ID and the IDs of its deleted child URLs,
     * or a 404 Response if the URL was not found.
     */
    public function delete(string $id): array|Response
    {
        $urlModel = $this->url->where('_id', $id)->first();

        if (!$urlModel) {
            // Return 404.
            return new Response('Not found', 404);
        }

        $result = [];

        $owner = new class ($this->getChildrenOf($id)) {
            private int $initialCount;

            private Collection $children;

            private Collection $deletedChildren;

            public function __construct($builder)
            {
                $this->children = $builder->get();

                $this->initialCount = $builder->count();

                $this->deletedChildren = new Collection();
            }

            public function delete(Url $child): void
            {
                $this->deletedChildren->push($child);

                $child->delete();
            }

            public function getChildren(): Collection
            {
                return $this->children;
            }

            public function getDeletedChildren(): Collection
            {
                return $this->deletedChildren;
            }

            public function hasChildren(): bool
            {
                return $this->initialCount <> $this->deletedChildren->count();
            }
        };

        // Delete all children.
        foreach ($owner->getChildren() as $child) {
            // Remove owner from child, manually.
            $child->owner_ids = array_diff($child->owner_ids, [$urlModel->id]);

            // Reindex array - will be object in db without.
            $child->owner_ids = array_values($child->owner_ids);

            // If the child is not a parent(index link) and there are no owners, delete.
            if ((!isset($child->depth) || $child->depth < 0) && !count($child->owner_ids)) {
                $owner->delete($child);
                continue;
            }

            $child->save();
        }

        if (!$owner->hasChildren()) {
            $result[] = $id;

            $urlModel->depth = -1;
            $urlModel->save();
        }

        return array_merge($result,
            $owner->getDeletedChildren()->pluck('_id')->toArray()
        );
    }

    private function getChildrenOf($id): Url|Builder
    {
        return $this->url->where('owner_ids', 'LIKE', '%' . $id . '%');
    }
}
