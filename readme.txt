=== Plugin Name ===
Contributors: olivermaerz
Donate link: 
Tags: Consent Receipt, Privacy
Requires at least: 3.0.1
Tested up to: 4.6
Stable tag: 0.28
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A Consent Receipt plugin for Wordpress. 


== Description ==


A Consent Receipt plugin for Wordpress. The WP Consent Receipt plugin can be called from themes and other plugins via the wp_consent_receipt_button filter hook:

`
if (has_filter('wp_consent_receipt_button')) {
	$html = apply_filters('wp_consent_receipt_button', $html, $consentReceiptData);
}
`

This implementation of the Consent Receipt is based on the specifcation of the Kantara Initiative - Consent & Information Sharing Work Group. For more information about the Consent Receipt and for the latest specification see: http://kantarainitiative.org/confluence/display/infosharing/Consent+Receipt+Specification

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin files to the `/wp-content/plugins/wp-consent-receipt` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Call the plugin from your theme or other plugin via the wp_consent_receipt_button filter hook (see Other Notes tab).



== Frequently Asked Questions ==

= How can I use the plugin? =

The WP Consent Receipt plugin can be called from themes and other plugins via the wp_consent_receipt_button filter hook: 

`
if (has_filter('wp_consent_receipt_button')) {
	$html = apply_filters('wp_consent_receipt_button', $html, $consentReceiptData);
}
`


== Screenshots ==

1. Receipt 

== Changelog ==

= 0.21 =
* Initial Version

= 0.22 =
* Minor bugfixes

= 0.23 =
* New JSON field names, matches the updated spec from Kantara Initiative

= 0.24 =
* Support for Consent Receipt spec 0.8 from Kantara Initiative
* New mode 1 receipt (=visual display for end user) - work in progress

= 0.25 =
* Bug fix

= 0.26 =
* Bug fix for from address when email receipt 

= 0.27 =
* Improved visual display of consent receipt (mode 1)

= 0.28 =
* Bugfix i18n


== Upgrade Notice ==

= 0.21 =
Initial stable release 

= 0.23 =
* New JSON field names, matches the updated spec from Kantara Initiative

= 0.24 =
* Support for Consent Receipt spec 0.8 from Kantara Initiative
* New mode 1 receip

= 0.25 =
* Bug fix

= 0.26 =
* Bug fix for from address when email receipt 

= 0.27 =
* Improved visual display of consent receipt (mode 1)

= 0.28 =
* Bugfix i18n


== Arbitrary section ==

The WP Consent Receipt plugin can be called from themes and other plugins via the wp_consent_receipt_button filter hook: 

`
if (has_filter('wp_consent_receipt_button')) {
	$html = apply_filters('wp_consent_receipt_button', $html, $consentReceiptData);
}
`

Example data for the $consentReceiptData associative array:

`$consentReceiptData = array(

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
`
