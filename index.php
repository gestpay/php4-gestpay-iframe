<!DOCTYPE HTML>
<?php
//Check the PARes parameter for 2nd call due to 3D enrolled credit card
$PARes = $_REQUEST["PaRes"];
$shopLogin = $_REQUEST["ShopLogin"];


//Setting up the basic parameter set to retrieve an encrypted string form GestPay
$shopLogin = ''; //YOUR SHOP LOGIN Eg. production code '9000001' , test code 'gespay0001' 
$testEnv = true; // test or production environment? 

//if $PARes is not empty, we are coming back after 3D security check
if (strlen($PARes) > 0){
	$encString = $_COOKIE["encString"];
	$TransKey = $_COOKIE["TransKey"];
	$shopLogin = $_COOKIE["shopLogin"];
	$_COOKIE["encString"] = NULL;
	$_COOKIE["TransKey"] = NULL;
	$_COOKIE["shopLogin"] = NULL;
} else {
	// FIRST PAGE LOAD
	// WSCryptDecrypt consuming example in php
	// The script use the NuSoap - SOAP toolkit for PHP http://sourceforge.net/projects/nusoap/
	//load the NuSoap toolkit
	require_once("nusoap.php");
	
	$currency = '242'; //payment currency  242 -> Euro
	$amount = '0.05'; //payment amount
	
	$shopTransactionID = 'iFramePHPTestPage_' . date("H:i:s"); //your payment order identifier

	$wsdl = null; 
	//setting up the WSLD url
	if ($testEnv === true) {
		//Test
		$wsdl = "https://testecomm.sella.it/gestpay/gestpayws/WSCryptDecrypt.asmx?WSDL";
	} else {
		//Production
		$wsdl = "https://ecomms2s.sella.it/gestpay/gestpayws/WSCryptDecrypt.asmx?WSDL";
	}
	
	//NuSoap client
	$client = new nusoap_client($wsdl,true); 
	//setting up the parameters array
	$param = array(
		'shopLogin' => $shopLogin, 
		'uicCode' => $currency, 
		'amount' => $amount, 
		'shopTransactionId' => $shopTransactionID);

	//Call the Encrypt method
	$objectresult = $client->call('Encrypt', $param);

	//Check for call error 
	$err = $client->getError();

	if ($err) {

		// A call error has occurred 	    	    
		// Display the error
		echo '<h2>Error</h2><pre>' . $err . '</pre>';

	} else {

		//check ErrorCode
		$errCode = $objectresult['EncryptResult']['GestPayCryptDecrypt']['ErrorCode'];

		if ($errCode == '0') {
		
			//the call returned the encrypted string
    	$encString = $objectresult['EncryptResult']['GestPayCryptDecrypt']['CryptDecryptString'];

		} else {
			//An error has occurred;  check ErrorCode and ErrorDescription
			echo '<!DOCTYPE HTML><head><style>html,body{margin: 0;padding: 0;} .error{width:100%;margin:0;border-bottom:1px solid black;background-color:#FFEC8B;text-align:center;font-size:1em;font-weight:blod;color:red;padding: 0;}</style></head><html><body>';
			echo '<div class="error">Error:';
			echo $errCode;
			echo '<br>ErrorDesc:';
			echo $objectresult['EncryptResult']['GestPayCryptDecrypt']['ErrorDescription'] ;
			echo '</div>';
			exit();
		}
	}
}
?>

<html>
<head>
<!-- Load the GestPay javascript file -->
<?php if ($testEnv === false) { ?>

<!-- Production -->
<script type="text/javascript" src="https://ecomm.sella.it/Pagam/JavaScript/js_GestPay.js"></script>

<?php } else { ?> 

<!-- TEST --> 
<script type="text/javascript" src="https://testecomm.sella.it/Pagam/JavaScript/js_GestPay.js"></script>

<?php } ?>

<script>

