<?php

/**
 * Description of MImageFileValidator
 *
 * @author mlapko
 * 
 */
class MImageFileValidator
{
    public static $imageProcessor = 'image';
    
    /**
     * Min width for image
     * @var integer 
     */
    public static $minWidth;
    
    /**
     * Max width for image
     * @var integer 
     */
    public static $maxWidth;
    
    /**
     * Min height for image
     * @var integer
     */
    public static $minHeight;
    
    /**
     * Max height for image
     * @var integer
     */
    public static $maxHeight;
    
    /**
     * @var string the error message used when the uploaded image is too large width.
     * @see maxWidth
     */
    public static $tooLargeWidth;
   
    /**
     * @var string the error message used when the uploaded image is too small width.
     * @see minWidth
     */
    public static $tooSmallWidth;
    
    /**
     * @var string the error message used when the uploaded image is too large height.
     * @see maxHeight
     */
    public static $tooLargeHeight;
   
    /**
     * @var string the error message used when the uploaded image is too small height.
     * @see minHeight
     */
    public static $tooSmallHeight;
    
    /**
     *
     * @var string the error message used when the uploaded file is not image.
     */
    public static $invalidImage;
    
    /**
     * Internally validates a file object.
     * @param CModel $object the object being validated
     * @param string $attribute the attribute being validated
     * @param string $filename exists file passed to check against a set of rules
     */
    public static function validate($object, $attribute, $filename)
    {
        $image = Yii::app()->getComponent(self::imageProcessor)->getImageHandler();
        try {
            $name = pathinfo($filename, PATHINFO_BASENAME);
            $image->load($filename);
            if (self::minWidth !== null && $image->getWidth() < self::minWidth) {
                $message = self::tooSmallWidth !== null ? self::tooSmallWidth : Yii::t('mimage', 'The image "{file}" is too small. Its width cannot be smaller than {limit} px.');
                self::addError($object, $attribute, $message, array('{file}'  => $name, '{limit}' => self::minWidth));
            }
            if (self::minHeight !== null && $image->getHeight() < self::minHeight) {
                $message = self::tooSmallHeight !== null ? self::tooSmallHeight : Yii::t('mimage', 'The image "{file}" is too small. Its height cannot be smaller than {limit} px.');
                self::addError($object, $attribute, $message, array('{file}'  => $name, '{limit}' => self::minHeight));
            }
            if (self::maxWidth !== null && $image->getWidth() > self::maxWidth) {
                $message = self::tooLargeWidth !== null ? self::tooLargeWidth : Yii::t('mimage', 'The image "{file}" is too large. Its width cannot exceed {limit} px.');
                self::addError($object, $attribute, $message, array('{file}'  => $name, '{limit}' => self::maxWidth));
            }
            if (self::maxHeight !== null && $image->getHeight() > self::maxHeight) {
                $message = self::tooLargeHeight !== null ? self::tooLargeHeight : Yii::t('mimage', 'The image "{file}" is too large. Its height cannot exceed {limit} px.');
                self::addError($object, $attribute, $message, array('{file}'  => $name, '{limit}' => self::maxHeight));
            }
        } catch (Exception $exc) {
            $message = self::invalidImage !==null ? self::invalidImage : Yii::t('mimage', '{attribute} invalid image.');
            self::addError($object, $attribute, $message);
        }
    }
    
    public static function addError($object, $attribute, $message, $params = array())
    {
        $params['{attribute}'] = $object->getAttributeLabel($attribute);
		$object->addError($attribute, strtr($message, $params));
    }
}

