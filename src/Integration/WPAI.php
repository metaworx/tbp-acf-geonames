<?php

namespace Tbp\WP\Plugin\AcfFields\Integration;

use Tbp\WP\Plugin\AcfFields\Entities\LanguageBase;

class WPAI
{

// constants
    public const SOURCE_IDENTIFIER = 'tbp-acf';

// protected properties
    /** @var \Tbp\WP\Plugin\AcfFields\Integration\FacetWP\ACF|null */
    static protected $facetAcf;


    /**
     * FacetWP constructor.
     */
    public function __construct()
    {

        if ( PHP_SAPI === 'cli'
            || (
                $_SERVER['DOCUMENT_URI'] === '/wp-admin/admin.php'
                && ! empty( $_GET )
                && ( $_GET['page'] ?? '' ) === 'pmxi-admin-import'
                && ( $_GET['action'] ?? '' ) === 'process' )
        )
        {
            add_action(
                'init',
                [
                    $this,
                    'loadFields',
                ],
                1,
                1
            );
        }

        add_action(
            'wp_ajax_get_acf',
            [
                $this,
                'loadFields',
            ],
            1,
            1
        );

        add_action(
            'pmxi_article_data',
            [
                $this,
                'loadFields',
            ],
            1,
            1
        );

        add_action(
            'pmxi_article_data',
            static function (
                ?array $articleData,
                \PMXI_Import_Record $PMXIImportRecord,
                $post_to_update,
                $current_xml_node
            ) {

                require_once WP_CONTENT_DIR . '/uploads/wpallimport/functions.php';

                switch ( $PMXIImportRecord->options['custom_type'] )
                {
                case 'language':
                    $language = $articleData['post_title'];
                    $language = normalizeLanguage( 1, $language );
                    $language = LanguageBase::get( $language, true );

                    if ( $language === null )
                    {
                        return $articleData;
                    }

                    $articleData['post_title'] = $language->getName(
                        $PMXIImportRecord->options['wpml_addon']['lng']
                    );
                    $articleData['post_name']  = $language->getCode();

                    break;
                }

                return $articleData;
            },
            10,
            4
        );

    }


    public function loadFields( $articleData )
    {

        static $loaded = false;

        if ( $loaded )
        {
            return $articleData;
        }

        $dir = new \DirectoryIterator( __DIR__ . '/WPAI/Fields' );

        foreach ( $dir as $fileInfo )
        {

            if (
                $fileInfo->isDot()
                || $fileInfo->isDir()
                || $fileInfo->getExtension() !== 'php'
            )
            {
                continue;
            }

            require_once $fileInfo->getPathname();
        }

        $loaded = true;

        return $articleData;

    }

}