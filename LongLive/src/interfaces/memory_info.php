<?php

abstract class ymcLongLiveMemoryInfo 
{
    abstract public function reset();

    /**
     * Returns the amount of RAM available to applications in kB.
     *
     * @return integer
     */
    abstract public function getApplicationFreeMemory();

    private static $osName;
    private static function getOsName()
    {
        if( self::$osName === null )
        {
            self::$osName = php_uname( "s" );
        }

        return self::$osName;
    }

    /**
     * Create instance depending on current operating system
     */
    public static function createInstance()
    {
        if( file_exists( '/proc/meminfo' ) )
        {
            return new ymcLongLiveMemoryInfoMeminfo();
        }
        else
        {
            $osName = self::getOsName();
            switch( $osName )
            {
                case 'Darwin':
                    return new ymcLongLiveMemoryInfoDarwinVmStat();
                default:
                    throw new Exception( "No MemoryInfo implementation available for '{$osName}'" );
            }
        }
    }
}
