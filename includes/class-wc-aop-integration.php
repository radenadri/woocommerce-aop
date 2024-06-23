<?php

/**
 * Class WC_AOP_Integration
 *
 * @package WC_AOP/Classes
 * @since 1.0.0
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WC_AOP_Integration')) :

    /**
     * Creates the submenu item for the plugin.
     *
     * Registers a new menu item under 'Tools' and uses the dependency passed into
     * the constructor in order to display the page corresponding to this menu item.
     *
     * @package WC_AOP_Integration
     */
    class WC_AOP_Integration extends WC_Integration
    {

        /**
         * Init and hook in the integration.
         *
         * @since 1.0.0
         * @return void
         */
        public function __construct()
        {
            $this->id = 'wc-aop';
            $this->method_title = __('Automate Order Process', 'wc-aop');
            $this->method_description = __('Settings for Automate Order Process integration.', 'wc-aop');

            // Load the settings.
            $this->init_settings();
            $this->init_form_fields();

            // Actions.
            add_action('woocommerce_update_options_integration_' .  $this->id, array($this, 'process_admin_options'));
        }

        /**
         * Initialize integration settings form fields.
         *
         * @since 1.0.0
         * @return void
         */
        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'wc-aop'),
                    'type' => 'checkbox',
                    'label' => __('Enable Automate Order Process integration', 'wc-aop'),
                    'default' => 'no',
                ),
                'url' => array(
                    'title' => __('URL for submitting completed order', 'wc-aop'),
                    'type' => 'text',
                    'description' => __('Enter the URL for processing completed order (ex. https://demo.pentacode.dev/api/process_order)', 'wc-aop'),
                    'desc_tip' => true,
                    'default' => '',
                ),
            );
        }

        /**
         * Validate URL Field.
         *
         * @since 1.0.0
         * @return string $value
         */
        public function validate_url_field($key, $value)
        {
            if (isset($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                WC_Admin_Settings::add_error(esc_html__('Looks like you made a mistake with the URL field. Make sure it\'s an url with the correct format!', 'woocommerce-integration-demo'));
            }

            return $value;
        }
    }

endif;
