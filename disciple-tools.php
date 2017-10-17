<?php
/**
 * Plugin Name: Disciple Tools
 * Plugin URI: https://github.com/DiscipleTools/disciple-tools
 * Description: Disciple Tools is a disciple relationship management system for disciple making movements. The plugin is the core of the system. It is intended to work with the Disciple Tools Theme, and Disciple Tools extension plugins.
 * Version: 0.1
 * Author: Chasm.Solutions
 * Author URI: https://github.com/DiscipleTools
 * GitHub Plugin URI: https://github.com/DiscipleTools/disciple-tools
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 4.7.2
 *
 * @package Disciple_Tools
 * @author  Chasm Solutions <chasm.crew@chasm.solutions>
 * @link    https://github.com/DiscipleTools
 * @license GPL-3.0
 * @version 0.1
 */

// If this file is called directly, abort.
if( !defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

function dt_admin_notice_required_php_version()
{
    ?>
    <div class="notice notice-error">
        <p><?php esc_html_e( "The Disciple Tools plug-in requires PHP 7.0 or greater before it will have any effect. Please upgrade your PHP version or uninstall this plugin." ); ?></p>
    </div>
    <?php
}

if( version_compare( phpversion(), '7.0', '<' ) ) {

    /* We only support PHP >= 7.0, however, we want to support allowing users
     * to install this plugin even on old versions of PHP, without showing a
     * horrible message, but instead a friendly notice.
     *
     * For this to work, this file must be compatible with old PHP versions.
     * Feel free to use PHP 7 features in other files, but not in this one.
     */

    add_action( 'admin_notices', 'dt_admin_notice_required_php_version' );
    error_log( 'Disciple Tools plugin requires PHP version 7.0 or greater, please upgrade PHP or uninstall this plugin' );

    return;
}

/**
 * Included WordPress plugins that are dependencies
 */
require_once( 'dt-core/libraries/posts-to-posts/posts-to-posts.php' ); // P2P library/plugin

/**
 * Activation Hook
 */
function disciple_tools_activate( $network_wide )
{
    require_once plugin_dir_path( __FILE__ ) . 'dt-core/admin/class-activator.php';
    Disciple_Tools_Activator::activate( $network_wide );
}
register_activation_hook( __FILE__, 'disciple_tools_activate' );

/**
 * Deactivation Hook
 */
function disciple_tools_deactivate( $network_wide )
{
    require_once plugin_dir_path( __FILE__ ) . 'dt-core/admin/class-deactivator.php';
    Disciple_Tools_Deactivator::deactivate( $network_wide );
}
register_deactivation_hook( __FILE__, 'disciple_tools_deactivate' );

/**
 * Multisite datatable maintenance
 */
function dt_on_create_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta )
{
    require_once plugin_dir_path( __FILE__ ) . 'dt-core/admin/class-activator.php';
    Disciple_Tools_Activator::on_create_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta );
}
add_action( 'wpmu_new_blog', 'dt_on_create_blog', 10, 6 );
/**
 * @param $tables
 *
 * @return array
 */
function dt_on_delete_blog( $tables )
{
    require_once plugin_dir_path( __FILE__ ) . 'dt-core/admin/class-activator.php';

    return Disciple_Tools_Activator::on_delete_blog( $tables );
}
add_filter( 'wpmu_drop_tables', 'dt_on_delete_blog' );
/* End Multisite datatable maintenance */

// Adds the Disciple_Tools Plugin after plugins load
add_action( 'plugins_loaded', 'dt_plugins_loaded' );

function dt_plugins_loaded()
{
    Disciple_Tools::instance();

    /* We want to make sure migrations are run on plugin updates. The only way
     * to do this is through the "plugins_loaded" hook. See
     * https://www.sitepoint.com/wordpress-plugin-updates-right-way/ */
    require_once( dirname( __FILE__ ) . '/dt-core/admin/class-migration-engine.php' );
    Disciple_Tools_Migration_Engine::migrate( disciple_tools()->migration_number );
}

/**
 * Returns the main instance of Disciple_Tools to prevent the need to use globals.
 *
 * I'm not sure why this called Disciple_Tools capitalized, maybe one day we
 * can change it to disciple_tools to match convention for function names and
 * to avoid conflating the function with the class.
 *
 * @since  0.1
 * @return object Disciple_Tools
 */

