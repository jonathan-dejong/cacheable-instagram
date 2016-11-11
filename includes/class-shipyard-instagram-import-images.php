<?php

final class Shipyard_Instagram_Import_Images {

    /**
     * Public instance of the class.
     */
    public static $instance;


    /**
     * @var string URL to the Instagram API.
     */
    protected $api_url = 'https://api.instagram.com/v1/';


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
        add_action( 'update_instagram_feed', array( $this, 'update_instagram_feed' ) );
        add_action( 'delete_old_instragram_posts', array( $this, 'delete_old_instragram_posts' ) );
    }


    /**
     * This runs on a cron hook and will add any new images
     * to the site.
     */
    public function update_instagram_feed() {
        $hashtag   = Shipyard_Instagram_Options_Page::get()->get_setting( 'hashtag' );
        $client_id = Shipyard_Instagram_Options_Page::get()->get_setting( 'client_id' );
        if ( empty( $hashtag ) || empty( $client_id ) ) {
            return;
        }

        $feed_items = $this->get_instagram_feed( $client_id, $hashtag );
        if ( false === $feed_items || empty( $feed_items ) ) {
            return;
        }

        foreach ( $feed_items as $feed_item ) {
            $this->maybe_add_instgram_post( $feed_item );
        }
    }


    /**
     * Make an API call to Instagram to get images tagged
     * with a specific hash tag.
     *
     * @param string $client_id Instagram client API id.
     * @param string $hash_tag  The Hash tag to search for.
     *
     * @return mixed false on failure or an array of images.
     */
    public function get_instagram_feed( $client_id, $hash_tag ) {
        $url = esc_url( $this->api_url . 'tags/' . $hash_tag . '/media/recent/?client_id=' . $client_id );

        $request = wp_remote_get( $url );
        if ( is_wp_error( $request ) ) {
            return false;
        }

        $response_code = wp_remote_retrieve_response_code( $request );
        if ( 200 != $response_code ) {
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $request ) );

        return $body->data;
    }


    /**
     * Add an instagram image as a post.
     *
     * @param object $media_object Instagram media object
     */
    public function maybe_add_instgram_post( $media_object ) {
        $post_exists = $this->check_if_post_exists( $media_object->id );
        if ( true === $post_exists ) {
            return;
        }

        $post_id = wp_insert_post( array(
            'post_type'    => Shipyard_Instagram_Post_Type::get()->post_type,
            'post_title'   => sprintf( __( 'Image by %s', 'shipyard-instagram' ), $media_object->user->username ),
            'post_status'  => 'publish',
            'post_content' => $media_object->caption->text,
            'guid'         => $media_object->link,
            'post_date'    => date( 'Y-m-d H:i:s', $media_object->created_time ),
        ) );

        if ( ! $post_id || is_wp_error( $post_id ) ) {
            return false;
        }

        update_post_meta( $post_id, '_instagram_id',     $media_object->id );
        update_post_meta( $post_id, '_instagram_images', $media_object->images );
        update_post_meta( $post_id, '_instagram_type',   $media_object->type );

        return $post_id;
    }


    /**
     * Check to see if a post exists before inserting it.
     *
     * This is just a duplicate check.
     *
     * @param string $instagram_id Instragram Media ID.
     *
     * @return bool True if post exists, false if not.
     */
    public function check_if_post_exists( $instagram_id ) {
        $query = new WP_Query( array(
            'post_type'              => Shipyard_Instagram_Post_Type::get()->post_type,
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'fields'                 => 'ids',
            'meta_key'               => '_instagram_id',
            'meta_value'             => $instagram_id,
        ) );

        return $query->have_posts();
    }


    /**
     * Delete instagram when there are more than 100.
     *
     * Otherwise images will just keep piling in and fill
     * up the database for no good reason.
     */
    public function delete_old_instragram_posts() {
        $max_num_posts = 100;
        $images = new WP_Query( array(
            'post_type'              => Shipyard_Instagram_Post_Type::get()->post_type,
            'posts_per_page'         => 500,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ) );

        if ( $images->found_posts > $max_num_posts ) {
            array_splice( $images->posts, 0, $max_num_posts );

            foreach ( $images->posts as $image ) {
                wp_delete_post( $image->ID, false );
            }
        }
    }



}