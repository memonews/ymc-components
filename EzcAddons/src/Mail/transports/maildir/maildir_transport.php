<?php

class ymcEzcMailMaildirTransport implements ezcMailTransport
{
    private $options;
    private $hostname;
    private $pid;

    public function __construct( ymcEzcMailMaildirTransportOptions $options )
    {
        $this->options = $options;
        $this->hostname = php_uname( 'n' );
        $this->pid = getmypid();

        $path = $options->maildirPath;

        // create maildir if neccessary
        if( !file_exists( "{$path}/tmp") )
        {
            mkdir( "{$path}/tmp", 0755, true );
        }
        if( !file_exists( "{$path}/new") )
        {
            mkdir( "{$path}/new", 0755, true );
        }
    }

    public function send( ezcMail $mail )
    {
        $this->append( $mail );
    }

    private function generateFilename()
    {
        return time().".{$this->pid}.{$this->hostname}";
    }

    public function append( ezcMail $mail )
    {
        $path = $this->options->maildirPath;

        $filename = $this->generateFilename();
        $tmpFile = "{$path}/tmp/{$filename}";
        while( file_exists( $tmpFile ) )
        {
            sleep( 2 );
            $fileName = $this->generateFilename();
            $tmpFile = "{$path}/tmp/{$filename}";
        }

        try {
            touch( $tmpFile );
            file_put_contents( $tmpFile, $mail->generate() );

            link( $tmpFile, "{$path}/new/{$filename}" );
            unlink( $tmpFile );
        } 
        catch ( Exception $e )
        {
            unlink( $tmpFile );
        }
    }
}
