<?php defined('SYSPATH') or die ('No direct script access.');

/**
 * This model was created using cl4_ORM and should provide
 * standard Kohana ORM features in additon to cl4-specific features.
 */
class Model_XM_Contact extends ORM {
	protected $_table_names_plural = FALSE;
	protected $_table_name = 'contact';
	public $_table_name_display = 'Contact'; // cl4-specific

	// column definitions
	protected $_table_columns = array(
		/**
		* see http://v3.kohanaphp.com/guide/api/Database_MySQL#list_columns for all possible column attributes
		* see the modules/cl4/config/cl4orm.php for a full list of cl4-specific options and documentation on what the options do
		*/
		'id' => array(
			'field_type' => 'hidden',
			'edit_flag' => TRUE,
			'is_nullable' => FALSE,
		),
		'name' => array(
			'field_type' => 'text',
			'list_flag' => TRUE,
			'edit_flag' => TRUE,
			'search_flag' => TRUE,
			'view_flag' => TRUE,
			'is_nullable' => FALSE,
			'field_attributes' => array(
				'maxlength' => 100,
			),
		),
		'email' => array(
			'field_type' => 'text',
			'list_flag' => TRUE,
			'edit_flag' => TRUE,
			'search_flag' => TRUE,
			'view_flag' => TRUE,
			'is_nullable' => FALSE,
			'field_attributes' => array(
				'maxlength' => 100,
			),
		),
		'phone' => array(
			'field_type' => 'text',
			'list_flag' => TRUE,
			'edit_flag' => TRUE,
			'search_flag' => TRUE,
			'view_flag' => TRUE,
			'is_nullable' => FALSE,
			'field_attributes' => array(
				'maxlength' => 15,
				'size' => 15,
			),
		),
		'message' => array(
			'field_type' => 'textarea',
			'list_flag' => TRUE,
			'edit_flag' => TRUE,
			'search_flag' => TRUE,
			'view_flag' => TRUE,
			'is_nullable' => FALSE,
		),
		'date_submitted' => array(
			'field_type' => 'datetime',
			'list_flag' => TRUE,
			'search_flag' => TRUE,
			'view_flag' => TRUE,
			'is_nullable' => FALSE,
		),
		'ip_address' => array(
			'field_type' => 'text',
			'list_flag' => TRUE,
			'search_flag' => TRUE,
			'view_flag' => TRUE,
			'is_nullable' => FALSE,
			'field_attributes' => array(
				'maxlength' => 15,
				'size' => 15,
			),
		),
	);

	/**
	 * @var timestamp $_created_column The time this row was created.
	 *
	 * Use format => 'Y-m-j H:i:s' for DATETIMEs and format => TRUE for TIMESTAMPs.
	 */
	protected $_created_column = array(
		'column' => 'date_submitted',
		'format' => 'Y-m-j H:i:s'
	);

	/**
	 * Label definitions for validation
	 *
	 * @return array
	 */
	public function labels() {
		return array(
			'id' => 'ID',
			'name' => 'Name',
			'email' => 'Email',
			'phone' => 'Phone',
			'message' => 'Message',
			'date_submitted' => 'Date Submitted',
			'ip_address' => 'IP Address',
		);
	}

	/**
	 * Rule definitions for validation
	 *
	 * @return array
	 */
	public function rules() {
		return array(
			'name' => array(
				'not_empty' => NULL,
				'min_length' => array(2),
			),
			'message' => array(
				'not_empty' => NULL,
				'min_length' => array(5),
			),
			'email' => array(array($this, 'check_for_email_or_phone')),
		);
	}

	/**
	 * Filter definitions for validation
	 *
	 * @return array
	 */
	public function filters() {
		return array(
		    TRUE => array(array('trim')),
		);
	}

	public function check_for_email_or_phone(Validate $validate, $field) {
		if (empty($this->email) && empty($this->phone)) {
			$validate->error('email', 'email_or_phone');
		} else {
			if ( ! empty($this->email) && ! Validate::email($this->email)) {
				$validate->error('email', 'email');
			}

			if ( ! empty($this->phone) && ! Validate::phone($this->phone)) {
				$validate->error('phone', 'phone');
			}
		} // if
	} // function check_for_email_or_phone

	public function save_and_mail($to, $to_name) {
		try {
			if ( ! Kohana::load(Kohana::find_file('vendor', 'recaptcha/recaptchalib'))) {
				throw new Kohana_Exception('Unable to find reCAPTCHA. Ensure it\'s in a vendor folder');
			}

			$errors = FALSE;

			if ( ! empty($_POST)) {
				// check to see if the recaptcha fields are received before using this
				// if not, then don't check recaptcha
				if ( ! isset($_POST['recaptcha_challenge_field']) || ! isset($_POST['recaptcha_response_field'])) $recaptcha_received = FALSE;
				else $recaptcha_received = TRUE;
				if ($recaptcha_received) $resp = recaptcha_check_answer(RECAPTCHA_PRIVATE_KEY, $_SERVER['REMOTE_ADDR'], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);
				if ( ! $recaptcha_received || ! $resp->is_valid) {
					$errors = TRUE;
					Message::add(Kohana::message('contact', 'recaptcha'), Message::$error);
					Message::add('reCAPTCHA said: ' . $resp->error, Message::$debug);
				}

				// set the values within the object
				$this->save_values($_POST);

				// validate the object and only continue (to save) if there haven't been errors so far
				if ( ! $errors) {
					$this->ip_address = $_SERVER['REMOTE_ADDR'];
					$this->save();
				}

				if ( ! $errors) {
					try {
						$mail = new Mail();
						$mail->AddAddress($to, $to_name);
						$mail->add_log_bcc();
						$mail->Subject = 'Website Contact Us Submission - ' . date('Y-m-d H:m');
						$mail->IsHTML(TRUE);
						if ( ! empty($this->email)) $mail->AddReplyTo($this->email, $this->name);

						$mail->Body = '<p>The following submission was received from the Website Contact Us form:</p>';
						$mail->Body .= $this->set_mode('view')
							->set_view_options_for_email()
							->get_view();

						$mail->Send();

						// clear the object because it's been sent
						$this->clear();

						Message::add(Kohana::message('contact', 'sent'), Message::$notice);

					} catch (Exception $e) {
						cl4::exception_handler($e);
						Message::add(Kohana::message('contact', 'problem_sending'), Message::$error);
					}
				} // if
			}

			$this->set_mode('add');

		} catch (ORM_Validation_Exception $e) {
			Message::add('Please fix the following errors: ' . Message::add_validation_errors($e, 'contact'), Message::$error);
		} catch (Exception $e) {
			cl4::exception_handler($e);
			Message::add(Kohana::message('contact', 'error'), Message::$error);
		}

		return $errors;
	} // function save_and_mail
} // class