<?php

namespace Tbp\WP\Plugin\AcfFields\Entities;

use ErrorException;
use WP_Error;
use WP_Post;

class CountryPost
    extends
    Country
{

// constants
    public const LOAD_NUMERIC_ID_AS_LOCATION_ID = 2;
    public const LOAD_NUMERIC_ID_AS_POST_ID     = 1;
    public const LOCATION_FIELD                 = 'location';
    public const POST_TYPE                      = 'location';

//  public properties

    public static $_countryClass = self::class;
    public static $_returnFormat = self::class;

// protected properties

    /**
     * @var \WP_Post|null
     */
    protected $post;

    /**
     * @var int|null
     */
    protected $postId;


    public function getGeonameId(): int
    {

        $geonameId = parent::getGeonameId();

        if ( $geonameId )
        {
            return $geonameId;
        }

        $post = $this->getPost();

        if ( $post === null )
        {
            return $geonameId;
        }

        $geonameId = get_field( static::LOCATION_FIELD, $post->ID, false );

        if ( ! empty( $geonameId ) )
        {
            if ( is_array( $geonameId ) )
            {
                $geonameId = reset( $geonameId );
            }

            $this->setGeonameId( $geonameId );
        }

        return parent::getGeonameId();
    }


    public function getNameAsSlug(
        $autoload = true,
        $decode = false
    ): string {

        $name = $this->getNameIntl( 'en', $autoload );
        $name = sanitize_title_with_dashes( $name, '', 'save' );

        if ( $decode )
        {
            $name = urldecode( $name );
        }

        return $name;
    }


    /**
     * @return \WP_Post
     */
    public function getPost(): ?WP_Post
    {

        static $lastGeonameLookup = [];

        if ( $this->post !== null )
        {
            return $this->post;
        }

        // lookup by ID

        if ( $this->post === null && $this->postId !== null )
        {
            $this->setPost( WP_Post::get_instance( $this->postId ) );
        }

        // lookup by geonameId

        if ( $this->post === null
            && $this->geonameId !== null
            && ! in_array( $this->geonameId, $lastGeonameLookup, true )
        )
        {
            $lastGeonameLookup[] = $this->geonameId;
            $posts               = static::getPosts( $this->geonameId );

            if ( ! empty( $posts ) )
            {
                $this->setPost( $posts );
            }
        }

        // lookup by post_type

        if ( $this->post === null
            && ( $name = $this->getNameAsSlug() ) !== null
            && ! in_array( $name, $lastGeonameLookup, true )
        )
        {
            $lastGeonameLookup[] = $name;
            $posts               = static::getPosts( $name );

            if ( ! empty( $posts ) )
            {
                $this->setPost( $posts );
            }
        }

        return $this->post;
    }


    /**
     * @param  \WP_Post  $post
     *
     * @return \Tbp\WP\Plugin\AcfFields\Entities\CountryPost
     */
    public function setPost( WP_Post $post ): self
    {

        $this->post   = $post;
        $this->postId = $post->ID;

        return $this;
    }


    /**
     * @return int
     */
    public function getPostId(): ?int
    {

        return $this->postId;
    }


    /**
     * @param  int  $postId
     *
     * @return CountryPost
     */
    public function setPostId( int $postId ): CountryPost
    {

        $this->postId = $postId;

        return $this;
    }


    public function hasPost( $autoload = false ): bool
    {

        return null !== (
            $autoload
                ? $this->getPost()
                : $this->postId
            );
    }


    /**
     * @return $this
     * @throws \ErrorException
     */
    public function createPost(): self
    {

        if ( $this->hasPost( true ) )
        {
            return $this;
        }

        // Create post object
        $my_post = [
            'post_title'   => $this->getNameIntl( 'en' ),
            'post_content' => null,
            'post_status'  => 'pending',
            'post_type'    => static::POST_TYPE,
        ];

        // Insert the post into the database
        $id = wp_insert_post( $my_post );

        if ( $id instanceof WP_Error )
        {
            throw new ErrorException( $id->get_error_message() );
        }

        $this->setPostId( $id );

        update_field( static::LOCATION_FIELD, $this->getGeonameId(), $id );

        return $this;
    }


    public function loadValues(
        $values,
        $defaults = [],
        ?bool $ignoreNonExistingProperties = true
    ): ?int {

        if ( $values instanceof WP_Post )
        {
            $this->setPost( $values );

            if ( empty( $defaults ) )
            {
                return parent::loadValues( $defaults );
            }

            return 0;
        }

        return parent::loadValues( $values, $defaults );
    }


    public static function getCustomPostType(): array
    {

        return [
            "label"                 => __( "Locations", "tbp-acf-fields" ),
            "labels"                => static::getCustomPostTypeLabels(),
            "description"           => __( "Locations with associated post type", "tbp-acf-fields" ),
            "public"                => true,
            "publicly_queryable"    => true,
            "show_ui"               => true,
            "show_in_rest"          => true,
            "rest_base"             => "location",
            "rest_controller_class" => "WP_REST_Posts_Controller",
            "has_archive"           => "locations",
            "show_in_menu"          => true,
            "show_in_nav_menus"     => true,
            "delete_with_user"      => false,
            "exclude_from_search"   => false,
            "capability_type"       => [
                "location",
                "locations",
            ],
            "map_meta_cap"          => true,
            "hierarchical"          => true,
            "rewrite"               => [
                "slug"       => "location",
                "with_front" => true,
            ],
            "query_var"             => true,
            "menu_position"         => 6,
            "menu_icon"             => "dashicons-flag",
            "supports"              => [
                "title",
                "editor",
                "thumbnail",
                "custom-fields",
                "revisions",
                "page-attributes",
            ],
        ];
    }


    protected static function getCustomPostTypeLabels(): array
    {

        return [
            "name"              => __( "Locations", "tbp-acf-fields" ),
            "singular_name"     => __( "Location", "tbp-acf-fields" ),
            "menu_name"         => __( "Locations", "tbp-acf-fields" ),
            "all_items"         => __( "All Locations", "tbp-acf-fields" ),
            "add_new"           => __( "Add Location", "tbp-acf-fields" ),
            "add_new_item"      => __( "Add New Location", "tbp-acf-fields" ),
            "edit_item"         => __( "Edit Location", "tbp-acf-fields" ),
            "new_item"          => __( "New Location", "tbp-acf-fields" ),
            "view_item"         => __( "Show Location", "tbp-acf-fields" ),
            "view_items"        => __( "Show Locations", "tbp-acf-fields" ),
            "search_items"      => __( "Search Location", "tbp-acf-fields" ),
            "not_found"         => __( "Location not found", "tbp-acf-fields" ),
            "parent"            => __( "Parent Location", "tbp-acf-fields" ),
            "archives"          => __( "Locations", "tbp-acf-fields" ),
            "filter_items_list" => __( "Filter Locations", "tbp-acf-fields" ),
            "parent_item_colon" => __( "Parent Location", "tbp-acf-fields" ),
        ];
    }


    /**
     * @param $countryIds
     *
     * @return array|false|mixed
     * @noinspection AdditionOperationOnArraysInspection
     */
    protected static function getPosts(
        $countryIds,
        int $numericAs = self::LOAD_NUMERIC_ID_AS_LOCATION_ID
    ) {

        $loadingAll = $countryIds === null;

        if ( $countryIds === [] )
        {
            return $countryIds;
        }

        // get all missing languages by id
        $missingIds = array_filter(
            (array) $countryIds,
            static function ( $key )
            {

                return is_numeric( $key );
            },
            ARRAY_FILTER_USE_BOTH
        );

        // get all missing languages by slug
        $missingSlugs = array_filter(
            (array) $countryIds,
            static function ( $key )
            {

                return ! is_numeric( $key );
            },
            ARRAY_FILTER_USE_BOTH
        );

        $posts = [];

        $param
            = [
            'posts_per_page'         => $loadingAll || is_array( $countryIds )
                ? - 1
                : 1,
            'paged'                  => 0,
            'post_type'              => static::POST_TYPE,
            'orderby'                => 'ID',
            'order'                  => 'ASC',
            'post_status'            => $loadingAll
                ? 'publish'
                : 'any',
            'suppress_filters'       => false,
            'update_post_meta_cache' => false,
        ];

        // lookup by location field id
        if ( ! empty( $missingIds ) && $numericAs === self::LOAD_NUMERIC_ID_AS_LOCATION_ID )
        {
            $param['meta_query'] = [
                [
                    'key'     => static::LOCATION_FIELD,
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
        elseif ( $loadingAll || ( ! empty( $missingIds ) && $numericAs === self::LOAD_NUMERIC_ID_AS_POST_ID ) )
        {
            if ( ! empty( $missingIds ) )
            {
                $param['post__in']    = $missingIds;
                $param['post_status'] = [
                    'publish',
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

        if ( ! $loadingAll && ! is_array( $countryIds ) )
        {
            return reset( $posts );
        }

        return $posts;
    }


    /**
     * @param  int|string|int[]|string[]|null  $ids
     * @param  object|null                     $options
     *
     * @return array|mixed|null
     * @throws \ErrorException
     */
    public static function load(
        $ids = null,
        ?object $options = null
    ) {

        $options            = $options ?? new \stdClass();
        $options->output    = $options->output ?? static::$_returnFormat;
        $options->numericAs = $options->numericAs ?? self::LOAD_NUMERIC_ID_AS_LOCATION_ID;

        $countries = $options->numericAs === self::LOAD_NUMERIC_ID_AS_POST_ID
            ? static::getPosts( (array) $ids, self::LOAD_NUMERIC_ID_AS_POST_ID )
            : $ids;

        $countries = static::loadRecords( $countries, $options );

        if ( empty( $countries ) )
        {
            return $ids === null || $ids === []
                ? []
                : null;
        }

        return ( $ids === null || is_array( $ids ) )
            ? $countries
            : reset( $countries );
    }


    protected static function loadDetectId(
        &$id,
        $index,
        object $options
    ): void {

        $options               = $options ?? new \stdClass();
        $options->countryClass = $options->countryClass ?? static::$_countryClass;

        if ( $id instanceof $options->countryClass )
        {

            return;
        }

        if ( $id instanceof WP_Post )
        {

            $countryId = get_field( static::LOCATION_FIELD, $id->ID, false );

            if ( empty( $countryId ) )
            {
                throw new \ErrorException( "Invalid WP_Post as no location information found" );
            }

            $options->posts[ $countryId ] = $id;
            $id                           = (int) $countryId;
        }

        parent::loadDetectId(
            $id,
            $index,
            $options
        );
    }


    public static function loadRecords(
        $ids = null,
        object $options = null
    ): ?array {

        $options = $options ?? new \stdClass();

        $countries = parent::loadRecords( $ids, $options );

        if ( empty( $countries ) )
        {
            return $ids === null
                ? []
                : null;
        }

        // find countries without post returning their geonameId
        $missing = array_filter(
            array_map(
                static function ( $country )
                {

                    // ignore $countries that are not of class CountryPost or already have a post
                    if ( ! $country instanceof self || $country->hasPost( false ) )
                    {
                        return null;
                    }

                    // if the post is missing, return the geonameId
                    return $country->getGeonameId();
                },
                $countries
            )
        );

        // bail early if no missing posts
        if ( empty( $missing ) )
        {
            return $countries;
        }

        $posts   = static::getPosts( $missing );
        $missing = array_flip( $missing );

        // match the found posts with the countries
        array_walk(
            $posts,
            static function ( WP_Post $post ) use
            (
                &
                $countries,
                &
                $missing
            )
            {

                $countryId = get_field( static::LOCATION_FIELD, $post->ID, false );

                if ( ! $countryId )
                {
                    return;
                }

                /** @var \Tbp\WP\Plugin\AcfFields\Entities\CountryPost $country */
                $country = $countries["_$countryId"];

                $country->setPost( $post );

                unset( $missing[ $country->getGeonameId() ] );
            }
        );

        // bail early if no missing posts
        if ( empty( $missing ) )
        {
            return $countries;
        }

        unset ( $posts );

        // find countries without post returning their slug
        array_walk(
            $missing,
            static function (
                &$value,
                $countryId
            ) use
            (
                &
                $countries
            )
            {

                /** @var \Tbp\WP\Plugin\AcfFields\Entities\CountryPost $country */
                $country = $countries["_$countryId"];

                // ignore $countries that are not of class CountryPost or already have a post
                if ( ! $country instanceof self )
                {
                    $value = null;

                    return;
                }

                // if the post is missing, return the geonameId
                $value = $country->getNameAsSlug( false, true );
            }
        );

        $posts   = static::getPosts( $missing );
        $missing = array_flip( $missing );

        array_walk(
            $posts,
            static function ( WP_Post $post ) use
            (
                &
                $countries,
                &
                $missing
            )
            {

                $countryId = $missing[ $post->post_name ];

                /** @var \Tbp\WP\Plugin\AcfFields\Entities\CountryPost $country */
                $country = $countries["_$countryId"];

                $country->setPost( $post );

                $countryId = get_field( static::LOCATION_FIELD, $post->ID, false );

                // assign that country to that post
                if ( empty( $countryId ) )
                {
                    update_field( static::LOCATION_FIELD, $country->getGeonameId(), $post->ID );
                }
            }
        );

        unset ( $posts );

        return $countries;
    }


    public static function registerCustomPostType(): void
    {

        register_post_type( static::POST_TYPE, static::getCustomPostType() );
    }

}