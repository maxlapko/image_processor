<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'MDriverAbstract.php';

/**
 * @author mlapko <maxlapko@gmail.com>
 * @version 0.1
 */
class MDriverImageMagic extends MDriverAbstract
{
    const IMG_GIF  = 'GIF';
    const IMG_JPEG = 'JPEG';
    const IMG_PNG  = 'PNG';    

    public function resize($width, $height, $proportional = true)
    {
        $this->_checkLoaded();

        $width = $width !== false ? $width : $this->_width;
        $height = $height !== false ? $height : $this->_height;

        if ($proportional) {
            $newHeight = $height;
            $newWidth = round($newHeight / $this->_height * $this->_width);

            if ($newWidth > $width) {
                $newWidth = $width;
                $newHeight = round($newWidth / $this->_width * $this->_height);
            }
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }
        
        $this->_image->scaleImage($newWidth, $newHeight);
        
        $this->_width = $newWidth;
        $this->_height = $newHeight;

        return $this;
    }

    public function watermark($watermarkFile, $offsetX, $offsetY, $corner = self::CORNER_RIGHT_BOTTOM)
    {

        $this->_checkLoaded();

        if ($watermark = $this->_loadImage($watermarkFile)) {

            $posX = 0;
            $posY = 0;
            if ($this->_width < $watermark['width'] || $this->_height < $watermark['height']) {
                $watermark['image']->scaleImage($this->_width, $this->_height, true);
            }
            
            list($posX, $posY) = $this->_getCornerPosition(
                $corner, $watermark['image']->getImageWidth(), 
                $watermark['image']->getImageHeight(), $offsetX, $offsetY
            );            

            $this->_image->compositeImage($watermark['image'], Imagick::COMPOSITE_OVER, $posX, $posY);

            return $this;
        } else {
            return false;
        }
    }

    public function flip($mode)
    {
        $this->_checkLoaded();

        switch ($mode) {
            case self::FLIP_HORIZONTAL:
                $this->_image->flopImage();                
                break;
            case self::FLIP_VERTICAL:
                $this->_image->flipImage();
                break;
            case self::FLIP_BOTH:
                $this->_image->flopImage();
                $this->_image->flipImage();                
                break;
            default:
                throw new Exception('Invalid $mode value');
        }

        return $this;
    }

    public function rotate($degrees, $backgroundColor = '#000000')
    {
        $this->_checkLoaded();

        $degrees = (int) $degrees;
        $this->_image->rotateImage(new ImagickPixel($backgroundColor), $degrees);
        
        $geometry = $this->_image->getImageGeometry();
        $this->_width = $geometry['width'];
        $this->_height = $geometry['height'];;

        return $this;
    }

    public function crop($width, $height, $startX = false, $startY = false)
    {
        $this->_checkLoaded();

        $width = (int) $width;
        $height = (int) $height;

        //Centered crop
        $startX = $startX === false ? floor(($this->_width - $width) / 2) : intval($startX);
        $startY = $startY === false ? floor(($this->_height - $height) / 2) : intval($startY);

        //Check dimensions
        $startX = max(0, min($this->_width, $startX));
        $startY = max(0, min($this->_height, $startY));
        $width = min($width, $this->_width - $startX);
        $height = min($height, $this->_height - $startY);

        $this->_image->cropImage($width, $height, $startX, $startY);
        $this->_width = $width;
        $this->_height = $height;

        return $this;
    }

    public function text($text, $fontFile, $size = 12, $color = '#000000', $corner = self::CORNER_LEFT_TOP, $offsetX = 0, $offsetY = 0, $angle = 0)
    {
        $this->_checkLoaded();

        /* This object will hold the font properties */
        $draw = new ImagickDraw();
        /* Setting gravity to the center changes the origo
                where annotation coordinates are relative to */
        $draw->setGravity(Imagick::GRAVITY_CENTER);

        /* Use a custom truetype font */
        $draw->setFont($fontFile);
        /* Set the font size */
        $draw->setFontSize($size);
        
        $im = new Imagick();
        /* Get the text properties */
        $properties = $im->queryFontMetrics($draw, $text);

        /* Region size for the watermark. Add some extra space on the sides  */
        $textWidth = intval($properties['textWidth'] + 5);
        $textHeight = intval($properties['textHeight'] + 5);
 
        /* Create a canvas using the font properties.
                Add some extra space on width and height */
        $im->newImage($textWidth, $textHeight, new ImagickPixel("transparent"));
        /* Use png format */
        $draw->setFillColor($color);
        $im->setImageFormat('png');
        $im->annotateImage($draw, 0, 0, 0, $text);
        
        list($posX, $posY) = $this->_getCornerPosition($corner, $textWidth, $textHeight, $offsetX, $offsetY);
        
        /* Composite the watermark on the image to the top left corner */
        $this->_image->compositeImage($im, Imagick::COMPOSITE_OVER, $posX, $posY);

        return $this;
    }

    

