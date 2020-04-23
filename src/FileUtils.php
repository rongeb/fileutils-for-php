<?php

Namespace Rongeb;

/**
 * Class FileUtils
 * Basic but useful class to manage a file with php
 */
final class FileUtils
{
    public static $renameUploadedFile = 0;
    public static $renameExistingFile = 1;
    public static $deleteExistingFile = 2;

    public function __construct()
    {
    }

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
        //$filename = strtr($filename, 'àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ', 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY'); //replace accents
        $filename = FileUtils::normalizeChar($filename);
        $filename = trim($filename); //trim the filename
        $filename = stripslashes($filename);
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
    public function uploadFile($file, $path, $newNameF, $actionFileExists)
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
    public function reduceImgFiles($imgFile, $finalName, $max_v, $max_h, $source, $destination, $prefix)
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
    public function deleteFile($file, $path)
    {
        if ((file_exists($path . $file)) && (isset($file))) {
            @unlink($path . $file);
        }
    }

    //https://stackoverflow.com/questions/3371697/replacing-accented-characters-php
    private static function normalizeChar($value) {
            $replace = array(
                'ъ'=>'-', 'Ь'=>'-', 'Ъ'=>'-', 'ь'=>'-',
                'Ă'=>'A', 'Ą'=>'A', 'À'=>'A', 'Ã'=>'A', 'Á'=>'A', 'Æ'=>'A', 'Â'=>'A', 'Å'=>'A', 'Ä'=>'Ae',
                'Þ'=>'B',
                'Ć'=>'C', 'ץ'=>'C', 'Ç'=>'C',
                'È'=>'E', 'Ę'=>'E', 'É'=>'E', 'Ë'=>'E', 'Ê'=>'E',
                'Ğ'=>'G',
                'İ'=>'I', 'Ï'=>'I', 'Î'=>'I', 'Í'=>'I', 'Ì'=>'I',
                'Ł'=>'L',
                'Ñ'=>'N', 'Ń'=>'N',
                'Ø'=>'O', 'Ó'=>'O', 'Ò'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'Oe',
                'Ş'=>'S', 'Ś'=>'S', 'Ș'=>'S', 'Š'=>'S',
                'Ț'=>'T',
                'Ù'=>'U', 'Û'=>'U', 'Ú'=>'U', 'Ü'=>'Ue',
                'Ý'=>'Y',
                'Ź'=>'Z', 'Ž'=>'Z', 'Ż'=>'Z',
                'â'=>'a', 'ǎ'=>'a', 'ą'=>'a', 'á'=>'a', 'ă'=>'a', 'ã'=>'a', 'Ǎ'=>'a', 'а'=>'a', 'А'=>'a', 'å'=>'a', 'à'=>'a', 'א'=>'a', 'Ǻ'=>'a', 'Ā'=>'a', 'ǻ'=>'a', 'ā'=>'a', 'ä'=>'ae', 'æ'=>'ae', 'Ǽ'=>'ae', 'ǽ'=>'ae',
                'б'=>'b', 'ב'=>'b', 'Б'=>'b', 'þ'=>'b',
                'ĉ'=>'c', 'Ĉ'=>'c', 'Ċ'=>'c', 'ć'=>'c', 'ç'=>'c', 'ц'=>'c', 'צ'=>'c', 'ċ'=>'c', 'Ц'=>'c', 'Č'=>'c', 'č'=>'c', 'Ч'=>'ch', 'ч'=>'ch',
                'ד'=>'d', 'ď'=>'d', 'Đ'=>'d', 'Ď'=>'d', 'đ'=>'d', 'д'=>'d', 'Д'=>'D', 'ð'=>'d',
                'є'=>'e', 'ע'=>'e', 'е'=>'e', 'Е'=>'e', 'Ə'=>'e', 'ę'=>'e', 'ĕ'=>'e', 'ē'=>'e', 'Ē'=>'e', 'Ė'=>'e', 'ė'=>'e', 'ě'=>'e', 'Ě'=>'e', 'Є'=>'e', 'Ĕ'=>'e', 'ê'=>'e', 'ə'=>'e', 'è'=>'e', 'ë'=>'e', 'é'=>'e',
                'ф'=>'f', 'ƒ'=>'f', 'Ф'=>'f',
                'ġ'=>'g', 'Ģ'=>'g', 'Ġ'=>'g', 'Ĝ'=>'g', 'Г'=>'g', 'г'=>'g', 'ĝ'=>'g', 'ğ'=>'g', 'ג'=>'g', 'Ґ'=>'g', 'ґ'=>'g', 'ģ'=>'g',
                'ח'=>'h', 'ħ'=>'h', 'Х'=>'h', 'Ħ'=>'h', 'Ĥ'=>'h', 'ĥ'=>'h', 'х'=>'h', 'ה'=>'h',
                'î'=>'i', 'ï'=>'i', 'í'=>'i', 'ì'=>'i', 'į'=>'i', 'ĭ'=>'i', 'ı'=>'i', 'Ĭ'=>'i', 'И'=>'i', 'ĩ'=>'i', 'ǐ'=>'i', 'Ĩ'=>'i', 'Ǐ'=>'i', 'и'=>'i', 'Į'=>'i', 'י'=>'i', 'Ї'=>'i', 'Ī'=>'i', 'І'=>'i', 'ї'=>'i', 'і'=>'i', 'ī'=>'i', 'ĳ'=>'ij', 'Ĳ'=>'ij',
                'й'=>'j', 'Й'=>'j', 'Ĵ'=>'j', 'ĵ'=>'j', 'я'=>'ja', 'Я'=>'ja', 'Э'=>'je', 'э'=>'je', 'ё'=>'jo', 'Ё'=>'jo', 'ю'=>'ju', 'Ю'=>'ju',
                'ĸ'=>'k', 'כ'=>'k', 'Ķ'=>'k', 'К'=>'k', 'к'=>'k', 'ķ'=>'k', 'ך'=>'k',
                'Ŀ'=>'l', 'ŀ'=>'l', 'Л'=>'l', 'ł'=>'l', 'ļ'=>'l', 'ĺ'=>'l', 'Ĺ'=>'l', 'Ļ'=>'l', 'л'=>'l', 'Ľ'=>'l', 'ľ'=>'l', 'ל'=>'l',
                'מ'=>'m', 'М'=>'m', 'ם'=>'m', 'м'=>'m',
                'ñ'=>'n', 'н'=>'n', 'Ņ'=>'n', 'ן'=>'n', 'ŋ'=>'n', 'נ'=>'n', 'Н'=>'n', 'ń'=>'n', 'Ŋ'=>'n', 'ņ'=>'n', 'ŉ'=>'n', 'Ň'=>'n', 'ň'=>'n',
                'о'=>'o', 'О'=>'o', 'ő'=>'o', 'õ'=>'o', 'ô'=>'o', 'Ő'=>'o', 'ŏ'=>'o', 'Ŏ'=>'o', 'Ō'=>'o', 'ō'=>'o', 'ø'=>'o', 'ǿ'=>'o', 'ǒ'=>'o', 'ò'=>'o', 'Ǿ'=>'o', 'Ǒ'=>'o', 'ơ'=>'o', 'ó'=>'o', 'Ơ'=>'o', 'œ'=>'oe', 'Œ'=>'oe', 'ö'=>'oe',
                'פ'=>'p', 'ף'=>'p', 'п'=>'p', 'П'=>'p',
                'ק'=>'q',
                'ŕ'=>'r', 'ř'=>'r', 'Ř'=>'r', 'ŗ'=>'r', 'Ŗ'=>'r', 'ר'=>'r', 'Ŕ'=>'r', 'Р'=>'r', 'р'=>'r',
                'ș'=>'s', 'с'=>'s', 'Ŝ'=>'s', 'š'=>'s', 'ś'=>'s', 'ס'=>'s', 'ş'=>'s', 'С'=>'s', 'ŝ'=>'s', 'Щ'=>'sch', 'щ'=>'sch', 'ш'=>'sh', 'Ш'=>'sh', 'ß'=>'ss',
                'т'=>'t', 'ט'=>'t', 'ŧ'=>'t', 'ת'=>'t', 'ť'=>'t', 'ţ'=>'t', 'Ţ'=>'t', 'Т'=>'t', 'ț'=>'t', 'Ŧ'=>'t', 'Ť'=>'t', '™'=>'tm',
                'ū'=>'u', 'у'=>'u', 'Ũ'=>'u', 'ũ'=>'u', 'Ư'=>'u', 'ư'=>'u', 'Ū'=>'u', 'Ǔ'=>'u', 'ų'=>'u', 'Ų'=>'u', 'ŭ'=>'u', 'Ŭ'=>'u', 'Ů'=>'u', 'ů'=>'u', 'ű'=>'u', 'Ű'=>'u', 'Ǖ'=>'u', 'ǔ'=>'u', 'Ǜ'=>'u', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'У'=>'u', 'ǚ'=>'u', 'ǜ'=>'u', 'Ǚ'=>'u', 'Ǘ'=>'u', 'ǖ'=>'u', 'ǘ'=>'u', 'ü'=>'ue',
                'в'=>'v', 'ו'=>'v', 'В'=>'v',
                'ש'=>'w', 'ŵ'=>'w', 'Ŵ'=>'w',
                'ы'=>'y', 'ŷ'=>'y', 'ý'=>'y', 'ÿ'=>'y', 'Ÿ'=>'y', 'Ŷ'=>'y',
                'Ы'=>'y', 'ž'=>'z', 'З'=>'z', 'з'=>'z', 'ź'=>'z', 'ז'=>'z', 'ż'=>'z', 'ſ'=>'z', 'Ж'=>'zh', 'ж'=>'zh'
            );
            return strtr($value, $replace);
    }
}

