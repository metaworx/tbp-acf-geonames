<?php

namespace Tbp\WP\Plugin\AcfFields\Entities;

use ErrorException;
use Tbp\WP\Plugin\AcfFields\Helpers\Utils;
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
    public const POST_TYPE                      = 'tbp-location';

//  public properties

    public static $_countryClass = self::class;

    public static $_returnFormat = self::class;

// protected properties

    /**
     * @var \Tbp\WP\Plugin\AcfFields\Entities\CountryPost[]
     */
    protected static $_countryPosts = [];

    /**
     * @var \WP_Post|null
     */
    protected $post;

    /**
     * @var int|null
     */
    protected $postId;


    public function __get( $property )
    {

        if ( parent::__isset( $property ) )
        {
            return parent::__get( $property );
        }

        $post = $this->getPost();

        if ( $post && isset( $post->$property ) )
        {
            return $post->$property;
        }

        return null;
    }


    public function __isset( $property )
    {

        if ( parent::__isset( $property ) )
        {
            return true;
        }

        $post = $this->getPost();

        return $post && isset( $post->$property );
    }


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

        if ( $this->postId !== null )
        {
            $post = WP_Post::get_instance( $this->postId );

            if ( $post instanceof WP_Post )
            {
                $this->setPost( $post );

                return $this->post;
            }
        }

        // lookup by geonameId

        if ( $this->geonameId !== null
            && ! in_array( $this->geonameId, $lastGeonameLookup, true )
        )
        {
            $lastGeonameLookup[] = $this->geonameId;
            $post                = static::getPosts( $this->geonameId );

            if ( $post instanceof WP_Post )
            {
                $this->setPost( $post );

                return $this->post;
            }
        }

        // lookup by post_type

        if ( ( $name = $this->getNameAsSlug() ) !== null
            && ! in_array( $name, $lastGeonameLookup, true )
        )
        {
            $lastGeonameLookup[] = $name;
            $post                = static::getPosts( $name );

            if ( $post instanceof WP_Post )
            {
                $this->setPost( $post );

                return $this->post;
            }
        }

        return null;
    }


    /**
     * @param  \WP_Post  $post
     *
     * @return \Tbp\WP\Plugin\AcfFields\Entities\CountryPost
     */
    public function setPost( WP_Post $post ): self
    {

        $this->post = $post;
        $this->setPostId( $post->ID );

        $slug = $this->post->post_name;

        if ( empty( $slug ) )
        {
            $slug = $this->post->post_name = $this->getNameAsSlug();

            if ( empty( $slug ) )
            {
                throw new \ErrorException( "Empty slug!" );
            }

            $result = wp_update_post( $this->post );

            if ( $result instanceof WP_Error )
            {
                throw new \ErrorException(
                    sprintf(
                        "could not update country's slug with $slug: %s",
                        implode( " ", $result->get_error_messages() )
                    )
                );
            }

            if ( $result === 0 )
            {
                throw new \ErrorException(
                    sprintf( "could not update country's slug with $slug" )
                );
            }
        }

        self::$_countryPosts[ $slug ] = $this;

        ksort( self::$_countryPosts );

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

        $old = null;

        if ( $this->postId && array_key_exists( "_$this->postId", self::$_countryPosts ) )
        {
            $old = self::$_countryPosts["_$this->postId"];

            if ( $old === $this )
            {
                if ( $this->postId === $postId )
                {
                    // nothing changed
                    return $this;
                }

                if ( $postId === null )
                {
                    // remove entry
                    unset( self::$_countryPosts["_$this->postId"] );
                }
            }

        }

        $this->postId = $postId;

        if ( $this->postId && $old !== $this )
        {
            self::$_countryPosts["_$this->postId"] = $this;
        }

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
            'post_name'    => $this->getNameAsSlug( 'en' ),
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
            "capability_type"       => static::POST_TYPE,
            "map_meta_cap"          => true,
            "hierarchical"          => true,
            "rewrite"               => [
                "slug"                 => "location",
                "with_front"           => true,
                "remove_rewrite_regex" => "@trackback|comment-page@",
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
     */
    protected static function getPosts(
        $countryIds,
        int $numericAs = self::LOAD_NUMERIC_ID_AS_LOCATION_ID
    ) {

        $cached = null;

        // get all missing countries by id
        $isIdCallback = static function ( $key ) use
        (
            &
            $cached
        )
        {

            if ( ! is_numeric( $key ) )
            {
                return false;
            }

            /** @var \Tbp\WP\Plugin\AcfFields\Entities\CountryPost $post */
            if ( ( $post = self::$_countryPosts["_$key"] ?? null )
                && $post->hasPost( false ) )
            {
                $cached["_$key"] = $post->getPost();

                return false;
            }

            return true;
        };

        // get all missing countries by slug
        $isSlugCallback = static function ( $key ) use
        (
            &
            $cached
        )
        {

            if ( is_numeric( $key ) )
            {
                return false;
            }

            /** @var \Tbp\WP\Plugin\AcfFields\Entities\CountryPost $post */
            if ( ( $post = self::$_countryPosts[ $key ] ?? null )
                && $post->hasPost( false ) )
            {
                $cached[ $key ] = $post->getPost();

                return false;
            }

            return true;
        };

        $posts = Utils::getPosts(
            $countryIds,
            static::POST_TYPE,
            $numericAs === self::LOAD_NUMERIC_ID_AS_LOCATION_ID
                ? static::LOCATION_FIELD
                : null,
            $isIdCallback,
            $isSlugCallback,
            $cached
        );

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
            ? static::getPosts( $ids, self::LOAD_NUMERIC_ID_AS_POST_ID )
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
                static function ( $country ) use
                (
                    $options
                )
                {

                    // ignore $countries that are not of class CountryPost or already have a post
                    if ( ! $country instanceof self || $country->hasPost( false ) )
                    {
                        return null;
                    }

                    $geonameId = $country->getGeonameId();

                    if ( is_array( $options->posts ?? null ) && array_key_exists( $geonameId, $options->posts ) )
                    {
                        $country->setPost( $options->posts[ $geonameId ] );
                        unset( $options->posts[ $geonameId ] );

                        return null;
                    }

                    // if the post is missing, return the geonameId
                    return $geonameId;
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