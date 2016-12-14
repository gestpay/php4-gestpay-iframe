<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/nusoap.php");
//$wsdl="https://ecomms2s.sella.it/gestpay/gestpayws/WSCryptDecrypt.asmx?WSDL";
$wsdl = "https://testecomm.sella.it/gestpay/gestpayws/WSCryptDecrypt.asmx?WSDL";
$client = new nusoap_client($wsdl,true);
$shopLogin = $_GET["a"];
$CryptedString = $_GET["b"];
echo $shopLogin . '<br/>';
echo $CryptedString . '<br/>';
$params = array('shopLogin' => $shopLogin, 'CryptedString' => $CryptedString);

$objectresult = $client->call('Decrypt',$params);
foreach ($objectresult as $elem)
		echo $elem." - ";


$err = $client->getError();

	    if ($err) {
	        // Display the error
	        echo '<h2>Error</h2><pre>' . $err . '</pre>';
	    } else {
	        // Display the result
			echo '<h2></h2>';
		
		echo '<h2>Result</h2>';
		echo '<pre>';
	
	   print_r ($objectresult);
	    
	    
	   foreach ($objectresult as $elem)
		echo $elem." - ";
	
		echo '</pre>';
	    }

?>