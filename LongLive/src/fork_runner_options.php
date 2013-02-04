<?php

/**
 * ymcLongLiveBatchRunnerOptions 
 * 
 * @property maxExecutionTime The maximum duration in seconds after which no new batch run should
 *                            be started.
 */
class ymcLongLiveForkRunnerOptions extends ezcBaseOptions
{
    protected $properties = array( 
        'beforeForkCallback' => null,
        'afterForkCallback' => null,
        'statusPort' => 39311,
        'processTitle' => null,
    );

    /**
     * Sets the option $name to $value.
     *
     * @throws ezcBasePropertyNotFoundException
     *         if the property $name is not defined
     * @throws ezcBaseValueException
     *         if $value is not correct for the property $name
     * @param string $name
     * @param mixed $value
     * @ignore
     */
    public function __set( $name, $value )
    {
        if( !array_key_exists( $name, $this->properties ) )
        {
            throw new ezcBasePropertyNotFoundException( $name );
        }

        switch( $name )
        {
            case 'statusPort':
                if( !is_int( $value ) )
                {
                    throw new ezcBaseValueException( $name, $value, 'integer' );
                }
                break;
            case 'beforeForkCallback':
            case 'afterForkCallback':
                if( $value !== null && !is_callable( $value ) )
                {
                    throw new ezcBaseValueException( $name, $value, 'callback' );
                }
                break;
            case 'processTitle':
                // nothing to check
                break;
        }

        $this->properties[$name] = $value;
    }
}
