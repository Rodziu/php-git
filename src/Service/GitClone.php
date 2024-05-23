<?php

declare(strict_types=1);

namespace Rodziu\Git\Service;

use Rodziu\Git\Exception\GitException;
use Rodziu\Git\GitRepository;
use Rodziu\Git\Manager\GitRepositoryManager;
use Rodziu\Git\Util\FileSystemUtil;

abstract class GitClone
{
    public static function cloneRepository(
        string $url,
        string $destinationPath,
        bool $parseRepositoryName = true,
        bool $checkout = true
    ): GitRepository {
        if ($parseRepositoryName) {
            $repositoryName = GitClone::getRepositoryNameFromUrl($url);

            if ($repositoryName === null) {
                throw new GitException("Could not parse repository name from `$url`");
            }

            $destinationPath = $destinationPath.DIRECTORY_SEPARATOR.$repositoryName;
        }

        $umask = umask(0);

        if (is_dir($destinationPath)) {
            throw new GitException("Destination directory `$destinationPath` already exists.");
        }

        $repositoryPath = $destinationPath.DIRECTORY_SEPARATOR.'.git';

        try {
            self::initializeRepository($repositoryPath, $url);

            $manager = new GitRepositoryManager($repositoryPath);
            $fetch = $manager->getGitFetch();
            $repositoryInfo = $fetch();

            if ($checkout) {
                $manager->getGitCheckout()
                    ->checkout(
                        $repositoryInfo['head']->getBranch()
                    );
            }

            umask($umask);
            return new GitRepository($repositoryPath);
        } catch (GitException $e) {
            umask($umask);
            throw $e;
        }
    }

    public static function getRepositoryNameFromUrl(string $url): ?string
    {
        if (preg_match('#/([^/]+)$#', $url, $match)) {
            return preg_replace('#\.git$#', '', $match[1]);
        }

        return null;
    }

    private static function initializeRepository(string $repositoryPath, string $url): void
    {
        $dirsToCreate = ['objects', 'refs/heads', 'refs/remotes', 'refs/tags'];

        foreach ($dirsToCreate as $dir) {
            FileSystemUtil::mkdirIfNotExists($repositoryPath.DIRECTORY_SEPARATOR.$dir);
        }

        $indent = str_pad('', 8);

        $configData = [
            '[core]',
            "{$indent}repositoryformatversion = 0",
            "{$indent}filemode = true",
            "{$indent}bare = true",
            "{$indent}logallrefupdates = true",
            "[remote \"origin\"]",
            "{$indent}url = {$url}",
            "{$indent}fetch = +refs/heads/*:refs/remotes/origin/*",
        ];

        file_put_contents(
            $repositoryPath.DIRECTORY_SEPARATOR.'config',
            implode(PHP_EOL, $configData)
        );
    }
}
