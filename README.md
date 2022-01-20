# Relay

A better way to create and manage complex batch job queues in Laravel.

Relay provides the following features:

 -  [Flatten nested batch callbacks](#flatten-nested-callbacks)
 -  [Store metadata and use it later to search for specific batches](#metadata)
 -  [Get batch progress considering previous and upcoming batches](#progress)
 -  [Get corresponding failed jobs](#failed-jobs)

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
        ])->name('Cleaning up')->dispatch();
    })->name('Updating project data')->dispatch();
})->name('Downloading')->dispatch();
```

Here is the same code written with Relay:

```php
use Agatanga\Relay\Facades\Relay;

Relay::chain('Downloading', [
        new DownloadSources($project),
        new DetectSettings($project),
    ])
    ->then('Updating project data', [
        new ReadStringFiles($project),
        new ReadSourceFiles($project),
    ])
    ->finally('Cleaning up', [
        new IgnoreKnownStrings($project),
        new RemoveSources($project),
    ])
    ->dispatch();
```

### Metadata

Before we start, you may want to know that Relay doesn't modify `job_batches` table
to store metadata. All data is stored inside the `name` column and limited to
255 chars. Here is how the name of the batch may look like with the metadata:

```
Cleaning up|[project:58][project.update:58][3/3]
```

Now, let's use the `meta` method to store additional information:

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

Then search for the batch and retrieve metadata value or name of the batch:

```php
use Agatanga\Relay\Facades\Relay;

Relay::whereMeta('causer', $userId)->all();
Relay::whereMeta('project.update', $id)->first()->meta('causer');
Relay::whereMeta('project.update', $id)->first()->name; // returns "clean" name
```

### Progress

Let's assume that the search query from the section above returned the first
batch (`then` and `finally` callbacks are not yet started). Relay takes this into
account and will return the progress within `0-33%` range.

```php
use Agatanga\Relay\Facades\Relay;

Relay::whereMeta('project.update', $id)->first()->progress; // only the last callback can return 100%
```

### Failed Jobs

When batch job fails, Laravel adds a failed job record to the `failed_jobs` table.

Relay allows you to retrieve these failed jobs:

```php
use Agatanga\Relay\Facades\Relay;

Relay::whereMeta('project.update', $id)->first()->failedJobs();

// or get the exception string of the last failed job

$batch = Relay::whereMeta('project.update', $id)->first();

if ($batch->failed) {
    echo $batch->exception;
}
```
