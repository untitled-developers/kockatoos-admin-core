<?php

namespace UntitledDevelopers\KockatoosAdminCore\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use UntitledDevelopers\KockatoosAdminCore\Models\Blob;
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
});

// ── store ────────────────────────────────────────────────────────────────────

it('stores a blob and returns the model with correct fields', function () {
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $blob = $this->blobService->store($file, 'blobs', 'My Document', 5);

    expect($blob->id)->not->toBeNull()
        ->and($blob->name)->toBe('My Document')
        ->and($blob->directory)->toBe('blobs')
        ->and($blob->type)->toBe('application/pdf')
        ->and($blob->size)->toBeGreaterThan(0)
        ->and($blob->sort_number)->toBe(5);

    Storage::disk('public')->assertExists('blobs/' . basename(parse_url($blob->url, PHP_URL_PATH)));
});

it('uses the original filename when no name is provided', function () {
    $file = UploadedFile::fake()->create('my-original-file.pdf', 100, 'application/pdf');

    $blob = $this->blobService->store($file, 'blobs');

    expect($blob->name)->toBe('my-original-file.pdf');
});

it('converts an image to webp on store', function () {
    $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

    $blob = $this->blobService->store($file, 'blobs');

    expect($blob->ext)->toBe('webp')
        ->and($blob->url)->toContain('.webp');
});

it('skips image optimization for non-image files', function () {
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $blob = $this->blobService->store($file, 'blobs');

    expect($blob->ext)->toBe('pdf')
        ->and($blob->url)->not->toContain('.webp');
});

// ── update ───────────────────────────────────────────────────────────────────

it('updates a blob with a new file', function () {
    $original = UploadedFile::fake()->create('original.pdf', 100, 'application/pdf');
    $blob = $this->blobService->store($original, 'blobs', 'Original');

    $newFile = UploadedFile::fake()->create('updated.pdf', 200, 'application/pdf');
    $updated = $this->blobService->update($blob, $newFile, 'blobs', 'Updated');

    expect($updated->name)->toBe('Updated');
    Storage::disk('public')->assertExists('blobs/' . basename(parse_url($updated->url, PHP_URL_PATH)));
});

it('deletes the old file after a successful update', function () {
    $original = UploadedFile::fake()->create('original.pdf', 100, 'application/pdf');
    $blob = $this->blobService->store($original, 'blobs');
    $oldFilename = basename(parse_url($blob->url, PHP_URL_PATH));

    $newFile = UploadedFile::fake()->create('updated.pdf', 200, 'application/pdf');
    $this->blobService->update($blob, $newFile);

    Storage::disk('public')->assertMissing('blobs/' . $oldFilename);
});

// ── delete ───────────────────────────────────────────────────────────────────

it('soft-deletes the blob record', function () {
    $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');
    $blob = $this->blobService->store($file, 'blobs');

    $this->blobService->delete($blob);

    expect(Blob::find($blob->id))->toBeNull();
    expect(Blob::withTrashed()->find($blob->id))->not->toBeNull();
});

it('removes the file from disk on delete', function () {
    $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');
    $blob = $this->blobService->store($file, 'blobs');
    $filename = basename(parse_url($blob->url, PHP_URL_PATH));

    $this->blobService->delete($blob);

    Storage::disk('public')->assertMissing('blobs/' . $filename);
});

it('restores the blob record when file deletion fails', function () {
    $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');
    $blob = $this->blobService->store($file, 'blobs');

    // Remove the file from disk to simulate a deletion failure
    Storage::disk('public')->delete('blobs/' . basename(parse_url($blob->url, PHP_URL_PATH)));

    expect(fn () => $this->blobService->delete($blob))->toThrow(\RuntimeException::class);

    expect(Blob::find($blob->id))->not->toBeNull();
});

// ── find ─────────────────────────────────────────────────────────────────────

it('finds a blob by id', function () {
    $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');
    $blob = $this->blobService->store($file, 'blobs');

    $found = $this->blobService->find($blob->id);

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($blob->id);
});

it('returns null for a non-existent blob id', function () {
    expect($this->blobService->find(9999))->toBeNull();
});

it('does not return soft-deleted blobs', function () {
    $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');
    $blob = $this->blobService->store($file, 'blobs');
    $id = $blob->id;

    $blob->delete();

    expect($this->blobService->find($id))->toBeNull();
});

// ── getByDirectory ───────────────────────────────────────────────────────────

