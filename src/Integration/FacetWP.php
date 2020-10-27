<?php

namespace Tbp\WP\Plugin\AcfFields\Integration;

use Tbp\WP\Plugin\AcfFields\Field;
use Tbp\WP\Plugin\AcfFields\Integration\FacetWP\ACF;
use Tbp\WP\Plugin\AcfFields\Plugin;

class FacetWP
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

        // load the filters only in case we really need them
        add_filter(
            'facetwp_preload_url_vars',
            [
                $this,
                'registerFilers',
            ]
        );

       add_filter(
            'facetwp_archive_abort_query',
            [
                $this,
                'registerFilers',
            ]
        );

        add_filter(
            'facetwp_load_assets',
            [
                $this,
                'registerFilers',
            ]
        );

        add_filter(
            'facetwp_facet_sources',
            [
                $this,
                'registerFilers',
            ],
            2
        );

        add_filter(
            'facetwp_indexer_query_args',
            [
                $this,
                'registerFilers',
            ],
            2
        );

        add_filter(
            'facetwp_facet_types',
            [
                $this,
                'registerFilers',
            ],
            20
        );

    }


    public function acfSource(
        ?string $source,
        ?int $post_id = null,
        string &$type = null,
        string &$property = null,
        &$field = null
    ): ?object {

        if ( false === strpos( $source ?? '', static::SOURCE_IDENTIFIER . '/' ) )
        {
            return null;
        }

        [
            ,
            $type,
            $property,
            $field,
        ]
            = explode( '/', $source );

        // skip field, if it doesn't currently exist - that should never happen :-)
        if ( ! $field = acf_get_field( $field ) )
        {
            return null;
        }

        // skip if not of the type we are looking for
        if ( $field['type'] !== $type )
        {
            return null;
        }

        // load the actual data for the post
        if ( $post_id !== null )
        {
            $field['value'] = get_metadata( 'post', $post_id, $field['name'], true );
        }

        return (object) [
            'type'     => $type,
            'property' => $property,
            'field'    => $field,
        ];

    }


    /**
     * Add ACF fields to the Data Sources dropdown
     *
     * @param  array  $sources
     *
     * @return array
     */
    public function facetwp_facet_render_args( array $args ): array
    {

        if ( $source = $this->acfSource(
            $args['facet']['source'],
            null,
            $type,
            $property,
            $field
        ) )
        {
            $args = apply_filters(
                "tbp-acf-fields/facet/render/field/key={$field['key']}",
                $args,
                $source
            );

            $args = apply_filters(
                "tbp-acf-fields/facet/render/field/name={$field['name']}",
                $args,
                $source
            );

            $args = apply_filters(
                "tbp-acf-fields/facet/render/field",
                $args,
                $source
            );

            $args = apply_filters(
                "tbp-acf-fields/facet/render/type={$type}/property={$property}",
                $args,
                $source
            );

            $args = apply_filters(
                "tbp-acf-fields/facet/render/type={$type}",
                $args,
                $source
            );

            $args = apply_filters(
                "tbp-acf-fields/facet/render",
                $args,
                $source
            );
        }

        return $args;
    }


    /**
     * Add ACF fields to the Data Sources dropdown
     *
     * @param  array  $sources
     *
     * @return array
     */
    public function facetwp_facet_sources( array $sources ): array
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


    public function facetwp_index_row(
        array &$params,
        &$class
    ) {

        if ( $source = $this->acfSource(
            $params['facet_source'],
            $params['post_id'],
            $type,
            $property,
            $field
        ) )
        {

            $params['source'] = $source;
            $params['facet']  = FWP()->helper->get_facet_by_name( $params['facet_name'] );

            $params = apply_filters(
                "tbp-acf-fields/facet/index/row/facet/name={$params['facet_name']}",
                $params,
                $class
            );

            $params = apply_filters(
                "tbp-acf-fields/facet/index/row/facet/type={$params['facet']['type']}",
                $params,
                $class
            );

            $params = apply_filters(
                "tbp-acf-fields/facet/index/row/field/key={$field['key']}",
                $params,
                $class
            );

            $params = apply_filters(
                "tbp-acf-fields/facet/index/row/field/name={$field['name']}",
                $params,
                $class
            );

            $params = apply_filters(
                "tbp-acf-fields/facet/index/row/field",
                $params,
                $class
            );

            $params = apply_filters(
                "tbp-acf-fields/facet/index/row/type={$type}/property={$property}",
                $params,
                $class
            );

            $params = apply_filters(
                "tbp-acf-fields/facet/index/row/type={$type}",
                $params,
                $class
            );

            $params = apply_filters(
                "tbp-acf-fields/facet/index/row",
                $params,
                $class
            );
        }

        return $params;

    }


    public function facetwp_indexer_row_data(
        array &$rows,
        array &$params
    ) {

        if ( $source = $this->acfSource(
            $params['facet']['source'],
            $params['defaults']['post_id'],
            $type,
            $property,
            $field
        ) )
        {

            $params['source'] = $source;

            $rows = apply_filters(
                "tbp-acf-fields/facet/index/data/facet/name={$params['facet']['name']}",
                $rows,
                $params
            );

            $rows = apply_filters(
                "tbp-acf-fields/facet/index/data/facet/type={$params['facet']['type']}",
                $rows,
                $params
            );

            $rows = apply_filters(
                "tbp-acf-fields/facet/index/data/field/key={$field['key']}",
                $rows,
                $params
            );

            $rows = apply_filters(
                "tbp-acf-fields/facet/index/data/field/name={$field['name']}",
                $rows,
                $params
            );

            $rows = apply_filters(
                "tbp-acf-fields/facet/index/data/field",
                $rows,
                $params
            );

            $rows = apply_filters(
                "tbp-acf-fields/facet/index/data/type={$type}/property={$property}",
                $rows,
                $params
            );

            $rows = apply_filters(
                "tbp-acf-fields/facet/index/data/type={$type}",
                $rows,
                $params
            );

            $rows = apply_filters(
                "tbp-acf-fields/facet/index/data",
                $rows,
                $params
            );
        }

        return $rows;

    }


    public function registerFilers( $input )
    {

        if ( ! class_exists( 'FacetWP_Integration_ACF', false ) )
        {
            return $input;
        }

        // only run any of this once
        remove_filter(
            'facetwp_preload_url_vars',
            [
                $this,
                'registerFilers',
            ]
        );

       remove_filter(
            'facetwp_archive_abort_query',
            [
                $this,
                'registerFilers',
            ]
        );

         remove_filter(
            'facetwp_load_assets',
            [
                $this,
                'registerFilers',
            ]
        );

        remove_filter(
            'facetwp_facet_sources',
            [
                $this,
                'registerFilers',
            ],
            2
        );

        remove_filter(
            'facetwp_indexer_query_args',
            [
                $this,
                'registerFilers',
            ],
            2
        );

        remove_filter(
            'facetwp_facet_types',
            [
                $this,
                'registerFilers',
            ],
            20
        );

        if ( static::$facetAcf instanceof ACF )
        {
            return $input;
        }

        static::$facetAcf = new ACF();

        add_filter(
            'facetwp_facet_sources',
            [
                $this,
                'facetwp_facet_sources',
            ],
            20
        );

        add_filter(
            'facetwp_indexer_row_data',
            [
                $this,
                'facetwp_indexer_row_data',
            ],
            10,
            2
        );

        add_filter(
            'facetwp_index_row',
            [
                $this,
                'facetwp_index_row',
            ],
            10,
            2
        );

        add_filter(
            'facetwp_facet_render_args',
            [
                $this,
                'facetwp_facet_render_args',
            ],
            10
        );

        //add_filter( 'facetwp_indexer_query_args', [ $this, 'lookup_acf_fields' ] );
        return $input;

    }


}