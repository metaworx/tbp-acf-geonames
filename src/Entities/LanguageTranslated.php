<?php

namespace Tbp\WP\Plugin\AcfFields\Entities;

class LanguageTranslated
    extends
    Language
{

// protected properties

    /** @var \Tbp\WP\Plugin\AcfFields\Entities\LanguageOriginal */
    protected $original;


    public function getLanguage( $languageCode = null ): Language
    {

        return $this->getOriginal()
                    ->getLanguage( $languageCode )
            ;
    }


    public function getOriginal(): LanguageOriginal
    {

        if ( $this->original === null )
        {
            $this->original = static::load(
                static::translateLanguageIds( $this->post_id )
            );
        }

        return $this->original;

    }

}