it('returns blobs for a directory ordered by sort_number', function () {
    $this->blobService->store(UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'), 'docs', 'A', 2);
    $this->blobService->store(UploadedFile::fake()->create('b.pdf', 100, 'application/pdf'), 'docs', 'B', 1);

    $results = $this->blobService->getByDirectory('docs');

    expect($results)->toHaveCount(2)
        ->and($results[0]->sort_number)->toBe(1)
        ->and($results[1]->sort_number)->toBe(2);
});

it('excludes soft-deleted blobs from getByDirectory', function () {
    $blob = $this->blobService->store(UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'), 'docs');
    $this->blobService->store(UploadedFile::fake()->create('b.pdf', 100, 'application/pdf'), 'docs');

    $blob->delete();

    expect($this->blobService->getByDirectory('docs'))->toHaveCount(1);
});

// ── list ─────────────────────────────────────────────────────────────────────

it('returns a paginated list of blobs', function () {
    $this->blobService->store(UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'), 'blobs', 'Test');

    $result = $this->blobService->list();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->total())->toBe(1);
});

it('filters the list by name using searchFor', function () {
    $this->blobService->store(UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'), 'blobs', 'Invoice 2024');
    $this->blobService->store(UploadedFile::fake()->create('b.pdf', 100, 'application/pdf'), 'blobs', 'Contract 2024');

    $result = $this->blobService->list(['searchFor' => 'Invoice']);

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->name)->toBe('Invoice 2024');
});

it('filters the list by directory', function () {
    $this->blobService->store(UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'), 'invoices');
    $this->blobService->store(UploadedFile::fake()->create('b.pdf', 100, 'application/pdf'), 'contracts');

    $result = $this->blobService->list(['directory' => 'invoices']);

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->directory)->toBe('invoices');
});

it('filters the list by type', function () {
    $this->blobService->store(UploadedFile::fake()->image('photo.png', 100, 100), 'blobs');
    $this->blobService->store(UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'), 'blobs');

    $result = $this->blobService->list(['type' => 'image']);

    expect($result->total())->toBe(1);
});

it('sorts the list by the given field and direction', function () {
    $this->blobService->store(UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'), 'blobs', 'Banana');
    $this->blobService->store(UploadedFile::fake()->create('b.pdf', 100, 'application/pdf'), 'blobs', 'Apple');

    $result = $this->blobService->list(['sortBy' => 'name', 'sortAs' => 'asc']);

    expect($result->items()[0]->name)->toBe('Apple')
        ->and($result->items()[1]->name)->toBe('Banana');
});

// ── list (paginate=false branch, FR-8) ───────────────────────────────────────

it('returns a collection (not paginator) when paginate=false is passed', function () {
    $this->blobService->store(UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'), 'blobs');
    $this->blobService->store(UploadedFile::fake()->create('b.pdf', 100, 'application/pdf'), 'blobs');
    $this->blobService->store(UploadedFile::fake()->create('c.pdf', 100, 'application/pdf'), 'blobs');

    $result = $this->blobService->list(['paginate' => false]);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->not->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result)->toHaveCount(3);
});

it('ignores perPage when paginate=false', function () {
    foreach (range(1, 5) as $i) {
        $this->blobService->store(UploadedFile::fake()->create("doc-$i.pdf", 100, 'application/pdf'), 'blobs');
    }

    $result = $this->blobService->list(['paginate' => false, 'perPage' => 2]);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(5);
});

it('still returns a LengthAwarePaginator with perPage=15 when paginate flag is absent', function () {
    $this->blobService->store(UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'), 'blobs');

    $result = $this->blobService->list();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->perPage())->toBe(15);
});

// ── attachExistence (FR-6, FR-10) ────────────────────────────────────────────

// Note: Eloquent's Model has a built-in public $exists property used for
// persistence state. Our `exists` attribute is set via setAttribute() and
// lives in $attributes — visible to JSON serialization (which is what FR-6
// needs) but masked from `$blob->exists` PHP property access. Tests read it
// via getAttribute('exists') / toArray()['exists'].

it('attachExistence marks on-disk blobs as exists=true', function () {
    $a = $this->blobService->store(UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'), 'blobs');
    $b = $this->blobService->store(UploadedFile::fake()->create('b.pdf', 100, 'application/pdf'), 'docs');

    $this->blobService->attachExistence([$a, $b]);

    expect($a->getAttribute('exists'))->toBeTrue()
        ->and($b->getAttribute('exists'))->toBeTrue()
        ->and($a->toArray()['exists'])->toBeTrue();
});

it('attachExistence marks deleted-from-disk blobs as exists=false', function () {
    $blob = $this->blobService->store(UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'), 'blobs');
    $relativePath = ltrim(\Illuminate\Support\Str::after(parse_url($blob->url, PHP_URL_PATH), parse_url(Storage::disk('public')->url(''), PHP_URL_PATH)), '/');
    Storage::disk('public')->delete($relativePath);

    $this->blobService->attachExistence([$blob]);

    expect($blob->getAttribute('exists'))->toBeFalse();
});

it('attachExistence marks off-disk URLs as exists=null', function () {
    $blob = new Blob();
    $blob->name = 'external.png';
    $blob->directory = 'external';
    $blob->url = 'https://external.example.com/foo.png';

    $this->blobService->attachExistence([$blob]);

    expect($blob->getAttribute('exists'))->toBeNull();
});

it('attachExistence lists each distinct directory at most once (FR-10)', function () {
    foreach (range(1, 3) as $i) {
        $this->blobService->store(UploadedFile::fake()->create("a-$i.pdf", 100, 'application/pdf'), 'dir-a');
    }
    foreach (range(1, 3) as $i) {
        $this->blobService->store(UploadedFile::fake()->create("b-$i.pdf", 100, 'application/pdf'), 'dir-b');
    }

    $allBlobs = Blob::all();
    expect($allBlobs)->toHaveCount(6);

    // Spy on the disk's files() invocations — assert exactly one call per
    // distinct directory (2), not one per row (6).
    $diskSpy = \Mockery::spy(Storage::disk('public'));
    Storage::shouldReceive('disk')->with('public')->andReturn($diskSpy);

    $this->blobService->attachExistence($allBlobs);

    $diskSpy->shouldHaveReceived('files')->twice();
    foreach ($allBlobs as $blob) {
        expect($blob->getAttribute('exists'))->toBeTrue();
    }
});