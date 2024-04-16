<?php

declare(strict_types=1);

namespace Rodziu\Git\Service;

use Rodziu\Git\Exception\GitException;
use Rodziu\Git\Object\Pack;

class GitClone
{
    private readonly string $repositoryPath;

    public function __construct(
        private readonly string $url,
        string $destinationPath
    ) {
        if (is_dir($destinationPath)) {
            throw new GitException("`$destinationPath` already exists!");
        }

        $this->repositoryPath = $destinationPath.DIRECTORY_SEPARATOR.'.git';
    }

    public function getRepositoryPath(): string
    {
        return $this->repositoryPath;
    }

    public static function getRepositoryNameFromUrl(string $url): ?string
    {
        if (preg_match('#/([^/]+).git$#', $url, $match)) {
            return $match[1];
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public function fetchRepositoryInfo(): array
    {
        $response = $this->uploadPackRequest("{$this->url}/info/refs?service=git-upload-pack");

        if ($response['info']['http_code'] !== 200) {
            throw new GitException(
                "Could not get repository info from `{$this->url}`"
            );
        }

        $repositoryInfo = $this->parseRepositoryInfo($response['data']);

        if (!is_dir($this->repositoryPath)) {
            mkdir($this->repositoryPath, 0755, true);
        }

        foreach ($repositoryInfo as $filePath => $content) {
            $filePath = str_replace('/', DIRECTORY_SEPARATOR, $filePath);
            $fullPath = $this->repositoryPath.DIRECTORY_SEPARATOR.$filePath;
            $dir = dirname($fullPath);

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($fullPath, $content.PHP_EOL);
        }

        return $repositoryInfo;
    }

    /**
     * @return array{data: string, info: mixed}
     */
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

    /**
     * @return array<string, string>
     */
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

    public function fetchObjects(string $head): void
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
            $this->repositoryPath, 'objects', 'pack'
        ]);
        mkdir(
            $dir,
            0755,
            true
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
}
