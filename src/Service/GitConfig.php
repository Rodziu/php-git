<?php
declare(strict_types=1);

namespace Rodziu\Git\Service;

use Rodziu\Git\Exception\GitException;
use Rodziu\Git\Manager\GitRepositoryManager;

readonly class GitConfig
{
    public function __construct(

        private GitRepositoryManager $manager,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function getSectionVariables(string $section, ?string $name = null): array
    {
        $configPath = $this->manager->resolvePath('config');

        if (!file_exists($configPath)) {
            throw new GitException('Config file does not exist');
        }

        $fileHandle = fopen($configPath, 'r');
        $sectionHeader = $this->getSectionHeader($section, $name);
        $foundHeader = false;
        $variables = [];

        while (($line = fgets($fileHandle)) !== false) {
            if (str_starts_with($line, $sectionHeader)) {
                $foundHeader = true;
                continue;
            }

            if (!$foundHeader) {
                continue;
            }

            if (str_starts_with($line, '[')) {
                break;
            }

            $exp = explode('=', $line, 2);

            if (count($exp) !== 2) {
                break;
            }

            $variables[trim($exp[0])] = trim($exp[1]);
        }

        return $variables;
    }

    private function getSectionHeader(string $section, ?string $name = null): string
    {
        $sectionHeader = "[{$section}";

        if ($name !== null) {
            $sectionHeader .= " \"{$name}\"";
        }

        $sectionHeader .= "]";

        return $sectionHeader;
    }
}
