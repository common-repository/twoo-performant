<?php

/*
Plugin Name: TWoo Performant
Plugin URI:  https://wordpress.org/plugins/twoo-performant
Description: An integration plugin for 2Performant's tracking code in WooCommerce based sites
Version:     1.0
Author:      Tudor Sandu (tetele)
Author URI:  http://tudorsandu.ro
License:     MIT
*/

defined( 'ABSPATH' ) or die( 'Go away!' );

class TWooPerformant {
    public static function init() {
        static $instance;

        if(!$instance) {
            $instance = new self;
        }

        add_filter( 'woocommerce_integrations', array($instance, 'register_integration') );

        return $instance;
    }

    public function register_integration($integrations) {
        include dirname( __FILE__ ) . '/integration.php';

        $integrations[] = 'TP_WC_2Performant_Tracking';

        return $integrations;
    }
}

$tp_wc_tracking = TWooPerformant::init();
