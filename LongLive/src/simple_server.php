<?php

/**
 * @TODO: This server is only a quick hack and needs clean up by somebody who knows more about
 *        networking.
 */
class ymcLongLiveSimpleServer
{
    /**
     * server 
     * 
     * @var mixed
     * @access protected
     */
    protected $server;
    protected $client;
    protected $port;
    protected $findNewPortIfTaken;

    public function __construct( $port, $findNewPortIfTaken = true )
    {
        $this->port = $port;
        $this->findNewPortIfTaken = $findNewPortIfTaken;
    }

    private function getServer()
    {
        if( !is_resource( $this->server ) )
        {
            $this->server = @stream_socket_server( 'tcp://127.0.0.1:'.$this->port, $errno, $errstr );
            if( !is_resource( $this->server ) )
            {
                if( $this->findNewPortIfTaken && $errstr == "Address already in use" )
                {
                    $oldPort = $this->port;
                    $newPort = $oldPort + 1;
                    ezcLog::getInstance()->log( sprintf( 'Port %d already taken, trying with %d', $oldPort, $newPort ), ezcLog::INFO );
                    $this->port = $newPort;
                    return $this->getServer();
                }
                else
                {
                    ezcLog::getInstance()->log( sprintf( 'Error opening socket: %d %s', $errno, $errstr ), ezcLog::ERROR );
                    return false;
                }
            }
        }
        return $this->server;
    }

    public function getLine( $timeout )
    {
        $server = $this->getServer();
        if( !is_resource( $server ) )
        {
            sleep( $timeout );
            return;
        }

        if( !is_resource( $this->client ) )
        {
            ezcLog::getInstance()->log( 'waiting for client connect', ezcLog::DEBUG );
            $this->client = @stream_socket_accept( $server, $timeout );
            if( is_resource( $this->client ) )
            {
                stream_set_blocking( $this->client, 0 );
                stream_set_timeout( $this->client, $timeout );
            }
        }

        if( is_resource( $this->client ) )
        {
            ezcLog::getInstance()->log( 'reading input line from client', ezcLog::DEBUG );
            $line = trim( fgets( $this->client ) ); 
            if( $line )
            {
                ezcLog::getInstance()->log( 'received input line '.$line, ezcLog::DEBUG );
            }elseif( feof( $this->client ) )
            {
                ezcLog::getInstance()->log( 'client closed connection', ezcLog::INFO );
                $this->close();
            }
            else
            {
                //ezcLog::getInstance()->log( 'no client input, sleeping', ezcLog::INFO );
                sleep( 1 );
            }
            return $line;
        }
    }

    public function write( $text )
    {
        if( is_resource( $this->client ) )
        {
            fwrite( $this->client, $text );
        }
    }

    public function close()
    {
        if( $this->client )
        {
            fclose( $this->client );
        }
        $this->client = NULL;
    }

    public function __get( $property )
    {
        switch( $property )
        {
            case 'port':
                $this->getServer();
                return $this->{$property};
            default:
                throw new ezcBasePropertyNotFoundException( $property );
        }
    }
}
