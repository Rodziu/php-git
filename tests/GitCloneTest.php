<?php

namespace Rodziu\Git;

use PHPUnit\Framework\TestCase;

class GitCloneTest extends TestCase
{
    private readonly GitClone $gitClone;

    public function setUp(): void
    {
        parent::setUp();
        $this->gitClone = new class() extends GitClone {
            public function __construct()
            {
                parent::__construct('https://github.com/Rodziu/php-git.git', '');
            }

            public function parseRepositoryInfoProxy(string $gitUploadPackInfoResponse): array
            {
                return $this->parseRepositoryInfo($gitUploadPackInfoResponse);
            }
        };
    }

    public function testParseRepositoryInfo(): void
    {
        /** @noinspection SpellCheckingInspection */
        $gitUploadPackInfoResponse = <<<EOF
001e# service=git-upload-pack
00000155f65e820a9211d8076618f5dee1c8ca2d79759664 HEAD multi_ack thin-pack side-band side-band-64k ofs-delta shallow deepen-since deepen-not deepen-relative no-progress include-tag multi_ack_detailed allow-tip-sha1-in-want allow-reachable-sha1-in-want no-done symref=HEAD:refs/heads/master filter object-format=sha1 agent=git/github-f2c0399b4791
003ff65e820a9211d8076618f5dee1c8ca2d79759664 refs/heads/master
003d96f3e4fa5111a26a408a52bd73ff9a45d6686520 refs/tags/1.0.0
003d69bdbc89b43de4ca173fc4dddff0ae14641d854e refs/tags/1.0.1
003df65e820a9211d8076618f5dee1c8ca2d79759664 refs/tags/1.1.0
EOF;

        $refs = $this->gitClone->parseRepositoryInfoProxy($gitUploadPackInfoResponse);

        $this->assertSame(
            [
                'HEAD' => 'ref: refs/heads/master',
                'refs/heads/master' => 'f65e820a9211d8076618f5dee1c8ca2d79759664',
                'refs/tags/1.0.0' => '96f3e4fa5111a26a408a52bd73ff9a45d6686520',
                'refs/tags/1.0.1' => '69bdbc89b43de4ca173fc4dddff0ae14641d854e',
                'refs/tags/1.1.0' => 'f65e820a9211d8076618f5dee1c8ca2d79759664'
            ],
            $refs
        );
    }
}
