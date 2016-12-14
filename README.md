# Gestpay iFrame example in PHP

This example focuses on how to completely customize the payment page the way you want - in Gestpay, we technically refer to this as the *iFrame solution*. 

A detailed explanation of what's going on is in the [Getting Started](http://docs.gestpay.it/gs/super-quick-start-guide.html) page. Refer to the paragraph *Using your customized payment page* to understand the process of paying with this solution. 

> **NOTE - to launch this example, the iFrame solution must be active on your environment. Ask Gestpay customer care to enable this** 

## What's in this repository 

| File | Description | 
| ---- |------------ | 
| `index.php` | This is the main entry point. Since it is only an example, it contains php instructions (executed on the server) along with html and javascript code (executed on the client).
| `response.php` |  when the payment is completed, Gestpay will redirect to this file to show to the user the payment status. `response.php` will decrypt the encrypted string and then it will show the SOAP message received - in the form of an array.
| `nusoap.php` | `NuSoap` is a SOAP library. PHP has SOAP built-in support from PHP5 and more, but if you use PHP4 you must use a library for this. We have chosen `NuSoap`. 
| `reset.css` and `iFrame.css` | because nobody likes ugly pages
| `README.md` | this file 

## How to start 

1. open `index.php` and set the `$shopLogin` variable (row 9) with your Gestpay shop login. 
2. in the same file, you can set the environment (*test* or *production*) via the variable `$testEnv`. (Default: `true`)
3. upload it to a php server with a public ip 
4. Connect to your [test merchant back-office](http://testecomm.sella.it) and log in 
5. In *Configuration* > *IP address*, insert the public IP of your server 
6. In the same page click on *Response Address* and insert:
	- URL for positive response: `<<your_server_address>>/response.php`
	- URL for negative response: `<<your_server_address>>/response.php`
	- URL Server to Server: `<<your_server_address>>/response.php` 
7. Pay with one of the cards present in *Notification* page.
8. Once you have payed, you'll be redirected by Gestpay on `response.php` to see the outcome ot the transaction.  

## Questions, Issues, etc.

For any questions, open an issue on Github.