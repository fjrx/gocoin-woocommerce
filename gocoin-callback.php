<?php
/**
*   Including functions to process callback after payment
*   Version: 1.0
*   Author: Roman Antonich 
*/  

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) 
{
	/**
    *  Main callback function
    * 
    */
    
	function gocoin_callback()
	{				
		if(isset($_GET['gocoin_callback']))
		{
			global $woocommerce;
			
			require(plugin_dir_path(__FILE__).'gocoin-lib.php');
			
			$gateways = $woocommerce->payment_gateways->payment_gateways();
			if (!isset($gateways['gocoin']))
			{
				return;
			}
            
			$gocoin = $gateways['gocoin'];
			$response = getNotifyData();

			if (isset($response->error))
				var_dump($response);
			else
			{
				$orderId = (int)$response->payload->order_id;
				$order = new WC_Order( $orderId );

				switch($response->event)
				{
					case 'invoice_created':
                    case 'invoice_paid':
						break;
					case 'invoice_confirmed':
					case 'invoice_complete':
						
						if ( in_array($order->status, array('on-hold', 'pending', 'failed' ) ) )
						{
							$order->payment_complete();
						}
						
						break;
				}
			}
		}
	}

	add_action('init', 'gocoin_callback');  
	
}