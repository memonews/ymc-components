<?php

/**
 * Options for MaildirTransport
 *
 * @property string $maildirPath 
 */
class ymcEzcMailMaildirTransportOptions extends ezcBaseOptions
{
    public function __construct( array $options = array() )
    {
        $this->maildirPath = getenv( 'HOME' ).'/Maildir';
        parent::__construct( $options );
    }

    public function __set( $name, $value )
    {
        switch ( $name )
        {
            case 'maildirPath':
                $this->properties[$name] = $value;
                break;
            default:
                throw new ezcBasePropertyNotFoundException( $name );
        }
    }
}
