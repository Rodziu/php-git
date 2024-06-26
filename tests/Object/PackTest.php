<?php

declare(strict_types=1);

namespace Object;

use PHPUnit\Framework\TestCase;
use Rodziu\Git\Object\GitObject;
use Rodziu\Git\Object\Pack;
use Rodziu\Git\TestsHelper;

class PackTest extends TestCase
{
    public function testGetIterator(): void
    {
        // Given
        $pack = new Pack(TestsHelper::GIT_TEST_PATH.DIRECTORY_SEPARATOR.'pack.pack');

        // When
        $ret = [];
        /** @var GitObject $gitObject */
        foreach ($pack as $gitObject) {
            $ret[] = [$gitObject->getHash(), $gitObject->getTypeName()];
        }

        // Then
        $expected = [
            ['0bc4b1afe343e54015ba3b324c8208c88697876c', 'commit'],
            ['77c178155275fe2bb13be18dda3be54a200ca7c3', 'commit'],
            ['fc37310604b012792492f208e30aa3cfa5b9a98a', 'commit'],
            ['f6a00ad158679f68d7b4ed8d56e6e93273df7672', 'commit'],
            ['7bf75435a06393c95a082e6ce3c425440e88a96c', 'commit'],
            ['022a7c766d56534bd9b0e98d4e9040d9b6d3fb9c', 'commit'],
            ['f5dc8c8e40d70fdbcfed9ca61f28e0e9320aac91', 'commit'],
            ['fff4afc309f72c135872bc160416b17a3ba17dcb', 'commit'],
            ['160c9b986860c498924f8ba649ed4880cd6502ce', 'commit'],
            ['8ece95676a63f1265eda251a7cc851479b4e9826', 'commit'],
            ['ab1fbb62b915db8d820896a5c2cc74a69d02bb63', 'commit'],
            ['ca70bb265a07f17e4538a11b24c4f295a03fe70a', 'commit'],
            ['b03cfa8b4f62eb9359f971bd2f6d317d9dd12878', 'commit'],
            ['3faea217df56b04711c3f87686ac4e7549e15996', 'commit'],
            ['a8f92fd9a295daec82e724176341a8f6bbdca1be', 'commit'],
            ['0b9bacbf318523d5ce4a7c57b88d8c38bbdfac41', 'commit'],
            ['fb7c45481eaab93b71e0a22daaa1931dd1ee0642', 'commit'],
            ['e0b4b56b4979acc53e292cc0a617e549fa507ef9', 'tree'],
            ['3c9fcbb6b8cfe63107722ba2a557ca9d27b7bc3f', 'tree'],
            ['657ceb6e26821cf485736a264a70910d5f688c6d', 'tree'],
            ['7b6e02467b0cc30270f87e85c727aa8ccd1b908e', 'tree'],
            ['81b4f1ef8d63b8267ed8db24f6e9cabb42d28008', 'tree'],
            ['c63b2781d45559b253c8818b36e3734feece14a0', 'tree'],
            ['52924f9cb6550e73580ded51f1ccc9f8becb445a', 'tree'],
            ['3f19e9574a3015daccaaef3c5d45dd0fda740f2e', 'tree'],
            ['ad6c13a806049853cba27b9957adf99c654f843c', 'tree'],
            ['24b1c58d171b429bcef2f84354278ce6e1c31be1', 'tree'],
            ['501ef03b6d96d0b721730a7ab5b209a7a806d27f', 'tree'],
            ['27e634833ea511b48a73cb5d60ae1b46860466c0', 'tree'],
            ['ecffdda7b7fe9162a251a27dbcf92f0aafd92d99', 'tree'],
            ['aa1346823724205f005f4f26c3361acb8da9af30', 'tree'],
            ['4b404c9f1faa9c314da4c5cad7df78fee6d434c3', 'tree'],
            ['7d8b951667b303409181b355684b54ca2d4d0aa1', 'tree'],
            ['56b632c08c9c3a3b35b4d662dda7bd04166db39c', 'tree'],
            ['3067fe3e51c2c0dab111caee3c13aa381ba7d3fc', 'tree'],
            ['f2981febf965f0968d69806c9d548ee86b4916e1', 'tree'],
            ['186cc0185fe58fd73ccc2a8c15d0e48d9ee3b109', 'tree'],
            ['01eef0d9d44433bdd4b51dfc488170ebfe6f8812', 'tree'],
            ['7b981cfd9a98ab8840c8e678f292a20abbf5fb47', 'tree'],
            ['eede373f9ee4514f0e6c68d7f7828755217e8aae', 'tree'],
            ['2c1240378e83e92932f96eb8ae3a59e76268d11e', 'tree'],
            ['8ffd7fed78ace54c243df835f8ab0a57eac28b25', 'tree'],
            ['0562dace120aa64bfcc02c85d2d2b78c14477efa', 'tree'],
            ['e6044eed334cf8b878505d792851d695c3d0cc0a', 'tree'],
            ['c826a5e53a0e996422b5da142da9b1f8ba02fad9', 'tree'],
            ['e38ffcc126050c7fcc9fe5b184593f27e0d54988', 'tree'],
            ['5c7d94730882a5233db86daac27d68649110cdc3', 'tree'],
            ['f90403c76822e41e59b4ada5602258a373551fa5', 'tree'],
            ['0e5cff53d93d96e36fc54385c84478eeda1b5755', 'tree'],
            ['2a9e4974490092a7d6c2a837d605ba8ebc53dd2e', 'tree'],
            ['90614285ae25ec9463fe53ef3b68ebaf169409f5', 'tree'],
            ['9703a4c10936608e55343599e7c410546b977c63', 'tree'],
            ['f78d4cbf225265f01093d2ddf154bb1780a011d2', 'tree'],
            ['39246213281eb4ad8d580221f03d11278c0b680e', 'tree'],
            ['9c2b076d362a7d99693e95559fb1725b7da02065', 'tree'],
            ['cc91ae2e69eac7544817f5bf6759297b7b0d93fe', 'tree'],
            ['d729e2bac530c644bac95397c93ee9ae1b9274ad', 'tree'],
            ['152298a4500685bc6a33d0958f2569abda4e8ede', 'tree'],
            ['55e438878d470b4d5febcb3647ac92aec48ee30c', 'tree'],
            ['fab4776457f7d24eb08aa4f896dcdcf83905e301', 'tree'],
            ['0170b466c756c31fa9d0fa43a71235c80b557626', 'tree'],
            ['af4ac1c3158a8bb42cee3d0467010d8cd8262f90', 'tree'],
            ['f29fad05c4365e642312e34f863d8a38679bebd0', 'tree'],
            ['4f6a73548ee9dfe44f306aea73f37774de9c6767', 'tree'],
            ['84e2f138683c7809460699a8e142421043ba4167', 'tree'],
            ['7107841f1f6e0ecec53ea52c8d3084a31adfd131', 'tree'],
            ['43b44e95f6c6a905dfe1314e9a4c6f1713c97ede', 'tree'],
            ['7f27b8422bb767a1555b5dc74bb2c7af10bd5f3a', 'tree'],
            ['6cf9545b30d53fb4a2c733f6142629b0801db25e', 'tree'],
            ['d3e03773047dc8330f04a8a1f269e768231e6727', 'tree'],
            ['9979f10981ba3b99dc55ccd27b4fdf0e155f179f', 'tree'],
            ['ba1f64ecdfd5c923e970eb9b21e63e5844efb904', 'tree'],
            ['6805fdead588e7db7b8b7c59d42df1ae3458f6f5', 'tree'],
            ['a78392d3f88caffed57164315f6d580eced06557', 'tree'],
            ['c8818007d4a867c45e250b500a59e11772a9c4d4', 'tree'],
            ['b339aa3a00c2c78105ecaecc661bbc42f87f1f85', 'tree'],
            ['4f99718e4a2e1ca0b8aa9b32036f0f248d2f4dcf', 'tree'],
            ['6371935c9e7d5b42371dfe689ae65087a533e58a', 'tree'],
            ['c173f6eadda6a752fde99a88143636ca1da51e4c', 'tree'],
            ['9c3a4c8f01d2f7d85dbe5c793176ba6e99bb1e4d', 'tree'],
            ['60b6f89e0f7afd1c58bbc245181394342d1c6d35', 'tree'],
            ['b396c6458c9c5a91ab8b7b57f0f886b0e3cc5e76', 'tree'],
            ['11fec2e3c2035fcfcbc437b50a40dc8f3078040e', 'tree'],
            ['bcc8d8e82f98e0b5dd4c12bc81e276a34594e9b7', 'tree'],
            ['7f07fa26e7158cf2d81ed01d3fd4eafa73c11c47', 'tree'],
            ['f41532305bb639b2a1474924f2ccbe2bca2fd06a', 'tree'],
            ['378a233e86cd9400cf1cd80a39644bed391f6d12', 'tree'],
            ['48ec5c5a06144c69e4a305cf70c6bc4e849cbe48', 'tree'],
            ['2a426c61d22ee79b292f3f0392ecd77340cfe50b', 'tree'],
            ['f67feb6a74bfbf49c7579055568c31dea2080cf0', 'tree'],
            ['9ad3ad0125cf4895a37a3d158582c9166099bf94', 'tree'],
            ['4f786c696f57c575744f0d4fa456713529ff5a72', 'tree'],
            ['e57838932e02ae2ca135eeb7ad7ced86cd1ee338', 'tree'],
            ['fd7748c02b7e5264ae234a58b6f166b3d3d2aa95', 'tree'],
            ['0276bab319911b313694c2648dcc40ae7374f0d0', 'tree'],
            ['50598c1153706f69653b04e30c453ad7d2d87f9d', 'tree'],
            ['45f919f10c3f13f0a034b51616e9b24bd62bed1a', 'tree'],
            ['1270786120b4d8079430f4572c7ab1934175db6d', 'tree'],
            ['a46f75fef5fd1fa68b5f4a14a8c11477fc49a1db', 'tree'],
            ['133805c3ee41b642fd2a2b6a812f042c1a70f5b2', 'tree'],
            ['18a9eedcda8668c4c65a4bec069da346df55e7be', 'tree'],
            ['d0d0be140f99003d2c467e287713ac163d06508f', 'tree'],
            ['42b2f2df616e8063093e441ea7dc7ee10be238bc', 'tree'],
            ['9d38cb345daaf4d7233808bb93f390f4437917c6', 'tree'],
            ['40cc8976a3b12c2f1849d89079a4a276abef8563', 'tree'],
            ['33e25377cd12993f21141baa8228dcac4911b6ab', 'tree'],
            ['26a6f103a210c6fec454baba6b68103da51d3a46', 'tree'],
            ['a1e439d7aef4b0e07929d68005e1efd0a1a3b8c0', 'tree'],
            ['7faa7874c42f450c9cfe364542f5cbae01106979', 'tree'],
            ['88309676a113b29b768c6751ad52e9e0e352c841', 'tree'],
            ['4f0d6c45007cb7c6d6fac63f34e4630f0927f617', 'tree'],
            ['d9c9e01a5943c0ec4df47f2807e9b99af44821ab', 'tree'],
            ['953da192b31cfd94c2866d60b3df8553566aec12', 'tree'],
            ['6aaeea14148d6f7c5f522f96732b2ef11720824c', 'tree'],
            ['f5a46a4d3ff7f1c288db12bfb4f7379c2184425a', 'tree'],
            ['102fcc72100865726c6b35df252373d03312f4b1', 'tree'],
            ['7a9502adbe50fb89225d306f5934ecdb113d00bd', 'tree'],
            ['9be528519dfe1d9a56c2e9ccb231a9c8c53f121c', 'tree'],
            ['055465e0121c2c89eb55baf6c17fcf07cc1aac99', 'tree'],
            ['dd570090d0de37a3feef0643a7594cfd15d6e014', 'tree'],
            ['d99d030bb2aa0fa763e676b7f93dbb892e08d66d', 'blob'],
            ['7e693ebef7c41554bb1b285c1250f341ba4cdb49', 'blob'],
            ['922f363ed783e16ff950ccb931f8f12ac8d990c1', 'blob'],
            ['2b5e12ee8a7bd4bbfcd0a798563f54b4de797401', 'blob'],
            ['f61073668a3779d5426db033476350bea1471af7', 'blob'],
            ['a05277b5768fa7d5d333f0c1d5f3b656dcfed9d8', 'blob'],
            ['568b5672dd7809c0b6a8ba23a2e1be89dac285aa', 'blob'],
            ['4f0ff70f8bb827fb3c218d9ce8cd5eff6504dcfc', 'blob'],
            ['90ea4496fefc1254c2031087738f1abbd12a3d6b', 'blob'],
            ['46f67555c191492f9c99238456d998582ac5523e', 'blob'],
            ['f72b407831f95f04f592592c738b6ee1899e0d24', 'blob'],
            ['f0e744dd60c23040d4def203e98c5e33207b7b30', 'blob'],
            ['200d2a46f8eb293a72793d6ae854c9caa3ba0c42', 'blob'],
            ['ef308b5dd2c9ddcf8c97a72abe69d9b40d99cd62', 'blob'],
            ['99d94d513b75728d94dfc2aabefb6a7e6f085c4f', 'blob'],
            ['cf74eb4f09ae7a3837fc21179c0a8408ec883986', 'blob'],
            ['990dd27272c6a3efa2fc76d163fbb43c96da2ef6', 'blob'],
            ['53da14479aee412a935853e30085e8620523168b', 'blob'],
            ['d5148b48eedc3739e5e2b5580e44640d5ea5e1e2', 'blob'],
            ['e533a1fd93917f618d5057ad3d99aaa1644fbc9b', 'blob'],
            ['e42b0494d3539014e8aa6359001d9908dce8b66d', 'blob'],
            ['01a89a55c245e94d287346bad77aec3480663d5e', 'blob'],
            ['629f6dcea40a0cbd0d4b2ecda521c361f97bf37f', 'blob'],
            ['4cc0a9e6e5aa29262a8789a2714e25aeedd2cfdd', 'blob'],
            ['ddef15d2b3c522d37144f779c0c0480a7b15bf01', 'blob'],
            ['56d1cbaa05b419730fdc676a5f66ef85d91a2e4b', 'blob'],
            ['4810dc3326427479c964a8272b6b6de22ef8a873', 'blob'],
            ['8e1c6d5593cda483365b988a86506200bfaa64b9', 'blob'],
            ['9070692b0b40635108f3eb1442c2247c2251ec76', 'blob'],
            ['b5d0b19f98ce49b5fc44782ff68f613502299b91', 'blob'],
            ['1ea55092efb67a6d91827e5ccffb1218e66b3be4', 'blob'],
            ['92ad12bb8648fbe2b80657197ac2686117b5b7a4', 'blob'],
            ['8ebbf50b541f893a1d0606bbae5a3f9e9a62d68f', 'blob'],
            ['dcd22dfd770196c5d8d912044f97cb8b42a944ac', 'blob'],
            ['b93d8bd5761e27c056243127862a678e4b2cda19', 'blob'],
            ['14faec3a34dd2859c8ca292ee8b3813d67ee37c1', 'blob'],
            ['159e114580c32d10a494c8fee995169025a87a78', 'blob'],
            ['5a4475577a6a38d6d8a9aa18f73ce79621c7c9ca', 'blob'],
            ['ad073eff07a8648ddf3295dd2755f3a70a428887', 'blob'],
            ['20f8c1b3accf171c2670cf4826acee62297d322c', 'blob'],
            ['f682704afca072bec8c9c702b89ffab3ec8ef7d1', 'blob'],
            ['be65977b36c7a0942dda31fe7bc4c6239f922e30', 'blob'],
            ['28dda82748e2940f36c1c070646b30b5fbf5217f', 'blob'],
            ['e982b94aefa0c4cb76d791f63b3c693625c8549a', 'blob'],
            ['76225ea159ec2a62d061bf5f035ba3c1ca3ec9ab', 'blob'],
            ['c7e401988937a0d181512dc354b3b08487e0aef1', 'blob'],
            ['621e1020febb28e549228f90fa40cee56faaa474', 'blob'],
            ['a51ffc49a8ad390202dcbcce69ed98c937bfcd18', 'blob'],
            ['8f3dcb3d793fa1f04bbc09b0c91b31339753a0e7', 'blob'],
            ['b9c209ceadd120f89c85253cd1ea552f989f84fd', 'blob'],
            ['d0a604a74d455ae2177bf47fb22d990d596ab5c3', 'blob'],
            ['7eb327b9deccfdd6246a34ffe0453ac1d92d81c1', 'blob'],
            ['527b22fb8f94ae00d760525abea27d5bf4f747d2', 'blob'],
            ['9b9ff5c300f48007dcf4ace494972f57ff4632e2', 'blob'],
            ['e1a439098e422963b49f0e1667d4171787b9e2ca', 'blob'],
            ['d6268f85154ff13399759b27aaacccb9642206a1', 'blob'],
            ['96902996bf737ed66918d2327ed5b41a4eae4bab', 'blob'],
            ['496bca54a7c7c802dd887fbff2fa0b9aade62157', 'blob'],
            ['eb39b9c351a24f4c81cd240b0ca5f004391c35f5', 'blob'],
            ['50376ddbd5cad694b7d988aa19eac53b7cfa7189', 'blob'],
            ['dcb0afcec0aea0cc606e73b5e643ba07c147bb86', 'blob'],
            ['495ed15323dc0af947c8ec81fc469e9c6fe8019c', 'blob'],
            ['691cd85726986b1c0fb5b19db8ed61f648798a0d', 'blob'],
            ['c81b081b77880dad7419d9dfe0008aaf32acc56d', 'blob'],
            ['019c4aacd084b924f0bf515437e1b64fc6c03e8c', 'blob'],
            ['3dd9b1deea2018fb43509f06684229b8b3d93b2d', 'blob'],
            ['ca7fdacefc64bf441fe7248606538b0e9b0ecb28', 'blob'],
            ['eb9193945bc31a4af9adf70b320ad39d84220f8d', 'blob'],
            ['8e4f558d4896067f04268d80ecfbacaae9ce20b4', 'blob'],
            ['fab8dd9ad4a36a07d233a5a4eb54dde1aa63f9ec', 'blob'],
            ['899be42f9ed4422e2f3e25ab2f4a7d7700671484', 'blob'],
            ['0b541d2c69ad1321485c43b59f96a911e8ad16cc', 'blob'],
            ['99bd168061f1034908cc9e5fee21bb3beaaf047c', 'blob'],
            ['cf52e5534747f748ed81d318817a1f95f2ba16c5', 'blob'],
            ['969b10222e6f41e36ec0410ff6ddb5117e65ca14', 'blob'],
            ['f60e8f5a0675d7db1c4532a4f5176f7edf743342', 'blob'],
            ['1aa6e016eb1134f30142dc30a5d5778ea49e0635', 'blob'],
            ['74edc7f288ce230b83228baa7c8c803b0d042e0b', 'blob'],
            ['bc237556865e07c193ef1888aa317aa2eb51d3d3', 'blob'],
            ['6b3e24a30ffe4a2417038b1e2b7a6114c038a596', 'blob'],
            ['2dd45c6cc0cfbdca84960c8a5b925e30b2cfd033', 'blob'],
            ['ec25aed12da28ff482ad958d4768a253d72d6c74', 'blob'],
            ['88efcf6f5c47f1c001398d13da9bc8c5c9da28fd', 'blob'],
            ['c5b12cf026c71d8e5e4ce4191ee1dfba7cdeaa52', 'blob'],
            ['7620988f0c827f94ac56feb387496f6551b26dad', 'blob'],
            ['d06fe0c324f8d3b163ca536453a231d42d5b628e', 'blob'],
            ['b3b5bba7c0cdf6b6bb514926c475dc288263d324', 'blob'],
            ['04eaa345efc9ab7cf4e7cef8f3eab57cb2d794ec', 'blob'],
            ['89dfb57d3803d9ae085efd530d7f7817225fcf89', 'blob'],
            ['5a0ebbdc5b06a5a385ea7ac1c695837b34cac522', 'blob'],
            ['157182e752c4948f49ab307609d504510000bbf0', 'blob'],
            ['f1504e12253cb9ab5f2fd63a9208a4f14ba8c1f1', 'blob'],
            ['d8f3d45908efa9fbb8cbcead561bb81358a48939', 'blob'],
            ['02e3008ca2c60892e388d4b63de010a91125fcb9', 'blob'],
            ['c9148b6858d1dd36dc01a1170a8c74203da8cc25', 'blob'],
            ['a263ecca99ac29e26aa80b4e8baa394d6970cd0b', 'blob'],
            ['8a489a99bdf32e28211e4a54679b7a37d22a807d', 'blob'],
            ['8e45073802124d28ee7323105c3db258c3d41497', 'blob'],
            ['da38200f19689a83c4187438d194525e2d7fafcc', 'blob'],
            ['da91da46c59db6fb346635270f59d84aa6917d90', 'blob'],
            ['e83e3d2c9eccae739def8f2abd3473657eed3780', 'blob'],
            ['8c8589f0d136cf7a48bf3faa02fd463c0d8c7d34', 'blob'],
            ['f98ac9c07308776e5b7d05ce022861d758310f64', 'blob'],
            ['011ef9745f695db5ffd2763dd8b34df940d67ff7', 'blob'],
            ['180fd5df1df82693547ea070f50245b669802ecc', 'blob'],
            ['cac516e1b48225b48335ad0c2fdd19187a416430', 'blob'],
            ['0e737bfa6d2ae22116a0fa68e78616389abe6c19', 'blob'],
            ['3bf9b9c5ad8fd24ff16b54c786eebc89c73640b2', 'blob'],
            ['88618aa7e1076a4bec0de1a9228e3c08eadd3b68', 'blob'],
            ['75215fe42b54cd3ca51949a5a253f31a3bc2ee6e', 'blob'],
            ['a03b6739bef51409d76aa60c0c026e9a7c2e8a84', 'blob']
        ];

        self::assertSame($expected, $ret);
    }

    public function testUnpackObject(): void
    {
        // Given
        $pack = new Pack(TestsHelper::GIT_TEST_PATH.DIRECTORY_SEPARATOR.'pack.pack');

        // When
        $object = $pack->unpackObject(12);

        // Then
        self::assertSame(254, $object->getSize());
        self::assertSame('commit', $object->getTypeName());
        self::assertSame('0bc4b1afe343e54015ba3b324c8208c88697876c', $object->getHash());
    }

    public function testGetPackedObject(): void
    {
        // Given
        $pack = new Pack(TestsHelper::GIT_TEST_PATH.DIRECTORY_SEPARATOR.'pack.pack');

        // When
        $object = $pack->getPackedObject('da91da46c59db6fb346635270f59d84aa6917d90');

        // Then
        self::assertSame('blob', $object->getTypeName());
        self::assertSame('da91da46c59db6fb346635270f59d84aa6917d90', $object->getHash());
    }
}
