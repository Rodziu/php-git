<?php

declare(strict_types=1);

namespace Rodziu\Git\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Rodziu\Git\Exception\GitException;
use Rodziu\Git\Manager\GitRepositoryManager;
use Rodziu\Git\Object\GitRef;
use Rodziu\Git\Object\Head;
use Rodziu\Git\Object\Pack;
use Rodziu\Git\Util\FileSystemUtil;

readonly class GitUploadPackClient
{
    private Client $client;

    public function __construct(
        private GitRepositoryManager $manager,
        HandlerStack $handlerStack = null,
    ) {
        $this->client = new Client([
            'handler' => $handlerStack ?? HandlerStack::create(),
            'headers' => [
                'User-Agent' => 'git/2.20.1',
                'Content-Type' => 'application/x-git-upload-pack-request',
            ],
        ]);
    }

    /**
     * @return array{head: Head, refs: GitRef[]}
     */
    public function fetchRepositoryInfo(string $remote = 'origin'): array
    {
        $url = $this->getRemoteUrl('/info/refs?service=git-upload-pack', $remote);
        try {
            $response = $this->client->get($url);

            if ($response->getStatusCode() !== 200) {
                throw new GitException("Wrong response code `{$response->getStatusCode()}` from url `{$url}`");
            }
        } catch (\Throwable $e) {
            throw new GitException(
                "Could not get repository info from `{$url}`",
                0,
                $e
            );
        }

        return $this->parseRepositoryInfo($response->getBody()->getContents(), $remote);
    }

    private function getRemoteUrl(string $relativeUri, string $remote = 'origin'): UriInterface
    {
        $remoteConfig = $this->manager->getConfig()
            ->getSectionVariables('remote', $remote);

        if (!array_key_exists('url', $remoteConfig)) {
            throw new GitException('Could not determine remote url');
        }

        $uri = new Uri($remoteConfig['url'].$relativeUri);

        return Uri::fromParts(
            [
                "scheme" => 'https',
                "host" => $uri->getHost(),
                "query" => $uri->getQuery(),
                "path" => $uri->getPath(),
            ]
        );
    }

    /**
     * @return array{head: Head, refs: GitRef[]}
     */
    private function parseRepositoryInfo(string $repositoryInfoResponse, string $remote): array
    {
        $repositoryInfoResponse = str_replace('/heads/', "/remotes/{$remote}/", $repositoryInfoResponse);
        $lines = explode("\n", $repositoryInfoResponse);
        $lineCount = count($lines);
        $headRef = '';
        $repositoryInfo = [
            'head' => null,
            'refs' => []
        ];

        for ($i = 0; $i < $lineCount; ++$i) {
            $line = $lines[$i];

            if (!$headRef) {
                if (preg_match('#symref=HEAD:([^ ]+)#', $line, $match)) {
                    $headRef = $match[1];
                }
                continue;
            }

            $splitLine = explode(' ', substr($line, 4));

            if (count($splitLine) !== 2) {
                continue;
            }

            [$hash, $refFqn] = $splitLine;

            $splitRefFqn = explode('/', $refFqn, 3);

            if (count($splitRefFqn) !== 3) {
                continue;
            }

            [, $type, $refName] = $splitRefFqn;

            if ($headRef === $refFqn) {
                $repositoryInfo['head'] = new Head(
                    $hash,
                    $refName
                );
            }

            $annotatedTagHash = null;
            if (array_key_exists($i + 1, $lines) && str_ends_with($lines[$i + 1], '^{}')) {
                $i++;

                [$annotatedTagHash] = explode(' ', substr($lines[$i], 4));
            }

            $repositoryInfo['refs'][] = new GitRef(
                $type,
                $refName,
                $hash,
                $annotatedTagHash
            );
        }

        if (!$headRef) {
            throw new GitException('Failed to match HEAD in git-upload-pack info response');
        }

        return $repositoryInfo;
    }

    /**
     * @param string[] $haves
     * @param string[] $wants
     */
    public function fetchObjects(array $haves, array $wants, string $remote = 'origin'): void
    {
        if (count($haves) === 0) {
            $haves = ['0000000000000000000000000000000000000000'];
        }

        $want = implode(PHP_EOL, array_map(fn ($item) => "0032want $item", $wants));
        $have = implode(PHP_EOL, array_map(fn ($item) => "0032have $item", $haves));
        $url = $this->getRemoteUrl('/git-upload-pack', $remote);
        try {
            $response = $this->client->post($url, ['body' => "{$want}\n0000$have\n0009done\n"]);

            if ($response->getStatusCode() !== 200) {
                throw new GitException("Wrong response code `{$response->getStatusCode()}` from url `{$url}`");
            }
        } catch (GuzzleException $e) {
            throw new GitException(
                "Could not fetch objects from `{$url}`",
                0,
                $e
            );
        }

        $dir = $this->manager->resolvePath('objects', 'pack');

        FileSystemUtil::mkdirIfNotExists($dir);

        $tempPackPath = $dir.DIRECTORY_SEPARATOR.'pack-hash.pack';
        $pack = explode("\n", $response->getBody()->getContents());

        do {
            $line = array_shift($pack);
        } while (str_starts_with($line, '00'));

        array_unshift($pack, $line);
        $pack = implode("\n", $pack);

        file_put_contents(
            $tempPackPath,
            $pack
        );
        $hash = (new Pack($tempPackPath))
            ->getChecksum();
        rename(
            $tempPackPath,
            $dir.DIRECTORY_SEPARATOR."pack-$hash.pack"
        );
    }
}
