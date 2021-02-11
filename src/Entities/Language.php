<?php

namespace Tbp\WP\Plugin\AcfFields\Entities;

use WP_Post;

class Language
    extends
    LanguageBase
{

// constants

    public const POST_TYPE = 'language';

//  public properties

    /**
     * @var \Tbp\WP\Plugin\AcfFields\Entities\LanguageBase[]
     */
    public static $allLanguages = [];

// protected properties

    /** @var int|null
     *
     */
    protected $post_id;

    /**
     * @var \WP_Post|null
     */
    protected $post;


    /**
     * Language constructor.
     *
     * @param  string         $languageCode
     * @param  \WP_Post|null  $post
     */
    protected function __construct(
        string $languageCode,
        WP_Post $post = null
    ) {

        self::setPost( $post, $this );

        parent::__construct( $languageCode );
    }


    public function getDefaultLocale(): ?string
    {

        return $this->getField( 'default_locale' );
    }


    public function getDefaultScript(): ?string
    {

        return $this->getField( 'default_script' );
    }


    protected function getField( $name ): ?string
    {

        $field = $this->post->$name
            ?? get_field( $name, $this->post_id );

        return $field
            ?: null;

    }


    public function getFlag(): ?string
    {

        return $this->getField( 'flag' );
    }


    public function getId(): ?int
    {

        return $this->post_id
            ?? $this->post
                ? $this->post->ID
                : null;
    }


    public function getPost(): WP_Post
    {

        if ( $this->post === null && $this->post_id !== null )
        {
            $this->setPost( WP_Post::get_instance( $this->post_id ) );
        }

        return $this->post;
    }


    public function getSlug(): string
    {

        return $this->getPost()->post_name;
    }


    public function getTitle(
        $post,
        $field
    ) {

        // get post_id
        if ( ! $this->post_id )
        {
            $this->post_id = acf_get_form_data( 'post_id' );
        }

        // vars
        $title = acf_get_post_title( $post );

        // featured_image
        if ( acf_in_array( 'featured_image', $field['elements'] ) )
        {

            // vars
            $class     = 'thumbnail';
            $thumbnail = acf_get_post_thumbnail(
                $post->ID,
                [
                    17,
                    17,
                ]
            );

            // icon
            if ( $thumbnail['type'] === 'icon' )
            {

                $class .= ' -' . $thumbnail['type'];

            }

            // append
            $title = '<div class="' . $class . '">' . $thumbnail['html'] . '</div>' . $title;

        }

        // filters
        $title = apply_filters( 'acf/fields/language/result', $title, $post, $field, $this->post_id );
        $title = apply_filters(
            'acf/fields/language/result/name=' . $field['_name'],
            $title,
            $post,
            $field,
            $this->post_id
        );
        $title = apply_filters(
            'acf/fields/language/result/key=' . $field['key'],
            $title,
            $post,
            $field,
            $this->post_id
        );

        // return
        return $title;

    }


    /**
     * @param  \WP_Post  $post
     *
     * @return \Tbp\WP\Plugin\AcfFields\Entities\Language
     */
    private function setPost( WP_Post $post ): Language
    {

        $this->post = $post;

        if ( $this->post )
        {
            $this->post_id = $this->post->ID;
        }

        return $this;
    }


    public static function adminLanguagePostColumnValues(
        $column,
        $post_id
    ) {

        static $last_post_id = null;
        static $last_language = null;

        if ( $post_id !== $last_post_id )
        {
            $last_post_id  = $post_id;
            $last_language = Language::load( $post_id );
        }

        switch ( $column )
        {
        case 'code':
            echo $last_language->getCode();
            break;

        case 'native':
            echo $last_language->getNativeName();
            break;

        case 'script':
            echo $last_language->getDefaultScript();
            break;

        case 'locale':
            echo $last_language->getDefaultLocale();
            break;

        case 'flag':
            $flag = $last_language->getFlag();
            if ( $flag )
            {
                /** @noinspection HtmlUnknownTarget */
                printf( '<img alt="%s" src="%s" />', $last_language->getName(), $flag );
            }
            break;

        }

    }


    public static function adminLanguagePostColumns( array $cols )
    {

        $array_splice_assoc = static function (
            &$input,
            $keyOrOffset,
            $length = 0,
            $replacement = null
        ) {

            $keys   = array_keys( $input );
            $offset = is_int( $keyOrOffset )
                ? $keyOrOffset
                : array_search( $keyOrOffset, $keys );

            if ( $replacement )
            {
                $values             = array_values( $input );
                $extracted_elements = array_combine(
                    array_splice( $keys, $offset, $length, array_keys( $replacement ) ),
                    array_splice( $values, $offset, $length, array_values( $replacement ) )
                );
                $input              = array_combine( $keys, $values );
            }
            else
            {
                $extracted_elements = array_slice( $input, $offset, $length );
            }

            return $extracted_elements;
        };

        //printf( "<pre>%s</pre>\n", print_r( $cols, true ) );

        $array_splice_assoc(
            $cols,
            2,
            0,
            [
                'code'   => __( 'Code', 'tbp-acf-fields' ),
                'flag'   => __( 'Flag', 'tbp-acf-fields' ),
                'locale' => __( 'Default Locale', 'tbp-acf-fields' ),
                'script' => __( 'Default Script', 'tbp-acf-fields' ),
                'native' => __( 'Native Name', 'tbp-acf-fields' ),

            ]
        );

        return $cols;
    }


    public static function getCurrentLanguage(): string
    {

        // WPML integration
        if ( defined( 'ICL_LANGUAGE_CODE' ) )
        {
            return ICL_LANGUAGE_CODE;
        }

        if ( isset( $_REQUEST )
            && isset( $_REQUEST['icl_post_language'] )
            && $GLOBALS['sitepress'] instanceof \SitePress
        )
        {
            return $GLOBALS['sitepress']->get_current_language();
        }

        return 'en';
    }


    public static function getLanguagesExisting(): array
    {

        return static::load( null );
    }


    public static function getLanguagesMissing(): array
    {

        return array_diff_key( static::getLanguagesAll(), static::getLanguagesExisting() );
    }


    /**
     * $param array|int|string|object|null $param
     *
     * @return   \Tbp\WP\Plugin\AcfFields\Entities\LanguageBase|\Tbp\WP\Plugin\AcfFields\Entities\Language|\Tbp\WP\Plugin\AcfFields\Entities\LanguageBase[]|\Tbp\WP\Plugin\AcfFields\Entities\Language[]|null
     * @noinspection AdditionOperationOnArraysInspection
     */
    public static function load(
        $ids,
        $param = []
    ) {

        static $cachedAll = false;

        if ( $ids === '' )
        {
            return null;
        }

        // if it's an array with exactly one element that includes commas, treat it as a CSV list
        if ( is_array( $ids ) && count( $ids ) === 1 && false !== strpos( $ids[0], ',' ) )
        {
            $ids = array_filter( explode( ',', $ids[0] ) );
        }

        // if it's an string that includes commas, treat it as a CSV list
        elseif ( is_string( $ids ) && false !== strpos( $ids, ',' ) )
        {
            $ids = array_filter( explode( ',', $ids ) );
        }

        if ( $ids === [] || $ids === [ '' ] )
        {
            return [];
        }

        $keyed   = array_flip( (array) $ids );
        $cached  = array_intersect_key( static::$allLanguages, $keyed );
        $missing = array_diff_key( $keyed, $cached );
        $posts   = [];

        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $param = wp_parse_args(
            $param,
            [
                'posts_per_page'         => - 1,
                'paged'                  => 0,
                'post_type'              => static::POST_TYPE,
                'orderby'                => 'menu_order title',
                'order'                  => 'ASC',
                'post_status'            => 'any',
                'suppress_filters'       => false,
                'update_post_meta_cache' => false,
            ]
        );

        if ( empty( $ids ) )
        {
            if ( $cachedAll )
            {
                $cached = array_filter(
                    static::$allLanguages,
                    static function ( $key )
                    {

                        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                        return is_string( $key );
                    },
                    ARRAY_FILTER_USE_KEY
                );
            }
            else
            {
                $posts     = get_posts( $param );
                $cachedAll = true;
            }
        }
        elseif ( ! empty( $missing ) )
        {
            // get all missing languages by id
            $missingIds = array_filter(
                $missing,
                static function ( $key )
                {

                    return is_numeric( $key );
                },
                ARRAY_FILTER_USE_KEY
            );

            // get all missing languages by slug
            $missingSlugs = array_filter(
                $missing,
                static function ( $key )
                {

                    return is_string( $key );
                },
                ARRAY_FILTER_USE_KEY
            );

            // lookup by id
            if ( ! empty( $missingIds ) )
            {
                $param['include'] = array_keys( $missingIds );

                $missingIds = get_posts( $param );
                $missingIds = array_column( $missingIds, null, 'ID' );

                $missing = array_diff_key( $missing, $missingIds );

                $posts += $missingIds;

                unset( $param['include'] );
            }

            // lookup by slug
            if ( ! empty( $missingSlugs ) )
            {
                $param['post_name__in'] = array_keys( $missingSlugs );

                $missingSlugs = get_posts( $param );
                $missingSlugs = array_column( $missingSlugs, null, 'post_name' );

                $missing = array_diff_key( $missing, $missingSlugs );

                $posts += $missingSlugs;

                unset( $param['post_name__in'] );
            }
        }

        if ( ! empty( $posts ) )
        {

            $posts = array_column( $posts, null, 'post_name' );

            array_walk(
                $posts,
                static function (
                    WP_Post &$post,
                    string $post_name
                ) {

                    /**
                     * @noinspection CallableParameterUseCaseInTypeContextInspection
                     */
                    $post = new static( $post_name, $post );

                    // cache languages by id and by language code
                    static::$allLanguages[ $post->getId() ]   = $post;
                    static::$allLanguages[ $post->getCode() ] = $post;
                }
            );

            $cached = array_replace( $cached, $posts );

        }

        // return missing languages as LanguageBase object
        if ( ! empty( $missing ) )
        {
            array_walk(
                $missing,
                static function (
                    &$lang,
                    string $code
                ) {

                    $lang = new LanguageBase( $code );
                }
            );

            $cached = array_replace( $cached, $missing );
        }

        // order posts by search
        if ( ( $param['s'] ?? false ) && empty( $args['orderby'] ) )
        {

            $cached = array_column( $cached, null, 'getCaption' );
            $sort   = array_column( $cached, 'post_title', 'ID' );

            //ToDo: replace function with a version that respects spaces,
            //      so when searching for "g", "Bulgarian" comes after "Swiss German",
            //      despite the fact that the g is closer to the beginning in the former
            $sort = acf_order_by_search( $sort, $param['s'] );

            $cached = array_replace( $sort, $cached );

        }

        /** @var \Tbp\WP\Plugin\AcfFields\Entities\LanguageBase[] $posts */

        if ( ! empty( $ids ) && ! is_array( $ids ) )
        {
            return reset( $cached );
        }

        return $cached;

    }


    public static function registerCustomPostType(): void
    {

        /** @noinspection SqlResolve */
        $labels = [
            "name"                     => __( "Languages", "tbp-acf-fields" ),
            "singular_name"            => __( "Language", "tbp-acf-fields" ),
            "menu_name"                => __( "Languages", "tbp-acf-fields" ),
            "all_items"                => __( "All Languages", "tbp-acf-fields" ),
            "add_new"                  => __( "Add new", "tbp-acf-fields" ),
            "add_new_item"             => __( "Add new Language", "tbp-acf-fields" ),
            "edit_item"                => __( "Edit Language", "tbp-acf-fields" ),
            "new_item"                 => __( "New Language", "tbp-acf-fields" ),
            "view_item"                => __( "View Language", "tbp-acf-fields" ),
            "view_items"               => __( "View Languages", "tbp-acf-fields" ),
            "search_items"             => __( "Search Languages", "tbp-acf-fields" ),
            "not_found"                => __( "No Languages found", "tbp-acf-fields" ),
            "not_found_in_trash"       => __( "No Languages found in trash", "tbp-acf-fields" ),
            "parent"                   => __( "Parent Language:", "tbp-acf-fields" ),
            "featured_image"           => __( "Featured image for this Language", "tbp-acf-fields" ),
            "set_featured_image"       => __( "Set featured image for this Language", "tbp-acf-fields" ),
            "remove_featured_image"    => __( "Remove featured image for this Language", "tbp-acf-fields" ),
            "use_featured_image"       => __( "Use as featured image for this Language", "tbp-acf-fields" ),
            "archives"                 => __( "Language archives", "tbp-acf-fields" ),
            "insert_into_item"         => __( "Insert into Language", "tbp-acf-fields" ),
            "uploaded_to_this_item"    => __( "Upload to this Language", "tbp-acf-fields" ),
            "filter_items_list"        => __( "Filter Languages list", "tbp-acf-fields" ),
            "items_list_navigation"    => __( "Languages list navigation", "tbp-acf-fields" ),
            "items_list"               => __( "Languages list", "tbp-acf-fields" ),
            "attributes"               => __( "Languages attributes", "tbp-acf-fields" ),
            "name_admin_bar"           => __( "Language", "tbp-acf-fields" ),
            "item_published"           => __( "Language published", "tbp-acf-fields" ),
            "item_published_privately" => __( "Language published privately.", "tbp-acf-fields" ),
            "item_reverted_to_draft"   => __( "Language reverted to draft.", "tbp-acf-fields" ),
            "item_scheduled"           => __( "Language scheduled", "tbp-acf-fields" ),
            "item_updated"             => __( "Language updated.", "tbp-acf-fields" ),
            "parent_item_colon"        => __( "Parent Language:", "tbp-acf-fields" ),
        ];

        $args = [
            "label"                 => __( "Languages", "tbp-acf-fields" ),
            "labels"                => $labels,
            "description"           => __(
                "Languages that can be selected for specific content. Not the website language.",
                "tbp-acf-fields"
            ),
            "public"                => true,
            "publicly_queryable"    => true,
            "show_ui"               => true,
            "show_in_rest"          => true,
            "rest_base"             => "",
            "rest_controller_class" => "WP_REST_Posts_Controller",
            "has_archive"           => false,
            "show_in_menu"          => true,
            "show_in_nav_menus"     => true,
            "delete_with_user"      => false,
            "exclude_from_search"   => true,
            "capability_type"       => "languages",
            "map_meta_cap"          => true,
            "hierarchical"          => true,
            "rewrite"               => [
                "slug"       => static::POST_TYPE,
                "with_front" => false,
            ],
            "query_var"             => true,
            "menu_position"         => 6,
            "menu_icon"             => "dashicons-format-status",
            "supports"              => [
                "page-attributes",
            ],
        ];

        register_post_type( self::POST_TYPE, $args );

        // remove title field
        add_action(
            'admin_init',
            function ()
            {

                remove_post_type_support( self::POST_TYPE, 'title' );
            }
        );

        // register update hook
        add_filter(
            'wp_insert_post_data',
            /**
             * Filters slashed post data just before it is inserted into the database.
             *
             * @since 2.7.0
             * @since 5.4.1 `$unsanitized_postarr` argument added.
             *
             * @param  array  $data                 An array of slashed, sanitized, and processed post data.
             * @param  array  $postarr              An array of sanitized (and slashed) but otherwise unmodified post data.
             * @param  array  $unsanitized_postarr  An array of slashed yet *unsanitized* and unprocessed post data as
             *                                      originally passed to wp_insert_post().
             */
            static function (
                $data,
                $postarr,
                $unsanitized_postarr
            ) {

                if ( $data['post_type'] !== self::POST_TYPE
                    || in_array(
                        $data['post_status'],
                        [
                            'auto-draft',
                            // 'publish',
                            'trash',
                            'inherit',
                        ]
                    ) )
                {
                    return $data;
                }

                $language = ! empty( $postarr['acf'] ) && ! empty( $postarr['acf']['field_tbp-language-code'] )
                    ? new LanguageBase( $postarr['acf']['field_tbp-language-code'] )
                    : LanguageBase::get( $postarr['post_name'] ?? null, true );

                if ( $language === null )
                {
                    return $data;
                }

                // set the post name/slug to the language code
                $data['guid'] = str_replace( $data['post_name'], $language, $data['guid'] );

                // set the post name/slug to the language code
                $data['post_name'] = $language->getCode();

                // set the post name/slug to the language code
                $data['post_title'] = $language->getCaption( 'en' );

                return $data;
            },
            10,
            6
        );

        // make sure the post_name/slug is the language code
        add_filter(
            'wp_insert_post_empty_content',
            /**
             * Filters whether the post should be considered "empty".
             *
             * The post is considered "empty" if both:
             * 1. The post type supports the title, editor, and excerpt fields
             * 2. The title, editor, and excerpt fields are all empty
             *
             * Returning a truthy value from the filter will effectively short-circuit
             * the new post being inserted and return 0. If $wp_error is true, a WP_Error
             * will be returned instead.
             *
             * @since 3.3.0
             *
             * @param  bool   $maybe_empty  Whether the post should be considered "empty".
             * @param  array  $postarr      Array of post data.
             */
            static function (
                $maybe_empty,
                $postarr
            ) {

                // return if not language post_type or unwanted post_status
                if ( empty( $postarr['post_type'] )
                    || $postarr['post_type'] !== static::POST_TYPE
                    || in_array(
                        $postarr['post_status'],
                        [
                            'inherit',
                            'trash',
                            'auto-draft',
                        ],
                        true
                    ) )
                {
                    return $maybe_empty;
                }

                // get the language
                try
                {

                    $lang = new LanguageBase(
                        $_REQUEST['acf']['field_tbp-language-code']
                        ?? $postarr['post_name']
                        ?? null
                    );
                }
                catch ( \Throwable $e )
                {
                    return true;
                }

                // ad a sanitize filter that will always return the language code
                add_filter(
                    'sanitize_title',
                    /**
                     * Filters a sanitized title string.
                     *
                     * @since 1.2.0
                     *
                     * @param  string  $title      Sanitized title.
                     * @param  string  $raw_title  The title prior to sanitization.
                     * @param  string  $context    The context for which the title is being sanitized.
                     */
                    static function ()
                    use
                    (
                        $lang
                    )
                    {

                        return $lang->getCode();
                    },
                    5,
                    0
                );

                return $maybe_empty;
            },
            5,
            2
        );

        // add validation to make sure the language does not already exist
        add_action(
            'acf/validate_save_post',
            static function ()
            {

                if ( ! isset( $_REQUEST['post_type'] )
                    || $_REQUEST['post_type'] !== static::POST_TYPE
                    || in_array(
                        $_REQUEST['post_status'],
                        [
                            'inherit',
                            'trash',
                            'auto-draft',
                        ],
                        true
                    ) )
                {
                    return;
                }

                $lang = static::load( $_REQUEST['acf']['field_tbp-language-code'] );

                if ( $lang instanceof Language && $lang->getPost()->ID !== (int) $_REQUEST['post_ID'] )
                {
                    acf_add_validation_error( 'acf[field_tbp-language-code]', 'Language does already exist' );
                }
            }
        );
    }


    public static function registerLanguageCustomFields(): void
    {

        if ( ! function_exists( 'acf_add_local_field_group' ) )
        {
            return;
        }

        $missingLanguages = LanguageBase::getLanguagesAll();
        array_walk(
            $missingLanguages,
            static function (
                LanguageBase &$lang,
                string $code
            ) {

                /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                $lang = sprintf( '%s - %s', $code, $lang->getCaption() );
            }
        );

        asort( $missingLanguages );

        acf_add_local_field_group(
            [
                'key'                   => 'group_tbp-language',
                'title'                 => 'Language',
                'fields'                => [
                    [
                        'key'                 => 'field_tbp-language-code',
                        'label'               => 'Language',
                        'name'                => 'lang',
                        'type'                => 'select',
                        'instructions'        => '',
                        'required'            => 1,
                        'conditional_logic'   => 0,
                        'wrapper'             => [
                            'width' => '',
                            'class' => '',
                            'id'    => '',
                        ],
                        'wpml_cf_preferences' => 0,
                        'acfe_permissions'    => '',
                        'choices'             => $missingLanguages,
                        'default_value'       => false,
                        'allow_null'          => 0,
                        'multiple'            => 0,
                        'ui'                  => 1,
                        'return_format'       => 'value',
                        'ajax'                => 0,
                        'placeholder'         => 'Select new language',
                    ],
                    [
                        'key'                 => 'field_tbp-language-flag',
                        'label'               => 'Flag',
                        'name'                => 'flag',
                        'type'                => 'url',
                        'instructions'        => '',
                        'required'            => 1,
                        'conditional_logic'   => 0,
                        'wrapper'             => [
                            'width' => '',
                            'class' => '',
                            'id'    => '',
                        ],
                        'wpml_cf_preferences' => 0,
                        'acfe_permissions'    => '',
                        'default_value'       => '',
                        'placeholder'         => '',
                        'acfe_form'           => true,
                    ],
                    [
                        'key'                 => 'field_tbp-language-default-locale',
                        'label'               => 'Default Locale',
                        'name'                => 'default_locale',
                        'type'                => 'text',
                        'instructions'        => '',
                        'required'            => 1,
                        'conditional_logic'   => 0,
                        'wrapper'             => [
                            'width' => '',
                            'class' => '',
                            'id'    => '',
                        ],
                        'wpml_cf_preferences' => 0,
                        'acfe_permissions'    => '',
                        'default_value'       => '',
                        'placeholder'         => '',
                        'prepend'             => '',
                        'append'              => '',
                        'maxlength'           => 30,
                        'acfe_form'           => true,
                    ],
                    [
                        'key'                 => 'field_tbp-language-default-script',
                        'label'               => 'Default Script',
                        'name'                => 'default_script',
                        'type'                => 'text',
                        'instructions'        => '',
                        'required'            => 0,
                        'conditional_logic'   => 0,
                        'wrapper'             => [
                            'width' => '',
                            'class' => '',
                            'id'    => '',
                        ],
                        'wpml_cf_preferences' => 0,
                        'acfe_permissions'    => '',
                        'default_value'       => '',
                        'placeholder'         => '',
                        'prepend'             => '',
                        'append'              => '',
                        'maxlength'           => 7,
                        'acfe_form'           => true,
                    ],
                ],
                'location'              => [
                    [
                        [
                            'param'    => 'post_type',
                            'operator' => '==',
                            'value'    => 'language',
                        ],
                    ],
                ],
                'menu_order'            => 0,
                'position'              => 'normal',
                'style'                 => 'seamless',
                'label_placement'       => 'left',
                'instruction_placement' => 'label',
                'hide_on_screen'        => [
                    0  => 'permalink',
                    1  => 'excerpt',
                    2  => 'discussion',
                    3  => 'comments',
                    4  => 'revisions',
                    5  => 'author',
                    6  => 'format',
                    7  => 'featured_image',
                    8  => 'categories',
                    9  => 'tags',
                    10 => 'send-trackbacks',
                ],
                'active'                => true,
                'description'           => __( 'Details for Language Custom Post Type', 'tbp-acf-fields' ),
                'acfe_display_title'    => '',
                'acfe_autosync'         => [
                ],
                'acfe_permissions'      => [
                    0 => 'administrator',
                ],
                'acfe_form'             => 1,
                'acfe_meta'             => [
                    '5f650ea8f5375' => [
                        'acfe_meta_key'   => '',
                        'acfe_meta_value' => '',
                    ],
                ],
                'acfe_note'             => '',
            ]
        );

    }


    /**
     *
     * @param  int|string|array  $object_id           Use term_id for taxonomies, post_id for posts
     * @param  string|true|null  $language_code       Optional, default is NULL.
     *                                                If set to a language code, it will return a translation for that
     *                                                language code or the original if the translation is missing. If
     *                                                NULL or missing, it will use the current language. If TRUE it
     *                                                will return the id of the original post
     *
     * @return int|string|array
     *
     * @throws \ErrorException
     */
    public static function translateLanguageIds(
        $object_id,
        $language_code = null,
        $returnFormat = 'id'
    ) {

        return static::translateObjectIds(
            $object_id,
            static::POST_TYPE,
            $language_code,
            $returnFormat
        );
    }


    /**
     * @see https://wpml.org/wpml-hook/wpml_object_id/
     *
     * @param  int|string|array  $object_id           Use term_id for taxonomies, post_id for posts
     * @param  string            $type                Use post, page, {custom post type name}, nav_menu, nav_menu_item,
     *                                                category, tag, etc. You can also pass 'any', to let WPML guess
     *                                                the type, but this will only work for posts.
     * @param  string|true|null  $language_code       Optional, default is NULL.
     *                                                If set to a language code, it will return a translation for that
     *                                                language code or the original if the translation is missing. If
     *                                                NULL or missing, it will use the current language. If TRUE it
     *                                                will return the id of the original post
     *
     * @return int|string|array
     *
     */
    public static function translateObjectIds(
        $object_id,
        $type = 'any',
        $language_code = null,
        $returnFormat = 'id'
    ) {

        static $temporaryId = 0;

        if ( $object_id instanceof LanguageBase )
        {

            if ( $returnFormat === 'id' )
            {
                return $object_id instanceof Language
                    ? $object_id->getId()
                    : ++ $temporaryId;
            }

            if ( property_exists( $object_id, $returnFormat ) )
            {
                return $object_id->$returnFormat;
            }

            if ( method_exists( $object_id, $returnFormat )
                && is_callable(
                    [
                        $object_id,
                        $returnFormat,
                    ]
                ) )
            {
                return $object_id->$returnFormat();
            }

            throw new \ErrorException(
                sprintf( 'Invalid return_format "%s" given as 4th argument to %s', $returnFormat, __METHOD__ )
            );
        }

        if ( is_array( $object_id ) )
        {
            $translated_object_ids = [];
            foreach ( $object_id as $id )
            {
                $translated_object_ids[] = static::translateObjectIds(
                    $id,
                    $type,
                    $language_code,
                    $returnFormat
                );
            }

            return $translated_object_ids;
        }

        // if string
        if ( is_string( $object_id ) )
        {

            // check if we have a comma separated ID string
            $is_comma_separated = strpos( $object_id, "," );

            if ( $is_comma_separated !== false )
            {

                // explode the comma to create an array of IDs
                $object_id = explode( ',', $object_id );

                // translate
                $translated_object_ids = static::translateObjectIds(
                    $object_id,
                    $type,
                    $language_code,
                    $returnFormat
                );

                // make sure the output is a comma separated string (the same way it came in!)
                return implode( ',', $translated_object_ids );
            }

            if ( is_numeric( $object_id ) )
            {
                // if we don't find a comma in the string then this is a single ID
                return static::translateObjectIds( (int) $object_id, $type, $language_code, $returnFormat );
            }

            if ( strlen( $object_id ) !== 2 )
            {
                throw new \ErrorException(
                    sprintf( 'Invalid object_id "%s" given as first argument to %s', $object_id, __METHOD__ )
                );
            }

            return static::translateObjectIds(
                Language::load( $object_id ),
                $type,
                $language_code,
                $returnFormat
            );
        }

        if ( $language_code === true || $language_code === 'true' )
        {
            $language_info = apply_filters(
                'wpml_element_language_details',
                null,
                [
                    'element_id'   => $object_id,
                    'element_type' => static::POST_TYPE,
                ]
            );

            $language_code = $language_info->source_language_code ?? $language_info->language_code;
        }

        $object_id = apply_filters( 'wpml_object_id', $object_id, $type, true, $language_code );

        if ( $returnFormat === 'id' )
        {
            return $object_id;
        }

        return static::translateObjectIds(
            Language::load( $object_id ),
            $type,
            $language_code,
            $returnFormat
        );
    }

}