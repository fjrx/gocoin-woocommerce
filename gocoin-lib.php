<?php
/**
*   PHP library including functions to process gocoin payment
*   Version: 0.1.4
*/ 

 
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