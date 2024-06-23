<?php

/**
 * Plugin Name: WooCommerce Automate Order Process
 * Plugin URI: https://github.com/radenadri
 * Description: Create scheduler for submit completed orders..
 * Version: 1.0.0
 * Author: Adriana Eka Prayudha
 * Author URI: https://radenadri.github.io
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wc-aop
 * Domain Path: /languages
 * WC tested up to: 4.3.0
 * WC requires at least: 3.2.0
 *
 * @package WC_AOP
 */

if (!class_exists('WC_AOP')) :

    class WC_AOP
    {
        /**
         * Construct the plugin.
         */
        public function __construct()
        {
            add_action('plugins_loaded', array($this, 'init'));
        }

        /**
         * Initialize the plugin.
         */
        public function init()
        {
            $aop_enabled = get_option('woocommerce_wc-aop_settings')['enabled'];

            // Checks if WooCommerce is installed.
            if (class_exists('WC_Integration')) {

                // Include our integration class.
                include_once 'includes/class-wc-aop-integration.php';

                // Register the integration.
                add_filter('woocommerce_integrations', array($this, 'add_integration'));

                if ($aop_enabled == 'yes') {
                    include_once 'includes/class-wc-aop-order.php';
                    new WC_AOP_Order;
                }
            } else {
                WC_Admin_Settings::add_error(esc_html__('Please enable woocommerce to activate this plugin!', 'woocommerce-integration-demo'));
            }
        }

        /**
         * Add a new integration to WooCommerce.
         */
        public function add_integration($integrations)
        {
            $integrations[] = 'WC_AOP_Integration';

            return $integrations;
        }
    }

    $WC_AOP = new WC_AOP(__FILE__);
endif;