//declaring local object to handle asynchronous responses from the payment page 
var localObj = {}
//setting up a function to handle asynchronous security check result after creating the iFrame and loading the payment page
localObj.PaymentPageLoad = function(Result){
	//check for errors, if the Result.ErroCode is 10 the iFrame is created correctly and the security check are passed 
	if(Result.ErrorCode == 10 ){
		//iFrame created and and the security check passed 
		//now we can show the form with the credit card fields
		//Handle 3D authentication 2nd call
		var PARes = '<?php echo($PARes) ?>';
		if (PARes.length > 0){
			//The card holder land for the 2nd call after 3d authentication so we can proceed to process the transaction without showing the form 
			document.getElementById('text').innerHTML = 'Payment in progress...';			
			GestPay.SendPayment({PARes:PARes,TransKey:'<?php echo($TransKey) ?>'},localObj.PaymentCallBack);			
		}else{
			document.getElementById('InnerFreezePane').className='Off';
			document.getElementById('FreezePane').className='Off';
			document.getElementById('CCForm').className='On';
		}
	}else{
		//An error has occurred, check the Result.ErrorCode and Result.ErrorDescription 
		//place error handle code HERE
		document.getElementById('ErrorBox').innerHTML='Error:' + Result.ErrorCode + ' ' + Result.ErrorDescription;
		document.getElementById('ErrorBox').className='On'
		document.getElementById('InnerFreezePane').className = 'Off'
		document.getElementById('FreezePane').className = 'Off'
	}							
}
//setting up a function to handle payment result
localObj.PaymentCallBack = function (Result){
	if(Result.ErrorCode == 0 ){
		//Transaction correctly processed
		//Decrypt the string to read the Transaction Result
		document.location.replace('response.php?a=<?php echo($shopLogin) ?>&b='+ Result.EncryptedString);
	}else{
		//An error has occurred
		//check for 3D authentication required
		if(Result.ErrorCode == 8006){
			//The credit card is enrolled we must send the card holder to the authentication page on the issuer website
			//Get the TransKey, IMPORTANT! this value must be stored for further use
			var TransKey = Result.TransKey
			var date = new Date();
			date.setTime(date.getTime()+(1200000));
 			document.cookie = 'TransKey='+TransKey.toString()+'; expires='+ date.toGMTString() +' ; path=/';
			//put the EcryptedString generated by the WSCryptDecrypt webservice in a cookies for later use, the cookies will expire in 20 minutes
			var date = new Date();
			date.setTime(date.getTime()+(1200000));
			document.cookie = 'encString=<?php echo($encString) ?>; expires='+ date.toGMTString() +' ; path=/';		//Get the VBVRisp encrypted string required to access the issuer authentication page
			document.cookie ='shopLogin=<?php echo($shopLogin)  ?>; expires='+ date.toGMTString() +' ; path=/'; 	//Get the shopLogin required to access the issuer authentication page
			//Get the VBVRisp encrypted string required to access the issuer authentication page
			var VBVRisp = Result.VBVRisp
			//redirect the user to the issuer authentication page
			var a = '<?php echo($shopLogin) ?>'; 
			var b = VBVRisp;
			var c= document.location.href; //this is the landing page where the user will be redirected after the issuer authentication must be ABSOLUTE
 			var AuthUrl = 'https://testecomm.sella.it/pagam/pagam3d.aspx'; //TESTCODES
 			//var AuthUrl = 'https://ecomm.sella.it/pagam/pagam3d.aspx'; //PRODUCTION
 			document.location.replace(AuthUrl+'?a='+a+'&b='+b+'&c='+c);
		}else{
			//Hide overlapping layer
			document.getElementById('InnerFreezePane').className='Off';
			document.getElementById('FreezePane').className='Off';	
			document.getElementById('submit').disabled=false;
			//Check the ErrorCode and ErrorDescription
			if(Result.ErrorCode == 1119 || Result.ErrorCode == 1120){
				document.getElementById('ErrorBox').innerHTML='Error:' + Result.ErrorCode +' - ' + Result.ErrorDescription;
				document.getElementById('ErrorBox').className='On'
				//alert(Result.ErrorDescription);
				document.getElementById('CC').focus();
			}
			if(Result.ErrorCode == 1124 || Result.ErrorCode == 1126){
				document.getElementById('ErrorBox').innerHTML='Error:' + Result.ErrorCode +' - ' + Result.ErrorDescription;
				document.getElementById('ErrorBox').className='On'				
				//alert(Result.ErrorDescription);
				document.getElementById('EXPMM').focus();
			}
			if(Result.ErrorCode == 1125){
				document.getElementById('ErrorBox').innerHTML='Error:' + Result.ErrorCode +' - ' + Result.ErrorDescription;
				document.getElementById('ErrorBox').className='On'
				//alert(Result.ErrorDescription);
				document.getElementById('EXPYY').focus();
			}
			if(Result.ErrorCode == 1149){
				document.getElementById('ErrorBox').innerHTML='Error:' + Result.ErrorCode +' - ' + Result.ErrorDescription;
				document.getElementById('ErrorBox').className='On'
				//alert(Result.ErrorDescription);
				document.getElementById('CVV2').focus();
			}
			if(Result.ErrorCode != 1149 || Result.ErrorCode != 1119 || Result.ErrorCode != 1120 || Result.ErrorCode != 1124 || Result.ErrorCode != 1126 || Result.ErrorCode != 1125){
				document.getElementById('ErrorBox').innerHTML='Error:' + Result.ErrorCode +' - ' + Result.ErrorDescription;
				document.getElementById('ErrorBox').className='On'
			
			}		
		}
	}
}
//Send data to GestPay and process transaction
function CheckCC(){
	document.getElementById('submit').disabled=true; //disable submit button
	//raise the Overlap layer
	document.getElementById('FreezePane').className='FreezePaneOn';
	//raise the loading message
	document.getElementById('text').innerHTML = 'Pagamento in corso...';
	document.getElementById('InnerFreezePane').className='On';
	GestPay.SendPayment ({
					 CC : document.getElementById('CC').value,
					 EXPMM : document.getElementById('EXPMM').value,
					 EXPYY : document.getElementById('EXPYY').value,
					 CVV2: document.getElementById('CVV2').value
			},localObj.PaymentCallBack);
	return false;
	
}
</script>

