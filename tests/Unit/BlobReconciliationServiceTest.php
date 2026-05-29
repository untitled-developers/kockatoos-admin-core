<?php

namespace UntitledDevelopers\KockatoosAdminCore\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use UntitledDevelopers\KockatoosAdminCore\Services\BlobReconciliationService;
use UntitledDevelopers\KockatoosAdminCore\Services\BlobService;
use UntitledDevelopers\KockatoosAdminCore\Services\FileService;
use UntitledDevelopers\KockatoosAdminCore\Services\ImageService;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->fileService = new FileService('public');
    $this->blobService = new BlobService($this->fileService, new ImageService());
    $this->reconcile = new BlobReconciliationService($this->fileService);
});

// ── T1 / FR-8 — disk getter ─────────────────────────────────────────────────

it('exposes the disk via FileService::disk()', function () {
    expect((new FileService('public'))->disk())->toBe('public');
    expect((new FileService('custom'))->disk())->toBe('custom');
});

// ── T2 / FR-6 / SC-5 — key derivation ───────────────────────────────────────

it('derives the relative key from a disk URL by round-tripping through url()', function () {
    // Whatever the disk's URL shape is, the helper must invert it (FR-6 / SC-5).
    $key = 'products/Decor/STUCCO MASS.webp';
    $url = Storage::disk('public')->url($key);

    expect($this->fileService->relativePathFromUrl($url))->toBe($key);
});

it('returns null for a URL on a different host/bucket (unverifiable)', function () {
    $foreign = 'https://different-host.example.com/some/foreign/object.jpg';

    expect($this->fileService->relativePathFromUrl($foreign))->toBeNull();
});

// ── T3 / FR-1, FR-2, FR-3 / SC-1..SC-3 — classification ─────────────────────

it('classifies a blob whose file exists on disk as matched', function () {
    $blob = $this->blobService->store(
        UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'),
        'blobs',
        'a.pdf'
    );

    $reconciled = $this->reconcile->reconcile([$blob]);

    expect($reconciled)->toHaveCount(1);
    expect($reconciled[0]['status'])->toBe('matched');
    expect($reconciled[0]['exists'])->toBeTrue();
    expect($reconciled[0]['key'])->not->toBeNull();
});

it('classifies a blob whose file was deleted as db_only', function () {
    $blob = $this->blobService->store(
        UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'),
        'blobs',
        'a.pdf'
    );
    $key = $this->fileService->relativePathFromUrl($blob->url);
    Storage::disk('public')->delete($key);

    $reconciled = $this->reconcile->reconcile([$blob]);

    expect($reconciled[0]['status'])->toBe('db_only');
    expect($reconciled[0]['exists'])->toBeFalse();
});

it('surfaces a file on disk with no blob row as a synthetic disk_only entry', function () {
    Storage::disk('public')->put('untracked/orphan.txt', 'hello');

    $reconciled = $this->reconcile->reconcile([]);

    $diskOnly = array_values(array_filter($reconciled, fn ($r) => $r['status'] === 'disk_only'));
    expect($diskOnly)->toHaveCount(1);
    expect($diskOnly[0]['id'])->toBeNull();
    expect($diskOnly[0]['name'])->toBe('orphan.txt');
    expect($diskOnly[0]['tree_directory'])->toBe('untracked');
    expect($diskOnly[0]['exists'])->toBeTrue();
});

it('classifies a blob whose URL is on a foreign host as unverifiable', function () {
    // Build a "blob" with a foreign URL directly (without going through upload).
    $fakeBlob = [
        'id' => 999,
        'url' => 'https://cdn.example.com/elsewhere/foo.jpg',
        'name' => 'foo.jpg',
        'directory' => 'elsewhere',
        'type' => 'image/jpeg',
        'size' => 100,
        'ext' => 'jpg',
        'sort_number' => 0,
        'created_at' => null,
        'updated_at' => null,
        'base_url' => null,
        'deleted_at' => null,
    ];

    $reconciled = $this->reconcile->reconcile([$fakeBlob]);

    expect($reconciled[0]['status'])->toBe('unverifiable');
    expect($reconciled[0]['exists'])->toBeNull();
    expect($reconciled[0]['key'])->toBeNull();
});

// ── FR-10 / SC-4 — bounded listing ──────────────────────────────────────────

it('takes a single listing pass over the disk regardless of file count', function () {
    Storage::disk('public')->put('a/1.txt', '1');
    Storage::disk('public')->put('a/2.txt', '2');
    Storage::disk('public')->put('b/3.txt', '3');
    Storage::disk('public')->put('b/c/4.txt', '4');
    Storage::disk('public')->put('5.txt', '5');

    // Spy: snapshot() is the single entry point that performs the listing.
    // Reconciling many files must call it exactly once.
    $service = new class($this->fileService) extends BlobReconciliationService {
        public int $snapshotCalls = 0;

        public function snapshot(): ?array
        {
            $this->snapshotCalls++;
            return parent::snapshot();
        }
    };

    $reconciled = $service->reconcile([]);

    expect($service->snapshotCalls)->toBe(1);
    expect(count($reconciled))->toBeGreaterThanOrEqual(5);
});

// ── Decision 9 — graceful degradation ───────────────────────────────────────

it('degrades gracefully when the disk listing fails', function () {
    $blob = $this->blobService->store(
        UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'),
        'blobs',
        'a.pdf'
    );

    // Force snapshot() to fail.
    $failing = new class($this->fileService) extends BlobReconciliationService {
        public function snapshot(): ?array
        {
            return null;
        }
    };

    $reconciled = $failing->reconcile([$blob]);

    expect($reconciled[0]['status'])->toBe('unverifiable');
    expect($reconciled[0]['exists'])->toBeNull();
    expect($reconciled[0]['error'])->toBe('disk_unreachable');
});

// ── attachExistence helper ──────────────────────────────────────────────────

it('attaches exists flags using a shared snapshot', function () {
    $blobA = $this->blobService->store(
        UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'),
        'blobs',
        'a.pdf'
    );
    $blobB = $this->blobService->store(
        UploadedFile::fake()->create('b.pdf', 100, 'application/pdf'),
        'blobs',
        'b.pdf'
    );
    // Delete b's file
    Storage::disk('public')->delete($this->fileService->relativePathFromUrl($blobB->url));

    $snap = $this->reconcile->snapshot();
    $result = $this->reconcile->attachExistence([$blobA, $blobB], $snap);

    expect($result[0]['exists'])->toBeTrue();
    expect($result[1]['exists'])->toBeFalse();
});
