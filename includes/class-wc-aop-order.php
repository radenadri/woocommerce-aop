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
        private $logger;

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

            // Instance the logger
            $this->logger = wc_get_logger();
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
            
            // Get all the order list and make the data
            $itemsList = array_map(function($item) {
                return $item->get_data();
            }, $order->get_items());

            foreach ($itemsList as $singleItem) {
                $orderList[] = array(
                    $singleItem['order_id'],   
                    date('j', strtotime($order->get_date_completed())),
                    date('F', strtotime($order->get_date_completed())),
                    date('Y', strtotime($order->get_date_completed())),
                    'Completed',
                    $singleItem['name'],
                    $singleItem['quantity'],
                    $singleItem['subtotal'],
                    $singleItem['subtotal'] - $singleItem['total'],
                    $order->get_shipping_total(),
                    $singleItem['total'] + $order->get_shipping_total(),
                    $order->get_shipping_city(),
                    $order->get_shipping_postcode(),
                );
            }

            // Log the data
            $this->logger->add('woocommerce_wc-aop_scheduler', 'Data to send : ' . json_encode($orderList, true));

            $this->send_data_to_server(json_encode($orderList, true));
        }
        
        /**
         * Send the complete order to server
         *
         * @since 1.0.0
         * @param string $data
         * @return void
         */
        public function send_data_to_server($data) {

            // Perform the POST request to external api based on user preference in settings
            $url = get_option('woocommerce_wc-aop_settings')['url'];            
            $response = wp_remote_post( $url, array(
                    'method'      => 'POST',
                    'timeout'     => 45,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking'    => true,
                    'headers'     => array(),
                    'body'        => array('data'  => $data),
                    'cookies'     => array()
                )
            );
            
            // Check the response, is it error?
            if ( is_wp_error( $response ) ) {
                $error_message = $response->get_error_message();
                $this->logger->add('woocommerce_wc-aop_scheduler', "Something went wrong : $error_message");
                
                send_data_to_server($data);
            } else {
                $this->logger->add('woocommerce_wc-aop_scheduler', "Response : " . json_encode($response, true));
            }
        }
    }


endif;
