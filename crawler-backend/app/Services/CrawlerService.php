<?php

namespace App\Services;

use App\Models\Url;
use Illuminate\Http\Response;
use Inewlegend\CrawlerLib\Crawler;

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

    public function getIndexLinks()
    {
        // TODO: Find better way to determine index links.
        $indexLinks = $this->url->where("depth", ">=", 0)->get();

        // Add the count of links for each index link.
        foreach ($indexLinks as $indexLink) {
            $indexLink->children_count = $this->getChildrenCount($indexLink->_id);
        }

        return $indexLinks;
    }

    public function getLinksById(string $id)
    {
        $urlModel = $this->url->where('_id', $id)->first();

        if (!$urlModel) {
            // Return 404.
            return new Response('Not found', 404);
        }

        $urlModel->children = $this->getChildren($id);

        return $urlModel;
    }

    public function getLinksByHash(string $hash)
    {
        $urlModel = $this->url->where('url_hash', $hash)->first();

        if (!$urlModel) {
            // Return 404.
            return new Response('Not found', 404);
        }

        return $this->getLinksById($urlModel->_id);
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
        $children = new \Illuminate\Support\Collection();

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

        $content = $this->getFromRemote($urlModel->url, $depth);

        foreach ($content as $key => $value) {
            $oldOwnerIds = [];
            $oldChildren = $this->url->where('url_hash', $key)->first();

            if ($oldChildren) {
                $oldOwnerIds = $oldChildren->toArray()['owner_ids'];
            }

            // Merge old `owner_ids` with new `owner_ids`.
            $ownerIds = array_merge($oldOwnerIds, [$urlModel->id]);

            // TODO - "This database engine does not support upserts."
            $this->url->updateOrCreate([
                'url' => $value['href'],
                'url_hash' => $key,
            ], [
                'text' => $value['text'],

                // Works as array in mongodb.
                'owner_ids' => $ownerIds,
            ]);
        }

        if (!isset($urlModel->depth)) {
            $urlModel->depth = 0;
        } // Update depth only if it is higher.
        else if ($depth > $urlModel->depth) {
            $urlModel->depth = intval($depth);
        }

        // Trigger update, anyway.
        $urlModel->save();

        // TODO: Avoid querying again, use memory.
        $urlModel->children = $this->getChildren($id);

        return $urlModel;
    }

    public function delete($id)
    {
        $urlModel = $this->url->where('_id', $id)->first();

        if (!$urlModel) {
            // Return 404.
            return new Response('Not found', 404);
        }

        // Delete all children.
        $children = $this->getChildren($id);

        foreach ($children as $child) {
            // Remove owner from child, manually.
            $child->owner_ids = array_diff($child->owner_ids, [$urlModel->id]);

            // Reindex array - will be object in db without.
            $child->owner_ids = array_values($child->owner_ids);

            // If no owners, delete.
            if ( ! count($child->owner_ids)) {
                $child->delete();
                continue;
            }

            $child->save();
        }

        // TODO - Use memory - If no children, remove depth - lazy no time.
        if ( ! $this->getChildrenCount($id)) {
            $urlModel->depth = -1;
            $urlModel->save();
        }

        return $urlModel;
    }

    private function getChildren($id)
    {
        return $this->url->where('owner_ids', 'LIKE', '%' . $id . '%')->get();
    }

    private function getChildrenCount($id)
    {
        return $this->url->where('owner_ids', 'LIKE', '%' . $id . '%')->count();
    }
}
