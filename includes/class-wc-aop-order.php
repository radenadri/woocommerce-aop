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
            add_action('woocommerce_order_status_completed', array($this, 'aop_woocommerce_order_status_processing'), 10, 1);
            add_action('wc_aop_process_processing_order', array($this, 'process_processing_order'));

            // Instance the logger
            $this->logger = wc_get_logger();
        }

        /**
         * Create the function to handle woocommerce orders when status is processing
         *
         * @since 1.0.0
         * @param int $order_id
         * @return void
         */
        public function aop_woocommerce_order_status_processing($order_id)
        {

            // Add action to scheduler
            WC()->queue()->add('wc_aop_process_processing_order', array($order_id), 'wc-aop');
        }

        /**
         * Process the processing order
         *
         * @since 1.0.0
         * @param int $order_id
         * @return void
         */
        public function process_processing_order($order_id)
        {
            // Get the order based on order_id
            $order = wc_get_order($order_id);

            // Get all the order list and make the data
            $itemsList = array_map(function ($item) {
                return $item->get_data();
            }, $order->get_items());

            foreach ($itemsList as $singleItem) {
                $this->logger->add('woocommerce_wc-aop_scheduler', 'Item : ' . json_encode($singleItem, JSON_UNESCAPED_SLASHES));

                if ($singleItem['variation_id']) {
                    $product = wc_get_product($singleItem['variation_id']);
                } else {
                    $product = wc_get_product($singleItem['product_id']);
                }

                $orderList[] = array(
                    'SOURCE' => $this->get_domain(get_site_url(null, '', 'https')),
                    'ORDER_NUMBER' => $singleItem['order_id'],
                    'FULL_NAME' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                    'EMAIL' => $order->get_billing_email(),
                    'PHONE' => $order->get_billing_phone(),
                    'ADDRESS' => $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),
                    'ORDER_STATUS' => 'Completed',
                    'PAYMENT_METHOD' => $order->get_payment_method(),
                    'ITEM_SKU' => $product->get_sku() ? $product->get_sku() : '-',
                    'ITEM_ID' => $singleItem['product_id'],
                    'ITEM_NAME' => $singleItem['name'],
                    'ITEM_QTY' => $singleItem['quantity'],
                    'ITEM_COST' => $singleItem['subtotal'],
                );
            }

            // Log the data
            $this->logger->add('woocommerce_wc-aop_scheduler', 'Data to send : ' . json_encode($orderList, JSON_UNESCAPED_SLASHES));

            $this->send_data_to_server(json_encode($orderList, JSON_UNESCAPED_SLASHES));
        }

        /**
         * Send the complete order to server
         *
         * @since 1.0.0
         * @param string $data
         * @return void
         */
        public function send_data_to_server($data)
        {

            // Perform the POST request to external api based on user preference in settings
            $url = get_option('woocommerce_wc-aop_settings')['url'];
            $response = wp_remote_post(
                $url,
                array(
                    'method'      => 'POST',
                    'timeout'     => 45,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking'    => true,
                    'headers'     => array(),
                    'body'        => array('origin' => get_site_url(null, '', 'https'), 'data'  => $data),
                    'cookies'     => array(),
                    'sslverify' => false,
                )
            );

            // Check the response, is it error?
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                $this->logger->add('woocommerce_wc-aop_scheduler', "Something went wrong : $error_message");
            } else {
                $this->logger->add('woocommerce_wc-aop_scheduler', "Response : " . json_encode($response, true));
            }
        }

        /**
         * Parse the complete url, only return the domain name
         *
         * @since 1.0.0
         * @param string $url
         * @return string
         */
        private function get_domain($url)
        {
            $pieces = parse_url($url);
            $domain = isset($pieces['host']) ? $pieces['host'] : $pieces['path'];

            if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
                return strstr($regs['domain'], '.', true);
            }

            return false;
        }
    }


endif;
