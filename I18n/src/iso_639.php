<?php

class ymcI18nIso639
{
    const DEFAULT_XMLPATH = '/usr/share/xml/iso-codes/iso_639.xml';
    const DOMAIN = 'iso_639';

    private static $languages;

    private $xmlPath;
    private $locale;

    private $doNotTranslate = FALSE;

    public function __construct( ymcI18nSystemLocale $locale, $xmlPath = null )
    {
        $this->xmlPath = ( null === $xmlPath ) ? self::DEFAULT_XMLPATH : $xmlPath;
        $this->locale = $locale;
        if( !$locale->systemLocale )
        {
            $this->doNotTranslate = TRUE;
        }
        self::initLanguages();
        bind_textdomain_codeset( self::DOMAIN, 'UTF-8' );
    }

    public function getLocaleLanguageName( $alpha2 )
    {
        if( !$this->doNotTranslate )
        {
            $this->locale->setLocale();
        }

        if( !array_key_exists( $alpha2, self::$languages ) )
        {
            throw new Exception( 'Unknown Alpha2 language code '.$alpha2 );
        }
        $language = self::$languages[$alpha2];

        if( !array_key_exists( 'name', $language ) )
        {
            throw new Exception( 'Language has no name '.$alpha2 );
        }

        $translation = $this->translate( $language['name'] );

        return $translation;
    }

    public function getLanguageList()
    {
        if( !$this->doNotTranslate )
        {
            $this->locale->setLocale();
        }

        $languageList = array();
        foreach( self::$languages as $alpha2Code => $language )
        {
            if( !array_key_exists( 'name', $language ) )
            {
                continue;
            }
            $languageList[$alpha2Code] = $this->translate( $language['name'] );
        }
        return $languageList;
    }

    private function translate( $languageName )
    {
        if( $this->doNotTranslate )
        {
            return $languageName;
        }
        return dgettext( self::DOMAIN, $languageName );
    }

    private function initLanguages()
    {
        $reader = new XMLReader();
        $reader->open( $this->xmlPath );

        $languages = array();

        while( $reader->read() )
        {
            if( $reader->name != "iso_639_entry" )
            {
                continue;
            }
            $language = array();
            while( $reader->moveToNextAttribute() )
            {
                $language[$reader->name] = $reader->value;
            }

            if( array_key_exists( 'iso_639_1_code', $language ) )
            {
                $languages[$language['iso_639_1_code']] = $language;
            }
        }
        $reader->close();
        self::$languages = $languages;
    }
}
