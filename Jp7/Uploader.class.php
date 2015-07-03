<?php

/*
$upload = new Jp7_Uploader('post');
if ($upload->hasFile('foto')) {
    $post->foto = $upload->save('foto', 'temp_foto.jpg');
}
*/

/**
 * Handler to help using $_FILES array.
 */
class Jp7_Uploader
{
    protected $fieldName;
    protected $extensionsFilter;
    protected $typesFilter;
    protected $basePath = '../../upload/';
    /**
     * Numero em bytes: 9000000 = 9MB (Aprox.).
     *
     * @var int
     */
    protected $maxSize = 0;

    /**
     * Creates a new $_FILES handler.
     *
     * @param string $fieldName        Name of the submitted field.
     * @param string $extensionsFilter Regex for $extensions [optional]
     * @param string $typesFilter      Regex for $types [optional]
     */
    public function __construct($fieldName = '', $extensionsFilter = '/\.(jp[e]?g|gif|png|bmp)$/i', $typesFilter = '/image\/([p]?jp[e]?g|gif|png|bmp)/i')
    {
        $this->fieldName = $fieldName;
        $this->extensionsFilter = $extensionsFilter;
        $this->typesFilter = $typesFilter;
    }

    public function count()
    {
        return count($_FILES[$this->fieldName]['name']);
    }

    /**
     * Saves the file to the given destination (keeping the uploaded extension).
     *
     * @param string $key
     * @param string $destination
     * @param array  $size        array(width, height)
     * @param string $resizeMode  Available: resize, crop
     *
     * @return string Full destination path (Base + Destination + Uploaded extension).
     *
     * @throws Jp7_Uploader_InvalidExtensionException For type/extension not valid.
     * @throws Exception                              For unknown upload error (browser, server, etc).
     */
    public function save($key, $destination, array $newsize = array(), $resizeMode = 'resize')
    {
        extract($_FILES[$this->fieldName]);

        // Validation
        if (!$name[$key]) {
            return false;
        }
        if ($error[$key]) {
            // @todo exibir mensagem de acordo com o erro encontrado
            throw new Exception('There was an error while uploading: '.$name[$key]);
        }
        if (!preg_match($this->extensionsFilter, $name[$key]) || !preg_match($this->typesFilter, $type[$key])) {
            throw new Jp7_Uploader_InvalidExtensionException($name[$key]);
        }
        if ($this->maxSize && $size[$key] > $this->maxSize) {
            throw new Jp7_Uploader_InvalidSizeException($name[$key]);
        }

        // Copy
        $extension = mb_strtolower(preg_replace('/(.*)(\.[^\.]*)$/', '\2', $name[$key]));
        if ($extension == '.jpeg') {
            $extension = '.jpg';
        }
        $finalDestination = $this->getBasePath().$destination.$extension;

        // Mkdir if needed
        if (!is_dir(dirname($finalDestination))) {
            @mkdir(dirname($finalDestination));
            @chmod(dirname($finalDestination), 0777);
        }

        if ($newsize && in_array($extension, array('.gif', '.jpg'))) {
            $resource = ($extension == '.jpg') ? imagecreatefromjpeg($tmp_name[$key]) : imagecreatefromgif($tmp_name[$key]);
            jp7_resizeImage($resource, $tmp_name[$key], $finalDestination, $newsize[0], $newsize[1], 90, 10000000, $resizeMode == 'crop');
            imagedestroy($resource);
        } else {
            // Copy
            copy($tmp_name[$key], $finalDestination);
        }
        if (!is_file($finalDestination)) {
            $msg = 'Impossível copiar arquivo "'.$tmp_name[$key].'" para "'.$finalDestination.'".<br /> getcwd(): '.getcwd();
            if (!is_file($tmp_name[$key])) {
                $msg .= '<br /> Arquivo '.basename($tmp_name[$key]).' não existe.';
            }
            if (!is_dir(dirname($finalDestination))) {
                $msg .= '<br /> Diretório '.dirname($finalDestination).' não existe.';
            } elseif (!is_writable(dirname($finalDestination))) {
                $msg .= '<br /> Diretório '.dirname($finalDestination).' não tem permissão de escrita.';
            }
            throw new Exception($msg);
        }

        @chmod($finalDestination, 0777);

        return $finalDestination;
    }
    /**
     * Returns $fieldName.
     *
     * @see Jp7_Uploader::$fieldName
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }
    /**
     * Sets $fieldName.
     *
     * @param object $fieldName
     *
     * @see Jp7_Uploader::$fieldName
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
    }
    /**
     * Returns $extensionsFilter.
     *
     * @see Jp7_Uploader::$extensionsFilter
     */
    public function getExtensionsFilter()
    {
        return $this->extensionsFilter;
    }
    /**
     * Sets $extensionsFilter.
     *
     * @param object $extensionsFilter
     *
     * @see Jp7_Uploader::$extensionsFilter
     */
    public function setExtensionsFilter($extensionsFilter)
    {
        $this->extensionsFilter = $extensionsFilter;
    }
    /**
     * Returns $typesFilter.
     *
     * @see Jp7_Uploader::$typesFilter
     */
    public function getTypesFilter()
    {
        return $this->typesFilter;
    }
    /**
     * Sets $typesFilter.
     *
     * @param object $typesFilter
     *
     * @see Jp7_Uploader::$typesFilter
     */
    public function setTypesFilter($typesFilter)
    {
        $this->typesFilter = $typesFilter;
    }
    /**
     * Returns $basePath.
     *
     * @see Jp7_Uploader::$basePath
     */
    public function getBasePath()
    {
        return $this->basePath;
    }
    /**
     * Sets $basePath.
     *
     * @param object $basePath
     *
     * @see Jp7_Uploader::$basePath
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
    }
    /**
     * Returns $maxSize.
     *
     * @return int
     *
     * @see Jp7_Uploader::$maxSize
     */
    public function getMaxSize()
    {
        return $this->maxSize;
    }
    /**
     * Sets $maxSize.
     *
     * @param int $maxSize
     *
     * @see Jp7_Uploader::$maxSize
     */
    public function setMaxSize($maxSize)
    {
        $this->maxSize = $maxSize;
    }
}
