<?php

namespace Rodziu\Git;

use Rodziu\Git\Pack\Pack;
use Rodziu\Git\Types\GitObject;
use Rodziu\Git\Types\Tree;
use Rodziu\Git\Types\TreeBranch;

class GitClone
{
    /**
     * @var string
     */
    protected $url;
    /**
     * @var string
     */
    protected $destination;

    protected function __construct(string $url, string $destination)
    {
        if (preg_match('#/([^/]+).git$#', $url, $match)) {
            $repoName = $match[1];
        } else {
            throw new GitException("Wrong url format!");
        }
        $destination = $destination.DIRECTORY_SEPARATOR.$repoName;
        if (is_dir($destination)) {
            throw new GitException("`$destination` already exists!");
        }
        $this->url = $url;
        $this->destination = $destination;
        mkdir($destination, 0755, true);
        mkdir($destination.DIRECTORY_SEPARATOR.'.git', 0755, true);
    }

    protected function getRepositoryInfo(): string
    {
        $response = $this->uploadPackRequest("{$this->url}/info/refs?service=git-upload-pack");
        if ($response['info']['http_code'] === 200) {
            $responseLines = explode("\n", $response['data']);
            $lines = count($responseLines);
            $gitPath = $this->destination.DIRECTORY_SEPARATOR.'.git'.DIRECTORY_SEPARATOR;
            $lastLine = 0;
            $head = null;
            foreach ($responseLines as $k => $r) {
                if (preg_match('#symref=HEAD:([^ ]+)#', $r, $match)) {
                    $head = $match[1];
                    file_put_contents(
                        $gitPath.'HEAD',
                        "ref: $head\n"
                    );
                    $lastLine = $k;
                }
            }
            if ($head === null) {
                throw new GitException("Failed to match HEAD");
            }
            for ($i = $lastLine + 1; $i < $lines; $i++) {
                $line = explode(" ", substr($responseLines[$i], 4));
                if (count($line) === 2) {
                    $dir = $gitPath.dirname($line[1]);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    file_put_contents($gitPath.$line[1], $line[0].PHP_EOL);
                    if ($line[1] == $head) {
                        $head = $line[0];
                    }
                }
            }
            return $head;
        } else {
            throw new GitException(
                "Could not get repository info from `{$this->url}`"
            );
        }
    }

    private function uploadPackRequest(string $url, ?string $postData = null): array
    {
        $handle = curl_init($url);
        $options = [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_REFERER => '',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER => false,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_ENCODING => 1,
            CURLOPT_USERAGENT => 'git/2.20.1',
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-git-upload-pack-request'],
        ];

        if ($postData) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $postData;
        }

        foreach ($options as $option => $value) {
            curl_setopt($handle, $option, $value);
        }
        $data = curl_exec($handle);
        $info = curl_getinfo($handle);
        curl_close($handle);

        return [
            'data' => $data,
            'info' => $info
        ];
    }

    protected function fetchObjects(string $head): void
    {
        $response = $this->uploadPackRequest(
            "{$this->url}/git-upload-pack",
            "0032want $head\n00000032have 0000000000000000000000000000000000000000\n0009done\n"
        );

        if ($response['info']['http_code'] === 200) {
            mkdir(
                $dir = implode(DIRECTORY_SEPARATOR, [
                    $this->destination, '.git', 'objects'
                ]),
                0755, true
            );
            mkdir(
                $dir .= DIRECTORY_SEPARATOR.'pack',
                0755, true
            );
            file_put_contents(
                $dir.DIRECTORY_SEPARATOR.'pack-hash.pack',
                explode("\n", $response['data'], 2)[1]
            );
            $hash = (new Pack($dir.DIRECTORY_SEPARATOR.'pack-hash.pack'))->getChecksum();
            rename(
                $dir.DIRECTORY_SEPARATOR.'pack-hash.pack',
                $dir.DIRECTORY_SEPARATOR."pack-$hash.pack"
            );
        } else {
            throw new GitException(
                "Could not fetch objects from `{$this->url}`"
            );
        }
    }

    protected function checkout(string $repositoryPath, string $head): void
    {
        $git = new GitRepository($repositoryPath);
        $tree = Tree::fromGitObject($git->getObject($git->getCommit($head)->tree));
        /** @var TreeBranch $branch */
        foreach ($tree->walkRecursive($git) as [$path, $branch, $object]) {
            /**
             * @var string $path
             * @var TreeBranch $branch
             * @var GitObject $object
             */
            $path = $this->destination.DIRECTORY_SEPARATOR.$path.$branch->getName();
            var_dump($path);
            if ($branch->getMode() === 0) {
                mkdir($path, 0755);
            } else {
                file_put_contents($path, $object->getData());
                if ($branch->getMode() === 755) {
                    chmod($path, 0755);
                }
            }
        }
    }

    public static function cloneRepository(string $url, string $destination): void
    {
        $umask = umask(0);
        try {
            $clone = new self($url, $destination);
            $head = $clone->getRepositoryInfo();
            $clone->fetchObjects($head);
            $clone->checkout($clone->destination.DIRECTORY_SEPARATOR.'.git', $head);
            umask($umask);
        } catch (GitException $e) {
            umask($umask);
            throw $e;
        }
    }
}
