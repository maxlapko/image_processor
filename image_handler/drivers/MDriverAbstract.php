<?php

/**
 * @author mlapko <maxlapko@gmail.com> 
 */
abstract class MDriverAbstract extends CComponent
{
    /**
     * 
     * @var mixed 
     */
    protected $_originalImage = null;
    
    /**
     * @var mixed 
     */
    protected $_image = null;
    
    protected $_format = 0;
    protected $_width = 0;
    protected $_height = 0;
    protected $_mimeType = '';
    protected $_fileName = '';

    const CORNER_LEFT_TOP = 1;
    const CORNER_RIGHT_TOP = 2;
    const CORNER_LEFT_BOTTOM = 3;
    const CORNER_RIGHT_BOTTOM = 4;
    const CORNER_CENTER = 5;
    
    const FLIP_HORIZONTAL = 1;
    const FLIP_VERTICAL = 2;
    const FLIP_BOTH = 3;

       
    /**
     *
     * @param integer $width
     * @param integer $height
     * @param boolean $proportional
     * @return \MDriverAbstract 
     */
    abstract public function resize($width, $height, $proportional = true);    
    
    
    /**
     *
     * @param string $watermarkFile
     * @param integer $offsetX
     * @param integer $offsetY
     * @param integer $corner
     * @return \MDriverAbstract|boolean
     * @throws Exception 
     */
    abstract public function watermark($watermarkFile, $offsetX, $offsetY, $corner = self::CORNER_RIGHT_BOTTOM);
    
    
    /**
     *
     * @param integer $mode
     * @return \MDriverAbstract
     * @throws Exception 
     */
    abstract public function flip($mode);
    
    
    /**
     *
     * @param integer $degrees
     * @param mixed $background
     * @return \MDriverAbstract 
     */
    abstract public function rotate($degrees, $backgroundColor = '#000000');
    
    
    /**
     *
     * @param integer $width
     * @param integer $height
     * @param integer $startX
     * @param integer $startY
     * @return \MDriverAbstract 
     */
    abstract public function crop($width, $height, $startX = false, $startY = false);
    
    
    /**
     *
     * @param string $text
     * @param string $fontFile
     * @param integer $size
     * @param mixed $color
     * @param integer $corner
     * @param integer $offsetX
     * @param integer $offsetY
     * @param integer $angle
     * @return \MDriverAbstract
     * @throws Exception 
     */
    abstract public function text($text, $fontFile, $size = 12, $color = '#000000', $corner = self::CORNER_LEFT_TOP, $offsetX = 0, $offsetY = 0, $angle = 0);
    

    /**
     *
     * @param integer $width
     * @param integer $geight
     * @param mixed $backgroundColor
     * @return \MDriverAbstract 
     */
    abstract public function resizeCanvas($width, $height, $backgroundColor = '#FFFFFF');
    
    
    /**
     * @var mixed $format 
     * @var integer $jpegQuality 
     */
    abstract public function show($format = false, $jpegQuality = 75);
    
    /**
     *
     * @param string|boolean $file
     * @param mixed $format
     * @param integer $jpegQuality
     * @param boolean $touch
     * @return \MDriverAbstract 
     */
    abstract public function save($file = false, $format = false, $jpegQuality = 75, $touch = false);
    
    
    abstract protected function _checkLoaded();
    
    /**
     * @param mixed $image 
     */
    abstract protected function _initImage($image = false);
    
    
    abstract protected function _freeImage();

    /**
     *
     * @param integer $width
     * @param integer $height
     * @param boolean $proportional
     * @return \MDriverAbstract 
     */
    public function thumb($width, $height, $proportional = true)
    {
        $this->_checkLoaded();

        if ($width !== false)
            $width = min($width, $this->_width);

        if ($height !== false)
            $height = min($height, $this->_height);


        $this->resize($width, $height, $proportional);

        return $this;
    }
    
    public function adaptiveThumb($width, $height)
    {
        $this->_checkLoaded();

        $width = intval($width);
        $height = intval($height);

        $widthProportion = $width / $this->_width;
        $heightProportion = $height / $this->_height;

        if ($widthProportion > $heightProportion) {
            $newWidth = $width;
            $newHeight = round($newWidth / $this->_width * $this->_height);
        } else {
            $newHeight = $height;
            $newWidth = round($newHeight / $this->_height * $this->_width);
        }

        $this->resize($newWidth, $newHeight);

        $this->crop($width, $height);

        return $this;
    }
    
    /**
     * 
     * @return mixed 
     */
    public function getImage()
    {
        return $this->_image;
    }

    /**
     *
     * @return string 
     */
    public function getFormat()
    {
        return $this->_format;
    }

    /**
     * @return integer
     */
    public function getWidth()
    {
        return $this->_width;
    }
    
    /**
     * @return integer
     */
    public function getHeight()
    {
        return $this->_height;
    }

    /**
     *
     * @return string 
     */
    public function getMimeType()
    {
        return $this->_mimeType;
    }         
    
    /**
     * Load image
     * 
     * @param string $file
     * @return mixed 
     */
    public function load($file)
    {
        $this->_freeImage();

        if (($this->_originalImage = $this->_loadImage($file))) {
            $this->_initImage();
            $this->_fileName = $file;
            return $this;
        } 
        return false;        
    }
    
    /**
     * Reload image
     * @return \MDriverAbstract 
     */
    public function reload()
    {
        $this->_checkLoaded();
        $this->_initImage();

        return $this;
    }
    
    /**
     * Calculate x, y position 
     * @param integer $corner
     * @param integer $imageWidth
     * @param integer $imageHeight
     * @param integer $offsetX
     * @param integer $offsetY
     * @return array
     * @throws Exception 
     */
    protected function _getCornerPosition($corner, $imageWidth, $imageHeight, $offsetX = 0, $offsetY = 0)
    {
        switch ($corner) {
            case self::CORNER_LEFT_TOP:
                $posX = $offsetX;
                $posY = $offsetY;
                break;
            case self::CORNER_RIGHT_TOP:
                $posX = $this->_width - $imageWidth - $offsetX;
                $posY = $offsetY;
                break;
            case self::CORNER_LEFT_BOTTOM:
                $posX = $offsetX;
                $posY = $this->_height - $imageHeight - $offsetY;
                break;
            case self::CORNER_RIGHT_BOTTOM:
                $posX = $this->_width - $imageWidth - $offsetX;
                $posY = $this->_height - $imageHeight - $offsetY;
                break;
            case self::CORNER_CENTER:
                $posX = floor(($this->_width - $imageWidth) / 2);
                $posY = floor(($this->_height - $imageHeight) / 2);
                break;
            default:
                throw new Exception('Invalid $corner value');
        }
        
        return array($posX, $posY);
    }
    
    public function __destruct()
    {
        $this->_freeImage();
    }
    

}
