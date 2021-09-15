# Relay

A better way to create complex batch job queues in Laravel.

## Installation

```bash
composer require agatanga/relay
```

## Usage Example

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
    ])->then(function (Batch $batch) use ($project) {
        Bus::batch([
            new IgnoreKnownStrings($project),
            new RemoveSources($project),
        ])->name('Update Project (3 of 3)')->dispatch();
    })->name('Update Project (2 of 3)')->dispatch();
})->name('Update Project (1 of 3)')->dispatch();
```

Here is the same code written with Relay:

```php
(new \Agatanga\Relay\Relay)
    ->name('Update Project (:current of :total)')
    ->chain([
        new DownloadSources($project),
        new DetectSettings($project),
    ])
    ->batch([
        new ReadStringFiles($project),
        new ReadSourceFiles($project),
    ])
    ->batch([
        new IgnoreKnownStrings($project),
        new RemoveSources($project),
    ])
    ->dispatch();
```
