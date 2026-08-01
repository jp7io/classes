<?php

namespace Tests\Interadmin;

use Jp7_Interadmin_Upload as Upload;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UploadLegacyTest extends TestCase
{
    protected function setUp(): void
    {
        Upload::setAdapter(new \Jp7_Interadmin_Upload_Legacy);
    }

    protected function tearDown(): void
    {

    }

    #[DataProvider('legacyProvider')]
    public function testUrlLegacy($filePath, $expected, $template = null)
    {
        global $config;
        $config = (object) [
            'storage' => [
                'host' => self::appHost(),
                'path' => ''
            ]
        ];

        $url = $this->url($filePath, $template);

        $this->assertEquals($expected, $url);
    }

    public static function legacyProvider()
    {
        return [
            ['../../upload/mediabox/00202630.jpeg', 'http://'.self::appHost().'/upload/mediabox/00202630.jpeg'],
            ['../../upload/mediabox/00202630.png', 'http://'.self::appHost().'/upload/mediabox/00202630.png'],
            ['../../upload/mediabox/00202630.jpeg', 'http://'.self::appHost().'/upload/mediabox/00202630.jpeg?size=40x40', 'thumb_interadmin'],
            ['../../upload/mediabox/00202630.jpeg?v=2', 'http://'.self::appHost().'/upload/mediabox/00202630.jpeg?v=2&size=40x40', 'thumb_interadmin'],
            ['../../upload/mediabox/00202630.pdf', 'http://'.self::appHost().'/upload/mediabox/00202630.pdf'],
            ['../../upload/mediabox/00202630.pdf?v=2', 'http://'.self::appHost().'/upload/mediabox/00202630.pdf?v=2'],
            ['_default/file.css', '_default/file.css'],
            [self::externalUrl().'/upload/image.jpg', self::externalUrl().'/upload/image.jpg'],
            [self::externalUrl().'/upload/image.jpg', self::externalUrl().'/upload/image.jpg', 'thumb_interadmin']
        ];
    }

    #[Group('path')]
    #[DataProvider('legacyProviderWithPath')]
    public function testUrlLegacyWithPath($filePath, $expected, $template = null)
    {
        global $config;
        $config = (object) [
            'storage' => [
                'host' => self::appHost(),
                'path' => 'client'
            ]
        ];
        $url = $this->url($filePath, $template);

        $this->assertEquals($expected, $url);
    }

    public static function legacyProviderWithPath()
    {
        return [
            ['../../upload/mediabox/00202630.jpeg', 'http://'.self::appHost().'/client/upload/mediabox/00202630.jpeg'],
            ['../../upload/mediabox/00202630.png', 'http://'.self::appHost().'/client/upload/mediabox/00202630.png'],
            ['../../upload/mediabox/00202630.jpeg', 'http://'.self::appHost().'/client/upload/mediabox/00202630.jpeg?size=40x40', 'thumb_interadmin'],
            ['../../upload/mediabox/00202630.jpeg?v=2', 'http://'.self::appHost().'/client/upload/mediabox/00202630.jpeg?v=2&size=40x40', 'thumb_interadmin'],
            ['../../upload/mediabox/00202630.pdf', 'http://'.self::appHost().'/client/upload/mediabox/00202630.pdf'],
            ['../../upload/mediabox/00202630.pdf?v=2', 'http://'.self::appHost().'/client/upload/mediabox/00202630.pdf?v=2'],
            ['_default/file.css', '_default/file.css'],
            [self::externalUrl().'/upload/image.jpg', self::externalUrl().'/upload/image.jpg'],
            [self::externalUrl().'/upload/image.jpg', self::externalUrl().'/upload/image.jpg', 'thumb_interadmin']
        ];
    }

    private function url($filePath, $template)
    {
        if (isset($template)) {
            return Upload::url($filePath, $template);
        }
        return Upload::url($filePath);
    }

    private static function appHost()
    {
        return 'www.app.com.br';
    }

    private static function externalUrl()
    {
        return 'http://www.external.com';
    }
}
