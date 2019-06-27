<?php

namespace App\Services\System;

use Illuminate\Http\Request;
use Image;
use Illuminate\Support\Facades\Storage;

/**
 * Image Processing
 * @author : sean
 * @since : 2019-06-27
 * @copyright : Ant Internet Sdn Bhd
 * @website : https://ant-internet.com
 */

/**
 * Require intervention package
 * 'Image' => Intervention\Image\Facades\Image::class( add to $aliases )
 * Intervention\Image\ImageServiceProvider::class( add to provider )
 */
class ImageService
{
    /**
     * example of using this method
     * $image = new ImageService;
     * $imageCombination = data:image/png;base64,imageDataHere;
     * $image->imageProcessing($imageCombination);
     * It will return the path of the image;
     */
    public function imageProcessing($imageCombination, $setWidth = 1000, $setHeight = 1000)
    {
        //get image details
        $details = $this->base64Details($imageCombination);
        $imageExt = $details['imageExt'];
        $imageData = $details['imageData'];
        $imagePath = "D:\images\\";
        $imageName = uniqid('img_', true);
        $imagePathName = $imagePath . $imageName . '.' . $imageExt;
        //decode and save image
        $imageData = $this->decodeImageBase64($imageData);
        $this->saveImage($imageData, $imagePathName);
        //resize image
        $this->imageResize($imagePathName, $setWidth, $setHeight);
        //convert to Jpg
        $jpgPathFileName = $this->toJpg($imagePathName);
        //add water mark
        $this->addWaterMark($jpgPathFileName);
        return $jpgPathFileName;
    }
    
    public function imageResize($imagePathName, $setWidth , $setHeight)
    {
        //get image width and height
        $imageWidthHeight = $this->getImageWidthHeight($imagePathName);
        $imageWidth = $imageWidthHeight['width'];
        $imageHeight = $imageWidthHeight['height'];
        //get image ratio
        $imageRatio = $this->getImageRatio($imageWidth, $imageHeight);
        list($xRatio, $yRatio) = explode(':', $imageRatio);
        $img = Image::make($imagePathName);
        if($xRatio > $yRatio) {
            $img->resize($setWidth, null, function ($constraint) {
                $constraint->aspectRatio();
            });
        } else {
            $img->resize(null, $setHeight, function ($constraint) {
                $constraint->aspectRatio();
            });
        }
        $img->save();
    }

    public function encodeImageBase64($imageData)
    {
        $imageData = $imageData;
        $encodeBase64 = base64_encode($imageData);
        return $encodeBase64;
    }

    public function decodeImageBase64($base64Code)
    {
        $base64Code = $base64Code;
        $decodeBase64 = base64_decode($base64Code);
        return $decodeBase64;
    }

    public function base64Details($base64Code)
    {
        list($type, $imageData) = explode(',', $base64Code);
        list($firstPart, $codeType) = explode(';', $type);
        list($part1, $part2) = explode(':', $firstPart);
        list($part1, $imageExt) = explode('/', $part2);
        $details = [
            'imageExt' => $imageExt,
            'imageData' => $imageData
        ];
        return $details;
    }

    public function toJpg($imagePathName)
    {
        list($name1, $name2, $name3) = explode('.', $imagePathName);
        $jpgPathFileName = $name1 . $name2 . '.jpg';
        $jpg = Image::make($imagePathName)->encode('jpg', 100);
        if($jpg->save($jpgPathFileName)) {
            unlink($imagePathName);
            return $jpgPathFileName;
        } else {
            return false;
        }
    }

    public function saveImage($imageData, $imagePathName)
    {
        $img = Image::make($imageData);
        if($img->save($imagePathName)) {
            return true;
        } else {
            return false;
        }
    }

    public function getImageWidthHeight($imagePathName)
    {
        list($width, $height, $type, $attr) = getimagesize($imagePathName);
        $imageProperties = [
            'width' => $width,
            'height' => $height,
        ];
        return $imageProperties;
    }

    public function getImageRatio($width, $height)
    {
        $wh = function($width, $height) use (&$wh) {
            return ($width % $height) ? $wh($height, $width % $height) : $height;
        };
        $getwh = $wh($width, $height);
        $xRatio = $width/$getwh;
        $yRatio = $height/$getwh;
        $theRatio = $xRatio . ':' . $yRatio;
        return $theRatio;
    }

    public function addWaterMark($imagePathName)
    {
        $img = Image::make($imagePathName);
        $waterMark = "D:\images\ant internet.jpg";
        $img->insert($waterMark, 'bottom-right', 5, 5);
        $img->save($imagePathName);
    }
}