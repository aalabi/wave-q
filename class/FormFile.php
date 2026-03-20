<?php

/**
 * FormFile
 *
 * A class for mangaing $_FILES from HTML form
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   Alabian Solutions Limited
 * @version 	1.0 => October 2023
 * @link        alabiansolutions.com
*/

class FormFileExpection extends Exception
{
}

class FormFile
{
    /** @var array for holding $_FILES */
    protected array $file;

    /** @var array for a collection of permitted file types ['jpg', 'png', etc.] */
    protected array $permittedTypes;

    /** @var int for holding $_FILES */
    protected int $maxSize;

    /** @var string filename extension */
    protected string $fileExtension;

    /** @var bool check there was an attached file */
    protected bool $wasFileAttached;

    /** @var string to indicate state of no uploaded file */
    public const NO_FILE_UPLOADED = "no file was uploaded" ;

    /**
     * instantiation of FormFile
     *
     * @param array $file holding $_FILES[f=> ['name'=>$n, 'type'=> $t, 'tmp_name'=>$tm, 'error'=>$e, 'size'=> $s]]
     * @param array $permittedTypes a collection of permitted file types ['jpg', 'png', etc.]
     * @param int $maxSize the maximum file size in bytes
     */
    public function __construct(array $file, array $permittedTypes, int $maxSize)
    {
        $this->file = $file;
        $this->permittedTypes = array_map(fn ($value) => strtolower($value), $permittedTypes);
        $this->maxSize = $maxSize;
        $this->fileExtension = "";
        $this->wasFileAttached = $file['error'] == 4 ? false : true;
        if ($file['name']) {
            $this->fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        }
    }

    protected function errorCollections():array
    {
        return [
            0=>"OK",
            1=>"file exceeds the upload_max_filesize directive in php.ini",
            2=>"file exceeds the MAX_FILE_SIZE",
            3=>"file was only partially uploaded",
            4=>FormFile::NO_FILE_UPLOADED,
            6=>"missing a temporary folder",
            7=>"file failed to write to disk",
            8=>"php extension stopped uploadi"];
    }

    /**
     * for validating uploaded file
     *
     * @return array ["data"=>$_FILES, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validateFile():array
    {
        $file = $this->file;
        $errors = [];

        if ($file['error']) {
            $errors[] = $this->errorCollections()[$file['error']];
        }
        if (!in_array(FormFile::NO_FILE_UPLOADED, $errors)) {
            if ($file['size'] > $this->maxSize) {
                $errors[] = "file larger than ".$this->maxSize;
            }
            if (!in_array(pathinfo($file['name'], PATHINFO_EXTENSION), $this->permittedTypes)) {
                $errors[] = "file extension not among (".implode(", ", $this->permittedTypes).")";
            }
        }
        
        return ['data' => $file, 'errors' => $errors];
    }

    /**
     * for moving the uploaded file to a new location on the server
     *
     * @param string $path path on the server where the uploaded file is stored
     * @param string|null $nameWithoutExtension the new filename without an extension, it retains the original extension
     * @param string $nameWithExtension the new filename with an extension, can change the original extension, if present nameWithoutExtension is ignored
     * @return boolean true if the uploaded was successful
     */
    public function moveFile(string $path, string|null $nameWithoutExtension = null, string $nameWithExtension = ""):bool
    {
        $filename = $nameWithExtension ? $nameWithExtension : "$nameWithoutExtension.{$this->fileExtension}";
        $path = substr($path, -1) == DIRECTORY_SEPARATOR ? $path : $path.DIRECTORY_SEPARATOR;
        return move_uploaded_file($this->file['tmp_name'], $path.$filename);
    }

    /**
     * for getting the file extension
     *
     * @return string the file extension
     */
    public function getFileExtension():string
    {
        return $this->fileExtension;
    }

    /**
     * for checking if a file was even attached to the HTML form
     *
     * @return boolean true if a file was attached
     */
    public function wasFileAttached():bool
    {
        return $this->wasFileAttached;
    }


}
