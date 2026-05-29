<?php

namespace UntitledDevelopers\KockatoosAdminCore\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use UntitledDevelopers\KockatoosAdminCore\Models\Admin;
use UntitledDevelopers\KockatoosAdminCore\Services\BlobService;
use UntitledDevelopers\KockatoosAdminCore\Services\FileService;
use UntitledDevelopers\KockatoosAdminCore\Services\ImageService;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->blobService = new BlobService(
        new FileService('public'),
        new ImageService()
    );

    $admin = Admin::create([
        'name' => 'Test Admin',
        'username' => 'tester',
        'password' => bcrypt('secret'),
    ]);
    $this->actingAs($admin, 'sanctum');
});

function seedBlob(string $directory = 'blobs', string $name = 'doc.pdf'): \UntitledDevelopers\KockatoosAdminCore\Models\Blob
{
    /** @var BlobService $service */
    $service = test()->blobService;

    return $service->store(
        UploadedFile::fake()->create($name, 100, 'application/pdf'),
        $directory,
        $name
    );
}

// ── Regression — default response shape unchanged (FR-8 / SC-5) ──────────────

it('returns the existing paginator envelope when no flags are set', function () {
    seedBlob('blobs', 'a.pdf');
    seedBlob('blobs', 'b.pdf');

    $response = $this->getJson('/api/blobs');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'url', 'type', 'size', 'ext', 'name', 'directory', 'sort_number', 'created_at', 'updated_at'],
            ],
            'current_page',
            'last_page',
            'per_page',
            'total',
        ])
        ->assertJsonMissingPath('data.0.exists');

    // Tighter FR-8 regression guard: the per-row key set is exactly what
    // existing consumers see today. Any silently-added field will trip this.
    $expectedRowKeys = ['id', 'url', 'type', 'size', 'ext', 'name', 'directory', 'base_url', 'sort_number', 'created_at', 'updated_at', 'deleted_at'];
    $actualRowKeys = array_keys($response->json('data.0'));
    sort($expectedRowKeys);
    sort($actualRowKeys);
    expect($actualRowKeys)->toBe($expectedRowKeys);
});

// ── paginate=false envelope ──────────────────────────────────────────────────

it('returns a {data: [...]} envelope (no paginator keys) when paginate=false', function () {
    seedBlob('blobs', 'a.pdf');
    seedBlob('blobs', 'b.pdf');
    seedBlob('docs', 'c.pdf');

    $response = $this->getJson('/api/blobs?paginate=false');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'url', 'name', 'directory'],
            ],
        ])
        ->assertJsonCount(3, 'data')
        ->assertJsonMissingPath('current_page')
        ->assertJsonMissingPath('last_page')
        ->assertJsonMissingPath('per_page')
        ->assertJsonMissingPath('total');
});

// ── withExistence=1 (FR-6) ───────────────────────────────────────────────────

it('attaches exists=true to every on-disk blob when withExistence=1', function () {
    seedBlob('blobs', 'a.pdf');
    seedBlob('docs', 'b.pdf');

    $response = $this->getJson('/api/blobs?paginate=false&withExistence=1');

    $response->assertOk();
    foreach ($response->json('data') as $row) {
        expect($row['exists'])->toBeTrue();
    }
});

it('attaches exists=false when withExistence=1 and the file is removed from disk', function () {
    $blob = seedBlob('blobs', 'a.pdf');
    $relativePath = ltrim(\Illuminate\Support\Str::after(parse_url($blob->url, PHP_URL_PATH), parse_url(Storage::disk('public')->url(''), PHP_URL_PATH)), '/');
    Storage::disk('public')->delete($relativePath);

    $response = $this->getJson('/api/blobs?paginate=false&withExistence=1');

    $response->assertOk()
        ->assertJsonPath('data.0.exists', false);
});

it('does NOT include exists in the response when withExistence is absent', function () {
    seedBlob('blobs', 'a.pdf');

    $response = $this->getJson('/api/blobs');

    $response->assertOk()
        ->assertJsonMissingPath('data.0.exists');
});

// ── reconcile=1 (FR-2, FR-2b, FR-3) ──────────────────────────────────────────

it('returns a non-paginated {data: [...]} envelope when reconcile=1', function () {
    seedBlob('blobs', 'a.pdf');

    $response = $this->getJson('/api/blobs?reconcile=1');

    $response->assertOk()
        ->assertJsonStructure(['data' => ['*' => ['url', 'status', 'key', 'tree_directory', 'exists']]])
        ->assertJsonMissingPath('current_page')
        ->assertJsonMissingPath('total');
});

it('marks blobs whose file exists as matched and adds synthetic disk_only entries for orphan files', function () {
    seedBlob('blobs', 'a.pdf');
    // Untracked file on disk (no blob row)
    Storage::disk('public')->put('untracked/orphan.txt', 'hi');

    $response = $this->getJson('/api/blobs?reconcile=1');

    $response->assertOk();
    $rows = $response->json('data');

    $matched = collect($rows)->where('status', 'matched');
    $diskOnly = collect($rows)->where('status', 'disk_only');

    expect($matched)->toHaveCount(1);
    expect($diskOnly)->toHaveCount(1);
    expect($diskOnly->first()['name'])->toBe('orphan.txt');
    expect($diskOnly->first()['id'])->toBeNull();
});

it('marks a blob whose file is missing on disk as db_only', function () {
    $blob = seedBlob('blobs', 'a.pdf');
    $relativePath = ltrim(\Illuminate\Support\Str::after(parse_url($blob->url, PHP_URL_PATH), parse_url(Storage::disk('public')->url(''), PHP_URL_PATH)), '/');
    Storage::disk('public')->delete($relativePath);

    $response = $this->getJson('/api/blobs?reconcile=1');

    $response->assertOk();
    $rows = collect($response->json('data'));
    $dbOnly = $rows->where('status', 'db_only');

    expect($dbOnly)->toHaveCount(1);
    expect($dbOnly->first()['exists'])->toBeFalse();
});