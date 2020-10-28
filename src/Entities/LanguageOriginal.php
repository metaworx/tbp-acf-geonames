<?php

namespace Tbp\WP\Plugin\AcfFields\Entities;

class LanguageOriginal
    extends
    Language
{

// protected properties

    /**
     * @var \Tbp\WP\Plugin\AcfFields\Entities\Language[]
     */
    protected $languages = [];


    public function getLanguage( $languageCode = null ): Language
    {

        if ( $languageCode === '' )
        {
            return $this;
        }

        if ( ( $languageCode === null || $languageCode === ICL_LANGUAGE_CODE ) && $this->current !== null )
        {
            return $this->current;
        }

        if ( array_key_exists( $languageCode ?? ICL_LANGUAGE_CODE, $this->languages ) )
        {
            return $this->languages[ $languageCode ];
        }

        $language = $this->languages[ $languageCode ?? ICL_LANGUAGE_CODE ] = static::load(
            static::translateLanguageIds( $this->post_id, $languageCode ?? ICL_LANGUAGE_CODE ),
            [
                'suppress_filters' => true,
            ]
        );

        if ( $languageCode === null || $languageCode === ICL_LANGUAGE_CODE )
        {
            return $this->current = $language;
        }

        return $language;
    }


    public function getOriginal(): LanguageOriginal
    {

        return $this;
    }

}