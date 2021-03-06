<?php

/**
 * Attempts to detect the encoding
 * 
 * @package Curl
 * @author  Jiayong Ou
 */
class ymcCurlDefaultEncodingDetector implements ymcCurlEncodingDetector
{
    private $html;
    private $headers;
    private $defaultCharset;

    private $detectorResults = array();
    private $confidences = array();

    private $encoding;

    /**
     * Detectors and their weights. The method name for the detector
     * will be 'detectBy'.ucfirst($detector)
     *
     * @var array( string => float ) 
     */
    private static $detectors = array( 
        'header' => 1.0,
        'metaHttpEquiv' => 1.0,
        'metaCharset' => 1.0,
        'xmlPrologue' => 1.0,
        'googleAdsJs' => 0.4,
        // 'mbDetectEncoding' => 0.2,
    );

    /**
     * Custom detectors. Looks like 
     *  array( $name => array( 
     *      'callback' => $callback,
     *      'weight' => $weight 
     *  ) )
     */
    private static $customDetectors = array();

    public static function registerDetector( $name, $callback, $weight = 1 )
    {
        self::$customDetectors[$name] = array( 'callback' => $callback, 'weight' => $weight );
    }

    /**
     * __construct 
     * 
     * @param string $html HTML text
     * @param string $headers HTTP headers, as a blob of text (Lines of 'Header: Value')
     * @param string $defaultCharset Default charset to use if none can be determined
     * @return void
     */
    public function __construct( $html, $headers, $defaultCharset = 'iso-8859-1' )
    {
        $this->html = $html;
        $this->headers = $headers;
        $this->defaultCharset = $defaultCharset;
    }

    /**
     * Get the encoding
     * 
     * @return string
     */
    public function getEncoding()
    {
        if ( !$this->encoding )
        {
            $this->confidences = array();
            $this->detectorResults = array();

            $detectorSpecs = self::$customDetectors;
            foreach ( self::$detectors as $detector => $weight )
            {
                $callback = array( $this, 'detectBy'.ucfirst( $detector ) );
                $detectorSpecs[$detector] = array( 'callback' => $callback, 'weight' => $weight );
            }

            foreach ( $detectorSpecs as $detector => $detectorSpec )
            {
                $this->callDetector( $detector, $detectorSpec['callback'], $detectorSpec['weight'] );
            }

            if ( empty( $this->confidences ) )
            {
                $this->encoding = $this->defaultCharset;
            }
            else
            {
                asort( $this->confidences );
                $keys = array_keys( $this->confidences );
                $this->encoding = array_pop( $keys );
            }
        }

        return $this->encoding;
    }

    private function callDetector( $name, $callback, $weight )
    {
            $detectedEncoding = call_user_func( $callback, $this->headers, $this->html );
            $detectedEncoding = strtoupper( rtrim( $detectedEncoding ) );

            if ( $detectedEncoding )
            {
                if ( !isset( $this->confidences[$detectedEncoding] ) )
                {
                    $this->confidences[$detectedEncoding] = 0;
                }

                $this->confidences[$detectedEncoding] += $weight;
                $this->detectorResults[$name] = $detectedEncoding;
            }
            else 
            {
                $this->detectorResults[$name] = false;
            }
    }

    /**
     * Attempt to detect encoding from HTTP header
     * 
     * @return string|false 
     */
    private function detectByHeader()
    {
        if ( preg_match_all( '/Content-Type:.*?charset=([^\s;]+)/i', $this->headers, $matches ) )
        {
            $charsetMatches = $matches[1];

            // Use the last match in the headers. If there was a redirect, $headers contains
            // the headers for all requests. The last one should be the one that answered
            // with the effective body.
            return array_pop( $charsetMatches );
        } 
        else
        {
            return false;
        }
    }

    /**
     * Attempt to detect encoding from <meta http-equiv="Content-Type"> tag
     * 
     * @return string|false
     */
    private function detectByMetaHttpEquiv()
    {
        // regex to get the right meta tag
        $metaTagPattern = <<<REGEX
!
<meta
    [^>]+
    http-equiv=['"]?Content-Type
    [^>]*
>
!ix
REGEX;

        // regex to extract charset from the Content-Type meta tag
        $contentTypePattern = <<<REGEX
!
    .*?
    charset=([^\s;'"]+)
!ix
REGEX;

        if ( preg_match( $metaTagPattern, $this->html, $matches ) )
        {
            if ( preg_match( $contentTypePattern, $matches[0], $contentTypeMatches ) )
            {
                return $contentTypeMatches[1];
            }
            else 
            {
                return false;
            }
        } 
        else
        {
            return false;
        }
    }

    private function detectByMetaCharset()
    {
        // regex to get the right meta tag
        $charsetPattern = <<<REGEX
!
<meta
    [^>]+
    charset=['"]([^'"]+)['"]
    [^>]+
>
!ix
REGEX;

        if ( preg_match( $charsetPattern, $this->html, $matches ) )
        {
            return $matches[1];
        } 
        else
        {
            return false;
        }
    }

    private function detectByXmlPrologue()
    {
        // regex to get the right meta tag
        $prologuePattern = <<<REGEX
!
<\?xml
    [^>]+
    encoding=['"]([^'"]+)['"]
    [^>]+
>
!ix
REGEX;

        if ( preg_match( $prologuePattern, $this->html, $matches ) )
        {
            return $matches[1];
        } 
        else
        {
            return false;
        }
    }

    /**
     * Attempt to detect encoding from google ad javascript
     * 
     * @return void
     */
    private function detectByGoogleAdsJs()
    {
        if ( preg_match( '!google_encoding = [\'"]([^\'"]+)[\'"]!i', $this->html, $matches ) )
        {
            return $matches[1];
        }
        else 
        {
            return false;
        }
    }
}

