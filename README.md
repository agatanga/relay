# Relay

A better way to create and manage complex batch job queues in Laravel:

 -  [Cleanup your batch callbacks hell](#cleanup-callbacks-hell)
 -  [Store metadata and use it later to search for specific batches](#metadata)
 -  [Calculate progress considering previous and upcoming batches](#progress)

## Installation

```bash
composer require agatanga/relay
```

## Usage Examples

### Cleanup callbacks hell

Let's say you have the following job batches code:

```php
Bus::batch([
    [
        new DownloadSources($project),
        new DetectSettings($project),
    ],
])->then(function (Batch $batch) use ($project) {
    Bus::batch([
        new ReadStringFiles($project),
        new ReadSourceFiles($project),
    ])->finally(function (Batch $batch) use ($project) {
        Bus::batch([
            new IgnoreKnownStrings($project),
            new RemoveSources($project),
        ])->name('Update Project (3 of 3)')->dispatch();
    })->name('Update Project (2 of 3)')->dispatch();
})->name('Update Project (1 of 3)')->dispatch();
```

Here is the same code written with Relay:

```php
use Agatanga\Relay\Facades\Relay;

Relay::chain([
        new DownloadSources($project),
        new DetectSettings($project),
    ])
    ->then([
        new ReadStringFiles($project),
        new ReadSourceFiles($project),
    ])
    ->finally([
        new IgnoreKnownStrings($project),
        new RemoveSources($project),
    ])
    ->name('Update Project (:current of :total)')
    ->dispatch();
```

### Metadata

Use the `meta` method to store additional information about your batch queue:

```php
use Agatanga\Relay\Facades\Relay;

Relay::chain([
        new DownloadSources($project),
        new DetectSettings($project),
    ])
    ->then([
        new ReadStringFiles($project),
        new ReadSourceFiles($project),
    ])
    ->finally([
        new IgnoreKnownStrings($project),
        new RemoveSources($project),
    ])
    ->name('Update Project (:current of :total)')
    ->meta('project.update', $project->id)
    ->meta('causer', auth()->user()->id)
    ->dispatch();
```

Then search for the batch using this data:

```php
use Agatanga\Relay\Facades\Relay;

Relay::whereMeta('project', $id)->first();
Relay::whereMeta('causer', $id)->all();
```

### Progress

Let's assume that the search query from the section above returned the first
batch. This means that there are two more upcoming batches that are not started
yet. Relay takes this into account and the progress of the first one will be
recalculated to fit into the `0-33%` range:

```php
use Agatanga\Relay\Facades\Relay;

Relay::whereMeta('project.update', $id)->first()->progress(); // only the final batch can return 100%
```
