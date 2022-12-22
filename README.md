# Relay

A better way to create and manage complex batch job queues in Laravel:

```php
Relay::chain([new Job1, new Job2])
    ->batch([new Job3_1, new Job3_2])
    ->chain([new Job4, new Job5])
    ->through([new Middleware])
    ->dispatch();
```

**Main features**

 -  Relay doesn't create/modify DB tables
 -  Clean looking complex batches
 -  Ability to set middleware for multiple jobs
 -  Search for specific batch via metadata
 -  Monitor batch progress
 -  Search for failed jobs

## Installation

```bash
composer require agatanga/relay
```

## Usage Examples

### Flatten nested callbacks

Here is a flattened batches written with Relay:

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
    ->then('Updating project data', [
        new FixFalseUnusedStrings($project),
    ])
    ->finally('Cleaning up', [
        new IgnoreKnownStrings($project),
        new RemoveSources($project),
    ])
    ->through(new Middleware($project))
    ->dispatch();
```

<details>
    <summary>View the same code written without Relay</summary>

```php
Bus::batch([
    [
        new DownloadSources($project)->through(new Middleware($project)),
        new DetectSettings($project)->through(new Middleware($project)),
    ],
])->then(function (Batch $batch) use ($project) {
    Bus::batch([
        new ReadStringFiles($project)->through(new Middleware($project)),
        new ReadSourceFiles($project)->through(new Middleware($project)),
    ])->then(function (Batch $batch) use ($project) {
        Bus::batch([
            new FixFalseUnusedStrings($project)->through(new Middleware($project)),
        ])->finally(function (Batch $batch) use ($project) {
            Bus::batch([
                new IgnoreKnownStrings($project)->through(new Middleware($project)),
                new RemoveSources($project)->through(new Middleware($project)),
            ])->name('Cleaning up')->dispatch();
        })->name('Updating project data')->dispatch();
    })->name('Updating project data')->dispatch();
})->name('Downloading')->dispatch();
```
</details>

### Middleware

Relay allows you to set middleware for multiple jobs:

```php
Relay::chain([new Job1, new Job2])
    ->batch([new Job3_1, new Job3_2])
    ->chain([new Job4, new Job5], [new Middleware1])
    ->chain([new Job6, new Job7])
    ->through([new Middleware2]) // middleware for Job1-3, Job6-7
    ->dispatch();
```

### Metadata

Before we start, you may want to know that Relay doesn't modify `job_batches` table
to store metadata. All data is stored inside the `name` column and limited to
255 chars. Here is how the name of the batch may look like with the metadata:

```
Cleaning up|[project:58][project.update:58][3/3]
```

Now, let's see how to use `meta` method to store additional information:

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
Relay::whereMeta('project', $id)->first()->meta('causer');
Relay::whereMeta('project.update', $id)->first()->name; // returns clean name
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
