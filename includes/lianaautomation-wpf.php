<?php
/**
 * LianaAutomation WPForms handler
 *
 * PHP Version 7.4
 *
 * @package  LianaAutomation
 * @license  https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL-3.0-or-later
 * @link     https://www.lianatech.com
 */

/**
 * WPForms functionality. Sends the form data to the Automation API.
 *
 * @param mixed $fields    Fields.
 * @param mixed $entry     WPForms param (unused here).
 * @param mixed $form_id   WPForms form id (should be used here somewhere).
 * @param mixed $form_data WPForms structure of the form (unused here).
 *
 * @return null
 */
function lianaautomation_wpf_process_entry_save( $fields, $entry, $form_id, $form_data ) {
	// Gets liana_t tracking cookie if set.
	if ( isset( $_COOKIE['liana_t'] ) ) {
		$liana_t = sanitize_key( $_COOKIE['liana_t'] );
	} else {
		// We shall send the form even without tracking cookie data.
		$liana_t = null;
	}

	// Extract the form data to Automation compatible array
	$wpFormsArray = array();

	// Try to find an email address from the form fields data
	// WPForms is supposed to have a built-in field type 'email'.
	$email = null;
	foreach ( $fields as $field ) {
		if ( ! $email && $field['type'] == 'email' ) {
			$email = $field['value'];
		}
		// Fill the wpFormsArray while iterating the fields
		$wpFormsArray[ $field['name'] ] = $field['value'];
	}
	if ( empty( $email ) ) {
		error_log( 'ERROR: No /email/i found on form data. Bailing out.' );
		return false;
	}

	/*
	* Phone number is a WPForms PRO feature
	*
	// Try to find an email address from the form data
	$sms = null;
	*/

	// Add Gravity Forms 'magic' values for title and id
	$wpFormsArray['formtitle'] = $form_data['settings']['form_title'];
	$wpFormsArray['formid']    = $form_data['id'];

	// error_log(
	// "Liana_WPForms_Process_Entry_save:fields ".print_r($fields, true)
	// );

	// error_log(
	// "Liana_WPForms_Process_Entry_save:form_data ".print_r($form_data, true)
	// );

	/**
	* Retrieve Liana Options values (Array of All Options)
	*/
	$lianaautomation_wpf_options
		= get_option( 'lianaautomation_wpf_options' );

	if ( empty( $lianaautomation_wpf_options ) ) {
		error_log( 'lianaautomation_wpf_options was empty' );
		return false;
	}

	// The user id, integer
	if ( empty( $lianaautomation_wpf_options['lianaautomation_user'] ) ) {
		error_log( 'lianaautomation_options lianaautomation_user was empty' );
		return false;
	}
	$user = $lianaautomation_wpf_options['lianaautomation_user'];

	// Hexadecimal secret string
	if ( empty( $lianaautomation_wpf_options['lianaautomation_key'] ) ) {
		error_log(
			'lianaautomation_wpf_options lianaautomation_key was empty!'
		);
		return false;
	}
	$secret = $lianaautomation_wpf_options['lianaautomation_key'];

	// The base url for our API installation
	if ( empty( $lianaautomation_wpf_options['lianaautomation_url'] ) ) {
		error_log(
			'lianaautomation_wpf_options lianaautomation_url was empty!'
		);
		return false;
	}
	$url = $lianaautomation_wpf_options['lianaautomation_url'];

	// The realm of our API installation, all caps alphanumeric string
	if ( empty( $lianaautomation_wpf_options['lianaautomation_realm'] ) ) {
		error_log(
			'lianaautomation_wpf_options lianaautomation_realm was empty!'
		);
		return false;
	}
	$realm = $lianaautomation_wpf_options['lianaautomation_realm'];

	// The channel ID of our automation
	if ( empty( $lianaautomation_wpf_options['lianaautomation_channel'] ) ) {
		error_log(
			'lianaautomation_wpf_options lianaautomation_channel was empty!'
		);
		return false;
	}
	$channel = $lianaautomation_wpf_options['lianaautomation_channel'];

	/**
	* General variables
	*/
	$basePath    = 'rest';             // Base path of the api end points
	$contentType = 'application/json'; // Content will be send as json
	$method      = 'POST';             // Method is always POST

	// Build the identity array
	$identity = array();
	if ( ! empty( $email ) ) {
		$identity['email'] = $email;
	}
	if ( ! empty( $liana_t ) ) {
		$identity['token'] = $liana_t;
	}
	if ( ! empty( $sms ) ) {
		$identity['sms'] = $sms;
	}

	// Bail out if no identities found
	if ( empty( $identity ) ) {
		return false;
	}

	// Import Data
	$path = 'v1/import';

	$data = array(
		'channel'       => $channel,
		'no_duplicates' => false,
		'data'          => array(
			array(
				'identity' => $identity,
				'events'   => array(
					array(
						'verb'  => 'formsend',
						'items' => $wpFormsArray,
					),
				),
			),
		),
	);

	// Encode our body content data
	$data = json_encode( $data );
	// Get the current datetime in ISO 8601
	$date = date( 'c' );
	// md5 hash our body content
	$contentMd5 = md5( $data );
	// Create our signature
	$signatureContent = implode(
		"\n",
		array(
			$method,
			$contentMd5,
			$contentType,
			$date,
			$data,
			"/{$basePath}/{$path}",
		),
	);
	$signature        = hash_hmac( 'sha256', $signatureContent, $secret );
	// Create the authorization header value
	$auth = "{$realm} {$user}:" . $signature;

	// Create our full stream context with all required headers
	$ctx = stream_context_create(
		array(
			'http' => array(
				'method'  => $method,
				'header'  => implode(
					"\r\n",
					array(
						"Authorization: {$auth}",
						"Date: {$date}",
						"Content-md5: {$contentMd5}",
						"Content-Type: {$contentType}",
					)
				),
				'content' => $data,
			),
		)
	);

	// Build full path, open a data stream, and decode the json response
	$fullPath = "{$url}/{$basePath}/{$path}";
	$fp       = fopen( $fullPath, 'rb', false, $ctx );

	// If LianaAutomation API settings is invalid
	// or endpoint is not working properly, bail out
	if ( ! $fp ) {
		return false;
	}
	$response = stream_get_contents( $fp );
	$response = json_decode( $response, true );
}
add_action( 'wpforms_process_entry_save', 'lianaautomation_wpf_process_entry_save', 10, 4 );
