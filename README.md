# Pure PHP Git readonly client

Readonly Git client implementation, that allows one to read Git repository data without native Git client installed.

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
$head->commitHash; // commit hash that current head points to
$head->branch; // current branch, null if head is detached
```

### Get list of available (local) branches

```php
$gitRepository = new \Rodziu\Git\GitRepository('/path/to/your/project/.git');
$gitRepository->getBranches(); // returns an array of local branch names
```

### Get list of available tags

```php
$gitRepository = new \Rodziu\Git\GitRepository('/path/to/your/project/.git');
foreach ($gitRepository->getTags() as $tag) {
    $tag; // \Rodziu\Git\Objects\Tag or \Rodziu\Git\Objects\AnnotatedTag
    $tag->tag;
    $tag->taggedObjectHash;
    ...
} 
```

### Get tip (latest commit hash) of given branch

```php
$gitRepository = new \Rodziu\Git\GitRepository('/path/to/your/project/.git');
$gitRepository->getTip('master');
```

### Iterate log of given commit hash or branch

`GitRepository->getLog(?string $commitHash = null, ?string $branch = null): \Generator`

If both arguments are omitted, log will start from current HEAD.

```php
$gitRepository = new \Rodziu\Git\GitRepository('/path/to/your/project/.git');
foreach ($gitRepository->getLog() as $commit) {
    $commit; // \Rodziu\Git\Objects\Commit object 
    $commit->message;
    $commit->commitDate;
}
```

### Get commit object by hash

```php
$gitRepository = new \Rodziu\Git\GitRepository('/path/to/your/project/.git');
$gitRepository->getCommit('commit-hash'); // \Rodziu\Git\Objects\Commit object
...
```

### Read Git objects

```php
$gitRepository = new \Rodziu\Git\GitRepository('/path/to/your/project/.git');
$gitObject = $gitRepository->getObject('hash');
$gitObject->getTypeName(); // commit/tree/blob/tag
$gitObject->getSha1(); // hash
$gitObject->getSize(); // object size
$gitObject->getData(); // object contents
```

### Clone Git repository from remote

Fetch repository info and all objects up to current HEAD, then checkout its working tree.

```php
\Rodziu\Git\GitClone::cloneRepository(
    'https://your.repository.url/repository-name.git',
    '/destination/path/'
);
```
