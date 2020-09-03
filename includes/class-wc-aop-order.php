<?php

/**
 * Class WC_AOP_Order
 *
 * @package WC_AOP/Classes
 * @since 1.0.0
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WC_AOP_Order')) :

    /**
     * Creates the submenu item for the plugin.
     *
     * Registers a new menu item under 'Tools' and uses the dependency passed into
     * the constructor in order to display the page corresponding to this menu item.
     *
     * @package WC_AOP_Order
     */
    class WC_AOP_Order
    {
        /**
         * Init and hook in the integration.
         *
         * @since 1.0.0
         * @return void
         */
        public function __construct()
        {
            // Actions.
            add_action( 'woocommerce_order_status_completed', array($this, 'aop_woocommerce_order_status_completed'), 10, 1 );
            add_action( 'wc_aop_process_complete_order', array( $this, 'process_complete_order' ) );
        }

        /**
         * Create the function to handle woocommerce orders when status is completed
         *
         * @since 1.0.0
         * @param int $order_id
         * @return void
         */
        public function aop_woocommerce_order_status_completed( $order_id ) {
            // Add action to scheduler
            WC()->queue()->add( 'wc_aop_process_complete_order', array( $order_id ), 'wc-aop' );
        }

        /**
         * Process the complete order
         *
         * @since 1.0.0
         * @param int $order_id
         * @return void
         */
        public function process_complete_order($order_id)
        {
            // Get the order based on order_id
            $order = new WC_Order( $order_id );
            
            // Make the data
            $data = [
                'customer' => [
                    'id' => $order->get_customer_id(),
                    'first_name' => $order->get_billing_first_name(),
                    'last_name' => $order->get_billing_last_name(),
                    'email' => $order->get_billing_email(),
                    'phone' => $order->get_billing_phone(),
                    'order_date' => [
                        'date_created' => $order->get_date_created(),
                        'date_modified' => $order->get_date_modified(),
                        'date_completed' => $order->get_date_completed(),
                        'date_paid' => $order->get_date_paid(),
                    ],
                    'billing_address' => [
                        'address_line_1' => $order->get_billing_address_1(),
                        'address_line_2' => $order->get_billing_address_2(),
                        'city' => $order->get_billing_city(),
                        'state' => $order->get_billing_state(),
                        'postcode' => $order->get_billing_postcode(),
                        'country' => $order->get_billing_country(),
                    ],
                ],
                'items' => array_map(function($item) {
                    return $item->get_data();
                }, $order->get_items())
            ];

            // Log the data
            $logger = wc_get_logger();
            $logger->add('woocommerce_wc-aop_scheduler', 'Data to send : ' . json_encode($data, true));

            // Perform the POST request to external api based on user preference in settings
            $url = get_option('woocommerce_wc-aop_settings')['url'];            
            $response = wp_remote_post( $url, array(
                    'method'      => 'POST',
                    'timeout'     => 45,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking'    => true,
                    'headers'     => array(),
                    'body'        => array( 'data'  => $data ),
                    'cookies'     => array()
                )
            );
            
            // Check the response, is it error?
            if ( is_wp_error( $response ) ) {
                $error_message = $response->get_error_message();
                $logger->add('woocommerce_wc-aop_scheduler', "Something went wrong : $error_message");
            } else {
                $logger->add('woocommerce_wc-aop_scheduler', "Response : $response");
            }
        }
    }

endif;
