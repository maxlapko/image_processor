<?php

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'MDriverAbstract.php';

/**
 * Image gd handler
 * @author Pelesh Yaroslav aka Tokolist http://tokolist.com
 * @link http://code.google.com/p/yii-components/
 * @version 1.0
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class MDriverGD extends MDriverAbstract
{
    const IMG_GIF = 1;
    const IMG_JPEG = 2;
    const IMG_PNG = 3;   
    
    public $transparencyColor = array(0, 0, 0);
    
    
    /**
     * Resize image
     * @param integer $width
     * @param integer $height
     * @param boolean $proportional
     * @return \MDriverGD 
     */
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

        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        $this->_preserveTransparency($newImage);

        imagecopyresampled($newImage, $this->_image, 0, 0, 0, 0, $newWidth, $newHeight, $this->_width, $this->_height);

        imagedestroy($this->_image);

        $this->_image = $newImage;
        $this->_width = $newWidth;
        $this->_height = $newHeight;

        return $this;
    }
    
    /**
     *
     * @param string $watermarkFile
     * @param integer $offsetX
     * @param integer $offsetY
     * @param integer $corner
     * @return \MDriverGD|boolean 
     */
    public function watermark($watermarkFile, $offsetX, $offsetY, $corner = self::CORNER_RIGHT_BOTTOM)
    {

        $this->_checkLoaded();

        if ($wImg = $this->_loadImage($watermarkFile)) {
            list($posX, $posY) = $this->_getCornerPosition($corner, $wImg['width'], $wImg['height'], $offsetX, $offsetY);
            
            imagecopy($this->_image, $wImg['image'], $posX, $posY, 0, 0, $wImg['width'], $wImg['height']);


            imagedestroy($wImg['image']);

            return $this;
        } else {
            return false;
        }
    }
    
    /**
     *
     * @param integer $mode
     * @return \MDriverGD
     * @throws Exception 
     */
    public function flip($mode)
    {
        $this->_checkLoaded();

        $srcX = 0;
        $srcY = 0;
        $srcWidth = $this->_width;
        $srcHeight = $this->_height;

        switch ($mode) {
            case self::FLIP_HORIZONTAL:
                $srcX = $this->_width - 1;
                $srcWidth = -$this->_width;
                break;
            case self::FLIP_VERTICAL:
                $srcY = $this->_height - 1;
                $srcHeight = -$this->_height;
                break;
            case self::FLIP_BOTH:
                $srcX = $this->_width - 1;
                $srcY = $this->_height - 1;
                $srcWidth = -$this->_width;
                $srcHeight = -$this->_height;
                break;
            default:
                throw new Exception('Invalid $mode value.');
        }

        $newImage = imagecreatetruecolor($this->_width, $this->_height);
        $this->_preserveTransparency($newImage);

        imagecopyresampled($newImage, $this->_image, 0, 0, $srcX, $srcY, $this->_width, $this->_height, $srcWidth, $srcHeight);

        imagedestroy($this->_image);

        $this->_image = $newImage;
        //dimensions not changed

        return $this;
    }
    
    /**
     * Rotate image
     * 
     * @param integer $degrees
     * @param mixed $backgroundColor
     * @return \MDriverGD 
     */
    public function rotate($degrees, $backgroundColor = array(0, 0, 0))
    {
        $this->_checkLoaded();

        $degrees = (int) $degrees;
        $backgroundColor = $this->_getColor($this->_image, $backgroundColor);
        
        $this->_image = imagerotate($this->_image, $degrees, (int) $background);

        $this->_width = imagesx($this->_image);
        $this->_height = imagesy($this->_image);

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


        $newImage = imagecreatetruecolor($width, $height);

        $this->_preserveTransparency($newImage);

        imagecopyresampled($newImage, $this->_image, 0, 0, $startX, $startY, $width, $height, $width, $height);

        imagedestroy($this->_image);

        $this->_image = $newImage;
        $this->_width = $width;
        $this->_height = $height;

        return $this;
    }

    public function text($text, $fontFile, $size = 12, $color = array(0, 0, 0), $corner = self::CORNER_LEFT_TOP, $offsetX = 0, $offsetY = 0, $angle = 0)
    {
        $this->_checkLoaded();

        $bBox = imagettfbbox($size, $angle, $fontFile, $text);
        $textHeight = $bBox[1] - $bBox[7];
        $textWidth = $bBox[2] - $bBox[0];

        $color = imagecolorallocate($this->_image, $color[0], $color[1], $color[2]);
        list($posX, $posY) = $this->_getCornerPosition($corner, $textWidth, $textHeight, $offsetX, $offsetY);

        imagettftext($this->_image, $size, $angle, $posX, $posY + $textHeight, $color, $fontFile, $text);

        return $this;
    }

    public function resizeCanvas($width, $height, $backgroundColor = array(255, 255, 255))
    {
        $this->_checkLoaded();

        $newWidth = min($width, $this->_width);
        $newHeight = min($height, $this->_height);

        $widthProportion = $newWidth / $this->_width;
        $heightProportion = $newHeight / $this->_height;

        if ($widthProportion < $heightProportion) {
            $newHeight = round($widthProportion * $this->_height);
        } else {
            $newWidth = round($heightProportion * $this->_width);
        }

        $posX = floor(($width - $newWidth) / 2);
        $posY = floor(($height - $newHeight) / 2);


        $newImage = imagecreatetruecolor($width, $height);

        $backgroundColor = $this->_getColor($newImage, $backgroundColor);
        imagefill($newImage, 0, 0, $backgroundColor);

        imagecopyresampled($newImage, $this->_image, $posX, $posY, 0, 0, $newWidth, $newHeight, $this->_width, $this->_height);

        imagedestroy($this->_image);

        $this->_image = $newImage;
        $this->_width = $width;
        $this->_height = $height;

        return $this;
    }

    public function grayscale()
    {
        //$newImage=$this->createImage($this->_width, $this->_height, $this->trueColor);
        $newImage = imagecreatetruecolor($this->_width, $this->_height);

        imagecopy($newImage, $this->_image, 0, 0, 0, 0, $this->_width, $this->_height);
        imagecopymergegray($newImage, $newImage, 0, 0, 0, 0, $this->_width, $this->_height, 0);

        imagedestroy($this->_image);

        $this->_image = $newImage;

        return $this;
    }

    public function show($format = false, $jpegQuality = 75)
    {
        $this->_checkLoaded();

        if (!$format) {
            $format = $this->_format;
        }

        switch ($format) {
            case self::IMG_GIF:
                header('Content-type: image/gif');
                imagegif($this->_image);
                break;
            case self::IMG_JPEG:
                $jpegQuality = $this->_quality === null ? $jpegQuality : $this->_quality;
                header('Content-type: image/jpeg');
                imagejpeg($this->_image, null, $jpegQuality);
                break;
            case self::IMG_PNG:
                header('Content-type: image/png');
                imagepng($this->_image);
                break;
            default:
                throw new Exception('Invalid image format for putput');
        }

        return $this;
    }

    public function save($file = false, $format = false, $jpegQuality = 75, $touch = false)
    {
        if (empty($file)) {
            $file = $this->_fileName;
        }

        $this->_checkLoaded();

        if (!$format) {
            $format = $this->_format;
        }

        switch ($format) {
            case self::IMG_GIF:
                if (!imagegif($this->_image, $file)) {
                    throw new Exception('Can\'t save gif file');
                }
                break;
            case self::IMG_JPEG:
                $jpegQuality = $this->_quality === null ? $jpegQuality : $this->_quality;
                if (!imagejpeg($this->_image, $file, $jpegQuality)) {
                    throw new Exception('Can\'t save jpeg file');
                }
                break;
            case self::IMG_PNG:
                if (!imagepng($this->_image, $file)) {
                    throw new Exception('Can\'t save png file');
                }
                break;
            default:
                throw new Exception('Invalid image format for save');
        }

        if ($touch && $file != $this->_fileName) {
            touch($file, filemtime($this->_fileName));
        }

        return $this;
    }
    
    protected function _freeImage()
    {
        if (is_resource($this->_image)) {
            imagedestroy($this->_image);
        }

        if ($this->_originalImage !== null) {
            if (is_resource($this->_originalImage['image'])) {
                imagedestroy($this->_originalImage['image']);
            }
            $this->_originalImage = null;
        }
    }

    protected function _checkLoaded()
    {
        if (!is_resource($this->_image)) {
            throw new Exception('Load image first');
        }
    }
    
    /**
     * Load image
     * @param string $file
     * @return array
     * @throws Exception 
     */
    protected function _loadImage($file)
    {
        $result = array();

        if ($imageInfo = @getimagesize($file)) {
            $result['width'] = $imageInfo[0];
            $result['height'] = $imageInfo[1];

            $result['mimeType'] = $imageInfo['mime'];

            switch ($result['format'] = $imageInfo[2]) {
                case self::IMG_GIF:
                    if ($result['image'] = imagecreatefromgif($file)) {
                        return $result;
                    } else {
                        throw new Exception('Invalid image gif format');
                    }
                    break;
                case self::IMG_JPEG:
                    if ($result['image'] = imagecreatefromjpeg($file)) {
                        return $result;
                    } else {
                        throw new Exception('Invalid image jpeg format');
                    }
                    break;
                case self::IMG_PNG:
                    if ($result['image'] = imagecreatefrompng($file)) {
                        return $result;
                    } else {
                        throw new Exception('Invalid image png format');
                    }
                    break;
                default:
                    throw new Exception('Not supported image format');
            }
        } else {
            throw new Exception('Invalid image file');
        }
    }

    protected function _initImage($image = false)
    {
        if ($image === false) {
            $image = $this->_originalImage;
        }

        $this->_width = $image['width'];
        $this->_height = $image['height'];
        $this->_mimeType = $image['mimeType'];
        $this->_format = $image['format'];

        //Image
        if (is_resource($this->_image))
            imagedestroy($this->_image);

        $this->_image = imagecreatetruecolor($this->_width, $this->_height);
        $this->_preserveTransparency($this->_image);
        imagecopy($this->_image, $image['image'], 0, 0, 0, 0, $this->_width, $this->_height);
    }

    private function _preserveTransparency($newImage)
    {
        switch ($this->_format) {
            case self::IMG_GIF:
                $color = imagecolorallocate(
                    $newImage, $this->transparencyColor[0], $this->transparencyColor[1], $this->transparencyColor[2]
                );

                imagecolortransparent($newImage, $color);
                imagetruecolortopalette($newImage, false, 256);
                break;
            case self::IMG_PNG:
                imagealphablending($newImage, false);

                $color = imagecolorallocatealpha(
                    $newImage, $this->transparencyColor[0], $this->transparencyColor[1], $this->transparencyColor[2], 0
                );

                imagefill($newImage, 0, 0, $color);
                imagesavealpha($newImage, true);
                break;
        }
    }
    
    /**
     * Convert color to integer
     * @param resource $image
     * @param mixed $color
     * @return integer
     * @throws Exception 
     */
    private function _getColor($image, $color)
    {
        if (is_array($color)) {
            if (count($color) === 4) {
                $color = imagecolorallocatealpha(
                    $image, $color[0], $color[1], $color[2], $color[3]
                );                
            } elseif (count($color) === 3) {
                $color = imagecolorallocate(
                    $image, $color[0], $color[1], $color[2]
                );
            } else {
                throw new Exception('Incorrect background value.');
            }
        }
        return (int) $color;
    }

}
