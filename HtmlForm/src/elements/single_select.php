<?php

class ymcHtmlFormElementSingleSelect extends ymcHtmlFormElementBase
{
    protected $type = 'select';

    protected function filter( ymcHtmlFormInputSource $inputSource )
    {
        $options = $this->options;
        $emptyFailure = $options->emptyFailure;
        
        $value = $inputSource->get( $this->name, 
                                    $options->filter,
                                    $options->filterOptions );
        
        if( empty( $value ) )
        {
        	if( $emptyFailure )
        	{
        		$this->failures[] = new $emptyFailure( $this );
        	}
        }
        else if( !in_array( $value, $this->values ) )
        {
            $value = '';
            $this->failures[] = new ymcHtmlFormFailureNotInSet( $this );
        }
        return $value;
    }
}
