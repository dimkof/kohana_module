<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Minion exception
 *
 * @package    Kohana
 * @category   Minion
 * @author     Kohana Team
 * @copyright  (c) 2009-2011 Kohana Team
 * @license    http://kohanaframework.org/license
 */
class XM_Minion_Exception extends Kohana_Minion_Exception {
	/**
	 * Inline exception handler, displays the error message, source of the
	 * exception, and the stack trace of the error.
	 *
	 * Should this display a stack trace? It's useful.
	 *
	 * Should this still log? Maybe not as useful since we'll see the error on the screen.
	 *
	 * @uses    Kohana_Exception::text
	 * @param   Exception   $e
	 * @return  boolean
	 */
	public static function handler(Exception $e) {
		try {
			if ($e instanceof Minion_Exception) {
				echo $e->format_for_cli(), PHP_EOL;
			} else {
				echo Kohana_Exception::text($e), PHP_EOL;
			}

			// Log the exception
			Kohana_Exception::log($e);

			if (Kohana::$environment >= Kohana::DEVELOPMENT) {
				// display the trace when in development
				echo "--", PHP_EOL, $e->getTraceAsString(), PHP_EOL;
			} else {
				Kohana_Exception::notify($e);
			}

			$exit_code = $e->getCode();

			// Never exit "0" after an exception.
			if ($exit_code == 0) {
				$exit_code = 1;
			}

			exit($exit_code);
		} catch (Exception $e) {
			// Clean the output buffer if one exists
			ob_get_level() and ob_clean();

			// Display the exception text
			echo Kohana_Exception::text($e), PHP_EOL;

			// Exit with an error status
			exit(1);
		}
	}

	/**
	 * This is the same as Kohana_Minion_Exception but we used $this instead of $e.
	 * Kohana_Minion_Exception::format_for_cli() is broken.
	 *
	 * @return void
	 */
	public function format_for_cli() {
		return Kohana_Exception::text($this);
	}
}