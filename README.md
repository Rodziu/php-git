# Pure PHP GIT readonly client

Readonly GIT client implementation, that allows one to read GIT repository data without native GIT client installed.

## Prerequisites

- PHP ^7.1,
- ext-zlib - to properly decompress git objects.

## Features

- get current HEAD,
- get list of available branches,
- get list of available tags,
- get tip (last commit) of given branch,
- get full history of given branch (commit list),
- get commit by hash,
- read git objects (commit, tree, blob, tag),
- clone git repository from remote.