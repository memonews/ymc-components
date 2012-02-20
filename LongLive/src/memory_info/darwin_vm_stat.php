<?php

class ymcLongLiveMemoryInfoDarwinVmStat extends ymcLongLiveMemoryInfo
{
    private $parsedVmStat;
    public function __construct()
    {
        $this->parseVmStat();
    }

    private function parseVmStat()
    {
        exec( 'vm_stat', $vmStatOut, $vmStatReturn );
        if( $vmStatReturn !== 0 )
        {
            throw new Exception( 'Failed to execute `vm_stat`' );
        }

        $pageSize = 0;
        foreach( $vmStatOut as $line )
        {
            if( preg_match( '/^(?P<key>[^:]+):\s*(?P<value>\d+)/', $line, $matches ) )
            {
                if( substr( $matches['key'], 0, 5 ) == "Pages" )
                {
                    $key = substr( $matches['key'], 6 );
                    $this->parsedVmStat[$key] = (int)$matches['value'];
                }
            }
            if( preg_match( '/page\ssize\sof\s(?P<page_size>\d+)\sbytes/', $line, $matches ) )
            {
                // Page size is in bytes, we want kB in output
                $pageSize = (int)$matches['page_size'] / 1024;
            }
        }

        array_walk( $this->parsedVmStat, function( &$pages, $key, $pageSize ) {
            $pages *= $pageSize;
        }, $pageSize );
    }

    public function reset()
    {
        $this->parseVmStat();
    }

    public function getApplicationFreeMemory()
    {
        return $this->parsedVmStat['free'] 
             + $this->parsedVmStat['speculative']
             + $this->parsedVmStat['inactive'];
    }
}
