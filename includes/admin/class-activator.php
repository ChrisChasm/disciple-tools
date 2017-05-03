<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      0.1
 * @package    Disciple_Tools
 * @subpackage Disciple_Tools/includes/admin
 * @author     
 */
class Disciple_Tools_Activator {


	/**
	 * Activities to run during installation.
	 *
	 * Long Description.
	 *
	 * @since    0.1
	 */
	public static function activate() {

	    // Log version number of Disciple_Tools
	    Disciple_Tools::instance()->_log_version_number();

        // Create roles and capabilities
        require_once('class-roles.php');
        $roles = Disciple_Tools_Roles::instance();
        $roles->set_roles();



        /**
         * Activate database creation for Disciple Tools Activity logs
         * @since 0.1
         */
        require_once ( Disciple_Tools()->includes_path . 'models/class-activity-log-db.php');
        Disciple_Tools_Activity_Log_DB::activate();
        /* End Disciple Tools Activity Log */



        /**
         * Set defaults for options page
         */
        $settings_sections = Disciple_Tools()->settings->get_settings_sections ( );

        foreach ($settings_sections as $key => $value) {
            $section = Disciple_Tools()->settings->get_settings_fields ( $key );

            $preset = array();

            foreach ($section as $field => $item) {
                if(!empty($item['default'])) {
                    $preset[$field] = $item['default'];
                }
            }
            add_option( Disciple_Tools()->token . '-' . $key, $preset, '', 'yes'  );
        }

        /* End set defaults for options page */




	}

}
