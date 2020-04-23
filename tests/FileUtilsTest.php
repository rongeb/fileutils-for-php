<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Rongeb\FileUtils;

final class FileUtilsTest extends TestCase
{
    public function testShouldGetTheFileExtension(): void
    {
        $fileUtils = new FileUtils();
        $file = basename("dumb.rtf");
        $this->assertEquals("rtf",
            $fileUtils->extractExtension($file));
    }

    public function testShouldChangeAnInvalidFolderNameInAValidFolderName(): void
    {
        $fileUtils = new FileUtils();
        $folderName = " %<br>Foo* ";
        $this->assertEquals("Foo", $fileUtils->safeFolderName($folderName));
    }

    public function testShouldChangeAnInvalidFilenameInAValidFileName(): void
    {
        $fileUtils = new FileUtils();
        $filename = " àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ|/\:*?<> a ";
        $this->assertEquals("aaaaaeceeeeiiiinoooooeuuuueyyAAAAAeCEEEEIIIINOOOOOeUUUUeY________a",
            $fileUtils->formatNameFile($filename));
    }

    public function testShouldCreateAFile(): void
    {
        $fileUtils = new FileUtils();
        $this->assertEquals(true, $fileUtils->createFile("tests/files/", "test" . time() . ".txt"));
    }

    public function testShouldRenameFile(): void
    {
        $fileUtils = new FileUtils();
        $filename = "test" . time() . ".txt";
        FileUtils::createFile("tests/files/", $filename);
        $newFileName = "newtest" . time() . ".txt";
        $this->assertEquals(true, $fileUtils->renameExistingFile("tests/files/", $filename, $newFileName));
    }

    public function testShouldReduceImageSize()
    {
        $now = time();
        $fileUtils = new FileUtils();
        $imgFilename = "dumbImg.jpg";
        $reducedFilename = "testImg.jpg";
        $destination = "tests/files/";

        $fileUtils->reduceImgFiles($imgFilename, $reducedFilename, 200, 200, $destination,
            $destination, $now);

        $this->assertEquals(true, file_exists($destination . $now . $reducedFilename));
    }
}