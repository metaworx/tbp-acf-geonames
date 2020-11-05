<?php

namespace Tbp\WP\Plugin\AcfFields\Entities;

class LanguageBase
{

// protected properties

    protected string $languageCode;


    /**
     * Language constructor.
     *
     * @param  \WP_Post  $post
     */
    protected function __construct(
        string $languageCode
    ) {

        if ( $languageCode === "" || $languageCode === \locale_get_display_language( $languageCode, 'en' ) )
        {
            throw new \ErrorException( sprintf( '"%s" is an invalid language code', $languageCode ) );
        }

        $this->languageCode = $languageCode;
    }


    public function __toString(): string
    {

        return $this->languageCode;
    }


    public function getCaption( ?string $languageCode = null ): ?string
    {

        $caption = $this->getName( $languageCode );
        $native  = $this->getNativeName();

        if ( $native && $native !== $caption )
        {
            $caption .= " ($native)";
        }

        return $caption;
    }


    public function getCode(): string
    {

        return $this->languageCode;
    }


    public function getName( ?string $languageCode = '' ): string
    {

        if ( empty( $languageCode ) )
        {
            $languageCode = Language::getCurrentLanguage();
        }

        return \locale_get_display_language( $this->languageCode, $languageCode );
    }


    public function getNativeName(): ?string
    {

        return \locale_get_display_language( $this->languageCode, $this->languageCode );
    }


    public static function getLanguagesAll( bool $resolveExisting = true ): array
    {

        static $languages = null;

        if ( $languages !== null )
        {
            return $languages;
        }

        $existing = $resolveExisting
            ? Language::getLanguagesExisting()
            : [];

        $a = $b = range( 'a', 'z' );
        array_walk(
            $a,
            static function ( $a ) use
            (
                $b,
                &
                $existing,
                &
                $languages
            )
            {

                array_walk(
                    $b,
                    static function ( $b ) use
                    (
                        $a,
                        &
                        $existing,
                        &
                        $languages
                    )
                    {

                        $code = "$a$b";

                        try
                        {

                            $languages[ $code ] = $existing[ $code ] ?? new static( $code );
                        }
                        catch ( \Throwable $exception )
                        {
                            ;
                        }
                    }
                );
            }
        );

        return $languages;

    }

}