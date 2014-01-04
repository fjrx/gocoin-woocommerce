<?php
/*
*    Plugin Name: Gocoin Woocommerce Payment gateway
*    Plugin URI: http://www.gocoin.com
*    Description: This plugin adds the Gocion payment gateway to your Woocommerce plugin.  Woocommerce is required.
*    Version: 1.0
*    Author: Roman Antonich 
*/

require_once('gocoin.PHP/src/api.php');
require_once('gocoin.PHP/src/auth.php');
require_once('gocoin.PHP/src/client.php');

session_start();

/**
 * Check if WooCommerce is active
 **/
 
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) 
{

    function createWoocommerceGocoinGateway() {
        if ( ! class_exists( 'WC_Payment_Gateways' ) ) 
            return;

        class WC_Gocoin extends WC_Payment_Gateway {

            /**
            * gocoin client class to get gocoin access token
            * 
            * @var Client
            */
            
            private $client;

            /**
            * construnct function 
            * 
            */

            public function __construct() { 
                $this->id = 'gocoin';
                $this->icon = plugin_dir_url(__FILE__).'gocoin-icon.png';
                $this->has_fields = false;

                // Load the form fields.
                $this->init_form_fields();

                // Load the settings.
                $this->init_settings();

                // Create client for gocoin
                $this->client = $this->create_client(); 

                // Define user set variables
                $this->title = $this->settings['title'];
                $this->description = $this->settings['description'];

                // Actions
                add_action('woocommerce_update_options_payment_gateways_'.$this->id, array(&$this, 'process_admin_options'));
            }

            /**
            * create client object using clientId and clientSecret in settings
            * 
            * @return Client client
            */
            function create_client() {
                $client = new Client( array(
                    'client_id' => $this->settings['clientId'],
                    'client_secret' => $this->settings['clientSecret'],
                    'scope' => "user_read_write+merchant_read_write+invoice_read_write",
                ));
                if ($this->settings['accessToken'] != '') {
                    $client->setToken($this->settings['accessToken']);
                }
                return $client;
            }

            /**
            * initialize woocommerce settings fiels for gocoin payment gateway
            * 
            */

            function init_form_fields() {
                $this->form_fields = array(
                    'enabled' => array(
                        'title' => __( 'Enable/Disable', 'woothemes' ),
                        'type' => 'checkbox',
                        'label' => __( 'Enable Gocoin Payment', 'woothemes' ),
                        'default' => 'yes'
                    ),
                    'title' => array(
                        'title' => __( 'Title', 'woothemes' ),
                        'type' => 'text',
                        'description' => __( 'Payment Gateway title in checkout page.', 'woothemes' ),
                        'default' => __( 'Gocoin', 'woothemes' )
                    ),
                    'description' => array(
                        'title' => __( 'Customer Message', 'woothemes' ),
                        'type' => 'textarea',
                        'description' => __( 'Message which will show in checkout page.', 'woothemes' ),
                        'default' => 'You will be redirected to Gocoin.com to complete your purchase.'
                    ),
                    'clientId' => array(
                        'title' => __('Client ID', 'woothemes'),
                        'type' => 'text',
                        'description' => __('Enter the Client ID for the App you created at Gocoin.com'),
                    ),
                    'clientSecret' => array(
                        'title' => __('Client Secret', 'woothemes'),
                        'type' => 'text',
                        'description' => __('Enter the Client Secret for the App you created at Gocoin.com'),
                    ),
                    'accessToken' => array(
                        'title' => __('Access Token', 'woothemes'),
                        'type' => 'accesstoken',
                        'description' => __('Enter the Access Token you created at Gocoin.com'),
                    ),
                );
            }

            /**
            * create settings page for gocoin gateway in woocommerce admin
            *    
            */

            public function admin_options() {
            ?>
                <h3><?php _e('Gocoin Payment', 'woothemes'); ?></h3>
                <p><?php _e('Allows bitcoin payments via Gocoin.com.', 'woothemes'); ?></p>
                <table class="form-table">
                <?php
                // Generate the HTML For the settings form.
                    $this->generate_settings_html();
                ?>
                </table>
                <input type="hidden" id="cid" value="<?php echo $this->settings['clientId']?>"/>
                <input type="hidden" id="csec" value="<?php echo $this->settings['clientSecret']?>"/>
                <script type="text/javascript">
                    function getAuthUrl() {
                        var clientId = document.getElementById('woocommerce_gocoin_clientId').value;
                        var clientSecret = document.getElementById('woocommerce_gocoin_clientSecret').value;
                        if (!clientId) {
                            alert('Please input clientId!');
                            return;
                        }
                        if (!clientSecret) {
                            alert('Please input Client Secret!');
                            return;
                        }
                        
                        var cid = document.getElementById('cid').value;
                        var csec = document.getElementById('csec').value;
                        if (clientId != cid || clientSecret != csec) {
                            alert('Please save changed Client Id and Client Secret Key first!');
                            return;
                        }
                        
                        var currentUrl = document.URL;
                        currentUrl = currentUrl.substring(0,currentUrl.indexOf("?"));
                        var url = "https://dashboard.gocoin.com/auth?response_type=code"
                                    + "&client_id=" + clientId
                                    + "&redirect_uri=" + currentUrl
                                    + "&scope=user_read+merchant_read+invoice_read_write";
                        window.location.href = url;
                    }
                </script>
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

            public function generate_accesstoken_html( $key, $data ) {
                global $woocommerce;
                $html = '';

                $data['title'] = isset( $data['title'] ) ? $data['title'] : '';
                $data['disabled'] = empty( $data['disabled'] ) ? false : true;
                $data['class'] = isset( $data['class'] ) ? $data['class'] : '';
                $data['css'] = isset( $data['css'] ) ? $data['css'] : '';
                $data['placeholder'] = isset( $data['placeholder'] ) ? $data['placeholder'] : '';
                $data['type'] = isset( $data['type'] ) ? $data['type'] : 'text';
                $data['desc_tip'] = isset( $data['desc_tip'] ) ? $data['desc_tip'] : false;
                $data['description'] = isset( $data['description'] ) ? $data['description'] : '';

                // Description handling
                if ( $data['desc_tip'] === true ) {
                    $description = '';
                    $tip = $data['description'];
                } elseif ( ! empty( $data['desc_tip'] ) ) {
                    $description = $data['description'];
                    $tip = $data['desc_tip'];
                } elseif ( ! empty( $data['description'] ) ) {
                    $description = $data['description'];
                    $tip = '';
                } else {
                    $description = $tip = '';
                }

                // Custom attribute handling
                $custom_attributes = array();

                if ( ! empty( $data['custom_attributes'] ) && is_array( $data['custom_attributes'] ) )
                    foreach ( $data['custom_attributes'] as $attribute => $attribute_value )
                        $custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';

                $html .= '<tr valign="top">' . "\n";
                $html .= '<th scope="row" class="titledesc">';
                $html .= '<label for="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '">' . wp_kses_post( $data['title'] ) . '</label>';

                if ( $tip )
                    $html .= '<img class="help_tip" data-tip="' . esc_attr( $tip ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" height="16" width="16" />';

                $token = $this->client->getToken();
                if ( isset($_GET['code'])) {
                    $this->client->initToken();
                    $b_auth = $this->client->authorize_api();

                    if ($b_auth) {
                        $token = $this->client->getToken();
                    }  else {
                        var_dump($this->client->getError());
                    } 
                }
                $token = isset($token) ? $token:$this->get_option( $key );
                $html .= '</th>' . "\n";
                $html .= '<td class="forminp">' . "\n";
                $html .= '<fieldset><legend class="screen-reader-text"><span>' . wp_kses_post( $data['title'] ) . '</span></legend>' . "\n";
                $html .= '<input class="input-text regular-input ' . esc_attr( $data['class'] ) . '" type="' . esc_attr( $data['type'] ) . '" name="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '" id="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '" style="' . esc_attr( $data['css'] ) 
                . '" value="' . esc_attr( $token ) 
                . '" placeholder="' . esc_attr( $data['placeholder'] ) . '" ' . disabled( $data['disabled'], true, false ) . ' ' . implode( ' ', $custom_attributes ) . ' />';
                $html .= '<a href="#" class="button-primary" onclick="getAuthUrl();"> Get Access token from Gocoin</a>';
                if ( $description )
                $html .= ' <p class="description">' . wp_kses_post( $description ) . '</p>' . "\n";

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

            function process_payment( $order_id ) {
                require 'gocoin-lib.php';

                global $woocommerce, $wpdb;

                $order = &new WC_Order( $order_id );
                $order->update_status('on-hold', __('Awaiting payment notification from Gocoin.com', 'woothemes'));

                // invoice options
                $redirect_url = add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id'))));				
                $callback_url = get_option('siteurl')."/?gocoin_callback=1"; 				
                $currency = get_woocommerce_currency(); 				

                $options = array (
                    'currency' => $currency,
                    'callback_url' => $callback_url,
                    'redirect_url' => $redirect_url,
                    'customer_name' => $order->billing_first_name.' '.$order->billing_last_name,
                    'customer_address_1' => $order->billing_address_1,
                    'customer_address_2' => $order->billing_address_2,
                    'customer_city' => $order->billing_city,
                    'customer_region' => $order->billing_state,
                    'customer_postal_code' => $order->billing_postcode,
                    'customer_country' => $order->billing_country,
                    'customer_phone' => $order->billing_phone,
                    'customer_email' => $order->billing_email,
                );

                $invoice = createInvoice($order_id, $order->order_total, $options, $this->client );
                if (isset($invoice->error)) {
                    $order->add_order_note(var_export($invoice['error']));
                    $woocommerce->add_error(__('Error creating Gocoin invoice.  Please try again or try another payment method.'));
                } else {
                    $url = "https://gateway.gocoin.com/merchant/".$invoice->merchant_id."/invoices/".$invoice->id;
                    $woocommerce->cart->empty_cart();
                    return array(
                        'result' => 'success',
                        'redirect' => $url,
                    );
                }			 
            }
        }
    }

    include plugin_dir_path(__FILE__).'gocoin-callback.php';

    function add_Gocoin_gateway( $methods ) {
        $methods[] = 'WC_Gocoin'; 
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_Gocoin_gateway' );

    add_action('plugins_loaded', 'createWoocommerceGocoinGateway', 0);

    if (isset($_GET['code']) && !isset($_GET['section'])) {
        header("Location: /wp-admin/admin.php?page=woocommerce_settings&tab=payment_gateways&section=WC_Gocoin&code=".$_GET['code']);
        exit(1);
    }  
	
}