    public function resizeCanvas($width, $height, $backgroundColor = '#FFFFFF')
    {
        $this->_checkLoaded();
        
 
        /* Create a canvas with the desired color */
        $canvas = new Imagick();
        $canvas->newImage($width, $height, $backgroundColor);
        $canvas->setImageFormat($this->_format);

        /* Get the image geometry */
        $this->_image->thumbnailImage($width, $height, true);
        $geometry = $this->_image->getImageGeometry();

        $posX = floor(($width - $geometry['width']) / 2);
        $posY = floor(($height - $geometry['height']) / 2);

        /* Composite on the canvas  */
        $canvas->compositeImage($this->_image, imagick::COMPOSITE_OVER, $posX, $posY);        
        
        $this->_image = $canvas;
        $this->_width = $width;
        $this->_height = $height;

        return $this;
    }

    public function show($format = false, $jpegQuality = 75)
    {
        $this->_checkLoaded();

        if (!$format) {
            $format = $this->_format;
        }
        if ($format === 'JPEG' || $format === 'JPG') {
            $this->_image->setCompression(Imagick::COMPRESSION_JPEG);
            $this->_image->setCompressionQuality($jpegQuality);
        }
        header('Content-Type: image/' . $this->_image->getImageFormat());
        echo $this->_image;
        return $this;
    }
    
    /**
     *
     * @param type $file
     * @param type $format
     * @param type $jpegQuality
     * @param type $touch
     * @return \MImageHandler_IM 
     */
    public function save($file = false, $format = false, $jpegQuality = 75, $touch = false)
    {
        if (!$file) {
            $file = $this->_fileName;
        }

        $this->_checkLoaded();

        if (!$format) {
            $format = $this->_format;
        }
        $format = strtoupper($format);
        if ($format === self::IMG_JPEG || $format === 'JPG') {
            $this->_image->setCompression(Imagick::COMPRESSION_JPEG);
            $this->_image->setCompressionQuality($jpegQuality);
        }
        $this->_image->writeImage($file);
        if ($touch && $file != $this->fileName) {
            touch($file, filemtime($this->fileName));
        }
        return $this;
    }
    
    protected function _freeImage()
    {
        $this->_destroyImage($this->_image);
        $this->_image = null;
        if (isset($this->_originalImage['image'])) {
            $this->_destroyImage($this->_originalImage['image']);            
        }
        $this->_originalImage = null;
    }

    protected function _checkLoaded()
    {
        if (!($this->_image instanceof Imagick)) {
            throw new Exception('Image was not loaded.');
        }
    }
    
    /**
     * Destroy image
     * @var Imagick $image
     */
    protected function _destroyImage($image)
    {
        if ($image instanceof Imagick) {
            $image->destroy();
        }
    }
    
    /**
     * 
     * @var string $file path to image file
     */
    protected function _loadImage($file)
    {
        $result = array();
        
        $result['image'] = new Imagick($file);
        $geometry = $result['image']->getImageGeometry();
        $result['width'] = $geometry['width'];
        $result['height'] = $geometry['height'];
        $result['format'] = $result['image']->getImageFormat();
        return $result;
        
    }
    
    /**
     *
     * @param mixed $image 
     */
    protected function _initImage($image = false)
    {
        if ($image === false) {
            $image = $this->_originalImage;
        }

        $this->_width = $image['width'];
        $this->_height = $image['height'];
        $this->_format = $image['format'];

        //Image
        $this->_destroyImage($this->_image);
        $this->_image = $image['image'];
            
    }

}
