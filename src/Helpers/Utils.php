<?php

namespace Tbp\WP\Plugin\AcfFields\Helpers;

class Utils
{

// constants

    /**
     * @param                 $IDs
     * @param  string         $post_type
     * @param  string|null    $metaFieldToCheckIdsAgainst
     * @param  callable|null  $isIdCallback
     * @param  callable|null  $isSlugCallback
     * @param  array|null     $additionalPosts
     *
     * @return array|false|mixed
     * @noinspection AdditionOperationOnArraysInspection
     */
    public static function getPosts(
        $IDs,
        string $post_type,
        string $metaFieldToCheckIdsAgainst = null,
        ?callable $isIdCallback = null,
        ?callable $isSlugCallback = null,
        ?array &$additionalPosts = null
    ) {

        $loadingAll   = $IDs === null;
        $returnSingle = ( ! $loadingAll && ! is_array( $IDs ) && $additionalPosts === null );

        if ( $IDs === [] )
        {
            return $IDs;
        }

        // get all missing countries by id
        $missingIds = array_filter(
            (array) $IDs,
            $isIdCallback ?? static function ( $key )
            {

                return is_numeric( $key );
            },
            ARRAY_FILTER_USE_BOTH
        );

        // get all missing countries by slug
        $missingSlugs = array_filter(
            (array) $IDs,
            $isSlugCallback ?? static function ( $key )
            {

                return ! is_numeric( $key );
            },
            ARRAY_FILTER_USE_BOTH
        );

        $posts = [];

        $param
            = [
            'posts_per_page'         => $loadingAll || is_array( $IDs )
                ? - 1
                : 1,
            'paged'                  => 0,
            'post_type'              => $post_type,
            'orderby'                => 'ID',
            'order'                  => 'ASC',
            'post_status'            => $loadingAll
                ? 'publish'
                : 'any',
            'suppress_filters'       => false,
            'update_post_meta_cache' => false,
        ];

        // lookup by location field id
        if ( ! empty( $missingIds ) && ! empty( $metaFieldToCheckIdsAgainst ) )
        {
            $param['meta_query'] = [
                [
                    'key'     => $metaFieldToCheckIdsAgainst,
                    'value'   => $missingIds,
                    'compare' => 'IN',
                ],
            ];

            $missingIds = get_posts( $param );
            $missingIds = array_column( $missingIds, null, 'ID' );

            $posts += $missingIds;

            unset( $param['meta_query'] );
            unset( $missingIds );
        }

        // lookup by post id
        elseif ( $loadingAll || ! empty( $missingIds ) )
        {
            if ( ! empty( $missingIds ) )
            {
                $param['post__in']    = $missingIds;
                $param['post_status'] = [
                    'publish',
                    'pending',
                    'draft',
                    'auto-draft',
                    'trash',
                ];
            }

            $missingIds = get_posts( $param );
            $missingIds = array_column( $missingIds, null, 'ID' );

            $posts += $missingIds;

            unset( $param['post__in'] );
            unset( $missingIds );
        }

        // lookup by slug
        if ( ! empty( $missingSlugs ) )
        {
            $param['post_name__in'] = $missingSlugs;

            $missingSlugs = get_posts( $param );
            $missingSlugs = array_column( $missingSlugs, null, 'ID' );

            $posts += $missingSlugs;

            unset( $param['post_name__in'] );
            unset( $missingSlugs );
        }

        if ( ! empty( $posts ) )
        {

            // load geoname field
            $missingIds = array_column( $posts, 'ID' );

            update_meta_cache( 'post', $missingIds );
        }

        if ( $additionalPosts !== null && count( $additionalPosts ) !== 0 )
        {
            $posts += $additionalPosts;
        }

        if ( $returnSingle )
        {
            return reset( $posts );
        }

        return $posts;
    }

}