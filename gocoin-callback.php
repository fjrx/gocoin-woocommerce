<?php
/**
*   PHP functions to process gocoin payment
*   Version: 0.3.1
* 
*/   
 
/**
* Get call back
*/ 
    if ( !defined('ABSPATH') ) {
      require_once('../../../wp-load.php' );
    }
    require_once('gocoin-util.php');


    function gocoin_callback() {        
                    
 
      global $woocommerce;
              
      $gateways = $woocommerce->payment_gateways->payment_gateways();
      $logger = new WC_Logger();

      if (!isset($gateways['gocoin'])) {
        return;
      }
      $gocoin = $gateways['gocoin'];
      $gocoin_setting     =    isset($gocoin->settings) && is_array($gocoin->settings)?$gocoin->settings:array();
      
      $key                =    isset($gocoin_setting['accessToken']) && !empty($gocoin_setting['accessToken'])?$gocoin_setting['accessToken']:'';
        if(empty($key)){
         return $logger->add('gocoin-callback', 'Api Key is  blank');
        } 
      $data = Util::postData(); 
      if (isset($data->error)){
        return $logger->add('gocoin-callback', $data->error);
      }
      else {
      //  $key                = $gocoin -> settings -> accessToken;
        $event_id           = $data -> id;
        $event              = $data -> event;
        $invoice            = $data -> payload;
        $payload_arr        = get_object_vars($invoice) ;
                 ksort($payload_arr);
        $signature          = $invoice -> user_defined_8;
        
        $sig_comp           = Util::sign($payload_arr, $key);
        $status             = $invoice -> status;
        $order_id           = (int) $invoice -> order_id;
        $order              = WC_Order_Factory::get_order($order_id);
        
        if (!$order) {
          $msg = "Order with id: " . $order_id . " was not found. Event ID: " . $event_id;
          return $logger->add('gocoin-callback', $msg);
        }
       
        // Check that if a signature exists, it is valid
        if (isset($signature) && ($signature != $sig_comp)) {
          $msg = "Signature : " . $signature . "does not match for Order: " . $order_id ."$sig_comp        |    $signature ";
        }
        elseif (empty($signature) || empty($sig_comp) ) {
          $msg = "Signature is blank for Order: " . $order_id;
        }
        elseif($signature == $sig_comp) {
            
            
          switch($event) {

            case 'invoice_created':
              break;

            case 'invoice_payment_received':
              switch ($status) {
                 case 'ready_to_ship':
                  $msg = 'Order ' . $order_id .' is paid and awaiting payment confirmation on blockchain.';
                  $order->update_status('on-hold', __($msg, 'woothemes'));
                  break; 
                case 'paid':
                  $msg = 'Order ' . $order_id .' is paid and awaiting payment confirmation on blockchain.';
                  $order->update_status('on-hold', __($msg, 'woothemes'));
                  break;
                case 'underpaid':
                  $msg = 'Order ' . $order_id .' is underpaid.';
                  $order->update_status('on-hold', __($msg, 'woothemes'));
                  break;
              }
              break;

            case 'invoice_merchant_review':
              $msg = 'Order ' . $order_id .' is under review. Action must be taken from the GoCoin Dashboard.';
              $order->update_status('on-hold', __($msg, 'woothemes'));
              break;

            case 'invoice_ready_to_ship':
              $msg = 'Order ' . $order_id .' has been paid in full and confirmed on the blockchain.';
              $order->payment_complete();
              break;

            case 'invoice_invalid':
              $msg = 'Order ' . $order_id . ' is invalid and will not be confirmed on the blockchain.';
              $order->update_status('failed', __($msg, 'woothemes'));
              break;

            default: 
              $msg = "Unrecognized event type: ". $event;
          }
          if (isset($msg))
            $msg .= ' Event ID: '. $event_id;
        } 
        return $logger->add('gocoin-callback', $msg);
        
      }
                    
                    
    }

   gocoin_callback(); 