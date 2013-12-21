<?php

/**
*   PHP library including functions to process gocion payment
*   Version: 1.0
*   Author: Roman Antonich 
*/  

/**
* Create Invoice for gocoin
* 
* @param mixed $orderId
* @param mixed $price
* @param mixed $options
* @param mixed $client
* 
* @return Object $response
*/

function createInvoice($orderId, $price, $options = array(), $client) {	

    // data for invoice creation
    $my_data = array (
        "price_currency" => "BTC",
        "base_price" => $price,
        "base_price_currency" => "USD",//$options['currency'],
        "confirmations_required" => 6,
        "notification_level" => "all",
        "callback_url" => $options['callback_url'],
        "redirect_url" => $options['redirect_url'] ,
        "order_id" => $orderId,
        "customer_name" => $options['customer_name'],
        "customer_address_1" => $options['customer_address_1'],
        "customer_address_2" => $options['customer_address_2'],
        "customer_city" => $options['customer_city'],
        "customer_region" => $options['customer_region'],
        "customer_postal_code" => $options['customer_postal_code'],
        "customer_country" => $options['customer_country'],
        "customer_phone" => $options['customer_phone'],
        "customer_email" => $options['customer_email'],
    );

    $data_string = json_encode($my_data);

    $user = $client->api->user->self();
    if (!$user) {
        return array('error' => $client->getError());
    }
    // stick merchant id into params for invoice creation
    $invoice_params = array(
        'id' => $user->merchant_id,
        'data' => $data_string
    );
        
    if (!$invoice_params) {
        $response = new stdClass();
        $response->error = $client->getError();
        return $response;
    }

    $response = $client->api->invoices->create($invoice_params);
    return $response;
  
}

/**
* Get Invoice by id
* 
* @param mixed $invoiceId
* @param mixed $client
* 
* @return Object $response
*/

function getInvoice($invoiceId, $client) {
    
    if (!$client) {
        $response = new stdClass();
        $response->error = $client->getError();
        return $response;
    }
    
    $response = $client->api->invoices->get($invoiceId);

	return $response;	
}

/**
* Get Post Data for callback
* 
* @param Client $client
* 
* @return Object $response
*/

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

?>