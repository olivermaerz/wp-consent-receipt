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

	// 0: Version
	'version' => '0.8',

	// 1: This is the legal jurisdiction under which the processing of personal data occurs (MUST)
	'jurisdiction' => 'US', 

	// 2: Timestamp of when the consent was issued (MUST)
	'iat' => time(), 

	// 3: Is used to describe how the consent was collected i.e. webform opt in, or implicit, verbal (MUST)
	'moc' => 'web form', 

	// 4: Unique identifier for this consent receipt (MUST)
	'jti' => '', // will be filled in by plugin 

	// 5: public key url
	'publicKey' => get_site_url() . '/?cr_public_key=true',

	// 6: The identity and company of the data controller and any party nominated to be data controller on behalf of org (MUST)
	'dataController' => array(
		'onBehalf' => false,
		'contact' => 'John Doe',
		'company' => 'Kantara Initiative, Inc.',
		'address' => '401 Edgewater Place, Suite 600, Wakefield, MA, 01880 USA',
		'email' => 'privacy-controller@kantarainitiative.org',
		'phone' => '+1 123-456-7890',
	),

	// 13: the internet and immediately accessible privacy policy of the service referred to by the receipt (MUST)
	'policyUri' => $group->policy_url, 

	// 14: Name of the service that requires personal information.
	'services' => array(
		array(
			'serviceName' => 'Kantara Initiative ' . $group->group_name,
			'purposes' => array(
				array(	
					'purpose' => 'Authority to sign Participation Agreement',
					'consentType'  => 'Explicit',
					'purposeCategory' => array(
						'Affiliation',
					),
					'piiCategory' => array(
						'Membership',
					),
					'nonCorePurpose' => false,
					'purposeTermination' => $group->policy_url,
					'thirdPartyDisclosure' => false,
				),
				array(
					'purpose' => 'Voting Status',
					'consentType'  => 'Explicit',
					'purposeCategory' => array(
						'Core Function', 
					),
					'piiCategory' => array(
						'Membership',
					),
					'nonCorePurpose' => true,
					'purposeTermination' => '(when no activity)',
					'thirdPartyDisclosure' => false,
				),
				array(
					'purpose' => 'Agree to IPR Policy',
					'consentType'  => 'Explicit',
					'purposeCategory' => array(
						'Core Function', 
					),
					'piiCategory' => array(
						'Membership',
					),
					'nonCorePurpose' => false,
					'purposeTermination' => 'staff@kantarainitiative.org',
					'thirdPartyDisclosure' => false,
				),
				array(
					'purpose' => 'Web statistics',
					'consentType'  => 'Explicit',
					'purposeCategory' => array(
						'Improve Performance', 
					),
					'piiCategory' => array(
						'Network/Service',
					),
					'nonCorePurpose' => false,
					'purposeTermination' => 'staff@kantarainitiative.org',
					'thirdPartyDisclosure' => true,
					'3rdPartyName' => 'Google',
				),
			),
		),
	),

	// 21: Subject provided identifier, email address - or Claim, defined/namespaced	(MUST)
	'sub' => 'jane.doe@gmail.xyz', 

	// 23: Sensitive Data Y/N (MUST)
	'sensitive' => false,

	// 24: Category for Sensitive Information collection (MUST)
	'spiCat' => array(
		//emtpy
	),

		
); // End consentReceiptData array

```

