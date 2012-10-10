<?php
/**
 * Behavior for managing image
 *
 * @author mlapko <maxlapko@gmail.com>
 * @version 0.1
 * 
 * 
 *   public function behaviors()
 *   {
 *       return array(
 *          'MImage' => array(
 *               'class'          => 'ext.image_processor.MImageBehavior',
 *               'imageProcessor' => 'image' // image processor component name 
 *           )
 *       );
 *   }
 *   
 *   echo $model->getImagePath('image', 'preset'); // preset = orig it is original file
 *   echo $model->getImageUrl('image', 'preset', true);
 *   $model->uploadImage(CUploadedFile::getInstance($model, 'avatar'), 'avatar');
 *   $model->deleteImage('avatar'); or $model->deleteImage('avatar', 'preset'); 
 *   
 * 
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
    public function getImageUrl($attribute, $preset, $forceProcess = null)
    {
        return Yii::app()->getComponent($this->imageProcessor)->getImageUrl($this->getOwner()->$attribute, $preset, get_class($this->getOwner()), $forceProcess);
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
    
    /**
     * Delete image for 
     * @param string $attribute
     * @return boolean 
     */
    public function deleteImage($attribute, $preset = null)
    {        
        if (!empty($this->getOwner()->$attribute)) {
            if ($preset !== null) {
                $preset = (array) $preset;
                foreach ($preset as $p) {
                    if ($p === 'orig') {
                        $this->_removeOrigFile($attribute);
                    } else {
                        $this->_unlinkFile($this->getImagePath($attribute, $p));
                    }
                }
            } else {
                $this->_removeOrigFile($attribute);
                $keys = array_keys(Yii::app()->getComponent($this->imageProcessor)->presets);
                foreach ($keys as $preset) {
                    $this->_unlinkFile($this->getImagePath($attribute, $preset));
                }
            }
            return true;
        }
        return false;
    }
    
    /**
     * Remove orig file and backup if exists
     * @param string $attribute 
     */
    protected function _removeOrigFile($attribute)
    {
        $filename = $this->getImagePath($attribute, 'orig');
        $this->_unlinkFile($filename);
        $info = pathinfo($filename);
        $this->_unlinkFile($info['dirname'] . DIRECTORY_SEPARATOR . 'backup_' . $info['basename']);       
    }


    /**
     * Delete file if exists 
     * @param string $filename 
     */
    private function _unlinkFile($filename)
    {
        if (file_exists($filename)) {
            unlink($filename);
            // delete empty sub dirs
            $dir = dirname($filename);
            $imageDir = Yii::getPathOfAlias(Yii::app()->getComponent($this->imageProcessor)->imagePath);
            while ($dir !== $imageDir) {
                if (count(glob($dir . '/*')) === 0) {
                    if (rmdir($dir) === false) {
                        return;
                    }
                    $temp = explode(DIRECTORY_SEPARATOR, $dir);
                    array_pop($temp);
                    $dir = implode(DIRECTORY_SEPARATOR, $temp);
                } else {
                    return;
                }
            }            
        }
    }
    
    
    
}