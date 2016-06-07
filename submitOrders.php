<?php

$data = file_get_contents('php://input');

// Setup ServiceDiscovery
$services = getenv("VCAP_SERVICES");
$services_json = json_decode($services, true);
$sd_url = $services_json["service_discovery"][0]["credentials"]["url"];
$sd_token = $services_json["service_discovery"][0]["credentials"]["auth_token"];

function getServiceEndpoint($serviceName)
{
  global $sd_token, $sd_url;
  
  $url = $sd_url . "/api/v1/instances?service_name=" . $serviceName;
  
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $sd_token));
  
  $result = curl_exec($curl);
  
  curl_close($curl);
  
  $result_json = json_decode($result, true);
  
  return $result_json["instances"][0]["metadata"]["url"];
}

$ordersHost = getServiceEndpoint("Orders");

$parsedURL = parse_url($ordersHost);
$ordersRoute = $parsedURL["scheme"] . "://" . $parsedURL["host"];
$ordersURL = $ordersRoute . "/rest/orders";

function httpPost($data,$url)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_POST, true);  
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	$output = curl_exec ($ch);
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close ($ch);
	return $code;
}

echo json_encode(array("httpCode" => httpPost($data,$ordersURL), "ordersURL" => $ordersURL));

?>
