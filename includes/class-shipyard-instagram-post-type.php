<?php

final class Shipyard_Instagram_Post_Type {

    /**
     * Public instance of the class.
     */
    public static $instance;

    /**
     * @var string Post type.
     */
    public $post_type = 'instagram-image';

    /**
     * @var string Taxonomy.
     */
    public $taxonomy = 'innapropriate';

    /**
     * @var string Taxonomy term slug.
     */
    public $term = 'yes';

    /**
     * Creates or returns an instance of this class.
     *
     * @return A single instance of this class.
     */
    public static function get() {
        if ( self::$instance === null ) {
            self::$instance = new self;
        }

        return self::$instance;
    }


    /**
     * Class constructor.
     */
    private function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'init', array( $this, 'register_taxonomy' ) );

        add_action( 'add_meta_boxes', array( $this, 'add_image_meta_box' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_tax_meta_box' ) );
        add_action( 'admin_menu', array( $this, 'disable_links_to_new_posts' ) );
        add_action( 'admin_head', array( $this, 'hide_add_new_button' ) );

        add_filter( "manage_edit-{$this->post_type}_columns", array( $this, 'add_custom_column' ) );
        add_action( "manage_{$this->post_type}_posts_custom_column", array( $this, 'add_custom_column_image' ), 10, 2 );
        add_action( "save_post_{$this->post_type}", array( $this, 'save_post_tax' ) );
    }


    public function register_post_type() {
        register_post_type( $this->post_type, array(
            'labels'            => array(
                'name'                => __( 'Instagram images', 'shipyard-instagram' ),
                'singular_name'       => __( 'Instagram image', 'shipyard-instagram' ),
                'all_items'           => __( 'Instagram images', 'shipyard-instagram' ),
                'new_item'            => __( 'New instagram image', 'shipyard-instagram' ),
                'add_new'             => __( 'Add New', 'shipyard-instagram' ),
                'add_new_item'        => __( 'Add New instagram image', 'shipyard-instagram' ),
                'edit_item'           => __( 'Edit instagram image', 'shipyard-instagram' ),
                'view_item'           => __( 'View instagram image', 'shipyard-instagram' ),
                'search_items'        => __( 'Search instagram images', 'shipyard-instagram' ),
                'not_found'           => __( 'No instagram images found', 'shipyard-instagram' ),
                'not_found_in_trash'  => __( 'No instagram images found in trash', 'shipyard-instagram' ),
                'parent_item_colon'   => __( 'Parent instagram image', 'shipyard-instagram' ),
                'menu_name'           => __( 'Instagram', 'shipyard-instagram' ),
            ),
            'public'            => true,
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_in_nav_menus' => true,
            'supports'          => array( 'title' ),
            'has_archive'       => false,
            'rewrite'           => false,
            'query_var'         => true,
            'menu_icon'         => 'dashicons-format-image',
        ) );
    }


    /**
     * Register the taxonomy for hiding images.
     */
    public function register_taxonomy() {
        register_taxonomy( $this->taxonomy, $this->post_type, array(
            'rewrite'      => false,
            'hierarchical' => false,
            'show_ui'      => false,
        ) );
    }


    /**
     * Get the image post objects, exclude the 'hidden' images.
     *
     * @param int $posts_per_page Num posts per page to display.
     *
     * @return WP_Query instance.
     */
    public function get_images( $posts_per_page = 9 ) {
        return new WP_Query( array(
            'post_type'      => $this->post_type,
            'posts_per_page' => absint( $posts_per_page ),
            'no_found_rows'  => true,
            'tax_query'      => array(
                array(
                    'taxonomy' => $this->taxonomy,
                    'field'    => 'slug',
                    'terms'    => $this->term,
                    'operator' => 'NOT IN',
                ),
            ),
        ) );
    }


    /**
     * Add the custom column to the posts list.
     */
    public function add_custom_column( $columns ) {
        return array(
            'cb'    => '<input type="checkbox" />',
            'title' => __( 'Title', 'shipyard-instagram' ),
            'image' => __( 'Image', 'shipyard-instagram' ),
            'hide'  => __( 'Hidden', 'shipyard-instagram' ),
            'date'  => __( 'Date', 'shipyard-instagram' ),
        );
    }


    /**
     * Render data for the custom columns.
     */
    public function add_custom_column_image( $column, $post_id ) {
        switch ( $column ) {

            case 'image' :
                $image = get_post_meta( $post_id, '_instagram_images', true );
                echo '<a href="' . get_edit_post_link( $post_id ) . '"><img style="max-width: 75px" src="' . esc_attr( $image->thumbnail->url ) . '"></a>';
                break;


            case 'hide' :
                $terms = wp_get_object_terms( get_the_ID(), $this->taxonomy );
                if ( empty( $terms ) ) {
                    _e( 'No', 'shipyard-instagram' );
                } else {
                    _e( 'Yes', 'shipyard-instagram' );
                }
                break;
        }
    }


    /**
     * Register the meta box to display the instagram image.
     */
    public function add_image_meta_box() {
        add_meta_box(
            'shipyard-instagram',
            __( 'Instagram Image', 'shipyard-instagram' ),
            array( $this, 'render_instagram_image' ),
            $this->post_type
        );
    }


    /**
     * Register the meta box to display the instagram image.
     */
    public function add_tax_meta_box() {
        add_meta_box(
            'shipyard-instagram-tax',
            __( 'Hide Image', 'shipyard-instagram' ),
            array( $this, 'render_hide_image_box' ),
            $this->post_type,
            'side'
        );
    }


    /**
     * Meta box callback to display the image.
     */
    public function render_instagram_image() {
        $image = get_post_meta( get_the_ID(), '_instagram_images', true ); ?>

        <a target="_blank" href="<?php echo esc_url( get_the_guid( get_the_ID() ) ); ?>"><img src="<?php echo esc_attr( $image->low_resolution->url ); ?>"></a>
    <?php }


    /**
     * Disable the menu item linking to 'add new' for Instagram posts.
     */
    public function disable_links_to_new_posts() {
        global $submenu;
        unset( $submenu["edit.php?post_type={$this->post_type}"][10] );
    }


    /**
     * Hide the 'Add new' button with CSS.
     */
    public function hide_add_new_button() {
        if ( ( isset( $_GET['post_type'] ) && $_GET['post_type'] == $this->post_type ) || get_post_type() === $this->post_type ) { ?>
            <style type="text/css">
                #favorite-actions, .add-new-h2,
                .row-actions .trash,
                #delete-action {
                    display:none;
                }
            </style>
        <?php }
    }


    /**
     * Add the custom metabox to hide innapropriate images.
     */
    public function render_hide_image_box() {
        $terms = wp_get_object_terms( get_the_ID(), $this->taxonomy );
        $checked = '';
        if ( ! empty( $terms ) ) {
            $checked = 'checked="checked"';
        }
        wp_nonce_field( 'shipyard_save_meta_box_data', 'shipyard_meta_box_nonce' ); ?>

        <p>
            <input type="checkbox" id="ship-insta-hide-image" name="ship_insta_hide_image" value="1" <?php echo $checked ?>>
            <label for="ship-insta-hide-image"><?php _e( 'Hide this image', 'shipyard-instagram' ); ?></label>
        </p>
        <span class="description"><?php _e( 'Check the box to hide this image from the frontend of the site', 'shipyard-instagram' ); ?></span>
    <?php }


    /**
     * Save post terms where appropriate.
     */
    public function save_post_tax( $post_id ) {
        if ( ! isset( $_POST['shipyard_meta_box_nonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['shipyard_meta_box_nonce'], 'shipyard_save_meta_box_data' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Set the term if the checkbox is set.
        if ( isset( $_POST['ship_insta_hide_image'] ) && 1 == $_POST['ship_insta_hide_image'] ) {
            wp_set_object_terms( $post_id, $this->term, $this->taxonomy );
        }
        // Otherwise remove the term.
        else {
            wp_set_object_terms( $post_id, array(), $this->taxonomy );
        }

    }

}

