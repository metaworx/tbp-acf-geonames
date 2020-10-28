<?php

namespace Tbp\WP\Plugin\AcfFields\Entities;

abstract class Language
{

// constants
    public const POST_TYPE = 'language';

// protected properties

    /**
     * @var \Tbp\WP\Plugin\AcfFields\Entities\Language[]
     */
    static protected $allLanguages = [];

    /** @var int|null
     *
     */
    protected $post_id;

    /**
     * @var \WP_Post|null
     */
    protected $post;

    /**
     * @var \Tbp\WP\Plugin\AcfFields\Entities\Language
     */
    protected $current;

    /**
     * @var object
     */
    protected $languageInfo;

    protected $nativeName;


    /**
     * Language constructor.
     *
     * @param  \WP_Post  $post
     * @param  object    $languageInfo  Object
     *                                  (
     *                                  [element_id] => 15152
     *                                  [trid] => 65536
     *                                  [language_code] => en
     *                                  [source_language_code] =>
     *                                  )
     */
    protected function __construct(
        \WP_Post $post,
        object $languageInfo
    ) {

        $this->setPost( $post );
        $this->languageInfo = $languageInfo;

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


    public function getCode(): ?string
    {

        return $this->getField( 'code' );
    }


    public function getCurrent(): Language
    {

        if ( $this->current !== null )
        {
            return $this->current;
        }

        return $this->current = $this->getLanguage();
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

        $field = get_field( $name, $this->getOriginal()->post_id );

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


    abstract public function getLanguage( $languageCode = null ): Language;


    public function getName( ?string $languageCode = '' ): string
    {

        if ( $languageCode === '' )
        {
            return $this->getPost()->post_title;
        }

        return $this->getLanguage( $languageCode )
                    ->getName()
            ;
    }


    public function getNativeName(): ?string
    {

        return $this->getField( 'name' );
    }


    /**
     * @return \Tbp\WP\Plugin\AcfFields\Entities\LanguageOriginal
     */
    public abstract function getOriginal(): LanguageOriginal;


    public function getPost()
    {

        if ( $this->post === null && $this->post_id !== null )
        {
            $this->setPost( \WP_Post::get_instance( $this->post_id ) );
        }

        return $this->post
            ?: null;
    }


    public function getSlug(): string
    {

        return '';
    }


    function getTitle(
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
            if ( $thumbnail['type'] == 'icon' )
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
     * @param  bool|\WP_Post|null  $get_instance
     *
     * @return \Tbp\WP\Plugin\AcfFields\Entities\Language
     */
    private function setPost( $get_instance ): Language
    {

        $this->post       = $get_instance;
        $this->nativeName = null;

        if ( $this->post )
        {
            $this->post_id = $this->post->ID;
        }

        return $this;
    }


    /**
     * $param array|int|string|object|null $param
     *
     * @return   \Tbp\WP\Plugin\AcfFields\Entities\Language|\Tbp\WP\Plugin\AcfFields\Entities\Language[]|null
     */
    public static function load(
        $ids,
        $param = []
    ) {

        $keyed   = array_flip( (array) $ids );
        $cached  = array_intersect_key( static::$allLanguages, $keyed );
        $missing = array_diff_key( $keyed, $cached );
        $posts   = null;

        if ( ! empty( $missing ) || empty( $ids ) )
        {
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
                    'include'                => array_flip( $missing ),
                ]
            );

            $missing = null;
            $posts   = get_posts( $param );
        }

        if ( ! empty( $posts ) )
        {

            $posts = array_column( $posts, null, 'ID' );

            array_walk(
                $posts,
                static function ( \WP_Post &$post )
                {

                    $language_info = apply_filters(
                        'wpml_element_language_details',
                        null,
                        [
                            'element_id'   => $post->ID,
                            'element_type' => 'post_' . $post->post_type,
                        ]
                    );

                    $class = $language_info === null || $language_info->source_language_code === null
                        ? LanguageOriginal::class
                        : LanguageTranslated::class;

                    /**
                     * @noinspection CallableParameterUseCaseInTypeContextInspection
                     * @noinspection PhpUndefinedFieldInspection
                     */
                    static::$allLanguages[ $post->ID ] = $post = new $class( $post, $language_info );
                }
            );

            $cached = array_replace( $cached, $posts );

        }

        // order posts by search
        if ( ( $param['s'] ?? false ) && empty( $args['orderby'] ) )
        {

            $cached = array_column( $cached, null, 'getCaption' );
            $sort   = array_column( $cached, 'post_title', 'ID' );

            //ToDo: replace function with a verion that respectes spaces,
            //      so when serarching for "g", "Bulgarian" comes after "Swiss German",
            //      despite the fact that the g is closer to the beginning in the former
            $sort = acf_order_by_search( $sort, $param['s'] );

            $cached = array_replace( $sort, $cached );

        }

        /** @var \Tbp\WP\Plugin\AcfFields\Entities\Language[] $posts */

        if ( ! is_array( $ids ) )
        {
            return reset( $cached );
        }

        return $cached;

    }


    public static function registerCustomPostType()
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
                "title",
                "page-attributes",
            ],
        ];

        register_post_type( static::POST_TYPE, $args );
    }


    public static function registerLanguageCustomFields()
    {

        if ( ! function_exists( 'acf_add_local_field_group' ) )
        {
            return;
        }

        acf_add_local_field_group(
            [
                'key'                   => 'group_language',
                'title'                 => 'Language',
                'fields'                => [
                    [
                        'key'                 => 'field_tbpLanguageNativeName',
                        'label'               => 'Native Name',
                        'name'                => 'name',
                        'type'                => 'text',
                        'instructions'        => 'Name of the Language',
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
                        'maxlength'           => '',
                        'acfe_form'           => true,
                    ],
                    [
                        'key'                 => 'field_tbpLanguageCode',
                        'label'               => 'Code',
                        'name'                => 'code',
                        'type'                => 'text',
                        'instructions'        => 'ISO 639-1 Code (see https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes)',
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
                        'placeholder'         => 'ISO 639-1 Code',
                        'prepend'             => '',
                        'append'              => '',
                        'maxlength'           => 2,
                        'acfe_form'           => true,
                    ],
                    [
                        'key'                 => 'field_tbpLanguageFlat',
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
                        'key'                 => 'field_tbpLanguageDefaultLocale',
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
                        'maxlength'           => 7,
                        'acfe_form'           => true,
                    ],
                    [
                        'key'                 => 'field_tbpLanguageDefaultScript',
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
                    0 => 'json',
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
     */
    public static function translateLanguageIds(
        $object_id,
        $language_code = null
    ) {

        return static::translateObjectIds( $object_id, static::POST_TYPE, $language_code );
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
        $language_code = null
    ) {

        if ( is_array( $object_id ) )
        {
            $translated_object_ids = [];
            foreach ( $object_id as $id )
            {
                $translated_object_ids[] = static::translateObjectIds( $id, $type, $language_code );
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
                $translated_object_ids = static::translateObjectIds( $object_id, $type, $language_code );

                // make sure the output is a comma separated string (the same way it came in!)
                return implode( ',', $translated_object_ids );
            }

            // if we don't find a comma in the string then this is a single ID
            return static::translateObjectIds( (int) $object_id, $type, $language_code );
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

        return apply_filters( 'wpml_object_id', $object_id, $type, true, $language_code );
    }

}