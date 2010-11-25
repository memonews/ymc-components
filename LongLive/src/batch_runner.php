<?php

class ymcLongLiveBatchRunner
{
    /**
     * Counts the number of times the while loop iterated
     * 
     * @var integer
     */
    protected $numberOfPerformedJobs = 0;

    /**
     * The unix timestamp when the run() method was called.
     * 
     * @var integer
     */
    protected $startTime;

    protected $maxEndTime;

    protected $minEndTime;

    /**
     * A string representation of the callback for log messages
     * 
     * @var string
     */
    protected $callBackString;

    /**
     * The callback this batchrunner executes
     * 
     * @var callable
     */
    protected $callback;

    /**
     * arguments to pass to the callback
     * 
     * @var array
     */
    protected $arguments;

    /**
     * The memoryLimit to obey.
     * 
     * @var integer
     */
    protected $memoryLimit;

    /**
     * options 
     * 
     * @var ymcLongLiveBatchRunnerOptions
     */
    protected $options;

    public function __construct( ymcLongLiveBatchRunnerOptions $options = NULL )
    {
        if( NULL === $options )
        {
            $options = new ymcLongLiveBatchRunnerOptions;
        }
        $this->options = $options;
    }

    public function run( $callback = NULL, $arguments = NULL )
    {
        $this->initOnRunCall( $callback, $arguments );

        //@todo move this somewhere else
        if( $this->options->gracefulSigterm )
        {
            ymcLongLiveSignalHandler::registerCallback( SIGTERM, array( 'ymcLongLiveSignalHandler', 'halt' ) );
        }

        self::log( 'Enter batch runner while loop with sleep '.$this->options->sleep );
        while( TRUE )
        {
            if( $this->checkEndConditions() )
            {
                $this->waitForMinimumEndTime();
                return TRUE;
            }

            //self::log( 'start batch loop', ezcLog::DEBUG );
            if( $this->options->gracefulSigterm )
            {
                ymcLongLiveSignalHandler::dispatchAll();
            }

            self::log( sprintf( 'Start Function %s', $this->callbackString ), ezcLog::DEBUG );
            try
            {
                $return = call_user_func_array( $this->callback, $this->arguments );
            } catch ( Exception $e )
            {
                self::log( (string)$e, ezcLog::ERROR );
                $return = FALSE;
            }
            ++$this->numberOfPerformedJobs;
            self::log( sprintf( 'Function %s returned %s', $this->callbackString, $return ? 'TRUE' : 'FALSE' ), ezcLog::DEBUG );

            //@todo allow other break conditions
            if( !$return )
            {
                if( 0 === $this->options->sleep )
                {
                    self::log( 'batch runner exit due to return value', ezcLog::DEBUG );
                    return FALSE;
                }
                self::log( sprintf( 'sleep %d seconds', ( int )$this->options->sleep ), ezcLog::DEBUG );
                sleep( ( int )$this->options->sleep );
            }
            //self::log( 'End batch loop', ezcLog::DEBUG );
        }
    }

    protected function initOnRunCall( $callback, $arguments )
    {
        $this->startTime = time();

        if( !is_callable( $callback ) )
        {
            $this->callback = $this->options->callback;
            if( !is_callable( $this->callback ) )
            {
                throw new Exception( 'no callable callback given.' );
            }
        }

        if( !is_array( $arguments ) )
        {
            $this->arguments = $this->options->arguments;
        }

        $memoryLimit         = $this->options->memoryLimit;
        $relativeMemoryLimit = $this->options->relativeMemoryLimit;
        
        if( !$memoryLimit && 0.0 !== $relativeMemoryLimit )
        {
            $this->memoryLimit = ( int ) ( ymcLongLiveMemory::getMemoryLimit() * $relativeMemoryLimit );
        }
        else
        {
            $this->memoryLimit = $memoryLimit;
        }

        $this->maxEndTime = $this->options->maxExecutionTime ? $this->startTime + $this->options->maxExecutionTime : NULL;
        $this->minEndTime = $this->startTime + $this->options->minExecutionTime;

        $this->callbackString = self::callbackToString( $this->callback );
    }

    protected function checkEndConditions()
    {
        if( $this->maxEndTime && time() > $this->maxEndTime )
        {
            self::log( $this->callbackString.' batch runner exit due to time limit of '.$this->options->maxExecutionTime, ezcLog::DEBUG );
            return TRUE;
        }

        if( ymcLongLiveMemory::hasExhausted( $this->memoryLimit ) )
        {
            self::log( $this->callbackString.' batch runner exit due to memory limit of '.$this->memoryLimit, ezcLog::DEBUG );
            return TRUE;
        }

        if( $this->options->freeSystemMemory && !ymcLongLiveMemory::ensureFreeMemory( $this->options->freeSystemMemory ) )
        {
            self::log( $this->callbackString.' batch runner exit due to ensured free memory '.$this->options->freeSystemMemory, ezcLog::DEBUG );
            return TRUE;
        }
    }

    /**
     * Waits until the minEndTime is reached.
     *
     * The daemon tool that can be used to run PHP scripts in the background will consider a
     * script failed, if it runs less then a minimum time. Therefor a succesful execution should
     * at least run until minEndTime.
     * 
     * @return void
     */
    protected function waitForMinimumEndTime()
    {
        while( true )
        {
            $timeToSleep = $this->minEndTime - time();
            if( $timeToSleep <= 0 )
            {
                return;
            }
            self::log( sprintf( '%s: sleeping %d seconds to reach minExecutionTime of %d seconds',
                                $this->callbackString,
                                $timeToSleep,
                                $this->options->minExecutionTime ), ezcLog::DEBUG );
            sleep( $timeToSleep );
        }
    }

    public function getNumberOfPerformedJobs()
    {
        return $this->numberOfPerformedJobs;
    }

    public static function runWithDefaults( $callback = NULL, $arguments = NULL )
    {
        $runner = new self;
        return $runner->run( $callback, $arguments );
    }

    public function setOption( $name, $value )
    {
        $this->options->$name = $value;
    }

    public function getOption( $name )
    {
        return $this->options->$name;
    }

    public static function callbackToString( $callback )
    {
        if( is_string( $callback ) )
        {
            return $callback;
        }

        if( is_array( $callback ) && count( $callback ) === 2 )
        {
            if( is_string( $callback[0] ) )
            {
                return $callback[0].'::'.$callback[1];
            }elseif( is_object( $callback[0] ) )
            {
                return get_class( $callback[0] ).'->'.$callback[1];
            }
        }

        return 'not a callback';
    }

    protected static function log( $message, $severity = ezcLog::DEBUG, Array $attributes = array() )
    {
        static $log;
        if( !$log )
        {
            $log = ezcLog::getInstance();
        }

        if( !array_key_exists( 'source', $attributes ) )
        {
            $attributes['source'] = __CLASS__;
        }

        $log->log( $message, $severity, $attributes );
    }
}
