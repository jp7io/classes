<?php

namespace Jp7\Intervention;

use Illuminate\Routing\Controller as BaseController;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Storage;

/**
 * ImageCache using Storage as source
 */
class ImageCacheController extends BaseController
{
    /**
     * Where a rendered image lands. It is also what Interadmin\Files\Adapter\Intervention builds
     * URLs against, in another repo, with nothing linking the two spellings -- so a change here
     * is a change there.
     */
    public const ROOT = 'derivatives';

    public function create($template, $filepath)
    {
        try {
            $original = Storage::get('upload/' . $filepath);
        } catch (FileNotFoundException $e) {
            abort(404);
        }
        $img = Image::make($original);

        if ($template !== 'original') {
            $templates = config('imagecache.templates');
            if (!isset($templates[$template])) {
                abort(404);
            }
            $closure = $templates[$template];
            $img = $closure($img);
        }
        $img->encode();

        $saveOn = self::ROOT.'/'.$template.'/'.$filepath;
        Storage::put($saveOn, $img->__toString(), 'public');

        return $img->response();
    }

    public function clear($file)
    {
        // No config file declares these anywhere, and array_keys(null) is fatal on PHP 8.
        $templates = array_keys(config('imagecache.templates') ?? []);
        foreach ($templates as $template) {
            $deleteAt = self::ROOT.'/'.$template.'/'.$file;
            if (Storage::has($deleteAt)) {
                Storage::delete($deleteAt);
            }
        }
    }
}