// Creates the instance
// @codingStandardsIgnoreLine TODO: rename this function to disciple_tools
function Disciple_Tools()
{
    return Disciple_Tools::instance();
}

/**
 * Main Disciple_Tools Class
 *
 * @class   Disciple_Tools
 * @since   0.1
 * @package Disciple_Tools
 * @author  Chasm.Solutions & Kingdom.Training
 */
class Disciple_Tools
{
    /**
     * Disciple_Tools The single instance of Disciple_Tools.
     *
     * @var    object
     * @access private
     * @since  0.1
     */
    private static $_instance = null;

    /**
     * The token.
     *
     * @var    string
     * @access public
     * @since  0.1
     */
    public $token;

    /**
     * The version number.
     *
     * @var    string
     * @access public
     * @since  0.1
     */
    public $version;

    /**
     * The plugin directory URL.
     *
     * @var    string
     * @access public
     * @since  0.1
     */
    public $plugin_url;

    /**
     * The plugin directory path.
     *
     * @var    string
     * @access public
     * @since  0.1
     */
    public $plugin_path;
    public $metrics;
    /**
     * Activation of roles.
     *
     * @var    string
     * @access public
     * @since  0.1
     */
    private $roles;
    /**
     * Reports cron job process.
     *
     * @var    string
     * @access public
     * @since  0.1
     */
    public $report_cron;
    /**
     * SVG code for DT logo.
     *
     * @var    string
     * @access public
     * @since  0.1
     */
    public $dt_svg;
    /**
     * Notification object
     *
     * @var    string
     * @access public
     * @since  0.1
     */
    public $notifications;

    /**
     * The admin object.
     *
     * @var    object
     * @access public
     * @since  0.1
     */
    public $admin;

    /**
     * The settings object.
     *
     * @var    object
     * @access public
     * @since  0.1
     */
    public $settings;

    /**
     * The facebook_integration object.
     *
     * @var    object
     * @access public
     * @since  0.1
     */
    public $facebook_integration;

    /**
     * The post types we're registering.
     *
     * @var    array
     * @access public
     * @since  0.1
     */
    public $post_types = [];

