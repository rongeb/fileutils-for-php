<?php

/**
 * Class FileUtils
 * Basic but useful class to manage a file with php
 */
final class FileUtils
{
    public static $renameUploadedFile = 0;
    public static $renameExistingFile = 1;
    public static $deleteExistingFile = 2;

    /**
     * @param $file
     * @return mixed|string
     */
    public function extractExtension($file)
    {
        $fileInfo = pathinfo($file);
        return $fileInfo['extension'];;
    }

    /**
     * @param $folderName
     * @return string
     */
    public function safeFolderName($folderName)
    {
        $escChars = array("^", "[", "]", "<", ">", "'", "~", "!", "?", "€", "/", "@", "\\", "#", "{", "}", "$", "%", ":", "(", ")", "+", "*");

        $folderName = strip_tags($folderName);
        $folderName = addcslashes($folderName, '%_');
        $folderName = trim($folderName);
        $folderName = str_replace($escChars, "", $folderName);
        $folderName = htmlspecialchars($folderName);

        return $folderName;
    }

    /**
     * @param $filename
     * @return string
     */
    public function formatNameFile($filename)
    {
        $filename = strtr($filename, 'àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ', 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY'); //replace accents
        $filename = trim($filename); //trim the filename
        $filename = stripslashes($filename); //apostrophe
        $filename = strtr($filename, '"|/\:*?<> ', '__________'); //replace forbidden char by a _ char
        return $filename;
    }

    /**
     * @param $path
     * @param $filename
     * @return bool
     */
    public function createFile($path, $filename)
    {
        $message = true;
        $isOk = fopen($path . $filename, 'w');
        if (!$isOk) {
            $message = false;
        }

        return $message;
    }

    /**
     * @param $path
     * @param $oldName
     * @param $newName
     * @return bool
     */
    public function renameExistingFile($path, $oldName, $newName)
    {
        return @rename($path . $oldName, $path . $newName);
        //var_dump(@rename($path . $oldName, $path . $newName));
    }

    /**
     * @param $file
     * @param $path
     * @param $newNameF
     * @param $actionFileExists
     * @return array
     */
    function uploadFile($file, $path, $newNameF, $actionFileExists)
    {
        $newNameOfTheFile = array(false, "");
        $deleteExisting = array(false, "");
        $renameExisting = array(false, "");

        if ($file['name'] != "") {
            if (empty($newNameF)) {
                $newNameOfTheFile[1] = $this->formatNameFile($file['name']);
            } else {
                $newNameOfTheFile[1] = $newNameF;
            }

            $newNameOfTheFile[1] = strtolower($newNameOfTheFile[1]);

            if (file_exists($path . $newNameOfTheFile[1])) { //check if a file already exists with the same name

                if ($actionFileExists == self::$deleteExistingFile) {// deleting the existing file
                    $deleteExisting[0] = @unlink($path . $newNameOfTheFile[1]);
                    $deleteExisting[1] = $newNameOfTheFile[1];

                } elseif ($actionFileExists == self::$renameUploadedFile) { //rename uploaded file
                    $newNameOfTheFile[1] = time() . "_" . $newNameOfTheFile[1];
                } elseif ($actionFileExists == self::$renameExistingFile) { //rename existing file
                    $renameExisting[0] = @rename($path . $newNameOfTheFile[1], $path . time() . "_" . $newNameOfTheFile[1]);
                    $renameExisting[1] = time() . "_" . $newNameOfTheFile[1];

                }
            }

            $newNameOfTheFile[0] = @copy($file['tmp_name'], $path . $newNameOfTheFile[1]);
        }

        return array("filename" => $newNameOfTheFile, "deleteExisting" => $deleteExisting[0], "renameExisting" => $renameExisting);
    }

    /**
     * @param $imgFile
     * @param $finalName
     * @param $max_v
     * @param $max_h
     * @param $source
     * @param $destination
     * @param $prefix
     * @return string|void
     */
    function reduceImgFiles($imgFile, $finalName, $max_v, $max_h, $source, $destination, $prefix)
    {
       if (($this->extractExtension($imgFile) != "jpg") && ($this->extractExtension($imgFile) != "jpeg") && ($this->extractExtension($imgFile) != "png"))
            return;

        // the scaled image will have the ti_ prefix and the original filename
        $tiFileImg = $prefix . $finalName;

        if (file_exists($destination . $tiFileImg)) {
            $tiFileImg = $prefix . time() . "_" . $finalName;
        }

        if ($this->extractExtension($imgFile) == "png") {
            $im = ImageCreateFrompng($source . $imgFile);
        } else {
            $im = ImageCreateFromjpeg($source . $imgFile);
        }

        $v = ImageSY($im); // height
        $h = ImageSX($im); // width

        //Check height
        if ($v > $max_v) {
            $heightRate = $v / $max_v;
            $ti_v = (int)floor($max_v); //
            $ti_h = (int)floor($h / $heightRate);
        } else
            $ti_v = $v;

        if ($ti_h != "")
            $h_comp = $ti_h;
        else
            $h_comp = $h;
        if ($ti_v != "")
            $v_comp = $ti_v;
        else
            $v_comp = $v;

        //check width
        if ($h_comp > $max_h) {
            $widthRate = $h_comp / $max_h;
            $ti_h = (int)floor($max_h);
            $ti_v = (int)floor($v_comp / $widthRate);
        } else
            $ti_h = $h_comp;

        $ti_im = ImageCreateTrueColor($ti_h, $ti_v);
        imagecopyresampled($ti_im, $im, 0, 0, 0, 0, $ti_h, $ti_v, $h, $v);

        if ($this->extractExtension($imgFile) == "png") {
            imagepng($ti_im, "$destination" . "$tiFileImg");
        } else {
            imagejpeg($ti_im, "$destination" . "$tiFileImg");
        }

        return $filename = $tiFileImg;
    }

    /**
     * @param $file
     * @param $path
     */
    function deleteFile($file, $path)
    {
        if ((file_exists($path . $file)) && (isset($file))) {
            @unlink($path . $file);
        }
    }
}

