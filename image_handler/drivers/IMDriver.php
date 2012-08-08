<?php

/**
 * @author mlapko <maxlapko@gmail.com> 
 */
interface IMDriver
{
    /**
     * @var mixed $format 
     * @var integer $jpegQuality 
     */
    public function show($format = false, $jpegQuality = 75);
    
    /**
     *
     * @param string|boolean $file
     * @param mixed $format
     * @param integer $jpegQuality
     * @param boolean $touch
     * @return mixed 
     */
    public function save($file = false, $format = false, $jpegQuality = 75, $touch = false);
    
    /**
     * @param string $file
     * @return mixed 
     */
    public function load($file);    

}
