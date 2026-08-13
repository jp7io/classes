<?php

namespace Tests\Imgix;

use Jp7\Imgix\ImgResize;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Config;

/**
 * Covers the stored-URL -> CDN-URL rewrite: which URLs are claimed, and what the template
 * name adds to them.
 *
 * Only addTemplate() is exercised. The rest of the class (url(), tag(), bg(), srcset())
 * bottoms out in Cdn::asset() and so in Laravel's asset() helper, which needs a booted app
 * rather than the config() stub this suite provides.
 */
class ImgResizeTest extends TestCase
{
    private const STORAGE_HOST = 'storage.fakeurl.com';
    private const IMGIX_HOST = 'jp7.imgix.net';
    private const TEMPLATE = 'thumb-interadmin';

    protected function setUp(): void
    {
        Config::set([
            'interadmin' => [
                'storage' => [
                    'host' => self::STORAGE_HOST,
                    'scheme' => 'https',
                ],
            ],
            'imgix' => [
                'host' => self::IMGIX_HOST,
                'templates' => [
                    self::TEMPLATE => ['w' => 40, 'h' => 40, 'fit' => 'crop'],
                ],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Config::set([]);
    }

    #[DataProvider('urlProvider')]
    public function testAddTemplate($url, $template, $expected)
    {
        $this->assertSame($expected, ImgResize::addTemplate($url, $template));
    }

    public static function urlProvider()
    {
        $storage = 'https://'.self::STORAGE_HOST;
        $imgix = 'https://'.self::IMGIX_HOST;

        return [
            'stored image, named template' => [
                $storage.'/upload/mediabox/00202630.jpeg',
                self::TEMPLATE,
                $imgix.'/upload/mediabox/00202630.jpeg?w=40&h=40&fit=crop',
            ],
            'original is not a template, so only the host changes' => [
                $storage.'/upload/mediabox/00202630.jpeg',
                'original',
                $imgix.'/upload/mediabox/00202630.jpeg',
            ],
            'unknown template name adds no params' => [
                $storage.'/upload/mediabox/00202630.jpeg',
                'thumb-typo',
                $imgix.'/upload/mediabox/00202630.jpeg',
            ],
            'existing query string keeps its params' => [
                $storage.'/upload/mediabox/00202630.jpeg?v=2',
                self::TEMPLATE,
                $imgix.'/upload/mediabox/00202630.jpeg?v=2&w=40&h=40&fit=crop',
            ],
            'a url already on imgix still takes the template' => [
                $imgix.'/upload/mediabox/00202630.jpeg',
                self::TEMPLATE,
                $imgix.'/upload/mediabox/00202630.jpeg?w=40&h=40&fit=crop',
            ],
            'foreign host is left alone' => [
                'http://www.external.com/upload/image.jpg',
                self::TEMPLATE,
                'http://www.external.com/upload/image.jpg',
            ],
            'relative path is left alone' => [
                '_default/file.css',
                self::TEMPLATE,
                '_default/file.css',
            ],
            // The prefix compared includes the scheme, so the storage host over http is a
            // different host as far as this is concerned.
            'storage host over http is not claimed' => [
                'http://'.self::STORAGE_HOST.'/upload/mediabox/00202630.jpeg',
                self::TEMPLATE,
                'http://'.self::STORAGE_HOST.'/upload/mediabox/00202630.jpeg',
            ],
        ];
    }

    /** config/imgix.php defaults the host to false, so a project that never set IMGIX_HOST
     *  serves every image off a URL with no host in it at all. */
    public function testMissingImgixHostProducesASchemeOnlyUrl()
    {
        Config::set([
            'interadmin' => ['storage' => ['host' => self::STORAGE_HOST, 'scheme' => 'https']],
            'imgix' => ['host' => false, 'templates' => []],
        ]);

        $this->assertSame(
            'https:///upload/mediabox/00202630.jpeg',
            ImgResize::addTemplate('https://'.self::STORAGE_HOST.'/upload/mediabox/00202630.jpeg', self::TEMPLATE)
        );
    }
}
