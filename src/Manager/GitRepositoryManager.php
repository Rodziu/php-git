<?php
declare(strict_types=1);

namespace Rodziu\Git\Manager;

use Rodziu\Git\Exception\GitException;
use Rodziu\Git\Object\Pack;
use Rodziu\Git\Service\GitCheckout;
use Rodziu\Git\Service\GitConfig;
use Rodziu\Git\Service\GitDescribe;
use Rodziu\Git\Service\GitFetch;
use Rodziu\Git\Service\GitLog;
use Rodziu\Git\Service\GitObjectReader;
use Rodziu\Git\Service\GitRefReader;
use Rodziu\Git\Service\GitUploadPackClient;

class GitRepositoryManager
{
    private readonly string $repositoryPath;
    /**
     * @var array<string, object>
     */
    private array $dependencies = [];
    /**
     * @var Pack[]
     */
    private ?array $packs = null;

    public function __construct(
        string $repositoryPath
    ) {
        $repositoryPath = rtrim($repositoryPath, DIRECTORY_SEPARATOR);

        if (basename($repositoryPath) !== '.git' || !file_exists($repositoryPath)) {
            throw new GitException("$repositoryPath is not a git repository!");
        }

        $this->repositoryPath = $repositoryPath;
    }

    public function getRepositoryPath(): string
    {
        return $this->repositoryPath;
    }

    public function resolvePath(string ...$pathParts): string
    {
        array_unshift($pathParts, $this->repositoryPath);
        return implode(DIRECTORY_SEPARATOR, $pathParts);
    }

    /**
     * @return Pack[]|null
     */
    public function getPacks(): ?array
    {
        return $this->packs;
    }

    /**
     * @param Pack[]|null $packs
     */
    public function setPacks(?array $packs): GitRepositoryManager
    {
        $this->packs = $packs;
        return $this;
    }

    private function getDependency(string $class): object
    {
        if (!array_key_exists($class, $this->dependencies)) {
            $this->dependencies[$class] = new $class($this);
        }

        return $this->dependencies[$class];
    }

    public function getObjectReader(): GitObjectReader
    {
        return $this->getDependency(GitObjectReader::class);
    }

    public function getRefReader(): GitRefReader
    {
        return $this->getDependency(GitRefReader::class);
    }

    public function getGitLog(): GitLog
    {
        return $this->getDependency(GitLog::class);
    }

    public function getGitDescribe(): GitDescribe
    {
        return $this->getDependency(GitDescribe::class);
    }

    public function getConfig(): GitConfig
    {
        return $this->getDependency(GitConfig::class);
    }

    public function getUploadPackClient(): GitUploadPackClient
    {
        return $this->getDependency(GitUploadPackClient::class);
    }

    public function getGitFetch(): GitFetch
    {
        return $this->getDependency(GitFetch::class);
    }

    public function getGitCheckout(): GitCheckout
    {
        return $this->getDependency(GitCheckout::class);
    }
}
