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
		'Kantara Initiative wiki and other discusssion or work group services', 
		'Kantara Initiative mailing list', 
		'Kantara Initiative Auhtentication Services', 
	),
	'policy_uri' => 'http://kantarainitiative.org/confluence/display/GI/Option+Patent+and+Copyright+(RAND)', 
	'notice' => 'http://kantarainitiative.org/confluence/display/GI/Option+Patent+and+Copyright+(RAND)', 
	'data_controller' => array(
		'on_behalf' => FALSE,
		'contact' => 'Privacy Controller',
		'company' => 'Kantara Initiative',
		'address' => '445 Hoes Lane, Piscataway, NJ 08854',
		'email' => 'privacy-controller@kantarainitiative.org',
		'phone' => '+1 (732) 465-5817',
	),
	'consent_payload' => array(
		'Privacy Policy' => 'agree',
	),
	'purpose' => array(
		'Enable user to participate in Kantara Initiative discussion and/or work groups',
	),
	'pii_collected' => array(
		'name' => 'Karl Napp',
		'email' => 'karl.napp@test.com',
		'organization' => 'Magic Theater',
		'telephone' => '(212) 123-4567',
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
