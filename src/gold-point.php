<?php
/**
 * @version 0.1.0
 * Plugin Name: Gold Point 
 * Plugin URI: #
 * Description: Gold Point is an open source, free rewards points system for WooCommerce designed for Customer Loyalty Program. Gold Point provides individaul setting for multi-purpose points design to reward customers on purchases, register or custom marketing events. Reward your loyal Customers using points which can be redeemed for promotion provide incentive to shop back.
 * Version: 0.1.0
 * Author: Daniel Lee
 * Author URI: #
 * Requires at least: 3.3
 * Tested up to: 5.2.2
 * WC requires at least: 3.0.0
 * WC tested up to: 3.7.0
 * Copyright: © 2018-2019 Daniel Lee.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once( dirname( __FILE__ ) . '/helpers/class-wc-dependencies.php' );
// Check if WooCommerce is active
if ( ! WC_Dependencies::woocommerce_active_check() ) {
	return;
}

if ( ! class_exists( 'Gold_Point' ) ){
    class Gold_Point {
       
        /** @var string the plugin path */
        private $plugin_path;
    
        private static $instance;
     
        //plugin tablename
        public $tablename_point; 
        public $tablename_user_point;
        public $tablename_point_log; 

        /**
        * Returns the *Singleton* instance of this class.
        * @return Singleton The *Singleton* instance.
        */
        public static function get_instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        protected function __construct() {
            global $wpdb;
            $this->tablename_point = $wpdb->prefix . "wc_gold_points"; 
            $this->tablename_user_point = $this->tablename_point . "_user_points"; 
            $this->tablename_point_log = $this->tablename_user_point . "_log"; 

            add_action( 'plugins_loaded', array( $this, 'init' ) );
            add_shortcode( 'gold_point_short_code_user_balance', array( $this, 'gold_point_user_balance') );
            
        }

        function gold_point_user_balance() {
            global $wpdb;
            $user_id  = get_current_user_id();
            $data = Gold_Point_Repository::getUserGold($user_id);
            $output = sprintf("<a>購物金(%d)</a>",$data['total']);
            return $output;
        }
        public function init() {
            $this->schema_check();

            $this->includes();

            if ( is_admin() ) {
                $this->admin_includes();
            }
        }
        public function includes() {
            // repository class
		    require_once( dirname( __FILE__ ) . '/repositories/point-repository.php' );

        }
        private function admin_includes() {
            // load admin class
            require_once( dirname( __FILE__ ) . '/includes/class-gold-point-admin.php' );
            $this->admin = new Gold_Point_Admin();

        }

        function schema_check () {
            global $wpdb;
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $this->tablename_point (
                        id bigint(20) NOT NULL AUTO_INCREMENT,
                        name varchar (100) NOT NULL,
                        status int(11) NOT NULL COMMENT '1:啟用,0:停用',
                        create_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                        update_time  datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        start_time datetime  NOT NULL,
                        end_time datetime  NOT NULL,
                        valid_period int(11) NOT NULL DEFAULT 0,
                        valid_time_window varchar (10),
                        PRIMARY KEY  (id)
                    ) $charset_collate;
                    
                    CREATE TABLE $this->tablename_user_point (
                        user_id bigint(20) NOT NULL,
                        point_id bigint(20) NOT NULL,
                        total bigint(20) NOT NULL,
                        create_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                        update_time  datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY  (user_id, point_id)
                    ) $charset_collate;
                    
                    CREATE TABLE $this->tablename_point_log (
                        id bigint(20) NOT NULL AUTO_INCREMENT,
                        user_id bigint(20) NOT NULL,
                        total bigint(20) NOT NULL,
                        point_id bigint(20) NOT NULL,
                        type varchar(255),
                        order_id bigint(20),
                        create_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                        PRIMARY KEY  (id)
                    ) $charset_collate;
                    
                    ";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }
        
        public function get_plugin_path() {

            if ( $this->plugin_path ) {
                return $this->plugin_path;
            } 
            $this->plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );    
            return $this->plugin_path;
        }

    }
    $GLOBALS['gold_point'] = Gold_Point::get_instance();
}

