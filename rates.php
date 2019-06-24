<?php header('Content-Type: text/xml'); ?>
<rates>
<?php
ob_start();
session_start();
error_reporting(0);
if(file_exists("./install.php")) {
	header("Location: ./install.php");
} 
include("includes/config.php");
$db = new mysqli($CONF['host'], $CONF['user'], $CONF['pass'], $CONF['name']);
if ($db->connect_errno) {
    echo "Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error;
}
$db->set_charset("utf8");
$settingsQuery = $db->query("SELECT * FROM bit_settings ORDER BY id DESC LIMIT 1");
$settings = $settingsQuery->fetch_assoc();
include("includes/functions.php");
include(getLanguage($settings['url'],null,null));

function decodeGateway($gateway) {
	if($gateway == "PayPal") { return 'PP'; }
	elseif($gateway == "Skrill") { return 'SKL'; }
	elseif($gateway == "WebMoney") { return 'WMZ'; }
	elseif($gateway == "Perfect Money") { return 'PM'; }
	elseif($gateway == "Payeer") { return 'PR'; }
	elseif($gateway == "AdvCash") { return 'ADVC'; }
	elseif($gateway == "OKPay") { return 'OK'; }
	elseif($gateway == "Entromoney") { return 'EM'; }
	elseif($gateway == "SolidTrust Pay") { return 'STP'; }
	elseif($gateway == "Neteller") { return 'NTLR'; } 
	elseif($gateway == "UQUID") { } 
	elseif($gateway == "BTC-e") { return 'BTCE'; }
	elseif($gateway == "Yandex Money") { return 'YAM'; }
	elseif($gateway == "QIWI") { return 'QW'; }
	elseif($gateway == "Payza") { return 'PA'; }
	elseif($gateway == "Bitcoin") {	return ''; }
	elseif($gateway == "Litecoin") {	return ''; }
	elseif($gateway == "Dogecoin") {	return ''; }
	elseif($gateway == "Dash") {	return ''; }
	elseif($gateway == "Peercoin") {	return ''; }
	elseif($gateway == "Ethereum") {	return ''; }
	elseif($gateway == "TheBillioncoin") {	return ''; }
	elseif($gateway == "Bank Transfer") { return 'WIRE'; }
	elseif($gateway == "Western Union") { return 'WUU'; }
	elseif($gateway == "Moneygram") { return 'MGE'; }
	else {
		return 'Unknown';
	}
}

