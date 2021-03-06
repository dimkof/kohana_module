<?php defined('SYSPATH') or die ('No direct script access.');

/**
 * Contains methods to help with dealing with PayPal, specifically IPN.
 *
 * @package    XM
 * @category   Cart
 * @author     XM Media Inc.
 * @copyright  (c) 2012 XM Media Inc.
 */
class XM_PayPal {
	/**
	 * Changes the character set of the PayPal data if needed.
	 * Most data from PayPal comes in the charset "windows-1252" and this will convert it to UTF-8.
	 *
	 * @param  array  $ipn_data  The array of IPN data, likely the entire $_POST array.
	 * @return  array
	 */
	public static function change_ipn_charset($ipn_data) {
		if (array_key_exists('charset', $ipn_data) && ($charset = $ipn_data['charset'])) {
			// Ignore if same as our default
			if ($charset == Kohana::$charset) {
				return $ipn_data;
			}

			// Otherwise convert all the values
			foreach($ipn_data as $key => $value) {
				$ipn_data[$key] = mb_convert_encoding($value, 'utf-8', $charset);
			}

			// And store the charset values for future reference
			$ipn_data['charset'] = 'utf-8';
			$ipn_data['charset_original'] = $charset;
		}

		return $ipn_data;
	} // function change_ipn_charset

	/**
	 * Performs the post back to PayPal to verify the data we've received.
	 * If `FALSE` is returned, there was a problem connecting to PayPal.
	 * This uses the actual $_POST array as PayPal doesn't like having anything changed.
	 * If it's successful, an array will be returned with 2 keys:
	 *
	 * Type      | Key        | Description
	 * ----------|------------|-----------------------
	 * `boolean` | verified   | If the transaction was verified or not. (If "VERIFIED" was found in the response from PayPal.)
	 * `string`  | paypal_response | The full PayPal response as a string.
	 *
	 * @return  array
	 */
	public static function do_post_back() {
		$paypal_response = '';
		$verified = FALSE;

		// read the post from PayPal system and add 'cmd'
		$paypal_post = 'cmd=_notify-validate';
		// clean and compile the data for PayPal (otherwise it will be rejected)
		foreach ($_POST as $key => $value) {
			$value = urlencode(stripslashes($value));
			$value = preg_replace('/(.*[^%^0^D])(%0A)(.*)/i', '${1}%0D%0A${3}', $value);
			$paypal_post .= "&$key=$value";
		}

		// post back to PayPal system to validate what we've received
		$header = "POST /cgi-bin/webscr HTTP/1.0\r\n"
			. "Content-Type: application/x-www-form-urlencoded\r\n"
			. "Content-Length: " . strlen($paypal_post) . "\r\n\r\n";
		$fp = fsockopen('ssl://www.paypal.com', 443, $errno, $errstr, 30);

		// HTTP Error
		if ( ! $fp) {
			return FALSE;

		} else {
			fputs($fp, $header . $paypal_post);

			while ( ! feof($fp)) {
				$res = fgets($fp, 1024);
				$paypal_response .= $res;
				if (strcmp($res, "VERIFIED") == 0) {
					$verified = TRUE;
				}
			} // while

			fclose ($fp);
		}

		return array(
			'verified' => $verified,
			'paypal_response' => $paypal_response,
		);
	} // funciton do_post_back
}