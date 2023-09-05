<?php

namespace App\Http\Controllers;

use App\Models\Url;
use App\Services\CrawlerService;
use Illuminate\Http\Request;

class CrawlerController extends Controller
{
    private CrawlerService $crawlerService;

    public function __construct(Url $url)
    {
        $this->crawlerService = new CrawlerService($url);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $url = $request->input('url');
        $hash = $request->input('hash');

        $params = array_filter(compact('url', 'hash'));

        if (count($params) > 1) {
            return response()->json([
                'message' => json_encode($params) . ' are mutually exclusive'
            ], 400);
        }

        if (!empty($url)) {
            return $this->crawlerService->getByUrl($url);
        } else if (!empty($hash)) {
            return $this->crawlerService->getByHash($hash);
        }

        return $this->crawlerService->getIndex();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return $this->crawlerService->getById($id);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $url = $request->input('url');
        $depth = $request->get('depth', 0);

        if (empty($url)) {
            return response()->json([
                'message' => 'url is required'
            ], 400);
        }

        $content = $this->crawlerService->getFromRemote($url, $depth);

        return $this->crawlerService->create($url, $depth, $content);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $depth = $request->input('depth') ?? 0;

        return $this->crawlerService->update($id, $depth);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return $this->crawlerService->delete($id);
    }
}
