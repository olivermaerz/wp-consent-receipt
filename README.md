# WP Consent Receipt
A Consent Receipt plugin for Wordpress 

The WP Consent Receipt plugin be called from themes or other plugins via the wp_consent_receipt_button filter hook: 

```php
if (has_filter('wp_consent_receipt_button')) {
	$html = apply_filters('wp_consent_receipt_button', $html, $consentReceiptData);
}
```

$consentReceiptData is an associative array. Here some sample data:

```php
$consentReceiptData = array(
	'jurisdiction' => 'New Jersey, USA', 
	'sub' => 'admin@test.com', 
	'svc' => array(
		'Test Org wiki and other discusssion or work group services', 
		'Test Org Initiative mailing list', 
		'Test Org Initiative Auhtentication Services', 
	),
	'policy_uri' => 'https://test.com/GI/Option+Patent+and+Copyright+(RAND)', 
	'notice' => 'https://test.com/wiki/Notice+Option+Patent+and+Copyright+(RAND)', 
	'data_controller' => array(
		'on_behalf' => FALSE,
		'contact' => 'Privacy Controller',
		'company' => 'Test Org',
		'address' => '1 Main St, Laredo, TX 78041',
		'email' => 'privacy-controller@test.com',
		'phone' => '+1 (956) 111-1111',
	),
	'consent_payload' => array(
		'Privacy Policy' => 'agree',
	),
	'purpose' => array(
		'Enable user to participate in Test Org discussions and/or forums',
	),
	'pii_collected' => array(
		'name' => 'Karl Napp',
		'email' => 'karl.napp@test.com',
		'organization' => 'Magic Theater',
		'telephone' => '+1 (212) 111-1111',
	),
	'sensitive' => array(
			'ip address', 
			'pages visited',
	),
	'sharing' => array(
			'ip address',
	),
	'context' => array(
			'context',
	),				
); // End consentReceiptData array

```
