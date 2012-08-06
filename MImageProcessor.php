<?php

/**
 * Description of MImageProcessor
 *
 * @author mlapko
 */
class MImageProcessor extends CApplicationComponent
{    
    /**
     * Image path
     * @var string 
     */
    public $imagePath = 'webroot.files.img';
    
    public $imageUrl = '/files/img/';
    
    /**
     * Settings for image handler component
     * @var array 
     */
    public $imageHandler = array();
    
    /**
     * If flag = true when we process image while getImageUrl
     * @var boolean 
     */
    public $forceProcess = false;
    
    /**
     *
     * @var array 
     */
    public $presets = array();
    
    /**
     *  
     * @var array maxWidth, minWidth, actions    
     */
    public $afterUploadProcess;
    
    /**
     *
     * @var CComponent 
     */
    protected $_handler;
    
    
    /**
     * @return CComponent
     */
    public function getImageHandler()
    {
        if ($this->_handler === null) {
            $this->_handler = Yii::createComponent($this->imageHandler);
            $this->_handler->init();
        }
        return $this->_handler;
    }

    /**
     * Upload file
     *
     * @param CUploadedFile $image
     * @param string $namespace
     * @return mixed
     */
    public function upload($images, $namespace = 'cache')
    {
        if (is_array($images)) {
            $imgs = array();
            foreach ($images as $image) {
                $imgs[] = $this->_save($image, $namespace);
            }
            return $imgs;
        }
        return $this->_save($images, $namespace);
    }
    
    /**
     * Return 2 first symbols from md5 filename hash
     * 
     * @param string $filename
     * @return string
     */
    public function getSubDir($filename)
    {
        return substr(md5($filename), 0, 2);
    }
    
    /**
     * Return url for image
     * @param string $filename
     * @param string $preset
     * @param string $namespace
     * @return string 
     */
    public function getImageUrl($filename, $preset, $namespace = 'cache', $forceProcess = null) 
    {
        if (!$filename) {
            return '';
        }
        if ($forceProcess === null) {
            $forceProcess = $this->forceProcess;
        }
        if ($forceProcess && 
            !file_exists($fullName = $this->getImagePath($filename, $preset, $namespace))
        ) {
            $file = file_exists($filename) ? $filename : $this->getImagePath($filename, 'orig', $namespace);
            $this->process($file, $preset, array('newFilename' => $fullName));
        }
        return Yii::app()->getBaseUrl(true) . $this->imageUrl . $namespace . '/' . $preset . '/' .
            $this->getSubDir($filename) . '/' . basename($filename);
    }
    
    /**
     * Return path to image
     * @param string $filename
     * @param string $preset
     * @param string $namespace
     * @return string 
     */
    public function getImagePath($filename, $preset, $namespace = 'cache')
    {
        if (!$filename) {
            return '';
        }
        return Yii::getPathOfAlias($this->imagePath) . '/' . $namespace . '/' . $preset . '/' . 
            $this->getSubDir($filename) . '/' . basename($filename);
    }
    
    /**
     * Convert image
     *
     * @param string $fullFilename full name
     * @param string $preset
     * @param array $params
     *
     * @return $mixed
     */
    public function process($fullFilename, $preset, $params = array())
    {
        if (!file_exists($fullFilename)) {
            throw new Exception('File "' . $fullFilename . '" was not found.');            
        }
        $actions = $this->_getPreset($preset);
        $image = $this->getImageHandler()->load($fullFilename);
        $this->_process($image, $actions);        
        if (isset($params['newFilename'])) {
            $this->_createDir(dirname($params['newFilename']), false);
            $image->save($params['newFilename']);            
        } elseif (isset($params['namespace'])) {
            $filename = $this->getImagePath($fullFilename, $preset, $params['namespace']);
            $this->_createDir(dirname($filename), false);
            $image->save($filename);
        }
        return $image;
    }
    
    /**
     *
     * @param mixed $image
     * @param array $actions 
     */
    protected function _process($image, $actions)
    {
        foreach ($actions as $method => $params) {
            $refMethod = new ReflectionMethod($image, $method);
            if ($refMethod->getNumberOfParameters() > 0) {
                if ($this->_runWithParams($image, $refMethod, $params) === false) {
                    throw new Exception('Invalid params for "' . $method . '" method.');
                }
            } else {
                $image->$method();
            }
        }        
    }
    
    protected function _runWithParams($object, $method, $params)
    {
        $ps = array();
        foreach ($method->getParameters() as $i => $param) {
            $name = $param->getName();
            if (isset($params[$name])) {
                if ($param->isArray()) {
                    $ps[] = is_array($params[$name]) ? $params[$name] : array($params[$name]);                    
                } else if (!is_array($params[$name])) {
                    $ps[] = $params[$name];                    
                } else {
                    return false;                    
                }
            } else if ($param->isDefaultValueAvailable()) {
                $ps[] = $param->getDefaultValue();                
            } else {
                return false;                
            }
        }
        $method->invokeArgs($object, $ps);
        return true;
    }
        

    /**
     * Save image to disk
     * @param CUploadedFile $image
     * @param string $namespace
     */
    protected function _save($image, $namespace)
    {
        $filename = uniqid() . '.' . $image->getExtensionName();
        $directory = $this->_createDir("/$namespace/orig/" . $this->getSubDir($filename));
        $fullName = $directory . '/' . $filename;
        $image->saveAs($fullName);
        
        if ($this->afterUploadProcess !== null) {
            $this->_afterUploadProcess($directory, $filename);
        }
        
        return array(
            'filename' => $filename,
            'fullName' => $fullName
        );
    }
    
    /**
     *
     * @param string $directory
     * @param string $filename 
     */
    protected function _afterUploadProcess($directory, $filename)
    {
        $p = $this->_afterUploadProcess;
        $image = $this->getImageHandler()->load($directory . '/' . $filename);                
        if (isset($p['actions']) && 
            (
                (isset($p['maxWidth']) && $image->getWidth() > $p['maxWidth']) || 
                (isset($p['maxHeight']) && $image->getHeight() > $p['maxHeight'])
            )
        ) {            
            copy($directory . '/' . $filename, $directory . '/backup_' . $filename);
            $this->_process($image, $p['actions']);
            $image->save($directory . '/' . $filename);
        }
    }    
    
    /**
     * Create directory
     * @param string $subDir
     * @param boolean $prefix
     * @return string 
     */
    private function _createDir($subDir, $prefix = true)
    {
        $directory = $prefix ? Yii::getPathOfAlias($this->imagePath) . $subDir : $subDir;
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
            chmod($directory, 0777);
        }
        return $directory;
    }
    
    
    /**
     *
     * @param string $preset
     * @return array
     * @throws Exception if preset does not exists 
     */
    private function _getPreset($preset)
    {
        if (!isset($this->presets[$preset])) {
            throw new Exception('The "' . $preset . '" preset was not found.');
        }
        return $this->presets[$preset];        
    }
}