    /**
     * Main Disciple_Tools Instance
     * Ensures only one instance of Disciple_Tools is loaded or can be loaded.
     *
     * @since  0.1
     * @static
     * @see    Disciple_Tools()
     * @return Disciple_Tools instance
     */
    public static function instance()
    {
        if( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    } // End instance()

    /**
     * Constructor function.
     *
     * @access public
     * @since  0.1
     */
    public function __construct()
    {
        global $wpdb;

        /**
         * Prepare variables
         */
        $this->token = 'disciple_tools';
        $this->version = '0.1';
        $this->migration_number = 0;
        $this->plugin_url = plugin_dir_url( __FILE__ );
        $this->plugin_path = plugin_dir_path( __FILE__ );
        $this->plugin_img_url = plugin_dir_url( __FILE__ ) . 'dt-core/admin/img/';
        $this->plugin_img_path = plugin_dir_path( __FILE__ ) . 'dt-core/admin/img/';
        $this->plugin_js_url = plugin_dir_url( __FILE__ ) . 'dt-core/admin/js/';
        $this->plugin_js_path = plugin_dir_path( __FILE__ ) . 'dt-core/admin/js/';
        $this->plugin_css_url = plugin_dir_url( __FILE__ ) . 'dt-core/admin/css/';
        $this->plugin_css_path = plugin_dir_path( __FILE__ ) . 'dt-core/admin/css/';

        $wpdb->dt_activity_log = $wpdb->prefix . 'dt_activity_log'; // Prepare database table names
        $wpdb->dt_reports = $wpdb->prefix . 'dt_reports';
        $wpdb->dt_reportmeta = $wpdb->prefix . 'dt_reportmeta';
        $wpdb->dt_share = $wpdb->prefix . 'dt_share';
        $wpdb->dt_notifications = $wpdb->prefix . 'dt_notifications';
        /* End prep variables */

        require_once( 'dt-core/admin/config-site-defaults.php' ); // Force required site configurations
        require_once( 'dt-core/wp-async-request.php' ); // Async Task Processing


        /**
         * Rest API Support
         */
        require_once( 'dt-core/integrations/class-api-keys.php' ); // API keys for remote access
        $this->api_keys = Disciple_Tools_Api_Keys::instance();
        require_once( 'dt-core/admin/restrict-rest-api.php' ); // sets authentication requirement for rest end points. Disables rest for pre-wp-4.7 sites.
        require_once( 'dt-core/admin/restrict-xml-rpc-pingback.php' ); // protect against DDOS attacks.

        /**
         * User Groups & Multi Roles
         */
        require_once( 'dt-core/admin/user-groups/class-user-taxonomy.php' );
        require_once( 'dt-core/admin/user-groups/user-groups-taxonomies.php' );
        require_once( 'dt-core/admin/multi-role/multi-role.php' );
        $this->multi = Disciple_Tools_Multi_Roles::instance();

        /**
         * Data model
         *
         * @posttype   Contacts       Post type for contact storage
         * @posttype   Groups         Post type for groups storage
         * @posttype   Locations      Post type for location information.
         * @posttype   People Groups  (optional) Post type for people groups
         * @posttype   Prayer         Post type for prayer movement updates.
         * @posttype   Project        Post type for movement project updates. (These updates are intended to be for extended owners of the movement project, and different than the prayer guide published in the prayer post type.)
         * @taxonomies
         * @service    Post to Post connections
         * @service    User groups via taxonomies
         */
        require_once( 'dt-core/class-taxonomy.php' );

        /**
         * dt-posts
         */
        require_once( 'dt-core/posts.php' );

        /**
         * dt-contacts
         */
        require_once( 'dt-contacts/contacts-post-type.php' );
        $this->post_types[ 'contacts' ] = Disciple_Tools_Contact_Post_Type::instance();
        require_once( 'dt-contacts/contacts-endpoints.php' );
        Disciple_Tools_Contacts_Endpoints::instance();
        require_once( 'dt-contacts/contacts-template.php' ); // Functions to support theme

        /**
         * dt-groups
         */
        require_once( 'dt-groups/groups-post-type.php' );
        $this->post_types[ 'groups' ] = Disciple_Tools_Groups_Post_Type::instance();
        require_once( 'dt-groups/groups.php' );
        require_once( 'dt-groups/groups-endpoints.php' ); // builds rest endpoints
        Disciple_Tools_Groups_Endpoints::instance();
        require_once( 'dt-groups/groups-template.php' ); // Functions to support theme

        /**
         * dt-locations
         */
        require_once( 'dt-locations/locations-post-type.php' );
        $this->post_types[ 'locations' ] = Disciple_Tools_Location_Post_Type::instance();
        require_once( 'dt-locations/class-map.php' ); // Helper
        require_once( 'dt-locations/class-census-geolocation-api.php' );// APIs
        require_once( 'dt-locations/class-google-geolocation-api.php' );
        require_once( 'dt-locations/class-coordinates-db.php' );
        require_once( 'dt-locations/locations.php' ); // serves the locations rest endpoints
        require_once( 'dt-locations/locations-endpoints.php' ); // builds rest endpoints
        $this->location_api = Disciple_Tools_Locations_Endpoints::instance();
        require_once( 'dt-locations/locations-template.php' );

        /**
         * dt-people-groups
         */
        require_once( 'dt-people-groups/people-groups-post-type.php' );
        $this->post_types[ 'peoplegroups' ] = Disciple_Tools_People_Groups_Post_Type::instance();
        require_once( 'dt-people-groups/people-groups-template.php' );
        require_once( 'dt-people-groups/people-groups.php' );
        require_once( 'dt-people-groups/people-groups-endpoints.php' ); // builds rest endpoints
        $this->peoplegroups_api = Disciple_Tools_People_Groups_Endpoints::instance();

        /**
         * dt-assets
         */
        require_once( 'dt-asset-mapping/asset-mapping-post-type.php' );
        $this->post_types[ 'assetmapping' ] = Disciple_Tools_Asset_Mapping_Post_Type::instance();
        require_once( 'dt-asset-mapping/asset-mapping-template.php' );
        require_once( 'dt-asset-mapping/asset-mapping.php' );
        require_once( 'dt-asset-mapping/asset-mapping-endpoints.php' ); // builds rest endpoints

        /**
         * dt-resources
         */
        require_once( 'dt-resources/resources-post-type.php' );
        $this->post_types[ 'resources' ] = Disciple_Tools_Resources_Post_Type::instance();
        require_once( 'dt-resources/resources-template.php' );
        require_once( 'dt-resources/resources.php' );
        require_once( 'dt-resources/resources-endpoints.php' ); // builds rest endpoints

        /**
         * dt-prayer
         */
        require_once( 'dt-prayer/prayer-post-type.php' );
        $this->post_types[ 'prayer' ] = new Disciple_Tools_Prayer_Post_Type( 'prayer', __( 'Prayer Guide', 'disciple_tools' ), __( 'Prayer Guide', 'disciple_tools' ), [ 'menu_icon' => dt_svg_icon() ] );
        require_once( 'dt-prayer/prayer-template.php' );
        require_once( 'dt-prayer/prayer.php' );
        require_once( 'dt-prayer/prayer-endpoints.php' ); // builds rest endpoints

        /**
         * dt-progress
         */
        require_once( 'dt-progress/progress-post-type.php' );
        $this->post_types[ 'progress' ] = new Disciple_Tools_Progress_Post_Type( 'progress', __( 'Progress Update', 'disciple_tools' ), __( 'Progress Update', 'disciple_tools' ), [ 'menu_icon' => dt_svg_icon() ] );
        require_once( 'dt-asset-mapping/asset-mapping-endpoints.php' ); // builds rest endpoints

        /**
         * dt-metrics
         */
        require_once( 'dt-metrics/class-counter-factory.php' );
        $this->counter = Disciple_Tools_Counter_Factory::instance();
        require_once( 'dt-metrics/class-goals.php' );
        require_once( 'dt-metrics/metrics.php' );
        $this->metrics = Disciple_Tools_Metrics::instance();
        require_once( 'dt-metrics/metrics-template.php' );
        require_once( 'dt-metrics/metrics-endpoints.php' );
        new Disciple_Tools_Metrics_Endpoints();

        /**
         * dt-users
         */
        require_once( 'dt-users/users.php' );
        require_once( 'dt-users/users-template.php' );
        require_once( 'dt-users/users-endpoints.php' );
        new Disciple_Tools_Users_Endpoints();
        new Disciple_Tools_Users();

        /**
         * dt-notifications
         */
        require_once( 'dt-notifications/notifications-hooks.php' );
        $this->notification_hooks = Disciple_Tools_Notification_Hooks::instance();
        require_once( 'dt-notifications/notifications-template.php' );
        require_once( 'dt-notifications/notifications.php' );
        $this->notifications = Disciple_Tools_Notifications::instance();
        require_once( 'dt-notifications/notifications-endpoints.php' );
        $this->notification_endpoints = Disciple_Tools_Notifications_Endpoints::instance();
        require_once( 'dt-notifications/notifications-email.php' ); // sends notification emails through the async task process



        /**
         * Post-to-Post configuration
         */
        require_once( 'dt-core/config-p2p.php' ); // Creates the post to post relationship between the post type tables.

        // Custom Metaboxes
        require_once( 'dt-core/admin/metaboxes/box-address.php' ); // used by both theme and wp-admin

        /**
         * Logging
         */
        require_once( 'dt-core/logging/class-activity-api.php' );
        $this->activity_api = new Disciple_Tools_Activity_Log_API();
        require_once( 'dt-core/logging/class-activity-hooks.php' ); // contacts and groups report building
        $this->activity_hooks = Disciple_Tools_Activity_Hooks::instance();
        require_once( 'dt-core/logging/class-reports-api.php' );
        $this->report_api = new Disciple_Tools_Reports_API();
        require_once( 'dt-core/logging/class-reports-cron.php' ); // Cron scheduling for nightly builds of reports
        $this->report_cron = Disciple_Tools_Reports_Cron::instance();
        require_once( 'dt-core/logging/class-reports-dt.php' ); // contacts and groups report building
        require_once( 'dt-core/logging/debug-logger.php' );

        /**
         * Integrations
         */
        require_once( 'dt-core/integrations/class-integrations.php' ); // data integration for cron scheduling
        if( !class_exists( 'Ga_Autoloader' ) ) {
            require_once( 'dt-core/libraries/google-analytics/disciple-tools-analytics.php' );
            require_once( 'dt-core/integrations/class-google-analytics-integration.php' );
            $this->analytics_integration = Ga_Admin::instance();
        }
        require_once( 'dt-core/integrations/class-facebook-integration.php' ); // integrations to facebook
        $this->facebook_integration = Disciple_Tools_Facebook_Integration::instance();

        /**
         * Language
         */
        add_action( 'init', [ $this, 'load_plugin_textdomain' ] );

        /**
         * Admin panel
         * Contains all those features that only run if in the Admin panel
         * or those things directly supporting Admin panel features.
         */
        if( is_admin() ) {

            // Administration
            require_once( 'dt-core/admin/enqueue-scripts.php' ); // Load admin scripts
            require_once( 'dt-core/admin/admin-theme-design.php' ); // Configures elements of the admin enviornment
            require_once( 'dt-core/admin/restrict-record-access-in-admin.php' ); //
            require_once( 'dt-core/admin/three-column-screen-layout.php' ); // Adds multicolumn configuration to screen options
            require_once( 'dt-core/admin/class-better-author-metabox.php' ); // Allows multiple authors to be selected as post author
            $this->better_metabox = Disciple_Tools_BetterAuthorMetabox::instance();

            // Settings Menu
            require_once( 'dt-core/admin/menu/main.php' );
            $this->config_menu = Disciple_Tools_Config::instance();

            // Dashboard
            require_once( 'dt-core/admin/config-dashboard.php' );
            $this->config_dashboard = Disciple_Tools_Dashboard::instance();

            // Contacts
            require_once( 'dt-contacts/contacts-config.php' );
            $this->config_contacts = Disciple_Tools_Config_Contacts::instance();

            // Groups
            require_once( 'dt-groups/groups-config.php' );
            $this->config_groups = Disciple_Tools_Groups_Config::instance();

            // Locations
            require_once( 'dt-locations/admin-menu.php' );
            $this->location_tools = Disciple_Tools_Location_Tools_Menu::instance();
            require_once( 'dt-locations/class-import.php' ); // import class

            // People Groups
            require_once( 'dt-people-groups/admin-menu.php' );
            $this->people_groups_admin = Disciple_Tools_People_Groups_Admin_Menu::instance();

            // Assets
            // Progress

            // Notifications
            require_once( 'dt-core/admin/tables/notifications-table.php' );

            // Logging
            require_once( 'dt-core/logging/class-activity-list-table.php' ); // contacts and groups report building
            require_once( 'dt-core/logging/class-reports-list-table.php' ); // contacts and groups report building

            // Metaboxes
            require_once( 'dt-core/admin/metaboxes/box-four-fields.php' );
            require_once( 'dt-core/admin/metaboxes/box-church-fields.php' );
            require_once( 'dt-core/admin/metaboxes/box-map.php' );
            require_once( 'dt-core/admin/metaboxes/box-activity.php' );
            require_once( 'dt-core/admin/metaboxes/box-availability.php' );
            require_once( 'dt-core/admin/metaboxes/box-share-contact.php' );
        }
        /* End Admin configuration section */
    } // End __construct()

    /**
     * Load the localisation file.
     *
     * @access public
     * @since  0.1
     */
    public function load_plugin_textdomain()
    {
        load_plugin_textdomain( 'disciple_tools', false, dirname( plugin_basename( __FILE__ ) ) . '/dt-core/languages/' );
    } // End load_plugin_textdomain()

    /**
     * Log the plugin version number.
     *
     * @access private
     * @since  0.1
     */
    public function _log_version_number()
    {
        // Log the version number.
        update_option( $this->token . '-version', $this->version );
    } // End _log_version_number()

    /**
     * Cloning is forbidden.
     *
     * @access public
     * @since  0.1
     */
    public function __clone()
    {
        wp_die( esc_html__( "Cheatin' huh?" ), __FUNCTION__ );
    } // End __clone()

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @access public
     * @since  0.1
     */
    public function __wakeup()
    {
        wp_die( esc_html__( "Cheatin' huh?" ), __FUNCTION__ );
    } // End __wakeup()

} // End Class
