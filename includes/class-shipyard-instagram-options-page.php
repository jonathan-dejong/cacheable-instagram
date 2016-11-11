<?php

final class Shipyard_Instagram_Options_Page {

    /**
     * Public instance of the class.
     */
    public static $instance;

    /**
     * @var string Name of the option key in the database.
     */
    public $option_key = 'shipyard-instagram';

    /**
     * @var string Name of the settings section.
     */
    public $setting = 'shipyard-instgram-settings-group';


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
     *
     * Register the settings and menu option.
     */
    private function __construct() {
        add_action( 'admin_menu', array( $this, 'register_admin_menu_item' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_notices', array( $this, 'maybe_add_admin_notice' ) );
    }


    /**
     * Get a setting by key.
     *
     * @param string $key Array key.
     *
     * @return mixed Value of the setting.
     */
    public function get_setting( $key ) {
        $settings = (array) get_option( $this->option_key );
        if ( array_key_exists( $key, $settings ) ) {
            return $settings[ $key ];
        }

        return '';
    }


    /**
     * Add an admin notice if the plugin hasn't been configured.
     */
    public function maybe_add_admin_notice() {
        $hashtag = $this->get_setting( 'hashtag' );
        if ( empty( $hashtag ) ) : ?>
            <div class="update-nag"><p><?php
                printf(
                    __( 'The Instagram plugin %sneeds to be configured%s!', 'shipyard-instagram' ),
                    '<a href="' . admin_url( 'options-general.php?page=shipyard-instagram' ) . '">',
                    '</a>'
                ); ?>
        </p></div>
        <?php endif;
    }


    /***************************************

     * Settings API callbacks below follow *

     ***************************************/


    public function register_admin_menu_item() {
        add_options_page( __( 'Instagram', 'shipyard-instagram' ), __( 'Instagram', 'shipyard-instagram' ), 'manage_options', 'shipyard-instagram', array( $this, 'render_options_page' ) );
    }

    public function register_settings() {
        register_setting( $this->setting, $this->option_key );
        add_settings_section( 'section-one', __( 'Configure Settings', 'shipyard-instagram' ), array( $this, 'section_one_callback' ), 'shipyard-instagram' );
        add_settings_field( 'field-one', __( 'Title', 'shipyard-instagram' ), array( $this, 'field_one_callback' ), 'shipyard-instagram', 'section-one' );
        add_settings_field( 'field-two', __( 'Hashtag', 'shipyard-instagram' ), array( $this, 'field_two_callback' ), 'shipyard-instagram', 'section-one' );
        add_settings_field( 'field-three', __( 'Client key', 'shipyard-instagram' ), array( $this, 'field_three_callback' ), 'shipyard-instagram', 'section-one' );
    }

    public function section_one_callback() {}

    public function field_one_callback() { ?>
        <input type="text" class="regular-text" name="<?php echo $this->option_key; ?>[title]" value="<?php echo $this->get_setting( 'title' ); ?>">
    <?php }

    public function field_two_callback() { ?>
        <input type="text" class="regular-text" name="<?php echo $this->option_key; ?>[hashtag]" value="<?php echo $this->get_setting( 'hashtag' ); ?>">
    <?php }

    public function field_three_callback() { ?>
        <input type="text" class="regular-text" name="<?php echo $this->option_key; ?>[client_id]" value="<?php echo $this->get_setting( 'client_id' ); ?>">
    <?php }


    public function render_options_page() {
        if ( isset( $_GET['update_feed'] ) && isset( $_POST['update_feed'] ) ) {
            Shipyard_Instagram_Import_Images::get()->update_instagram_feed();
            echo '<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"><p><strong>'. __( 'Feed updated', 'shipyard-instagram' ) . '</strong></p></div>';
        }

        $action = add_query_arg( array( 'update_feed' => 1, 'page' => 'shipyard-instagram' ), get_home_url() . $_SERVER['REQUEST_URI'] ); ?>

        <div class="wrap">

            <h2><?php _e( 'Shipyard Instagram', 'shipyard-instagram' ); ?></h2>
            <form action="options.php" method="POST">
                <?php settings_fields( $this->setting ); ?>
                <?php do_settings_sections( 'shipyard-instagram' ); ?>
                <?php submit_button(); ?>
            </form>

            <h3><?php _e( 'Fetch new images', 'shipyard-instagram' ); ?></h3>
            <p class="description"><?php _e( 'Click the button below to check for new images in the feed. NB. The feed will automatically update every 15 minutes.', 'shipyard-instagram' ); ?></p>
            <form action="<?php echo esc_url( $action ); ?>" method="POST">
                <input type="hidden" name="update_feed" value="1">
                <?php submit_button( __( 'Fetch feed', 'shipyard-instagram' ), 'secondary', 'ship_get_instagram_feed' ); ?>
            </form>

        </div>
    <?php }

}

