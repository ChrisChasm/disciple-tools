<?php

/**
 * Disciple_Tools_Project_Reports
 *
 * @class Disciple_Tools_Project_Reports
 * @version	0.1
 * @since 0.1
 * @package	Disciple_Tools
 * @author Chasm.Solutions & Kingdom.Training
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Disciple_Tools_Project_Reports {

    /**
     * Disciple_Tools_Project_Reports The single instance of Disciple_Tools_Project_Reports.
     * @var 	object
     * @access  private
     * @since 	0.1
     */
    private static $_instance = null;

    /**
     * Main Disciple_Tools_Project_Reports Instance
     *
     * Ensures only one instance of Disciple_Tools_Project_Reports is loaded or can be loaded.
     *
     * @since 0.1
     * @static
     * @return Disciple_Tools_Project_Reports instance
     */
    public static function instance () {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self();
        return self::$_instance;
    } // End instance()

    /**
     * Constructor function.
     * @access  public
     * @since   0.1
     */
    public function __construct () {

    } // End __construct()

    public function run_reports () {
        $html = 'Project Reports';
        return $html;
    }

}