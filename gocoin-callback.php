<?php
/**
*   PHP functions to process gocoin payment
*   Version: 0.2.0
* 
*/   
 
/**
* Get call back
*/
    if ( !defined('ABSPATH') ) {
        require_once('../../../wp-load.php' );
    }

    function gocoin_callback() {				
                    
            global $woocommerce;
                    
            $gateways = $woocommerce->payment_gateways->payment_gateways();
            if (!isset($gateways['gocoin'])) {
                return;
            }

            $gocoin = $gateways['gocoin'];
            $response = getNotifyData();
                    
            if (isset($response->error))
                var_dump($response);
            else
            {
                $orderId            = (int)$response->payload->order_id;
                $order              = new WC_Order( $orderId );
                $event              = $response->event;
                $order_id           = (int) $response->payload->order_id;
                $redirect_url       = $response->payload->redirect_url;
                $invoice_id         = $response->payload->id;
                $total              = $response->payload->base_price;
                $status             = $response->payload->status;
                $currency           = $response->payload->base_price_currency;
                $currency_type      = $response->payload->price_currency;
                $invoice_time       = $response->payload->created_at;
                $expiration_time    = $response->payload->expires_at;
                $updated_time       = $response->payload->updated_at;
                $merchant_id        = $response->payload->merchant_id;
                $btc_price          = $response->payload->price;
                $price              = $response->payload->base_price;
                $url                = $response->payload->gateway_url;
                $fprint             = $response->payload->user_defined_8;
                
                $iArray = array(
                                'order_id'          => $order_id,
                                'invoice_id'        => $invoice_id,
                                'url'               => $url,
                                'status'            => $event,
                                'btc_price'         => $btc_price,
                                'price'             => $price,
                                'currency'          => $currency,
                                'currency_type'     => $currency_type,
                                'invoice_time'      => $invoice_time,
                                'expiration_time'   => $expiration_time,
                                'updated_time'      => $updated_time,
                                'fingerprint'       => $fprint
                    );
                
                $i_id = getFPStatus($iArray);
                if(!empty($i_id) && $i_id == $invoice_id){
                
                    updateTransaction('payment', $iArray);
                
                    switch($response->event)
                    {
                    case 'invoice_created':
                    case 'invoice_payment_received':

                      break;
                    case 'invoice_ready_to_ship':
                    if (($status == 'paid') || ($status == 'ready_to_ship')) {
                        if ( in_array($order->status, array('on-hold', 'pending', 'failed' ) ) )
                        {
                    
                            $order->payment_complete();
                        }
                    }
                        break;
                }
                
                }
                elseif(!empty($fprint)){
                    $msg = "\n Fingerprint : ".$fprint. "does not match for Order id :".$order_id;
                    error_log($msg, 3, 'gocoin_error_log.txt');
                }
                else{
                    $msg = "\n No Fingerprint received for with Order id :".$order_id;
                    error_log($msg, 3, 'gocoin_error_log.txt');
                }
            }
                    
                    
    }

    function getFPStatus($details){
        global $wpdb;
         $data = $wpdb->get_row(
                $wpdb->prepare("SELECT invoice_id FROM " . $wpdb->prefix . "gocoin_ipn where invoice_id = %s and   
            fingerprint = %s  ",array($details['invoice_id'],$details['fingerprint']))
                ); 
        
        if(isset($data->invoice_id) && !empty($data->invoice_id)){
            return $data->invoice_id;
        }
    }
    
    function updateTransaction($type = 'payment', $details) {
        global $wpdb;
        $field_array = array('status'       =>  $details['status'] ,   
                      'updated_time' =>  $details['updated_time']
                     );
        $where_array = array('invoice_id'       =>  $details['invoice_id'] ,   
                      'order_id' =>  $details['order_id']);
        
       return  $wpdb->update($wpdb->prefix."gocoin_ipn",$field_array,$where_array);
    }
    
    function getNotifyData() {
        //get webhook content
        $post_data = file_get_contents("php://input");
        if (!$post_data) {
            $response = new stdClass();
            $response->error = 'Post Data Error';
            return $response;
        }

        $response = json_decode($post_data);
        return $response;
    }
    
    gocoin_callback();
 