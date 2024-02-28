<?php

namespace Mindshaker\ImageUpload\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Mindshaker\ImageUpload\ImageUploadManager path($path)
 * @method static \Mindshaker\ImageUpload\ImageUploadManager name($name)
 * @method static string upload($image, $width = null, $height = null, $fit = false, $private = false)
 * @method static \Mindshaker\ImageUpload\ImageUploadManager delete($image, $private = false)
 * 
 * @see \Mindshaker\ImageUpload\ImageUploadManager
 */

class ImageUpload extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'imageupload';
    }
}
