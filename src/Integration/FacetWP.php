<?php

namespace Tbp\WP\Plugin\AcfFields\Integration;

use Tbp\WP\Plugin\AcfFields\Field;
use Tbp\WP\Plugin\AcfFields\Integration\FacetWP\ACF;
use Tbp\WP\Plugin\AcfFields\Plugin;

class FacetWP
{

    public const SOURCE_IDENTIFIER = 'tbp-acf';

    /** @var \Tbp\WP\Plugin\AcfFields\Integration\FacetWP\ACF|null */
    static protected $facetAcf;


    /**
     * FacetWP constructor.
     */
    public function __construct()
    {

        // load the filters only in case we really need them
        add_filter( 'facetwp_load_assets', [ $this, 'registerFilers' ] );
        add_filter( 'facetwp_facet_sources', [ $this, 'registerFilers' ], 2 );
        add_filter( 'facetwp_indexer_query_args', [ $this, 'registerFilers' ], 2 );
    }


    public function registerFilers( $input )
    {

        // only run any of this once
        remove_filter( 'facetwp_load_assets', [ $this, 'registerFilers' ] );
        remove_filter( 'facetwp_facet_sources', [ $this, 'registerFilers' ], 2 );
        remove_filter( 'facetwp_indexer_query_args', [ $this, 'registerFilers' ], 2 );

        if ( static::$facetAcf instanceof ACF )
        {
            return $input;
        }

        static::$facetAcf = new ACF();

        add_filter(
            'facetwp_facet_sources',
            [ static::class, 'facetwp_facet_sources' ],
            20
        );

        add_filter(
            'facetwp_indexer_row_data',
            [ static::class, 'facetwp_indexer_row_data' ],
            20,
            2
        );

        //add_filter( 'facetwp_indexer_query_args', [ $this, 'lookup_acf_fields' ] );
        return $input;

    }


    /**
     * Add ACF fields to the Data Sources dropdown
     *
     * @param  array  $sources
     *
     * @return array
     */
    public static function facetwp_facet_sources( array $sources ): array
    {

        array_walk(
            Plugin::$fields,
            static function (
                Field $field
            ) use
            (
                &
                $sources
            )
            {

                $acfFields = static::$facetAcf->types[ $field::NAME ] ?? null;

                if ( $acfFields === null )
                {
                    return;
                }

                $acfFields = array_flip( $acfFields );

                array_walk(
                    $acfFields,
                    static function (
                        &$field,
                        $key
                    ) {

                        $field = static::$facetAcf->fields[ $key ];

                    }
                );

                $sources = apply_filters(
                    "tbp-acf-fields/facet/source/field/name=" . $field::NAME,
                    $sources,
                    $field,
                    $acfFields
                );

                $sources = apply_filters(
                    "tbp-acf-fields/facet/source/field/category=" . $field::CATEGORY,
                    $sources,
                    $field,
                    $acfFields
                );

                $sources = apply_filters(
                    "tbp-acf-fields/facet/source/field",
                    $sources,
                    $field,
                    $acfFields
                );

            }
        );

        return $sources;
    }


    public static function facetwp_indexer_row_data(
        array &$rows,
        array &$params
    ) {

        if ( 0 === strpos( $params['facet']['source'] ?? '', static::SOURCE_IDENTIFIER . '/' ) )
        {
            [ , $type, $property, $field ] = explode( '/', $params['facet']['source'] );

            // skip field, if it doesn't currently exist - that should never happen :-)
            if ( ! $field = acf_get_field( $field ) )
            {
                return $rows;
            }

            // skip if not of the type we are looking for
            if ( $field['type'] !== $type )
            {
                return $rows;
            }

            // load the actual data for the post
            $field['value'] = get_metadata('post', $params['defaults']['post_id'] , $field['name'] ,true);

            $params['source'] = (object) [
                'type'     => $type,
                'property' => $property,
                'field'    => $field,
            ];

            $rows = apply_filters(
                "tbp-acf-fields/facet/index/field/key={$field['key']}",
                $rows,
                $params
            );

            $rows = apply_filters(
                "tbp-acf-fields/facet/index/field/name={$field['name']}",
                $rows,
                $params
            );

            $rows = apply_filters(
                "tbp-acf-fields/facet/index/field",
                $rows,
                $params
            );

            $rows = apply_filters(
                "tbp-acf-fields/facet/index/type={$type}/property={$property}",
                $rows,
                $params
            );

            $rows = apply_filters(
                "tbp-acf-fields/facet/index/type={$type}",
                $rows,
                $params
            );

            $rows = apply_filters(
                "tbp-acf-fields/facet/index",
                $rows,
                $params
            );
        }

        return $rows;

    }

}