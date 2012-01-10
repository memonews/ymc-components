<?php

/**
 * Attempts to detect the encoding
 * 
 * @package Base
 * @author  Jiayong Ou
 */
interface ymcCurlEncodingDetectorFactory
{
    /**
     * Create a detector
     * 
     * @return ymcCurlEncodingDetector
     */
    public function createDetector( $html, $headers, $defaultCharset = 'iso-8859-1' );
}

