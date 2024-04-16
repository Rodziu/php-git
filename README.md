# Pure PHP Git readonly client

Readonly Git client implementation, that allows one to clone or read Git repository data without native Git client installed.

## Prerequisites

- PHP >= 8.2,
- ext-zlib - to properly decompress git objects.

## Usage

You can interact with Git repository by instantiating `GitRepository` class.

```php
$gitRepository = new \Rodziu\Git\GitRepository('/path/to/your/project/.git');
```

### Get current HEAD

```php
$gitRepository = new \Rodziu\Git\GitRepository('/path/to/your/project/.git');
$head = $gitRepository->getHead();
$head->getCommitHash(); // commit hash that current head points to
$head->getBranch(); // current branch, null if head is detached
```

### Get list of available local or remote branches

```php
$gitRepository = new \Rodziu\Git\GitRepository('/path/to/your/project/.git');
$gitRepository->getBranches(); // returns an array of local branch names
$gitRepository->getBranches(remote: true); // returns an array of remote branch names
```

### Get list of available tags

```php
$gitRepository = new \Rodziu\Git\GitRepository('/path/to/your/project/.git');
foreach ($gitRepository->getTags() as $tag) {
    $tag; // \Rodziu\Git\Objects\Tag or \Rodziu\Git\Objects\AnnotatedTag
    $tag->getName();
    $tag->getTaggedObjectHash();
    ...
} 
```

### Iterate git log starting from given commit-ish

`GitRepository->getLog(?string $commitIsh = null): \Generator`

If argument is omitted, log will start from current HEAD.

```php
$gitRepository = new \Rodziu\Git\GitRepository('/path/to/your/project/.git');
foreach ($gitRepository->getLog() as $commit) {
    $commit; // \Rodziu\Git\Objects\Commit object 
    $commit->getMessage();
    $commit->getCommitDate();
    ...
}

// get origin/master branch log
$gitRepository->getLog('origin/master');
```

### Get commit object by hash

```php
$gitRepository = new \Rodziu\Git\GitRepository('/path/to/your/project/.git');
$gitRepository->getCommit('commit-hash'); // \Rodziu\Git\Objects\Commit object
...
```

### git describe

Give an object a human readable name based on an available ref

```php
$gitRepository = new \Rodziu\Git\GitRepository('/path/to/your/project/.git');
$gitRepository->describe(); // describe current HEAD with annotated tags
$gitRepository->describe('commit-ish', all: true); // describe given ref as in git describe --all
$gitRepository->describe('commit-ish', tags: true); // describe given ref as in git describe --tags
```

### git clone

Fetch repository info and all objects up to current HEAD, then checkout its working tree to `/destination/path/repository-name`.

```php
\Rodziu\Git\GitRepository::cloneRepository(
    'https://your.repository.url/repository-name.git',
    '/destination/path/'
);
```

### git checkout

Checkout working tree to given branch, tag or commit

```php
$gitRepository = new \Rodziu\Git\GitRepository('/path/to/your/project/.git');
$gitRepository->checkout('commit-ish');
```
