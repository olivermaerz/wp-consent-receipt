# WP Consent Receipt
A Consent Receipt plugin for Wordpress 

The WP Consent Receipt plugin can be called from themes and other plugins via the wp_consent_receipt_button filter hook: 

```php
if (has_filter('wp_consent_receipt_button')) {
	$html = apply_filters('wp_consent_receipt_button', $html, $consentReceiptData);
}
```

Example data for the $consentReceiptData associative array:

```php
$consentReceiptData = array(
				// This is the legal jurisdiction under which the processing of personal data occurs
				'jurisdiction' => 'US', 

				// Timestamp of when the consent was issued
				'iat' => time(), 

				// Is used to describe how the consent was collected i.e. webform opt in, or implicit, verbal, etc.
				'moc' => 'web form', 

				// This is the URI or Internet location of processing, i.e., one party-two party or three
				'iss' => 'https://olivermaerz.com', 

				// Unique identifier for this consent receipt
				'jti' => uniqid(),

				// Subject provided identifier, email address - or Claim, defined/namespaced	
				'sub' => $email, 

				// The identity and company of the data controller and any party nominated to be data controller on behalf of org
				'data_controller' => array(
					'on_behalf' => FALSE,
					'contact' => 'Privacy Controller',
					'company' => 'Some Company Name',
					'address' => '1 Main St, San Antonio, TX 78000',
					'email' => 'privacy-controller@someorganization.tx',
					'phone' => '+1 (123) 456-7890',
				),

				// the internet and immediately accessible privacy policy of the service referred to by the receipt
				'policy_uri' => $policy_url, 

				// Explicit, Specific and Legitimate: interpreted here as: 'Naming the Service' and 'Stating the Active Purpose ' see Appendix A these requirements
				'purpose' => array(
					'Enable user to participate in Kantara Initiative discussion and/or work groups',
				),

				// In many jurisdictions their are additional notice and administrative requirements for the collection, storage and processing of what are called Sensitive Personal Information Categories. These are Sensitive in the business, legal, and technical sense, but not specifically in the personal context. This list of categories are required in some jurisdiction, but, the actual notice and purpose requirements are out the scope of the MVCR.
				'sensitive' => array(
						'ip address', 
						'pages visited',
				),

				// This refers to the sharing of personal information collected about the individual, with another external party by the data controller (service provider). Should list categories of PII shared, from above list and under what purpose. Sharing is also a container for listing trust marks and trust protocols.
				'sharing' => array(
					'sharing' => 'Pages visited',
					'party_name' => 'Google',
					'purpose' => 'Statistics',
				),

				// Link to the short notice enables usability and layered policy. to provide enhanced transparency about data collection and information sharing practices
				'notice' => 'https://someorganization.tx/Privacy+Policy',

				// What you’re allowed to do on the service (these can be tied to legal / business / technical layers)
				'scopes' => 'read write update delete', 
					
			); // End consentReceiptData array

```

