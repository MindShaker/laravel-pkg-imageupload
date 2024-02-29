<?php

namespace Mindshaker\ImageUpload;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

class ImageUploadManager
{
    protected $public_path;
    protected $private_path;
    protected $random_name;
    protected $path = "";
    protected $name = "";
    protected $format;
    protected $driver = "imagick"; // "gd" or "imagick

    public function __construct()
    {
        /* Public folder creation */
        $this->public_path = public_path() . '/' . config('imageupload.public_path');
        if (!File::exists($this->public_path)) {
            File::makeDirectory($this->public_path, 0777, true, true);
        }

        /* Private folder creation */
        /* $this->private_path = config('imageupload.public_path');
        if (!Storage::disk('private')->exists($this->private_path)) {
            Storage::disk('private')->makeDirectory($this->private_path, 0777, true, true);
        } */

        $this->random_name = config('imageupload.random_name');
    }

    public function path($path)
    {
        $this->public_path = $this->public_path . '/' . $path;
        if (!File::exists($this->public_path)) {
            File::makeDirectory($this->public_path, 0777, true, true);
        }

        try {
            $this->private_path = $path;
            if (!Storage::disk('private')->exists($this->private_path)) {
                Storage::disk('private')->makeDirectory($this->private_path, 0777, true, true);
            }
        } catch (\Exception $e) {
            //dd($e);
        }

        $this->path = $path;

        return $this;
    }

    public function name($name)
    {
        $this->name = $name;

        return $this;
    }

    public function format($format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Upload an image
     * @param $image - The image file
     * @param $width - The width of the image
     * @param $height - The height of the image
     * @param $crop - If the image should be crop
     * @param $private - If the image should be private
     */
    public function upload($image, $width = null, $height = null, $crop = false, $private = false)
    {
        $folder = $private ? $this->private_path : $this->public_path;

        $image_name = "";
        if ($image) {

            $manager = $this->manager();

            $image_name = $this->generateName($image);

            if ($this->driver == "gd") {
                //if file is gif
                if ($image->getClientOriginalExtension() == "gif") {
                    throw new \Exception("Gif images are not supported with GD driver");
                }
            }
            $resized_image = $manager->read($image->getRealPath());
            if ($crop) {
                if ($height == null) {
                    $height = $width;
                }
                $resized_image->cover($width, $height);
            } else {
                $resized_image->scaleDown($width, $height);
            }

            if (!$private) {
                $resized_image->save($folder . '/' . $image_name, 75, $this->format);

                if ($this->path != "") {
                    $image_name = $this->path . "/$image_name";
                }
            } else {
                $resized_image->save();
                Storage::disk('private')->put($this->private_path . "/$image_name", (string) $resized_image->encode());
                if ($this->path != "") {
                    $image_name = $this->path . "/$image_name";
                }
            }
        }

        return $image_name;
    }

    public function manager()
    {
        try {
            $manager = new ImageManager(new ImagickDriver());
        } catch (\Exception $e) {
            $manager = new ImageManager(new GdDriver());
            $this->driver = "gd";
        }

        return $manager;
    }

    /**
     * Delete an image, The public / private path will be added automatically
     * @param string $image - The image name
     * @param bool $private - If the image is private
     */
    public function delete($image, $private = false)
    {
        if ($private) {
            $this->deletePrivate($image);
        } else {
            $this->deletePublic($image);
        }

        return $this;
    }

    /**
     * Generate a name for the image based on the configuration 
     * @param $image - The image file
     */
    private function generateName($image)
    {
        $extension = $image->getClientOriginalExtension();
        if ($this->format) {
            $extension = $this->format;
        }

        if ($this->name != "") {
            return $this->name . '.' . $extension;
        } else {
            if ($this->random_name) {
                return rand() . '.' . $extension;
            } else {
                return $image->getClientOriginalName();
            }
        }
    }

    /**
     * Delete an image from the public folder
     */
    private function deletePublic($image)
    {
        $image_path = $this->public_path . '/' . $image;
        if (File::exists($image_path)) {
            File::delete($image_path);
        }

        return true;
    }

    /**
     * Delete an image from the private folder
     */
    private function deletePrivate($image)
    {
        $image_path = $this->private_path . '/' . $image;
        if (Storage::disk('private')->exists($image_path)) {
            Storage::disk('private')->delete($image_path);
        }

        return true;
    }
}
