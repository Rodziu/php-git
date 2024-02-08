<?php

namespace Rodziu\Git;

use Rodziu\Git\Exception\GitException;
use Rodziu\Git\Objects\GitObject;
use Rodziu\Git\Objects\Tree;
use Rodziu\Git\Objects\TreeBranch;
use Rodziu\Git\Pack\Pack;

class GitClone
{
    protected readonly string $destination;
    protected readonly string $gitPath;

    protected function __construct(
        protected readonly string $url,
        string $destination
    ) {
        if (preg_match('#/([^/]+).git$#', $url, $match)) {
            $repositoryName = $match[1];
        } else {
            throw new GitException("Wrong url format!");
        }

        $destination = $destination.DIRECTORY_SEPARATOR.$repositoryName;

        if (is_dir($destination)) {
            throw new GitException("`$destination` already exists!");
        }

        $this->destination = $destination;
        $this->gitPath = $destination.DIRECTORY_SEPARATOR.'.git';
        mkdir($this->gitPath, 0755, true);
    }

    protected function getRepositoryInfo(): array
    {
        $response = $this->uploadPackRequest("{$this->url}/info/refs?service=git-upload-pack");

        if ($response['info']['http_code'] !== 200) {
            throw new GitException(
                "Could not get repository info from `{$this->url}`"
            );
        }

        $repositoryInfo = $this->parseRepositoryInfo($response['data']);

        foreach ($repositoryInfo as $filePath => $content) {
            $filePath = str_replace('/', DIRECTORY_SEPARATOR, $filePath);
            $fullPath = $this->gitPath.DIRECTORY_SEPARATOR.$filePath;
            $dir = dirname($fullPath);

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($fullPath, $content.PHP_EOL);
        }

        return $repositoryInfo;
    }

    protected function uploadPackRequest(string $url, ?string $postData = null): array
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

    protected function parseRepositoryInfo(string $gitUploadPackInfoResponse): array
    {
        $responseLines = explode("\n", $gitUploadPackInfoResponse);
        $lastLine = 0;
        $refs = [];

        foreach ($responseLines as $k => $responseLine) {
            if (preg_match('#symref=HEAD:([^ ]+)#', $responseLine, $match)) {
                $refs['HEAD'] = "ref: {$match[1]}";
                $lastLine = $k;
            }
        }

        if (!array_key_exists('HEAD', $refs)) {
            throw new GitException('Failed to match HEAD in git-upload-pack info response');
        }

        $lineCount = count($responseLines);
        for ($i = $lastLine + 1; $i < $lineCount; $i++) {
            $line = explode(" ", substr($responseLines[$i], 4));

            if (count($line) !== 2) {
                continue;
            }

            [$hash, $refName] = $line;
            $refs[$refName] = $hash;
        }

        return $refs;
    }

    protected function fetchObjects(string $head): void
    {
        $response = $this->uploadPackRequest(
            "{$this->url}/git-upload-pack",
            "0032want $head\n00000032have 0000000000000000000000000000000000000000\n0009done\n"
        );

        if ($response['info']['http_code'] !== 200) {
            throw new GitException(
                "Could not fetch objects from `{$this->url}`"
            );
        }

        $dir = implode(DIRECTORY_SEPARATOR, [
            $this->gitPath, 'objects', 'pack'
        ]);
        mkdir(
            $dir,
            0755, true
        );

        $tempPackPath = $dir.DIRECTORY_SEPARATOR.'pack-hash.pack';
        file_put_contents(
            $tempPackPath,
            explode("\n", $response['data'], 2)[1]
        );
        $hash = (new Pack($tempPackPath))
            ->getChecksum();
        rename(
            $tempPackPath,
            $dir.DIRECTORY_SEPARATOR."pack-$hash.pack"
        );
    }

    protected function checkout(string $head): void
    {
        $git = new GitRepository($this->gitPath);
        $tree = Tree::fromGitObject($git->getObject($git->getCommit($head)->tree));

        foreach ($tree->walkRecursive($git) as [$path, $branch, $object]) {
            /**
             * @var string $path
             * @var TreeBranch $branch
             * @var GitObject $object
             */
            $path = $this->destination.DIRECTORY_SEPARATOR.$path.$branch->getName();

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
            $repositoryInfo = $clone->getRepositoryInfo();
            $headRef = str_replace('ref: ', '', $repositoryInfo['HEAD']);
            $head = $repositoryInfo[$headRef];
            $clone->fetchObjects($head);
            $clone->checkout($head);
            umask($umask);
        } catch (GitException $e) {
            umask($umask);
            throw $e;
        }
    }
}
