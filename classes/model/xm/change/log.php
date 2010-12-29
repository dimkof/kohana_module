<?php defined('SYSPATH') or die ('No direct script access.');

/**
 * This model was created using cl4_ORM and should provide
 * standard Kohana ORM features in additon to cl4-specific features.
 */
class Model_XM_Change_Log extends ORM {
	protected $_table_names_plural = FALSE;
	protected $_table_name = 'change_log';
	protected $_primary_val = 'event_timestamp'; // default: name (column used as primary value)
	public $_table_name_display = 'Change Log'; // cl4-specific
	protected $_log = FALSE; // don't log changes (will create loop)

	// column labels
	protected $_labels = array(
		'id' => 'ID',
		'event_timestamp' => 'Event Timestamp',
		'user_id' => 'User',
		'table_name' => 'Table Name',
		'record_pk' => 'Record Primary Key',
		'query_type' => 'Query Type',
		'row_count' => 'Row Count',
		'query_time' => 'Query Time',
		'sql' => 'SQL',
		'changed' => 'Changed',
	);

	// default sorting
	protected $_sorting = array(
		'event_timestamp' => 'DESC',
	);

	// relationships
	protected $_has_one = array(
		'user' => array(
			'model' => 'user',
			'through' => 'user',
			'foreign_key' => 'id',
			'far_key' => 'user_id',
		),
	);

	// column definitions
	protected $_table_columns = array(
		/**
		* see http://v3.kohanaphp.com/guide/api/Database_MySQL#list_columns for all possible column attributes
		* see the modules/cl4/config/cl4orm.php for a full list of cl4-specific options and documentation on what the options do
		*/
		'id' => array(
			'field_type' => 'hidden',
			'edit_flag' => TRUE,
			'display_order' => 10,
		),
		'event_timestamp' => array(
			'field_type' => 'datetime',
			'list_flag' => TRUE,
			'edit_flag' => TRUE,
			'search_flag' => TRUE,
			'view_flag' => TRUE,
			'display_order' => 20,
		),
		'user_id' => array(
			'field_type' => 'select',
			'list_flag' => TRUE,
			'edit_flag' => TRUE,
			'search_flag' => TRUE,
			'view_flag' => TRUE,
			'display_order' => 30,
			'field_options' => array(
				'source' => array(
					'source' => 'model',
					'data' => 'user',
				),
			),
		),
		'table_name' => array(
			'field_type' => 'text',
			'list_flag' => TRUE,
			'edit_flag' => TRUE,
			'search_flag' => TRUE,
			'view_flag' => TRUE,
			'display_order' => 40,
			'field_attributes' => array(
				'maxlength' => 64,
			),
		),
		'record_pk' => array(
			'field_type' => 'text',
			'list_flag' => TRUE,
			'edit_flag' => TRUE,
			'search_flag' => TRUE,
			'view_flag' => TRUE,
			'display_order' => 50,
			'field_attributes' => array(
				'maxlength' => 11,
				'size' => 11,
			),
		),
		'query_type' => array(
			'field_type' => 'text',
			'list_flag' => TRUE,
			'edit_flag' => TRUE,
			'search_flag' => TRUE,
			'view_flag' => TRUE,
			'display_order' => 60,
			'field_attributes' => array(
				'maxlength' => 12,
				'size' => 12,
			),
		),
		'row_count' => array(
			'field_type' => 'text',
			'list_flag' => TRUE,
			'edit_flag' => TRUE,
			'search_flag' => TRUE,
			'view_flag' => TRUE,
			'display_order' => 70,
			'field_attributes' => array(
				'maxlength' => 11,
				'size' => 11,
			),
		),
		'query_time' => array(
			'field_type' => 'text',
			'list_flag' => TRUE,
			'edit_flag' => TRUE,
			'search_flag' => TRUE,
			'view_flag' => TRUE,
			'display_order' => 80,
			'field_attributes' => array(
				'maxlength' => 'unknown',
				'size' => 'unknown',
			),
		),
		'sql' => array(
			'field_type' => 'text',
			'list_flag' => FALSE,
			'edit_flag' => TRUE,
			'search_flag' => TRUE,
			'view_flag' => TRUE,
			'display_order' => 90,
			'field_attributes' => array(
				'maxlength' => 15000,
			),
		),
		'changed' => array(
			'field_type' => 'text',
			'list_flag' => FALSE,
			'edit_flag' => TRUE,
			'search_flag' => TRUE,
			'view_flag' => TRUE,
			'display_order' => 100,
			'field_attributes' => array(
				'maxlength' => 5000,
			),
		),
	);

	protected $_max_changed_length = 1000;

	public function add_change_log($data) {
		// of no user id was passed, then try to find one using the
		if ( ! array_key_exists('user_id', $data)) {
			$data['user_id'] = $this->get_user_id();
		}

		// shorten any changed fields that are longer than _max_changed_length
		if (array_key_exists('changed', $data)) {
			if (is_array($data['changed']) && $this->_max_changed_length !== NULL) {
				foreach ($data['changed'] as $column => $value) {
					if (strlen($value) > $this->_max_changed_length) {
						$data['changed'][$column] = Text::limit_chars($value, $this->_max_changed_length);
					}
				} // foreach
			} // if

			$data['changed'] = serialize($data['changed']);
		}

		return $this->values($data)
			->save();
	}

	protected function get_user_id() {
		$user = Auth::instance()->get_user();
		if ( ! empty($user)) {
			return $user->id;
		} else {
			return 0;
		}
	}
} // class