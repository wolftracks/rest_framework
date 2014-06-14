<?php
/*
 * By installing or using this file, you are confirming on behalf of the entity
 * subscribed to the SugarCRM Inc. product ("Company") that Company is bound by
 * the SugarCRM Inc. Master Subscription Agreement ("MSA"), which is viewable at:
 * http://www.sugarcrm.com/master-subscription-agreement
 *
 * If Company is not bound by the MSA, then by installing or using this file
 * you are agreeing unconditionally that Company will be bound by the MSA and
 * certifying that you have authority to bind Company accordingly.
 *
 * Copyright  2004-2013 SugarCRM Inc.  All rights reserved.
 */

/**
 *
 */
class ImageCaptureService
{
    const THUMBNAIL_MAX_WIDTH  = 200;
    const THUMBNAIL_MAX_HEIGHT = 150;

    protected $customerId;
    protected $coreUrl;

    /**
     * Load Configuration Data
     */
    public function __construct($customerId)
    {
        $this->customerId = $customerId;
        $this->coreUrl = SugarConfig::get('site.core_url');
    }

    /**
     * Generate Thumbnail Image From Html
     *
     * @param string  Html Content
     * @param array options e.g. image_type
     * @return array Image Data
     * @throws SugarApiException
     */
    public function imageFromHtml($htmlBody, $options=array())
    {
        $fileId = $this->customerId . "-" . create_guid();
        $tempHtmlFile = "temp/{$fileId}.html";
        $url = $this->coreUrl . "/" . $tempHtmlFile;

        try {
            $this->saveHtmlAsTempFile($tempHtmlFile, $htmlBody);

            $result = $this->urlToThumbnail($fileId, $url, $options);

            @unlink($tempHtmlFile);
            return $result;
        } catch (Exception $e) {
            @unlink($tempHtmlFile);
            throw new SugarApiExceptionError($e->getMessage);
        }
    }

    /**
     * Generate Thumbnail Image From URL
     *
     * @param string  URL
     * @param array options e.g. image_type
     * @return array Image Data
     * @throws SugarApiException
     */
    public function imageFromUrl($url, $options=array())
    {
        $fileId = $this->customerId . "-" . create_guid();
        try {
            $result = $this->urlToThumbnail($fileId, $url, $options);
            return $result;
        } catch (Exception $e) {
            throw new SugarApiExceptionError($e->getMessage);
        }
    }

    /**
     * Save HTML Content As a Temp File
     *
     * @param string  Temp File Name
     * @param string  Html Content
     * @return bool  true=success
     * @throws Exception
     */
    protected function saveHtmlAsTempFile($tempHtmlFile, $htmlBody) {
        $result = file_put_contents($tempHtmlFile, $htmlBody);
        $failure = ($result === false);
        if ($failure) {
            throw new Exception('Image Creation Failure');
        }
        return true;
    }

