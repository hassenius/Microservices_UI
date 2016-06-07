<?php
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

$catalogHost = getServiceEndpoint("Catalog");

$parsedURL = parse_url($catalogHost);
// Force http for now
#$catalogRoute = $parsedURL["scheme"] . "://" . $parsedURL["host"];
$catalogRoute = "http://" . $parsedURL["host"];

function CallAPI($method, $url)
{
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec($curl);
	curl_close($curl);
	return $result;
}
$result = CallApi("GET", $catalogRoute . "/items");
?>

<script>
var items = <?php echo $result?>;

function loadItems(){
	var i = 0;
	console.log("Load Items: " + items.rows);
	document.getElementById("loading").innerHTML = "";
	for(i = 0; i < items.rows.length; ++i){
		addItem(items.rows[i].doc);
	}
}

function addItem(item){
	var div = document.createElement('div');
	div.className = 'column';
	div.innerHTML = "<a class='th' href = '"+item.imgsrc+"'><img src = '"+item.imgsrc+"'/></a></div><h5>"+item.name+"</h5><p>$"+item.usaDollarPrice.toLocaleString() + " USD</p><p>"+item.description+"</p><a class='button expanded' onclick='orderItem(\""+item._id+"\")'>Buy</a>";
	if(item.isNew)
		document.getElementById('newItemWell').appendChild(div);
	else
		document.getElementById('itemWell').appendChild(div);
}

function orderItem(itemID){
	//create a random customer ID and count
	var custID = Math.floor((Math.random() * 999) + 1); 
	var count = Math.floor((Math.random() * 9999) + 1); 
	var myjson = {"itemid": itemID, "customerid":custID, "count":count};

	$.ajax ({
		type: "POST",
		contentType: "application/json",
		url: "submitOrders.php",
		data: JSON.stringify(myjson),
		dataType: "json",
		success: function( result ) {
			if(result.httpCode != "201" && result.httpCode != "200"){
				alert("Failure: check that your JavaOrders API App is running and your user-provided service has the correct URL.");
			}
			else{
				alert("Order Submitted! Check your Java Orders API to see your orders: \n" + result.ordersURL);
			}
		},
		error: function(XMLHttpRequest, textStatus, errorThrown) { 
			alert("Error");
			console.log("Status: " , textStatus); console.log("Error: " , errorThrown); 
		}  
	});

}
</script>

<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
	<meta charset="utf-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<title>Microservices Store | Demo</title>
	<link rel="stylesheet" href="http://dhbhdrzi4tiry.cloudfront.net/cdn/sites/foundation.min.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
</head>

<body onload='loadItems()'>
	<div id='loading'>Loading...</div>
	<div class="top-bar">
		<div class="top-bar-left">	
			<ul class="menu">
				<li class="menu-text">Microservices Store Demo</li>
			</ul>
		</div>
	</div>

	<div class="row column text-center">
		<h2>Our Newest Products</h2>
		<hr>
	</div>
	<div id='newItemWell' class="row small-up-2 large-up-4">

	</div>
	<hr>
	<div class="row column text-center">
		<h2>Some Other Neat Products</h2>
		<hr>
		<div id='itemWell' class="row small-up-2 large-up-4">
	</div>
	<div class="callout large secondary">
		<div class="row">
				<h5>Microservices Store Demo</h5>
				<p>You can find the blog post associated with this demo <a href="https://developer.ibm.com/bluemix/2015/03/16/sample-application-using-microservices-bluemix/" target="_blank">here</a></p>
		</div>
	</div>
	<script>
		$(document).foundation();
	</script>
</body>
</html>
