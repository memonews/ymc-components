<?php

/**
 * Attempts to detect the encoding
 * 
 * @package Base
 * @author  Jiayong Ou
 */
interface ymcCurlEncodingDetector
{
    /**
     * Get the encoding
     * 
     * @return string
     */
    public function getEncoding();
}

