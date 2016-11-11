<?php

final class Shipyard_Instagram_Display_Images {

    /**
     * Public instance of the class.
     */
    public static $instance;

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
     * Render the instagram images.
     *
     * @param int $posts_per_page Num posts per page to display.
     */
    public function render_images( $num_images = 9 ) {
        $images = Shipyard_Instagram_Post_Type::get()->get_images( $num_images );
        $title  = Shipyard_Instagram_Options_Page::get()->get_setting( 'title' );

        if ( ! $images->have_posts() ) {
            return;
        }

        if ( ! empty( $title ) ) : ?>
            <h3 class="instagram-title"><?php echo esc_html( $title ); ?></h3>
        <?php endif; ?>

        <ul class="instagram-images">
            <?php while ( $images->have_posts() ) :
                $images->the_post();
                $i_images = get_post_meta( get_the_ID(), '_instagram_images', true ); ?>
                <li>
                    <a href="<?php esc_url_raw( the_guid() ); ?>" target="_blank">
                        <img src="<?php echo esc_attr( $i_images->low_resolution->url ); ?>">
                    </a>
                </li>
            <?php endwhile; wp_reset_postdata(); ?>
        </ul>

    <?php }

}

