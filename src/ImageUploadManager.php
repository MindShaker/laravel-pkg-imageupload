<?php

namespace Mindshaker\ImageUpload;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ImageUploadManager
{
    protected $public_path;
    protected $private_path;
    protected $random_name;
    protected $path = "";
    protected $name = "";
    protected $format;

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
     * @param $fit - If the image should be fit
     * @param $private - If the image should be private
     */
    public function upload($image, $width = null, $height = null, $fit = false, $private = false)
    {
        $folder = $private ? $this->private_path : $this->public_path;

        $image_name = "";
        if ($image) {

            $image_name = $this->generateName($image);

            $resized_image = Image::make($image->getRealPath());
            $resized_image->orientate();
            if ($fit) {
                $resized_image->fit($width, $height);
            } else {
                $resized_image->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }
            if (!$private) {
                if ($image->getClientOriginalExtension() != "gif") {
                    $resized_image->save($folder . '/' . $image_name, 90, $this->format);
                } else {
                    copy($image->getRealPath(), $folder . '/' . $image_name);
                }
                if ($this->path != "") {
                    $image_name = $this->path . "/$image_name";
                }
            } else {
                $resized_image->save();
                Storage::disk('private')->put($this->private_path . "/$image_name", $resized_image->__toString());
                if ($this->path != "") {
                    $image_name = $this->path . "/$image_name";
                }
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