    /**
     * Render URL As Thumbnail Image
     *
     * @param string  Unique FileId
     * @param string  URL
     * @param array options e.g. image_type
     * @return array Image Info
     * @throws Exception
     */
    protected function urlToThumbnail($fileId, $url, $options=array()) {
        $output=null;
        $imageType = "jpg";
        $fileExtension = 'jpg';
        $tgtWidth  = static::THUMBNAIL_MAX_WIDTH;
        $tgtHeight = static::THUMBNAIL_MAX_HEIGHT;
        if (!empty($options['image_type']) && $options['image_type'] === 'png') {
            $imageType = 'png';
            $fileExtension = 'png';
        }
        if (!empty($options['image_width'])) {
            $tgtWidth = (int) $options['image_width'];
        }
        if (!empty($options['image_height'])) {
            $tgtHeight = (int) $options['image_height'];
        }

        ob_start();
        try {
            $fileName = "{$fileId}.{$fileExtension}";
            $data=array();
            $tempImageFile =  dirname(__FILE__) . "/../../../temp/{$fileName}";
            $html2ImageScript =  dirname(__FILE__) . "/../../../bin/thumbnail/html2image.js";
            $command = dirname(__FILE__) . "/../../../bin/thumbnail/html2image {$html2ImageScript} {$url} {$tempImageFile} 2>&1";
            $lastline = exec($command, $data, $output1);
            ob_clean();

            $result = array();
            $result['fileName'] = $fileName;

            if (file_exists($tempImageFile)) {
                $this->resize_image($tempImageFile, $tempImageFile, $tgtWidth, $tgtHeight, true);
                $imageData = getimagesize($tempImageFile);

                $result['success'] = true;
                $result['image_size'] = filesize($tempImageFile);
                $result['mime_type'] = $imageData['mime'];
                $result['image_width'] = $imageData[0];
                $result['image_height'] = $imageData[1];
                $result['contents'] = base64_encode(file_get_contents($tempImageFile));

                /**--- Can probably remove at some point ----**/
                $result['Html2ImageScript'] = $html2ImageScript;
                $result['URL'] = $url;
                $result['TempImageFile'] = $tempImageFile;
                $result['Exec_Command'] = $command;
                $result['Exec_Data'] = $data;

            } else {
                // Fail Softly For Now
                // throw new Exception("Image Capture Failed");
                $result['success'] = false;
                $result['Html2ImageScript'] = $html2ImageScript;
                $result['URL'] = $url;
                $result['TempImageFile'] = $tempImageFile;
                $result['Exec_Command'] = $command;
                $result['Exec_Data'] = $data;
            }

            @unlink($tempImageFile);
            return $result;
        } catch (Exception $e) {
            if ($output==null) {
                ob_clean();
            }
            @unlink($tempImageFile);
            throw $e;
        }
    }


    private function resize_image($srcFilePath, $tgtFilePath, $tgtWidth, $tgtHeight, $crop = 0)
    {
        $imageData = getimagesize($srcFilePath);
        $image_width = $imageData[0];
        $image_height = $imageData[1];
        $imageType = $imageData[2];

        switch ($imageType) {
            case IMAGETYPE_GIF:
                $img = imagecreatefromgif($srcFilePath);
                break;
            case IMAGETYPE_JPEG:
                $img = imagecreatefromjpeg($srcFilePath);
                break;
            case IMAGETYPE_PNG:
                $img = imagecreatefrompng($srcFilePath);
                break;
            default:
                return false;
        }

        // resize
        if ($crop) {
            if ($image_width < $tgtWidth || $image_height < $tgtHeight) {
                rename($srcFilePath, $tgtFilePath);
                return true;
            }
            $ratio = max($tgtWidth / $image_width, $tgtHeight / $image_height);
            $image_height = $tgtHeight / $ratio;
            $x = ($image_width - $tgtWidth / $ratio) / 2;
            $image_width = $tgtWidth / $ratio;
        } else {
            if ($image_width < $tgtWidth && $image_height < $tgtHeight) {
                rename($srcFilePath, $tgtFilePath);
                return true;
            }
            $ratio = min($tgtWidth / $image_width, $tgtHeight / $image_height);
            $tgtWidth = $image_width * $ratio;
            $tgtHeight = $image_height * $ratio;
            $x = 0;
        }

        $newImage = imagecreatetruecolor($tgtWidth, $tgtHeight);

        // preserve transparency
        if ($imageType == IMAGETYPE_GIF or $imageType == IMAGETYPE_PNG) {
            imagecolortransparent($newImage, imagecolorallocatealpha($newImage, 0, 0, 0, 127));
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
        }

        imagecopyresampled($newImage, $img, 0, 0, $x, 0, $tgtWidth, $tgtHeight, $image_width, $image_height);

        switch ($imageType) {
            case IMAGETYPE_GIF:
                imagegif($newImage, $tgtFilePath);
                break;
            case IMAGETYPE_JPEG:
                imagejpeg($newImage, $tgtFilePath);
                break;
            case IMAGETYPE_PNG:
                imagepng($newImage, $tgtFilePath);
                break;
        }
        return true;
    }
}
