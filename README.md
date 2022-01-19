# Relay

A better way to create and manage complex batch job queues in Laravel.

Relay provides the following features:

 -  [Flatten nested batch callbacks](#flatten-nested-callbacks)
 -  [Store metadata and use it later to search for specific batches](#metadata)
 -  [Get batch progress considering previous and upcoming batches](#progress)

## Installation

```bash
composer require agatanga/relay
```

## Usage Examples

### Flatten nested callbacks

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

You can use the `meta` method to store additional information about your batch queue:

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

Then search for the batch:

```php
use Agatanga\Relay\Facades\Relay;

Relay::whereMeta('causer', $userId)->all();
Relay::whereMeta('project.update', $id)->first()->meta('causer');
```

### Progress

Let's assume that the search query from the section above returned the first
batch (`then` and `finally` callbacks are not yet started). Relay takes this into
account and will return the progress within `0-33%` range.

```php
use Agatanga\Relay\Facades\Relay;

Relay::whereMeta('project.update', $id)->first()->progress(); // only the last callback can return 100%
```