$getsend = $db->query("SELECT * FROM bit_gateways ORDER BY id");
if($getsend->num_rows>0) {
	while($ss = $getsend->fetch_assoc()) {
		$gateway_send = $ss['id'];
		$getquery = $db->query("SELECT * FROM bit_gateways ORDER BY id");
		if($getquery->num_rows>0) {
			while($get = $getquery->fetch_assoc()) {
				$gateway_receive = $get['id'];
				$currency_from = gatewayinfo($gateway_send,"currency");
				$currency_to = gatewayinfo($gateway_receive,"currency");
				$fee = gatewayinfo($gateway_receive,"fee");
				$query = $db->query("SELECT * FROM bit_rates WHERE gateway_from='$gateway_send' and gateway_to='$gateway_receive'");
						if($query->num_rows>0) {
							$row = $query->fetch_assoc();
							$data['status'] = 'success';
							$rate_from = $row['rate_from'];
							$rate_to = $row['rate_to'];
						} else {
								if($currency_from == $currency_to) { 
									$fee = str_ireplace("-","",$fee);
									$calculate1 = (1 * $fee) / 100;
									$calculate2 = 1 - $calculate1;
									$rate_from = 1;
									$rate_to = $calculate2;
								} elseif($currency_to == "BTC") {
									if(checkCryptoExchange($gateway_sendname,$gateway_receivename)) {
										$query = $db->query("SELECT * FROM bit_rates WHERE gateway_from='$gateway_send' and gateway_to='$gateway_receive'");
										if($query->num_rows>0) {
											$row = $query->fetch_assoc();
											$data['status'] = 'success';
											$rate_from = $row['rate_from'];
											$rate_to = $row['rate_to'];
										} else {
											$data['status'] = 'error';
											$data['msg'] = '-';
										}
									} else {
										$ch = curl_init();
										$url = "https://www.changer.com/api/v2/rates/bitcoin_BTC/payeer_USD";
										// Disable SSL verification
										curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
										// Will return the response, if false it print the response
										curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
										// Set the url
										curl_setopt($ch, CURLOPT_URL,$url);
										// Execute
										$result=curl_exec($ch);
										// Closing
										curl_close($ch);
										$json = json_decode($result, true);
											$price = $json['rate'];
											$price = currencyConvertor($price,"USD",$currency_from);
											$calculate1 = ($price * $fee) / 100;
											$calculate2 = $price - $calculate1;
											$calculate2 = number_format($calculate2, 2, '.', '');
											$rate_to = 1;
											$rate_from = $calculate2;
									}
								}  elseif($currency_from == "BTC") {
									if(checkCryptoExchange($gateway_sendname,$gateway_receivename)) {
										$query = $db->query("SELECT * FROM bit_rates WHERE gateway_from='$gateway_send' and gateway_to='$gateway_receive'");
										if($query->num_rows>0) {
											$row = $query->fetch_assoc();
											$data['status'] = 'success';
											$rate_from = $row['rate_from'];
											$rate_to = $row['rate_to'];
										} else {
											$data['status'] = 'error';
											$data['msg'] = '-';
										}
									} else {
										$ch = curl_init();
										$url = "https://www.changer.com/api/v2/rates/bitcoin_BTC/payeer_USD";
										// Disable SSL verification
										curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
										// Will return the response, if false it print the response
										curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
										// Set the url
										curl_setopt($ch, CURLOPT_URL,$url);
										// Execute
										$result=curl_exec($ch);
										// Closing
										curl_close($ch);
										$json = json_decode($result, true);
											$price = $json['rate'];
										$price = currencyConvertor($price,"USD",$currency_to);
										$calculate1 = ($price * $fee) / 100;
										$calculate2 = $price - $calculate1;
										$calculate2 = number_format($calculate2, 2, '.', '');
										$rate_from = 1;
										$rate_to = $calculate2;
									}
								} elseif(checkCryptoExchange($gateway_sendname,$gateway_receivename)) {
									$query = $db->query("SELECT * FROM bit_rates WHERE gateway_from='$gateway_send' and gateway_to='$gateway_receive'");
									if($query->num_rows>0) {
										$row = $query->fetch_assoc();
										$data['status'] = 'success';
										$rate_from = $row['rate_from'];
										$rate_to = $row['rate_to'];
									} else {
										$data['status'] = 'error';
										$data['msg'] = '-';
									}
								} else {
									if(isCrypto($gateway_sendname) == "1" && isCrypto($gateway_receivename) == "0") {
										$query = $db->query("SELECT * FROM bit_rates WHERE gateway_from='$gateway_send' and gateway_to='$gateway_receive'");
										if($query->num_rows>0) {
											$row = $query->fetch_assoc();
											$data['status'] = 'success';
											$rate_from = $row['rate_from'];
											$rate_to = $row['rate_to'];
										} else {
											$data['status'] = 'error';
											$data['msg'] = '-';
										}
									} elseif(isCrypto($gateway_sendname) == "0" && isCrypto($gateway_receivename) == "1") {
										$query = $db->query("SELECT * FROM bit_rates WHERE gateway_from='$gateway_send' and gateway_to='$gateway_receive'");
										if($query->num_rows>0) {
											$row = $query->fetch_assoc();
											$data['status'] = 'success';
											$rate_from = $row['rate_from'];
											$rate_to = $row['rate_to'];
										} else {
											$data['status'] = 'error';
											$data['msg'] = '-';
										}
									} else {
										$rate_from = 1;
										$calculate = currencyConvertor($rate_from,$currency_from,$currency_to);
										$calculate1 = ($calculate * $fee) / 100;
										$calculate2 = $calculate - $calculate1;
										if($calculate2 < 1) { 
											$calculate = currencyConvertor($rate_from,$currency_to,$currency_from);
											$calculate1 = ($calculate * $fee) / 100;
											$calculate2 = $calculate - $calculate1;
											$rate_from = number_format($calculate2, 2, '.', '');
											$rate_to = 1;
										} else {
											$rate_to = number_format($calculate2, 2, '.', '');
										}
									}
								}
				}
				
				$reserve = gatewayinfo($gateway_receive,"reserve");
				$gatsend = decodeGateway(gatewayinfo($gateway_send,"name")).gatewayinfo($gateway_send,"currency");
				$gatreceive = decodeGateway(gatewayinfo($gateway_receive,"name")).gatewayinfo($gateway_receive,"currency");
				echo '<item>';
				echo '<from>'.$gatsend.'</from>';
				echo '<to>'.$gatreceive.'</to>';
				echo '<in>'.$rate_from.'</in>';
				echo '<out>'.$rate_to.'</out>';
				echo '<amount>'.$reserve.'</amount>';
				echo '</item>';
			}
		}
	}
}

mysqli_close($db);
?>
</rates>