<?php
/**
 * Plugin Name: Disciple Tools
 * Plugin URI: https://github.com/DiscipleTools/disciple-tools
 * Description: Disciple Tools is a disciple relationship management system for disciple making movements. The plugin is the core of the system. It is intended to work with the Disciple Tools Theme, and Disciple Tools extension plugins.
 * Version:  0.1.0
 * Author URI: https://github.com/DiscipleTools
 * GitHub Plugin URI: https://github.com/DiscipleTools/disciple-tools
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 4.8.2
 *
 * @package Disciple_Tools
 * @link    https://github.com/DiscipleTools
 * @license GPL-2.0 or later
 *          https://www.gnu.org/licenses/gpl-2.0.html
 * @version  0.1.0
 *
 */

// If this file is called directly, abort.
if ( !defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * File Organization Notes:
 * The section below contains functions that must run immediately on plugin load for database maintenance and migration,
 * and for that reason must load before the class. It is always preferred that new files and functions be linked from within
 * the Disciple_Tools() class inside the __construct.
 */

function dt_admin_notice_required_php_version()
{
    ?>
    <div class="notice notice-error">
        <p><?php esc_html_e( "The Disciple Tools plug-in requires PHP 7.0 or greater before it will have any effect. Please upgrade your PHP version or uninstall this plugin." ); ?></p>
    </div>
    <?php
}

if ( version_compare( phpversion(), '7.0', '<' ) ) {

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
function dt_activate( $network_wide )
{
    require_once plugin_dir_path( __FILE__ ) . 'dt-core/admin/class-activator.php';
    Disciple_Tools_Activator::activate( $network_wide );
}
register_activation_hook( __FILE__, 'dt_activate' );

/**
 * Deactivation Hook
 */
function dt_deactivate( $network_wide )
{
    require_once plugin_dir_path( __FILE__ ) . 'dt-core/admin/class-deactivator.php';
    Disciple_Tools_Deactivator::deactivate( $network_wide );
}
register_deactivation_hook( __FILE__, 'dt_deactivate' );

/**
 * Multisite: Create new blog db maintainance
 *
 * @param $blog_id
 * @param $user_id
 * @param $domain
 * @param $path
 * @param $site_id
 * @param $meta
 */
function dt_on_create_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta )
{
    require_once plugin_dir_path( __FILE__ ) . 'dt-core/admin/class-activator.php';
    Disciple_Tools_Activator::on_create_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta );
}
add_action( 'wpmu_new_blog', 'dt_on_create_blog', 10, 6 );

/**
 * Multisite: Delete blog db maintenance
 *
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


/**
 * Adds the Disciple_Tools Plugin after plugins load
 */
function dt_plugins_loaded()
{
    Disciple_Tools::instance();

    /** We want to make sure migrations are run on plugin updates. The only way
     * to do this is through the "plugins_loaded" hook. See
     * @see https://www.sitepoint.com/wordpress-plugin-updates-right-way/
     */
    require_once( dirname( __FILE__ ) . '/dt-core/admin/class-migration-engine.php' );
    Disciple_Tools_Migration_Engine::migrate( disciple_tools()->migration_number );

    /** Similarly, we want to make sure roles are up-to-date. */
    require_once( dirname( __FILE__ ) . '/dt-core/admin/class-roles.php' );
    Disciple_Tools_Roles::instance()->set_roles_if_needed();

    /**
     * Site options version check
     */

}
add_action( 'plugins_loaded', 'dt_plugins_loaded' );

/**
 * Returns the main instance of Disciple_Tools to prevent the need to use globals.
 *
 * @example
 *
 * @since  0.1.0
 * @return object Disciple_Tools
 */

// Creates the instance
function disciple_tools()
{
    return Disciple_Tools::instance();
}

/**
 * Main Disciple_Tools Class
 *
 * @class   Disciple_Tools
 * @since   0.1.0
 * @package Disciple_Tools
 *
 */
