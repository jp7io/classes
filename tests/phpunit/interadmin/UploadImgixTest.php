<?php

namespace Tests\Interadmin;

use Jp7_Interadmin_Upload as Upload;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UploadImgixTest extends TestCase
{
    protected function setUp(): void
    {
        Upload::setAdapter(new \Jp7_Interadmin_Upload_Imgix);
    }

    protected function tearDown(): void
    {

    }

    #[DataProvider('storageProvider')]
    public function testUrlStorage($filePath, $expected, $template = null)
    {
        global $config;
        $config = (object) [
            'storage' => [
                'host' => self::storageHost(),
                'path' => ''
            ],
            'imgix' => [
                'host' => self::imgixHost(),
                'templates' => [
                    'original' => [],
                    'thumb_interadmin' => ['w' => 40, 'h' => 40]
                ]
            ],
            'imagecache' => 'imgix',
        ];

        $url = $this->url($filePath, $template);

        $this->assertEquals($expected, $url);
    }

    public static function storageProvider()
    {
        return [
            ['../../upload/mediabox/00202630.jpeg', 'http://'.self::imgixHost().'/upload/mediabox/00202630.jpeg'],
            ['../../upload/mediabox/00202630.png', 'http://'.self::imgixHost().'/upload/mediabox/00202630.png'],
            ['../../upload/mediabox/00202630.jpeg', 'http://'.self::imgixHost().'/upload/mediabox/00202630.jpeg?w=40&h=40', 'thumb_interadmin'],
            ['../../upload/mediabox/00202630.jpeg?v=2', 'http://'.self::imgixHost().'/upload/mediabox/00202630.jpeg?v=2&w=40&h=40', 'thumb_interadmin'],
            ['../../upload/mediabox/00202630.pdf', 'http://'.self::storageHost().'/upload/mediabox/00202630.pdf'],
            ['../../upload/mediabox/00202630.pdf?v=2', 'http://'.self::storageHost().'/upload/mediabox/00202630.pdf?v=2'],
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

    private static function storageHost()
    {
        return 'storage.fakeurl.com';
    }

    private static function imgixHost()
    {
        return 'client.imgix.net';
    }

    private static function externalUrl()
    {
        return 'http://www.external.com';
    }
}
