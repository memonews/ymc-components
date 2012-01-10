<?php

class ymcCurlDefaultEncodingDetectorFactory implements ymcCurlEncodingDetectorFactory 
{
    public function createDetector( $html, $headers, $defaultCharset = 'iso-8859-1' )
    {
        return new ymcCurlDefaultEncodingDetector( $html, $headers, $defaultCharset );
    }
}
