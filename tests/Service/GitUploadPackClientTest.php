<?php
declare(strict_types=1);

namespace Rodziu\Git\Service;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rodziu\Git\Object\GitRef;
use Rodziu\Git\Object\Head;
use Rodziu\Git\TestsHelper;

class GitUploadPackClientTest extends TestCase
{
    /**
     * @param array{head: Head, refs: GitRef[]} $expected
     */
    #[DataProvider('fetchRepositoryInfoDataProvider')]
    public function testFetchRepositoryInfo(int $status, string $body, array $expected): void
    {
        // Given

        $manager = TestsHelper::getGitRepositoryManager();
        $client = new GitUploadPackClient(
            $manager,
            HandlerStack::create(
                new MockHandler([
                    new Response($status, [], $body)
                ])
            )
        );

        // When
        $repositoryInfo = $client->fetchRepositoryInfo();

        // Then
        self::assertEquals($expected, $repositoryInfo);
    }

    /**
     * @return array{status: int, body: string, expected: array{head: Head, refs: GitRef[]}}[]
     */
    public static function fetchRepositoryInfoDataProvider(): array
    {
        return [
            [
                'status' => 200,
                'body' => <<<EOF
001e# service=git-upload-pack
00000155cf1e76b98d891e6c5d57d747330abe1e1ed854f6 HEAD multi_ack thin-pack side-band side-band-64k ofs-delta shallow deepen-since deepen-not deepen-relative no-progress include-tag multi_ack_detailed allow-tip-sha1-in-want allow-reachable-sha1-in-want no-done symref=HEAD:refs/heads/master filter object-format=sha1 agent=git/github-f133c3a1d7e6
003fcf1e76b98d891e6c5d57d747330abe1e1ed854f6 refs/heads/master
003dbd5785f3aa2e35c60f70e4df8ef97613a43391b4 refs/tags/0.1.0
003db679aa263412e259e97e7687d6f8286bbac43be6 refs/tags/0.2.0
003db931c2fe4ad701ca4e4839ce5d729bdeb667e681 refs/tags/1.0.0
003d991c9a8db826196699a43776e43da3dd09e31dd5 refs/tags/2.0.0
0040890ecb06d3d373489adb661931f1d02b721375fc refs/tags/2.0.0^{}
003ca7538d5b9bf3b4e7c7f3c5c43391852339cefc67 refs/tags/tree
003f4ac978f076c8654bfd365838bad72608792a287c refs/tags/tree^{}
0000
EOF,
                'expected' => [
                    'head' => new Head(
                        'cf1e76b98d891e6c5d57d747330abe1e1ed854f6',
                        'origin/master'
                    ),
                    'refs' => [
                        new GitRef(
                            'remotes',
                            'origin/master',
                            'cf1e76b98d891e6c5d57d747330abe1e1ed854f6'
                        ),
                        new GitRef(
                            'tags',
                            '0.1.0',
                            'bd5785f3aa2e35c60f70e4df8ef97613a43391b4'
                        ),
                        new GitRef(
                            'tags',
                            '0.2.0',
                            'b679aa263412e259e97e7687d6f8286bbac43be6'
                        ),
                        new GitRef(
                            'tags',
                            '1.0.0',
                            'b931c2fe4ad701ca4e4839ce5d729bdeb667e681'
                        ),
                        new GitRef(
                            'tags',
                            '2.0.0',
                            '991c9a8db826196699a43776e43da3dd09e31dd5',
                            '890ecb06d3d373489adb661931f1d02b721375fc'
                        ),
                        new GitRef(
                            'tags',
                            'tree',
                            'a7538d5b9bf3b4e7c7f3c5c43391852339cefc67',
                            '4ac978f076c8654bfd365838bad72608792a287c'
                        ),
                    ]
                ]
            ]
        ];
    }
}
