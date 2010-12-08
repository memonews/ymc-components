<?php

class ymcHtmlFormElementButton extends ymcHtmlFormElementBase
{
    protected $type = 'submit';

    private $form;

    public function init( ymcHtmlForm $form, ymcHtmlFormInputSource $inputSource = NULL )
    {
        $form->registerOnInit( $this );
        $this->form = $form;
    }

    public function validate( ymcHtmlFormInputSource $inputSource )
    {
            $options = $this->options;
            $this->value = $inputSource->get( $this->name, 
                                              $options->filter,
                                              $options->filterOptions );

            if( $this->value )
            {
                $this->form->setButton( $this );
            }
            return array();
    }
}
