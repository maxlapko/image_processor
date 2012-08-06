<?php
/**
 * Description of MImageBehavior
 *
 * @author mlapko
 */
class MImageBehavior extends CBehavior
{
    /**
     * @var string
     */
    public $imageProcessor = 'image';   
        
    /**
     * Get Path for image
     * @param string $attribute
     * @param string $preset
     * 
     * @return string 
     */
    public function getImagePath($attribute, $preset)
    {
        return Yii::app()->getComponent($this->imageProcessor)->getImagePath($this->getOwner()->$attribute, $preset, get_class($this->getOwner()));
    }
    
    /**
     * Get Path for image
     * @param string $attribute
     * @param string $preset
     * 
     * @return string 
     */
    public function getImageUrl($attribute, $preset)
    {
        return Yii::app()->getComponent($this->imageProcessor)->getImageUrl($this->getOwner()->$attribute, $preset, get_class($this->getOwner()));
    }   
    
    /**
     * Upload image
     * @param CUploadedFile $image
     * @param string $attribute
     * @return boolean 
     */
    public function uploadImage($image, $attribute)
    {
        if ($image !== null) {
            $image = Yii::app()->getComponent($this->imageProcessor)->upload($image, get_class($this->getOwner()));
            $this->getOwner()->$attribute = $image['filename'];
            return true;
        }
        return false;
    }
}