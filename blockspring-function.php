<?php
require('blockspring.php');

function webhook($team_domain, $service_id, $token, $user_name, $team_id, $user_id, $channel_id, $timestamp, $channel_name, $text, $trigger_word, $raw_text) {

    //Get Metro train arrival times for Gallery Place from WMATA API
    $api_key = '7726911924eb4bd4b5b27135fd7023c8';
	$request_url = 'https://api.wmata.com/StationPrediction.svc/json/GetPrediction/B01,F01?api_key=' .$api_key;
    
    $trains = json_decode(file_get_contents($request_url));
    
    $attachments = array();
    $colors = array(
        'RD' => '#BF1038',
        'YL' => '#F6D514',
        'GR' => '#00AF52',
        'BL' => '#0A93D6',
        'OR' => '#DD8702',
        'SV' => '#A1A3A1'
        );
    
    foreach($trains->Trains as $train) {
        if(isset($train->Line) && isset($colors[$train->Line])){
            
            if(is_numeric($train->Min)){
                if($train->Min != 1){ $min_label = 'minutes'; } else { $min_label = 'minute'; }
             	$text = $train->Min. ' ' .$min_label;   
            } elseif($train->Min == 'ARR'){
                $text = 'Arriving Now';
            } elseif($train->Min == 'BRD'){
                $text = 'Boarding';
            } else {
             	$text = $train->Min;   
            }
            
			$attachments[] = array(
            	'title' => $train->DestinationName,
            	'color' => $colors[$train->Line],
            	'text' => $text. ' - ' .$train->Car. ' cars'
            );
        }
    }
    
    return array(
        "text" => 'Upcoming rail departures from Chinatown-Gallery Place:',  // send a text response (replies to channel if not blank)
        "attachments" => $attachments
    );
}

Blockspring::define(function ($request, $response) {
    $team_domain = isset($request->params['team_domain']) ? $request->params['team_domain'] : "";
    $service_id = isset($request->params['service_id']) ? $request->params['service_id'] : "";
    $token = isset($request->params['token']) ? $request->params['token'] : "";
    $user_name = isset($request->params['user_name']) ? $request->params['user_name'] : "";
    $team_id = isset($request->params['team_id']) ? $request->params['team_id'] : "";
    $user_id = isset($request->params['user_id']) ? $request->params['user_id'] : "";
    $channel_id = isset($request->params['channel_id']) ? $request->params['channel_id'] : "";
    $timestamp = isset($request->params['timestamp']) ? $request->params['timestamp'] : "";
    $channel_name = isset($request->params['channel_name']) ? $request->params['channel_name'] : "";
    $raw_text = $text = isset($request->params['text']) ? $request->params['text'] : "";
    $trigger_word = isset($request->params['trigger_word']) ? $request->params['trigger_word'] : "";
    
    // ignore all bot messages
    if($user_id == 'USLACKBOT') {
        return;
    }
    
    // Execute bot function
    $output = webhook($team_domain, $service_id, $token, $user_name, $team_id, $user_id, $channel_id, $timestamp, $channel_name, $text, $trigger_word, $raw_text);

    // set any keys that aren't blank
    foreach($output as $k => $v) {
        if($output[$k]) {
        	$response->addOutput($k, $output[$k]);
        }
    }

    $response->end();
});