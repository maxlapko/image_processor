<?php

/**
 * Description of MImageValidator
 *
 * @author mlapko
 */
class MImageValidator extends CFileValidator
{
    public $imageProcessor = 'image';
    
    /**
     * Min width for image
     * @var integer 
     */
    public $minWidth;
    
    /**
     * Max width for image
     * @var integer 
     */
    public $maxWidth;
    
    /**
     * Min height for image
     * @var integer
     */
    public $minHeight;
    
    /**
     * Max height for image
     * @var integer
     */
    public $maxHeight;
    
    /**
     * @var string the error message used when the uploaded image is too large width.
     * @see maxWidth
     */
    public $tooLargeWidth;
   
    /**
     * @var string the error message used when the uploaded image is too small width.
     * @see minWidth
     */
    public $tooSmallWidth;
    
    /**
     * @var string the error message used when the uploaded image is too large height.
     * @see maxHeight
     */
    public $tooLargeHeight;
   
    /**
     * @var string the error message used when the uploaded image is too small height.
     * @see minHeight
     */
    public $tooSmallHeight;
    
    /**
     *
     * @var string the error message used when the uploaded file is not image.
     */
    public $invalidImage;
    
    
    /**
     * Internally validates a file object.
     * @param CModel $object the object being validated
     * @param string $attribute the attribute being validated
     * @param CUploadedFile $file uploaded file passed to check against a set of rules
     */
    protected function validateFile($object, $attribute, $file)
    {
        parent::validateFile($object, $attribute, $file);
        if ($object->getError($attribute) === null) {
            $this->_validateImage($object, $attribute, $file);
        }
    }
    
    /**
     * Internally validates a file object.
     * @param CModel $object the object being validated
     * @param string $attribute the attribute being validated
     * @param CUploadedFile $file uploaded file passed to check against a set of rules
     */
    protected function _validateImage($object, $attribute, $file)
    {
        $image = Yii::app()->getComponent($this->imageProcessor)->getImageHandler();        
        try {
            $image->load($file->getTempName());
            if ($this->minWidth !== null && $image->getWidth() < $this->minWidth) {
                $message = $this->tooSmallWidth !== null ? $this->tooSmallWidth : Yii::t('mimage', 'The image "{file}" is too small. Its width cannot be smaller than {limit} px.');
                $this->addError($object, $attribute, $message, array('{file}'  => $file->getName(), '{limit}' => $this->minWidth));
            }
            if ($this->minHeight !== null && $image->getHeight() < $this->minHeight) {
                $message = $this->tooSmallHeight !== null ? $this->tooSmallHeight : Yii::t('mimage', 'The image "{file}" is too small. Its height cannot be smaller than {limit} px.');
                $this->addError($object, $attribute, $message, array('{file}'  => $file->getName(), '{limit}' => $this->minHeight));
            }
            if ($this->maxWidth !== null && $image->getWidth() > $this->maxWidth) {
                $message = $this->tooLargeWidth !== null ? $this->tooLargeWidth : Yii::t('mimage', 'The image "{file}" is too large. Its width cannot exceed {limit} px.');
                $this->addError($object, $attribute, $message, array('{file}'  => $file->getName(), '{limit}' => $this->maxWidth));
            }
            if ($this->maxHeight !== null && $image->getHeight() > $this->maxHeight) {
                $message = $this->tooLargeHeight !== null ? $this->tooLargeHeight : Yii::t('mimage', 'The image "{file}" is too large. Its height cannot exceed {limit} px.');
                $this->addError($object, $attribute, $message, array('{file}'  => $file->getName(), '{limit}' => $this->maxHeight));
            }           
        } catch (Exception $exc) {
            $message = $this->invalidImage !==null ? $this->invalidImage : Yii::t('mimage', '{attribute} invalid image.');
            $this->addError($object, $attribute, $message, array('{attribute}' => $attribute));
        }
    }
}

