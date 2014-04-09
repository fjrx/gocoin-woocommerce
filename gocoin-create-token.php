<?php
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) 
{
    function gocoin_create_token() {				
        if(isset($_GET['gocoin_create_token'])) {
            global $woocommerce;
            $obj = new WC_Gocoin;
            $code = isset($_GET['code']) && !empty($_GET['code'])?$_GET['code']:'';
            
            $client_id     = $obj->settings['clientId'];
            $client_secret = $obj->settings['clientSecret'];
                       
            $gateways = $woocommerce->payment_gateways->payment_gateways();
            if (!isset($gateways['gocoin'])) {
                return;
            }
            try {
                $token = GoCoin::requestAccessToken($client_id, $client_secret, $code, null);
                echo "<b>Copy this Access Token into your GoCoin Module: </b><br>" . $token;
            } catch (Exception $e) {
                echo "Problem in getting Token: " . $e->getMessage();
            }
            die();
        }
        
    }
    add_action('init', 'gocoin_create_token');  
}