class Disciple_Tools
{
    /**
     * Disciple_Tools The single instance of Disciple_Tools.
     *
     * @var    object
     * @access private
     * @since  0.1.0
     */
    private static $_instance = null;

    /**
     * The token.
     *
     * @var    string
     * @access public
     * @since  0.1.0
     */
    public $token;

    /**
     * The version number.
     *
     * @var    string
     * @access public
     * @since  0.1.0
     */
    public $version;

    /**
     * The plugin directory URL.
     *
     * @var    string
     * @access public
     * @since  0.1.0
     */
    public $plugin_url;

    /**
     * The plugin directory path.
     *
     * @var    string
     * @access public
     * @since  0.1.0
     */
    public $plugin_path;

    /**
     * SVG code for DT logo.
     *
     * @var    string
     * @access public
     * @since  0.1.0
     */
    public $dt_svg;

    /**
     * The admin object.
     *
     * @var    object
     * @access public
     * @since  0.1.0
     */
    public $admin;

    /**
     * The settings object.
     *
     * @var    object
     * @access public
     * @since  0.1.0
     */
    public $settings;

    /**
     * The facebook_integration object.
     *
     * @var    object
     * @access public
     * @since  0.1.0
     */
    public $facebook_integration;

    /**
     * Object holders for for different post types
     *
     * @var    array
     * @access public
     * @since  0.1.0
     */
    public $post_types = [];
    public $endpoints = [];
    public $core = [];
    public $hooks = [];

    public $logging = [];
    public $metrics;
    public $notifications;

    /**
     * The core controller files we're registering.
     *
     * @var    array
     * @access public
     * @since  0.1.0
     */



    /**
     * Main Disciple_Tools Instance
     * Ensures only one instance of Disciple_Tools is loaded or can be loaded.
     *
     * @since  0.1.0
     * @static
     * @see    disciple_tools()
     * @return Disciple_Tools instance
     */
    public static function instance()
    {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    } // End instance()

