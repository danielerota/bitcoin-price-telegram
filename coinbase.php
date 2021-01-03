<?php
//if you can't manage cron on your server, you can use this free service
//https://cron-job.org/ 


$btc_price = makeCall('BTC-EUR');
$telegramBtc = checkPriceChanges('btc', $btc_price, 'bitcoin_price.txt', ['upStart' => 15000, 'upEnd' => 100000, 'downStart' => 15000, 'offset' => 1000]);

$eth_price = makeCall('ETH-EUR');
$telegramEth = checkPriceChanges('eth', $eth_price, 'eth_price.txt', ['upStart' => 500, 'upEnd' => 10000, 'downStart' => 500, 'offset' => 100]);


if($telegramBtc['send'] || $telegramEth['send']){
	$msg = $telegramBtc['msg'].PHP_EOL.$telegramEth['msg'];
	
	telegram($msg);
}

/**
* return float
*/
function makeCall($crypto){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://api.coinbase.com/v2/prices/'.$crypto.'/buy');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	$result = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	$result = json_decode($result);
	return (float) $result->data->amount;
}

/**
*	return array
*/
function checkPriceChanges($crypto, $currentPrice, $fileName, $values){
	$telegramMsg = false;
	$sendTelegram = false;
	$lastPrice = (float) file_get_contents($fileName);
	
	for ($i = $values['upStart']; $i < $values['upEnd']; $i += $values['offset']){		
		if($currentPrice > $i && $lastPrice < $i){
			$telegramMsg = $crypto.' = '.$currentPrice.' €';
			$sendTelegram = true;
			file_put_contents($fileName, $currentPrice);
		}
	}

	for ($i = $values['downStart']; $i > 0; $i -= $values['offset']){
		if($currentPrice < $i && $lastPrice > $i){
			$telegramMsg = 'ATTENZIONE* '.$crypto.' = '.$currentPrice.' €';
			$sendTelegram = true;
			file_put_contents($fileName, $currentPrice);
		}
	}
	
	file_put_contents($fileName, $currentPrice);
	
	return ['send' => $sendTelegram, 'msg' => $telegramMsg];
}

/**
 * @param $msg
 */
function telegram($msg) {
    $telegrambot = '[YOUR BOT ID]';
    $telegramchatid = '[YOUR CHAT ID]';
    $url = 'https://api.telegram.org/bot'.$telegrambot.'/sendMessage';
    $msg .= PHP_EOL.'https://www.coinbase.com/';
    $data = [
        'chat_id' => $telegramchatid,
        'text' => $msg
    ];
    $options=[
        'http' => [
            'method'=>'POST',
            'header' => "Content-Type:application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data)
        ]
    ];
    $context = stream_context_create($options);
    $result = file_get_contents($url,false,$context);
    //echo 'Telegram: sent' . PHP_EOL;
}


