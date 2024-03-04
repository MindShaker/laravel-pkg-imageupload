<?php

namespace Mindshaker\ImageUpload\Facades;

use Illuminate\Support\Facades\Facade;
use RuntimeException;

/**
 * @method static \Mindshaker\ImageUpload\ImageUploadManager path($path)
 * @method static \Mindshaker\ImageUpload\ImageUploadManager name($name)
 * @method static \Mindshaker\ImageUpload\ImageUploadManager manager($image)
 * @method static string upload($image, $width = null, $height = null, $crop = false, $private = false)
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


    public static function __callStatic($method, $args)
    {
        /** @var \Illuminate\Contracts\Foundation\Application|null */
        $app = static::getFacadeApplication();
        if (!$app) {
            throw new RuntimeException('Facade application has not been set.');
        }

        // Resolve a new instance, avoid using a cached instance
        $instance = $app->make(static::getFacadeAccessor());

        return $instance->$method(...$args);
    }
}
