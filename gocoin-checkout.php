<?php
/*
 *    Plugin Name: Official GoCoin WooCommerce Plugin
 *    Plugin URI: http://www.gocoin.com
 *    Description: This plugin adds the GoCoin Payment Gateway to your WooCommerce Shopping Cart.  WooCommerce is required.
 *    Version: 0.1.4
 *    Author: GoCoin
 */
require_once('gocoin-php/src/GoCoin.php');
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
                $this->method_title = 'GoCoin';
                $this->method_description = 'Accept Bitcoin transactions using the GoCoin Payment Gateway';
                $this->icon = plugin_dir_url(__FILE__) . 'gocoin-icon.png';
                $this->has_fields = true;

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
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
            }

            /**
             * create client object using clientId and clientSecret in settings
             * 
             * @return Client client
             */
            function create_client() {
                $client = new Client(array(
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
             * Initialize woocommerce settings fields for gocoin payment gateway.
             */
            function init_form_fields() {
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
                    'clientId' => array(
                        'title' => __('Client ID', 'woothemes'),
                        'type' => 'text',
                        'description' => __('Enter the Client ID for the App you created at GoCoin.com'),
                    ),
                    'clientSecret' => array(
                        'title' => __('Client Secret', 'woothemes'),
                        'type' => 'text',
                        'description' => __('Enter the Client Secret for the App you created at GoCoin.com'),
                    ),
                    'accessToken' => array(
                        'title' => __('Access Token', 'woothemes'),
                        'type' => 'password',
                        'description' => __('Enter the Access Token you created at GoCoin.com'),
                    ),
                );
            }

            /**
             * create settings page for gocoin gateway in woocommerce admin
             */
            public function admin_options() {
                ?>
                <h3><?php _e('GoCoin Payment Gateway', 'woothemes'); ?></h3>
                <p><?php _e('Allows Bitcoin payments via GoCoin.com.', 'woothemes'); ?></p>
                <table class="form-table">
                <?php
                // Generate the HTML For the settings form.
                $this->generate_settings_html();
                ?>
                </table>
                <input type="hidden" id="cid" value="<?php echo $this->settings['clientId'] ?>"/>
                <input type="hidden" id="csec" value="<?php echo $this->settings['clientSecret'] ?>"/>
                <script type="text/javascript">
                    function getAuthUrl() {
                        var clientId = document.getElementById('woocommerce_gocoin_clientId').value;
                        var clientSecret = document.getElementById('woocommerce_gocoin_clientSecret').value;
                        
                        var cid = document.getElementById('cid').value;
                        var csec = document.getElementById('csec').value;
                        if (!clientId) {
                          alert('Please input Client Id!');
                          return;
                        }
                        if (!clientSecret) {
                          alert('Please input Client Secret!');
                          return;
                        }
                        if (document.getElementById('woocommerce_gocoin_accessToken').value != '') {
                            alert('Please clear your current access token and save before proceeding');
                            return;
                        }
                        if (clientId != cid || clientSecret != csec) {
                            alert('Please save changed Client Id and Client Secret Key first!');
                            return;
                        }

                        var currentUrl = document.URL;
                        currentUrl = currentUrl.substring(0, currentUrl.indexOf("?"));
                        
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
            public function generate_password_html($key, $data) {
                global $woocommerce;
                $html = '';

                $data['title'] = isset($data['title']) ? $data['title'] : '';
                $data['disabled'] = empty($data['disabled']) ? false : true;
                $data['class'] = isset($data['class']) ? $data['class'] : '';
                $data['css'] = isset($data['css']) ? $data['css'] : '';
                $data['placeholder'] = isset($data['placeholder']) ? $data['placeholder'] : '';
                $data['type'] = isset($data['type']) ? $data['type'] : 'text';
                $data['desc_tip'] = isset($data['desc_tip']) ? $data['desc_tip'] : false;
                $data['description'] = isset($data['description']) ? $data['description'] : '';

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

                if (!empty($data['custom_attributes']) && is_array($data['custom_attributes']))
                    foreach ($data['custom_attributes'] as $attribute => $attribute_value)
                        $custom_attributes[] = esc_attr($attribute) . '="' . esc_attr($attribute_value) . '"';

                $html .= '<tr valign="top">' . "\n";
                $html .= '<th scope="row" class="titledesc">';
                $html .= '<label for="' . esc_attr($this->plugin_id . $this->id . '_' . $key) . '">' . wp_kses_post($data['title']) . '</label>';

                if ($tip)
                    $html .= '<img class="help_tip" data-tip="' . esc_attr($tip) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" height="16" width="16" />';

                $token = $this->client->getToken();
                if (!isset($token)) {
                  if (isset($_GET['code'])) {
                    $code = $_GET['code'];
                    $client_id = $this->settings['clientId'];
                    $client_secret = $this->settings['clientSecret'];
                    $redirect_uri = get_current_url();
                    try {
                        $token = GoCoin::requestAccessToken($client_id, $client_secret, $code, $redirect_uri);
                    } catch (Exception $e) {
                        echo "Problem in getting Token: " . $e->getMessage();
                    }
                  }
                }
                $token = isset($token) ? $token : $this->get_option($key);
                $html .= '</th>' . "\n";
                $html .= '<td class="forminp">' . "\n";
                $html .= '<fieldset><legend class="screen-reader-text"><span>' . wp_kses_post($data['title']) . '</span></legend>' . "\n";
                $html .= '<input class="input-text regular-input ' . esc_attr($data['class']) . '" type="' . esc_attr($data['type']) . '" name="' . esc_attr($this->plugin_id . $this->id . '_' . $key) . '" id="' . esc_attr($this->plugin_id . $this->id . '_' . $key) . '" style="' . esc_attr($data['css'])
                        . '" value="' . esc_attr($token)
                        . '" placeholder="' . esc_attr($data['placeholder']) . '" ' . disabled($data['disabled'], true, false) . ' ' . implode(' ', $custom_attributes) . ' />';
                $html .= '<a style="margin-left: 10px" href="#" class="button-primary" onclick="getAuthUrl(); return false;"> Get Access Token from GoCoin</a>';
                if ($description)
                    $html .= ' <p class="description">' . wp_kses_post($description) . '</p>' . "\n";

                $html .= '</fieldset>';
                $html .= '</td>' . "\n";
                $html .= '</tr>' . "\n";

                return $html;
            }

            /**
             *  Add price currency fileds on payment gateway section.
             */
            function payment_fields() {
                if ($this->description)
                    echo wpautop(wptexturize($this->description));
                echo '<div class="form-row form-wide validate-required">';
                echo '<label for="gocoin_coin_type">Coin Currency <abbr class="required" title="required">*</abbr></label>';
                echo '<select id="gocoin_coin_type" name="coin_type" class="input-select">';
                echo '<option value="" selected="selected">--Please Select--</option>';
                echo '<option value="BTC">Bitcoin</option>';
                echo '<option value="XDG">Dogecoin</option>';
                echo '<option value="LTC">Litecoin</option>';
                echo '</select>';
                echo '</div>';
            }

            /**
             * Validate payment fields
             */
            function validate_fields() {
                global $woocommerce;
                $coin_type = $_POST['coin_type'];
                if ($coin_type == "") {
                    $woocommerce->add_error('Coin type is required');
                } else {
                    return true;
                }
            }

            /**
             * Process payment for woocommerce checkout
             * 
             * @param mixed $order_id
             */
            function process_payment($order_id) {
                require 'gocoin-lib.php';
               
                global $woocommerce, $wpdb;

                $coin_type = $_POST['coin_type'];

                $order = &new WC_Order($order_id);
                $order->update_status('on-hold', __('Awaiting payment notification from GoCoin.com', 'woothemes'));

                // invoice options
                if (version_compare(WOOCOMMERCE_VERSION, '2.1.0', '>=')) {
                    // >= 2.1.0
                    $redirect_url = $this->get_return_url($this->order);
                } else {
                    // < 2.1.0
                    $redirect_url = add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id'))));
                }
                $callback_url = get_option('siteurl') . "/?gocoin_callback=1";
                $currency = get_woocommerce_currency();

           
                
                $options = array(
                         "price_currency" => $coin_type,
                         "base_price" => $order->order_total,
                         "base_price_currency" => "USD", //$options['currency'],
                         "confirmations_required" => 6,
                         "notification_level" => "all",
                         "callback_url" => $callback_url,
                         "redirect_url" => $redirect_url,
                         "order_id" => $order_id,
                         "customer_name" => $order->billing_first_name . ' ' . $order->billing_last_name,
                         "customer_address_1" => $order->billing_address_1,
                         "customer_address_2" => $order->billing_address_2,
                         "customer_city" => $order->billing_city,
                         "customer_region" => $order->billing_state,
                         "customer_postal_code" => $order->billing_postcode,
                         "customer_country" => $order->billing_country,
                         "customer_phone" => $order->billing_phone,
                         "customer_email" => $order->billing_email,
                     );

                
                $client_id = $this->settings['clientId'];
                $client_secret = $this->settings['clientSecret'];
                $access_token = $this->settings['accessToken'];

                if (empty($access_token)) {
                    $woocommerce->add_error(__('Error creating GoCoin invoice.  Please try again or try another payment method.'));
                }
                   
                   try {
                    $user = GoCoin::getUser($access_token);
                    if ($user) {
                        $merchant_id = $user->merchant_id;
                        if (!empty($merchant_id)) {
                            $invoice = GoCoin::createInvoice($access_token, $merchant_id, $options);
                            if (isset($invoice->errors)) {
                                $order->add_order_note(var_export($invoice->error));
                                $woocommerce->add_error(__('Error creating GoCoin invoice.  Please try again or try another payment method.'));
                            } elseif (isset($invoice->error)) {

                                $order->add_order_note(var_export($invoice->error));
                                $woocommerce->add_error(__('Error creating GoCoin invoice.  Please try again or try another payment method.'));
                            } elseif (isset($invoice->merchant_id) && $invoice->merchant_id != '' && isset($invoice->id) && $invoice->id != '') {
                                $url = "https://gateway.gocoin.com/merchant/" . $invoice->merchant_id . "/invoices/" . $invoice->id;
                                $woocommerce->cart->empty_cart();
                                return array(
                                    'result' => 'success',
                                    'redirect' => $url,
                                );
                            }
                        }
                    } else {
                        // $order->add_order_note(var_export($invoice->error));
                        $woocommerce->add_error(__('Error creating GoCoin invoice.  Please try again or try another payment method.'));
                    }
                } catch (Exception $e) {
                    //$order->add_order_note(var_export($invoice->error));
                    $woocommerce->add_error(__('Error creating GoCoin invoice.  Please try again or try another payment method.'));
                } 
                
            }

        }

    }

    include plugin_dir_path(__FILE__) . 'gocoin-callback.php';

    function add_Gocoin_gateway($methods) {
        $methods[] = 'WC_Gocoin';
        return $methods;
    }
    function get_current_url() {
      // This is taken from GoCoin php and reworked to avoid query params.
        if (isset($_SERVER['HTTPS']) &&
            ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
            isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
            $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
          $protocol = 'https://';
        }
        else {
          $protocol = 'http://';
        }
        $currentUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $parts = parse_url($currentUrl);

        // use port if non default
        $port =
          isset($parts['port']) &&
          (($protocol === 'http://' && $parts['port'] !== 80) ||
           ($protocol === 'https://' && $parts['port'] !== 443))
          ? ':' . $parts['port'] : '';

        // rebuild
        return $protocol . $parts['host'] . $port . $parts['path'];
    }
    add_filter('woocommerce_payment_gateways', 'add_Gocoin_gateway');

    add_action('plugins_loaded', 'createWoocommerceGocoinGateway', 0);

    $pluginroot = WP_PLUGIN_DIR;
    $woo = 'woocommerce/woocommerce.php';
    $woodata = get_plugin_data("$pluginroot/$woo");
    if (isset($_GET['code']) && !isset($_GET['section'])) {
        if (version_compare($woodata['Version'], '2.1.0', '>=')) {
            // >= 2.1.0
            header("Location: /wp-admin/admin.php?page=wc-settings&tab=checkout&section=wc_gocoin&code=" . $_GET['code']);
        } else {
            // < 2.1.0
            header("Location: /wp-admin/admin.php?page=woocommerce_settings&tab=payment_gateways&section=WC_Gocoin&code=" . $_GET['code']);
        }
        exit(1);
    }
}