<link type="text/css" rel="stylesheet" href="reset.css">
<link type="text/css" rel="stylesheet" href="iFrame.css">
<!--[if IE]>
      <style type="text/css">
   #CCFieldset{
	background-color:#FFF;
	width:510px;
    }
    #CCcontainer{
    	width:500px;
    	
    }
    #CCcontainer div{
    	width: 350px;
    	margin:0 auto;
    }
    
   </style>
<![endif]-->

<!--[if lte IE 8]>
 <style type="text/css">
input{
	background-color:rgb(235,235,235);
	padding: 0.7em;

}
</style>
<![endif]-->

</head>
<body>
<!-- Overlap Layer -->
<div id="FreezePane" class="Off"></div>
<div id="InnerFreezePane" class="Off">
	<div id="text"></div>
</div>
<!-- Overlap Layer END -->
<!-- ErrorBox -->
<div id="ErrorBox" class="Off">Error</div>

<!-- Create the iFrame-->
<script type="text/javascript">

//check if the browser support HTML5 postmessage
if(BrowserEnabled){
	//Browser enabled
	//Creating the iFrame
	GestPay.CreatePaymentPage('<?php echo($shopLogin) ?>', '<?php echo($encString) ?>',localObj.PaymentPageLoad);
	//raise the Overlap layer
	document.getElementById('FreezePane').className='FreezePaneOn';
	//raise the loading message
	document.getElementById('text').innerHTML = 'Loading...';
	document.getElementById('InnerFreezePane').className='On';
	//Handle after 3D authentication second call
}else{
	//Browser not supported
	//Place error handle code here
	document.getElementById('ErrorBox').innerHTML='Error: Browser not supported';
	document.getElementById('ErrorBox').className='On'
}

</script>


<!-- main Box, hidden before the browser check and the iFrame is properly loaded -->
<div id="Main">
	<!-- Credit card form -->
	<form name="CCForm" method="post" id="CCForm" OnSubmit="return CheckCC();" class="Off">
		<div id="Fields">
		<fieldset id="CCFieldset">
		<legend>Credit card data</legend>
			<div id="CCcontainer">
			<div id="CCField"><label for="CC">Card Number</label><div class="fieldcontainerL"><input type="text" name="CC" id="CC" autocomplete="off" maxlength="19" placeholder="4444444444444444"/></div></div>
			<div id="ExpDate"><label>Exipiry Date (MM/YY)</label><div class="fieldcontainerS"><input type="text" name="EXPMM" id="EXPMM" autocomplete="off" maxlength="2" placeholder="01" /></div> / <div class="fieldcontainerS"><input type="text" name="EXPYY" id="EXPYY" autocomplete="off" maxlength="2"  placeholder="18"/></div>
			</div>
			<hr/>
			<div id="CCVField"><label>Security code (Cvv2/4DBC)</label><div class="fieldcontainerS"><input type="password" name="CVV2" id="CVV2" maxlength="4" /></div></div>
			<hr />
			<div id="NameField"><label for="Name">Buyer Name</label><div class="fieldcontainerL"><input type="text" name="Name" id="Name" value=""></div></div>
			<hr />
			<div id="EmailField"><label for="Email">Buyer Email</label><div class="fieldcontainerL"><input type="email" name="Email" id="Email" value=""></div></div>
			</div>
		</fieldset>
		<fieldset id="SubmitFieldset">	
			<input type="submit" value="Proceed" id="submit" />
		</fieldset>
		</div>
	</form>
</div>

</body>
</html>