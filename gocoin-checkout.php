<?php
/*
 *    Plugin Name: Official GoCoin WooCommerce Plugin
 *    Plugin URI: http://www.gocoin.com
 *    Description: This plugin adds the GoCoin Payment Gateway to your WooCommerce Shopping Cart.  WooCommerce is required.
 *    Version: 0.3.0
 *    Author: GoCoin
 */

require_once('gocoin-php/src/GoCoin.php');
require_once('gocoin-util.php');
require_once(ABSPATH . 'wp-admin/includes/plugin.php');

session_start();

/**
 * Check if WooCommerce is active
 * */
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    function createWoocommerceGocoinGateway() {
        if (!class_exists('WC_Payment_Gateways'))
            return;

        class WC_Gocoin extends WC_Payment_Gateway {


            /**
             * construnct function 
             * 
             */
            public function __construct() {

                $this->id = 'gocoin';
                $this->method_title = 'GoCoin';
                $this->method_description = 'Accept Bitcoin transactions using the GoCoin Payment Gateway';
                $this->icon = plugin_dir_url(__FILE__) . 'gocoin-icon.png';
                $this->has_fields = true;

                // Load the form fields.
                $this->init_form_fields();

                // Load the settings.
                $this->init_settings();


                // Define user set variables
                $this->title = $this->settings['title'];
                $this->description = $this->settings['description'];
                
                if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
                    $this->php_version_allowed = true ;
                 }
                else{
                      $this->php_version_allowed = false ;
                      $this->enabled = false;
                }
                
                // Actions
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
                
            }

            /**
             * Initialize woocommerce settings fields for gocoin payment gateway.
             */
            function init_form_fields() {
                  if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
                    $this->php_version_allowed = true ;

                    $this->form_fields = array(
                        'enabled' => array(
                            'title' => __('Enable/Disable', 'woothemes'),
                            'type' => 'checkbox',
                            'label' => __('Enable GoCoin', 'woothemes'),
                            'default' => 'yes'
                        ),
                        'title' => array(
                            'title' => __('Title', 'woothemes'),
                            'type' => 'text',
                            'description' => __('Payment Gateway title in checkout page.', 'woothemes'),
                            'default' => __('GoCoin', 'woothemes')
                        ),
                        'description' => array(
                            'title' => __('Customer Message', 'woothemes'),
                            'type' => 'textarea',
                            'description' => __('Message which will show in checkout page.', 'woothemes'),
                            'default' => 'You will be redirected to GoCoin.com to complete your purchase.'
                        ),
                        'merchantId' => array(
                            'title' => __('Merchant ID', 'woothemes'),
                            'type' => 'text',
                            'description' => __('Enter your GoCoin Merchant ID'),
                        ),
                        'accessToken' => array(
                            'title' => __('API Key', 'woothemes'),
                            'type' => 'password',
                            'description' => __('Enter your GoCoin API Key'),
                        ),
                    );
                 }
                else{
                      $this->php_version_allowed = false ;
                      $this->form_fields = array(
                            '' => array(
                            'title'			=> __( 'PHP Version Error:', 'woocommerce' ),
                            'type'			=> 'title',
                            'description'   => __( '<span style="color:#ff0000;">The minimum PHP version required for GoCoin plugin is 5.3.0</span>', 'woocommerce' )
                           ),
                     );
                }
            }

            /**
             * create settings page for gocoin gateway in woocommerce admin
             */
            public function admin_options() {
                    if($this->php_version_allowed==false){
                      echo '<h3 style="color:#ff0000;font-weight:bold">The minimum PHP version required for GoCoin plugin is 5.3.0</h3>  ';
                      return false;
                    }
                ?>
                <h3><?php _e('GoCoin Payment Gateway', 'woothemes'); ?></h3>
                <p><?php _e('Allows Bitcoin payments via GoCoin.com.', 'woothemes'); ?></p>
                <table class="form-table">
                <?php
                // Generate the HTML For the settings form.
                $this->generate_settings_html();
                ?>
                </table>
                
                <?php
            }

            /**
             * create custom settings form fields for accesstoken
             *  
             * @param mixed $key
             * @param mixed $data
             * 
             * @return String $html
             */
            public function generate_password_html($key, $data) {
                
                global $woocommerce;
                $html = '';

                $data['title']      = isset($data['title']) ? $data['title'] : '';
                $data['disabled']   = empty($data['disabled']) ? false : true;
                $data['class']      = isset($data['class']) ? $data['class'] : '';
                $data['css']        = isset($data['css']) ? $data['css'] : '';
                $data['placeholder']= isset($data['placeholder']) ? $data['placeholder'] : '';
                $data['type']       = isset($data['type']) ? $data['type'] : 'text';
                $data['desc_tip']   = isset($data['desc_tip']) ? $data['desc_tip'] : false;
                $data['description']= isset($data['description']) ? $data['description'] : '';

                // Description handling
                if ($data['desc_tip'] === true) {
                    $description = '';
                    $tip = $data['description'];
                } elseif (!empty($data['desc_tip'])) {
                    $description = $data['description'];
                    $tip = $data['desc_tip'];
                } elseif (!empty($data['description'])) {
                    $description = $data['description'];
                    $tip = '';
                } else {
                    $description = $tip = '';
                }

                // Custom attribute handling
                $custom_attributes = array();
                $token = $this->settings['accessToken'];
                if (!empty($data['custom_attributes']) && is_array($data['custom_attributes']))
                    foreach ($data['custom_attributes'] as $attribute => $attribute_value)
                        $custom_attributes[] = esc_attr($attribute) . '="' . esc_attr($attribute_value) . '"';

                $html .= '<tr valign="top">' . "\n";
                $html .= '<th scope="row" class="titledesc">';
                $html .= '<label for="' . esc_attr($this->plugin_id . $this->id . '_' . $key) . '">' . wp_kses_post($data['title']) . '</label>';

                if ($tip)
                    $html .= '<img class="help_tip" data-tip="' . esc_attr($tip) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" height="16" width="16" />';

              
 
                $html .= '</th>' . "\n";
                $html .= '<td class="forminp">' . "\n";
                $html .= '<fieldset><legend class="screen-reader-text"><span>' . wp_kses_post($data['title']) . '</span></legend>' . "\n";
                $html .= '<input class="input-text regular-input ' . esc_attr($data['class']) . '" type="' . esc_attr($data['type']) . '" name="' . esc_attr($this->plugin_id . $this->id . '_' . $key) . '" id="' . esc_attr($this->plugin_id . $this->id . '_' . $key) . '" style="' . esc_attr($data['css'])
                        . '" value="' . esc_attr($token)
                        . '" placeholder="' . esc_attr($data['placeholder']) . '" ' . disabled($data['disabled'], true, false) . ' ' . implode(' ', $custom_attributes) . ' />';
                if ($description)
                    $html .= ' <p class="description">' . wp_kses_post($description) . '</p>' . "\n";

                $html .= '</fieldset>';
                $html .= '</td>' . "\n";
                $html .= '</tr>' . "\n";

                return $html;
            }

            /**
             * Process payment for woocommerce checkout
             * 
             * @param mixed $order_id
             */
            function process_payment($order_id) {
               
              global $woocommerce, $wpdb;

              $access_token = $this->settings['accessToken'];
              $merchant_id = $this->settings['merchantId'];
              $logger = new WC_Logger();

              // Check to make sure we have an access token (API Key)
              if (empty($access_token)) {
                  $msg = 'Improper Gateway set up. Access token not found.';
                  $logger -> add('gocoin', $msg);
                  $woocommerce->add_error(__($msg));
              }
              //Check to make sure we have a merchant ID
              elseif (empty($merchant_id)) {
                  $msg = 'Improper Gateway set up. Merchant ID not found.';
                  $logger -> add('gocoin', $msg);
                  $woocommerce->add_error(__($msg));
              }
              // Proceed
              else {   
                // Build the WooCommerce order, is has status "Pending"
                $order = new WC_Order($order_id);

                // Handle breaking route changes for after-purchase pages
                if (version_compare(WOOCOMMERCE_VERSION, '2.1.0', '>=')) {
                    $redirect_url = $this->get_return_url($this->order);
                } else {
                    $redirect_url = add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id'))));
                }

                $callback_url =  plugin_dir_url(__FILE__) .'gocoin-callback.php';
                $currency = get_woocommerce_currency();

                $options = array(
                  "type"                     => 'bill',
                  "base_price"               => $order->order_total,
                  "base_price_currency"      => $currency,
                  "callback_url"             => $callback_url,
                  "redirect_url"             => $redirect_url,
                  "order_id"                 => $order_id,
                  "customer_name"            => $order->shipping_first_name . ' ' . $order->shipping_last_name,
                  "customer_address_1"       => $order->shipping_address_1,
                  "customer_address_2"       => $order->shipping_address_2,
                  "customer_city"            => $order->shipping_city,
                  "customer_region"          => $order->shipping_state,
                  "customer_postal_code"     => $order->shipping_postcode,
                  "customer_country"         => $order->shipping_country,
                  "customer_phone"           => $order->shipping_phone,
                  "customer_email"           => $order->shipping_email,
                );
                
                // Sign invoice with access token, if this fails we should still allow user to check out. 
                if ($signature = Util::sign($options, $access_token)) {
                  $options['user_defined_8'] = $signature;
                }

                try {
                  $invoice = GoCoin::createInvoice($access_token, $merchant_id, $options);
                  $url = $invoice->gateway_url;
                  $woocommerce->cart->empty_cart();
                  
                  return array(
                      'result' => 'success',
                      'redirect' => $url,
                  );
                } catch (Exception $e) {
                  $msg = $e->getMessage();
                  $order->add_order_note(var_export($msg));
                  $logger->add('gocoin', $msg);
                  $woocommerce->add_error(__($msg));
                }
              }
            }

        }

    }

    function add_Gocoin_gateway($methods) {
        $methods[] = 'WC_Gocoin';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_Gocoin_gateway');

    add_action('plugins_loaded', 'createWoocommerceGocoinGateway', 0);

    $pluginroot = WP_PLUGIN_DIR;
    $woo = 'woocommerce/woocommerce.php';
    $woodata = get_plugin_data("$pluginroot/$woo");
    
}