    /**
     * Constructor function.
     *
     * @access public
     * @since  0.1.0
     */
    public function __construct()
    {
        global $wpdb;

        /**
         * Prepare variables
         */
        $this->token = 'disciple_tools';
        $this->version = '0.1.1';
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

        require_once( 'dt-core/logging/debug-logger.php' ); // enables dt_write_log for debug output.
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
        $this->post_types['contacts'] = Disciple_Tools_Contact_Post_Type::instance();
        require_once( 'dt-contacts/contacts-endpoints.php' );
        $this->endpoints['contacts'] = Disciple_Tools_Contacts_Endpoints::instance();
        require_once( 'dt-contacts/contacts-template.php' ); // Functions to support theme

        /**
         * dt-groups
         */
        require_once( 'dt-groups/groups-post-type.php' );
        $this->post_types['groups'] = Disciple_Tools_Groups_Post_Type::instance();
        require_once( 'dt-groups/groups-template.php' ); // Functions to support theme
        require_once( 'dt-groups/groups.php' );
        require_once( 'dt-groups/groups-endpoints.php' ); // builds rest endpoints
        $this->endpoints['groups'] = Disciple_Tools_Groups_Endpoints::instance();

        /**
         * dt-locations
         */
        require_once( 'dt-locations/locations-post-type.php' );
        $this->post_types['locations'] = Disciple_Tools_Location_Post_Type::instance();
        require_once( 'dt-locations/class-map.php' ); // Helper
        require_once( 'dt-locations/class-census-geolocation-api.php' );// APIs
        require_once( 'dt-locations/class-google-geolocation-api.php' );
        require_once( 'dt-locations/class-coordinates-db.php' );

        require_once( 'dt-locations/locations-template.php' );
        require_once( 'dt-locations/locations.php' ); // serves the locations rest endpoints
        require_once( 'dt-locations/locations-endpoints.php' ); // builds rest endpoints
        $this->endpoints['locations'] = Disciple_Tools_Locations_Endpoints::instance();

        /**
         * dt-people-groups
         */
        require_once( 'dt-people-groups/people-groups-post-type.php' );
        $this->post_types['peoplegroups'] = Disciple_Tools_People_Groups_Post_Type::instance();
        require_once( 'dt-people-groups/people-groups-template.php' );
        require_once( 'dt-people-groups/people-groups.php' );
        require_once( 'dt-people-groups/people-groups-endpoints.php' ); // builds rest endpoints
        $this->endpoints['peoplegroups'] = Disciple_Tools_People_Groups_Endpoints::instance();

        /**
         * dt-assetmapping
         */
        require_once( 'dt-assetmapping/assetmapping-post-type.php' );
        $this->post_types['assetmapping'] = Disciple_Tools_Assetmapping_Post_Type::instance();
        require_once( 'dt-assetmapping/assetmapping-template.php' );
        require_once( 'dt-assetmapping/assetmapping.php' );
        require_once( 'dt-assetmapping/assetmapping-endpoints.php' ); // builds rest endpoints
        $this->endpoints['assetmapping'] = new Disciple_Tools_Assetmapping_Endpoints();

        /**
         * dt-resources
         */
        require_once( 'dt-resources/resources-post-type.php' );
        $this->post_types['resources'] = Disciple_Tools_Resources_Post_Type::instance();
        require_once( 'dt-resources/resources-template.php' );
        require_once( 'dt-resources/resources.php' );
        require_once( 'dt-resources/resources-endpoints.php' ); // builds rest endpoints
        $this->endpoints['resources'] = new Disciple_Tools_Resources_Endpoints();

        /**
         * dt-prayer
         */
        require_once( 'dt-prayer/prayer-post-type.php' );
        $this->post_types['prayer'] = new Disciple_Tools_Prayer_Post_Type( 'prayer', __( 'Prayer Guide', 'disciple_tools' ), __( 'Prayer Guide', 'disciple_tools' ), [ 'menu_icon' => dt_svg_icon() ] );
        require_once( 'dt-prayer/prayer-template.php' );
        require_once( 'dt-prayer/prayer.php' );
        require_once( 'dt-prayer/prayer-endpoints.php' ); // builds rest endpoints
        $this->endpoints['prayer'] = new Disciple_Tools_Prayer_Endpoints();

        /**
         * dt-progress
         */
        require_once( 'dt-progress/progress-post-type.php' );
        $this->post_types['progress'] = new Disciple_Tools_Progress_Post_Type( 'progress', __( 'Progress Update', 'disciple_tools' ), __( 'Progress Update', 'disciple_tools' ), [ 'menu_icon' => dt_svg_icon() ] );
        require_once( 'dt-progress/progress.php' );
        require_once( 'dt-progress/progress-template.php' );
        require_once( 'dt-progress/progress-endpoints.php' );
        $this->endpoints['progress'] = new Disciple_Tools_Progress_Endpoints();

        /**
         * dt-metrics
         */
        require_once( 'dt-metrics/class-counter.php' );
        $this->counter = Disciple_Tools_Counter::instance();
        require_once( 'dt-metrics/class-goals.php' );
        require_once( 'dt-metrics/metrics-template.php' );
        require_once( 'dt-metrics/metrics.php' );
        $this->metrics = Disciple_Tools_Metrics::instance();
        require_once( 'dt-metrics/metrics-endpoints.php' );
        $this->endpoints['metrics'] = new Disciple_Tools_Metrics_Endpoints();

        /**
         * dt-users
         */
        require_once( 'dt-users/users.php' );
        $this->core['users'] = new Disciple_Tools_Users();
        require_once( 'dt-users/users-template.php' );
        require_once( 'dt-users/users-endpoints.php' );
        $this->endpoints['users'] = new Disciple_Tools_Users_Endpoints();

        /**
         * dt-notifications
         */
        require_once( 'dt-notifications/notifications-hooks.php' );
        $this->hooks['notifications'] = Disciple_Tools_Notification_Hooks::instance();
        require_once( 'dt-notifications/notifications-template.php' );
        require_once( 'dt-notifications/notifications.php' );
        $this->core['notifications'] = Disciple_Tools_Notifications::instance();
        require_once( 'dt-notifications/notifications-endpoints.php' );
        $this->endpoints['notifications'] = Disciple_Tools_Notifications_Endpoints::instance();
        require_once( 'dt-notifications/notifications-email.php' ); // sends notification emails through the async task process

        /**
         * Post-to-Post configuration
         */
        require_once( 'dt-core/config-p2p.php' ); // Creates the post to post relationship between the post type tables.

        // Custom Metaboxes
        require_once( 'dt-core/admin/metaboxes/box-address.php' ); // todo remove theme dependency on this box. used by both theme and wp-admin

        /**
         * Logging
         */
        require_once( 'dt-core/logging/class-activity-api.php' );
        $this->logging_activity_api = new Disciple_Tools_Activity_Log_API();
        require_once( 'dt-core/logging/class-activity-hooks.php' ); // contacts and groups report building
        $this->logging_activity_hooks = Disciple_Tools_Activity_Hooks::instance();
        require_once( 'dt-core/logging/class-reports-api.php' );
        $this->logging_reports_api = new Disciple_Tools_Reports_API();
        require_once( 'dt-core/logging/class-reports-cron.php' ); // Cron scheduling for nightly builds of reports
        $this->logging_reports_cron = Disciple_Tools_Reports_Cron::instance();
        require_once( 'dt-core/logging/class-reports-dt.php' ); // contacts and groups report building


        /**
         * Workflows
         */
        require_once( 'dt-workflows/index.php' );
        $this->workflows = Disciple_Tools_Workflows::instance();

        /**
         * Integrations
         */
        require_once( 'dt-core/integrations/class-integrations.php' ); // data integration for cron scheduling
        if ( !class_exists( 'Ga_Autoloader' ) ) {
            require_once( 'dt-core/libraries/google-analytics/disciple-tools-analytics.php' );
            require_once( 'dt-core/integrations/class-google-analytics-integration.php' );
            $this->analytics_integration = DT_Ga_Admin::instance();
        }
        require_once( 'dt-core/integrations/class-facebook-integration.php' ); // integrations to facebook
        $this->facebook_integration = Disciple_Tools_Facebook_Integration::instance();

        /**
         * Language
         */
        add_action( 'init', [ $this, 'load_plugin_textdomain' ] );

        if ( ! class_exists( 'Puc_v4_Factory' ) ) {
            require 'dt-core/libraries/plugin-update-checker/plugin-update-checker.php';
        }
        $my_update_checker = Puc_v4_Factory::buildUpdateChecker(
            'https://raw.githubusercontent.com/DiscipleTools/disciple-tools-version-control/master/disciple-tools-plugin-version-control.json',
            __FILE__,
            'disciple-tools'
        );

        /**
         * Admin panel
         * Contains all those features that only run if in the Admin panel
         * or those things directly supporting Admin panel features.
         */
        if ( is_admin() ) {

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
            require_once( 'dt-core/admin/metaboxes/box-activity.php' );
            require_once( 'dt-core/admin/metaboxes/box-share-contact.php' );

        }
        /* End Admin configuration section */
    } // End __construct()

    /**
     * Load the localisation file.
     *
     * @access public
     * @since  0.1.0
     */
    public function load_plugin_textdomain()
    {
        load_plugin_textdomain( 'disciple_tools', false, dirname( plugin_basename( __FILE__ ) ) . '/dt-core/languages/' );
    } // End load_plugin_textdomain()

    /**
     * Log the plugin version number.
     *
     * @access private
     * @since  0.1.0
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
     * @since  0.1.0
     */
    public function __clone()
    {
        wp_die( esc_html__( "Cheatin' huh?" ), __FUNCTION__ );
    } // End __clone()

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @access public
     * @since  0.1.0
     */
    public function __wakeup()
    {
        wp_die( esc_html__( "Cheatin' huh?" ), __FUNCTION__ );
    } // End __wakeup()

} // End Class
