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
    protected $quality = 75;

    protected $driver = "imagick"; // "gd" or "imagick
    protected $manager;
    protected $Oimage; //Original Image
    protected $Eimage; //Edited Image

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

    /**
     * Set the path of the image
     * @param $path - The path of the image
     */
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

    /**
     * Set the name of the image
     */
    public function name($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set the format of the image
     * @param $format - The format of the image
     */
    public function format($format)
    {
        $this->format = $format;

        return $this;
    }

    public function quality($quality)
    {
        $this->quality = $quality;

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
        $image_name = "";
        if ($image) {

            $this->manager($image);

            if ($crop) {
                if ($height == null) {
                    $height = $width;
                }
                $this->Eimage->cover($width, $height);
            } else {
                $this->Eimage->scaleDown($width, $height);
            }

            $image_name = $this->save($private);
        }

        return $image_name;
    }

    /**
     * Save the image create by the manager
     * @param $private - If the image should be private
     */
    public function save($private = false)
    {
        $folder = $private ? $this->private_path : $this->public_path;

        $image_name = $this->generateName();
        if (!$private) {
            $this->Eimage->save($folder . '/' . $image_name, $this->quality, $this->format);

            if ($this->path != "") {
                $image_name = $this->path . "/$image_name";
            }
        } else {
            $this->Eimage->save();
            try {
                Storage::disk('private')->put($this->private_path . "/$image_name", (string) $this->Eimage->encode());
            } catch (\Exception $e) {
                throw new \Exception("The private disk is not configured");
            }
            if ($this->path != "") {
                $image_name = $this->path . "/$image_name";
            }
        }

        return $image_name;
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
     * Create an instance of the image manager
     * @param $image - The image file
     */
    public function manager($image)
    {
        if ($this->manager) {
            return $this;
        }

        try {
            $manager = new ImageManager(new ImagickDriver());
        } catch (\Exception $e) {
            $manager = new ImageManager(new GdDriver());
            $this->driver = "gd";
        }

        if ($this->driver == "gd") {
            if ($image->getClientOriginalExtension() == "gif") {
                throw new \Exception("Gif images are not supported with GD driver");
            }
        }

        $this->manager = $manager;
        $this->Eimage = $manager->read($image->getRealPath());
        $this->Oimage = $image;

        return $this;
    }

    /**
     * Call the methods of the image manager
     * @param $method - The method name
     * @param $parameters - The method parameters
     */
    public function __call($method, $parameters)
    {
        if (!$this->Eimage || !$this->manager) {
            throw new \Exception("You must call the manager method first");
        }
        if (method_exists($this->Eimage, $method)) {
            $this->Eimage = $this->Eimage->$method(...$parameters);
            return $this;
        }

        throw new \BadMethodCallException("Method [$method] does not exist.");
    }

    /**
     * Generate a name for the image based on the configuration 
     * @param $image - The image file
     */
    private function generateName()
    {

        $extension = $this->Oimage->getClientOriginalExtension();
        if ($this->format) {
            $extension = $this->format;
        }

        if ($this->name != "") {
            return $this->name . '.' . $extension;
        } else {
            if ($this->random_name) {
                return rand() . '.' . $extension;
            } else {
                return $this->Oimage->getClientOriginalName();
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
