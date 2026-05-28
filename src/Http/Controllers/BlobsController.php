<?php

namespace UntitledDevelopers\KockatoosAdminCore\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use UntitledDevelopers\KockatoosAdminCore\Services\BlobService;

class BlobsController extends Controller
{
    public function __construct(protected BlobService $blobService) {}

    public function index(Request $request): JsonResponse
    {
        $paginate = $request->boolean('paginate', true);

        $result = $this->blobService->list([
            'searchFor' => $request->input('searchFor'),
            'directory' => $request->input('directory'),
            'type'      => $request->input('type'),
            'sortBy'    => $request->input('sortBy'),
            'sortAs'    => $request->input('sortAs'),
            'perPage'   => $request->input('perPage'),
            'paginate'  => $paginate,
        ]);

        if (!$paginate) {
            return response()->json(['data' => $result]);
        }

        return response()->json($result);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|file']);

        [$file, $name, $directory, $sortNumber] = $this->extractPayload($request);

        $blob = $this->blobService->store(
            $file,
            $directory ?? 'blobs',
            $name,
            $sortNumber ?? 0
        );

        return response()->json($blob);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $blob = $this->blobService->find($id);
        if (!$blob) {
            abort(404);
        }

        [$file, $name, $directory, $sortNumber] = $this->extractPayload($request);

        if ($file) {
            $blob = $this->blobService->update($blob, $file, $directory, $name);
        }

        $attributes = array_filter([
            'name'        => $name,
            'directory'   => $directory,
            'sort_number' => $sortNumber,
        ], fn ($v) => $v !== null);

        if (!empty($attributes)) {
            $blob->update($attributes);
        }

        return response()->json($blob->fresh());
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $blob = $this->blobService->find($id);
        if (!$blob) {
            abort(404);
        }

        $this->blobService->delete($blob);

        return response()->json(['message' => 'OK']);
    }

    private function extractPayload(Request $request): array
    {
        $file      = $request->file('file');
        $name      = $request->input('name');
        $directory = $request->input('directory');
        $sortNumber = $request->input('sort_number');

        if ($request->has('data')) {
            $data      = json_decode($request->input('data'), true) ?? [];
            $name      ??= $data['name'] ?? null;
            $directory ??= $data['directory'] ?? null;
            $sortNumber ??= $data['sort_number'] ?? null;
        }

        return [$file, $name, $directory, $sortNumber !== null ? (int) $sortNumber : null];
    }
}