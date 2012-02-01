<?php

class ymcCurlErrorException extends ymcCurlException
{
    protected $curlMessage;
    protected $url;

    public function __construct( $errNo, $errMsg, $url = '' )
    {
        $message = sprintf(
            'Curl error %d. %s %s',
            $errNo,
            $url ?: '',
            $errMsg
        );
        parent::__construct( $message, $errNo );

        $this->curlMessage = $errMsg;
        $this->url = $url;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getCurlMessage()
    {
        return $this->curlMessage;
    }
}
