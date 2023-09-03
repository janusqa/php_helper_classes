<?php
defined('_SPEXEC') or die('Restricted access');
define('_SPPREPAREDSTATEMENT', 1);
?>
<?php
class spPreparedStatement
{

	private $_prepared_stmt = null;
	private $_result_array = null;
	private $_stmt = null;
	private $_db = null;
	private $_stmt_selection = null;

	public function __construct($db, $statement)
	{
		$this->_db = &$db;
		$this->_stmt_selection = $statement;

		// --- Begin Prepared SQL Statements
		$this->_prepared_stmt['PS_CUSTOM'] 		= array('tpl_query' => null, 'tpl_query_signature' => null, 'tpl_results' => null);
		$this->_prepared_stmt['PS_LOG_ERROR_DB']	= array('tpl_query' => 'INSERT INTO spt_errorlog (spc_id, spc_errfile, spc_errline, spc_ierrcode, spc_serrcode, spc_errmsg, spc_errstrace, spc_timestamp) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', 'tpl_query_signature' => 'ssiissss', 'tpl_results' => false);
		// --- End Prepared SQL Statements
	}

	public function prepare()
	{
		if (!isset($this->_stmt)) {
			$this->_stmt = mysqli_stmt_init($this->_db->getConnection());
		}

		if (isset($this->_stmt) && ($this->_stmt instanceof mysqli_stmt)) {
			if (isset($this->_prepared_stmt[$this->_stmt_selection]['tpl_query'])) {
				if (mysqli_stmt_prepare($this->_stmt, $this->_prepared_stmt[$this->_stmt_selection]['tpl_query'])) {
					return true;
				} else {
					// TODO: ERROR:
					trigger_error('Class [' . get_class($this) . "] : [" . mysqli_stmt_errno($this->_stmt) . "]" . mysqli_stmt_error($this->_stmt), E_USER_WARNING);
				}
			} else {
				// TODO: ERROR:
				trigger_error('Class [' . get_class($this) . "] : No query was specified.", E_USER_WARNING);
			}
		} else {
			// TODO: ERROR:
			trigger_error('Class [' . get_class($this) . "] : [" . mysqli_stmt_errno($this->_stmt) . "]" . mysqli_stmt_error($this->_stmt), E_USER_WARNING);
		}

		return false;
	}

	private function setargs($args)
	{
		if (isset($args)) {
			if (is_array($args) && (isset($this->_prepared_stmt[$this->_stmt_selection]['tpl_query_signature'])) && (strlen($this->_prepared_stmt[$this->_stmt_selection]['tpl_query_signature']) == count($args))) {
				if (call_user_func_array('mysqli_stmt_bind_param', array_merge(array($this->_stmt, $this->_prepared_stmt[$this->_stmt_selection]['tpl_query_signature']), $this->refValues($args)))) {
					return true;
				} else {
					// TODO: ERROR:
					trigger_error('Class [' . get_class($this) . "] : [" . mysqli_stmt_errno($this->_stmt) . "]" . mysqli_stmt_error($this->_stmt), E_USER_WARNING);
				}
			} else {
				// TODO: ERROR:
				trigger_error('Class [' . get_class($this) . "] : Incorrect number of arguments or invalid argument types supplied for query of type [{$this->_stmt_selection}].", E_USER_WARNING);
			}
		} else {
			return true;
		}
		return false;
	}

	public function execute($args = null)
	{

		$meta = null;
		$attributes = null;
		$temp_array = null;
		$table_col = null;

		if (isset($this->_stmt) && ($this->_stmt instanceof mysqli_stmt)) {
			if ($this->setargs($args)) {
				if (mysqli_stmt_execute($this->_stmt)) {
					if ($this->_prepared_stmt[$this->_stmt_selection]['tpl_results']) {
						$this->free_query_result($this->_result_array);
						$temp_array = $this->_result_array;
						$this->_result_array = null;
						unset($temp_array);
						if (mysqli_stmt_store_result($this->_stmt)) {
							if ((($meta = mysqli_stmt_result_metadata($this->_stmt)) != false) && isset($meta) && ($meta instanceof mysqli_result)) {
								while (($field = mysqli_fetch_field($meta)) != false) {
									$attributes[] = &$table_col[$field->name];
								}
								mysqli_free_result($meta);
								unset($meta);
								if (call_user_func_array('mysqli_stmt_bind_result', array_merge(array($this->_stmt), $attributes)) != false) {
									while (mysqli_stmt_fetch($this->_stmt)) {
										foreach ($table_col as $key => $val) {
											$temp_array[$key] = $val;
										}
										$this->_result_array[] = $temp_array;
										// To loop through _result_array you can (remember _result_array is 2-dimensional)
										// $print_headers = true;
										// foreach ($_result_array as $table_row ) {
										//	   if ($print_headers) {
										//         foreach ($table_row as $key => $table_col) {
										//             echo $key;
										//         }
										//         $print_headers = false;
										//     }
										//     foreach ($table_row as $key => $table_col) {
										//         echo $table_col;
										//     }
										// }
									}
									unset($table_col);
									unset($attributes);
									unset($temp_array);
									mysqli_stmt_free_result($this->_stmt);
									return true;
								} else {
									unset($table_col);
									unset($attributes);
									unset($temp_array);
									mysqli_stmt_free_result($this->_stmt);
									// TODO: ERROR:
									trigger_error('Class [' . get_class($this) . "] : [" . mysqli_stmt_errno($this->_stmt) . "]" . mysqli_stmt_error($this->_stmt), E_USER_WARNING);
								}
							} else {
								mysqli_stmt_free_result($this->_stmt);
								// TODO: ERROR:
								trigger_error('Class [' . get_class($this) . "] : [" . mysqli_stmt_errno($this->_stmt) . "]" . mysqli_stmt_error($this->_stmt), E_USER_WARNING);
							}
						} else {
							// TODO: ERROR:
							trigger_error('Class [' . get_class($this) . "] : [" . mysqli_stmt_errno($this->_stmt) . "]" . mysqli_stmt_error($this->_stmt), E_USER_WARNING);
						}
					} else {
						return true;
					}
				} else {
					// TODO: ERROR:
					trigger_error('Class [' . get_class($this) . "] : [" . mysqli_stmt_errno($this->_stmt) . "]" . mysqli_stmt_error($this->_stmt), E_USER_WARNING);
				}
			}
		} else {
			// TODO: ERROR:
			trigger_error('Class [' . get_class($this) . "] : There is no Prepared Statement. You must first prepre a statement before attempting to execute it.", E_USER_WARNING);
		}
		return false;
	}

	private function free_query_result(&$arr)
	{
		if (isset($arr)) {
			foreach ($arr as $key => $value) {
				unset($arr[$key]);
			}
			unset($arr);
		}
	}

	public function set_custom_stmt($tpl_query, $tpl_query_signature, $tpl_results)
	{
		if (isset($this->_stmt) && ($this->_stmt instanceof mysqli_stmt)) {
			mysqli_stmt_reset($this->_stmt);
			mysqli_stmt_close($this->_stmt);
			$this->_stmt = null;
		}
		$this->_prepared_stmt['PS_CUSTOM']['tpl_query'] = $tpl_query;
		$this->_prepared_stmt['PS_CUSTOM']['tpl_query_signature'] = $tpl_query_signature;
		$this->_prepared_stmt['PS_CUSTOM']['tpl_results'] = $tpl_results;
	}

	public function get_stmt_results()
	{
		if (isset($this->_result_array)) {
			return $this->_result_array;
		}
		return false;
	}

	private function quotesmart($input)
	{

		if (is_int($input)) {
			return $input;
		} elseif (is_float($input)) {
			return "'" . mysqli_real_escape_string($this->_db->getConnection(), number_format($input, ((strlen($input) - strlen(intval($input))) - 1), '.', '')) . "'";
		} elseif (is_bool($input)) {
			return $input ? '1' : '0';
		} elseif (is_null($input)) {
			return 'null';
		} else {
			return "'" . mysqli_real_escape_string($this->_db->getConnection(), $input) . "'";
		}
	}

	private function refValues($arr)
	{
		$refs = null;

		foreach ($arr as $key => $value) {
			// $arr[$key] = $this->quotesmart($arr[$key]);
			if ((version_compare(phpversion(), '5.3') >= 0)) { //Reference is required for PHP 5.3+
				$refs[$key] = &$arr[$key];
			}
		}

		if ((version_compare(phpversion(), '5.3') >= 0)) { //Reference is required for PHP 5.3+
			return $refs;
		}

		return $arr;
	}

	public function __set($property, $value)
	{
		trigger_error('Class [' . get_class($this) . "] does not support the property: [{$property}].", E_USER_ERROR);
	}

	public function __get($property)
	{
		trigger_error('Class [' . get_class($this) . "] does not support the property: [{$property}].", E_USER_ERROR);
	}

	public function __call($function, $args)
	{
		trigger_error('Class [' . get_class($this) . "] does not support the function: [{$function}].", E_USER_ERROR);
	}

	public function __destruct()
	{
		try {
			if (isset($this->_stmt) && ($this->_stmt instanceof mysqli_stmt)) {
				//mysqli_stmt_close($this->_stmt);
				unset($this->_stmt);
			}
			if (isset($this->_result_array)) {
				$this->free_query_result($this->_result_array);
				unset($this->_result_array);
			}
			if (isset($this->_prepared_stmt)) {
				$this->free_query_result($this->_prepared_stmt);
				unset($this->_prepared_stmt);
			}
			if (isset($this->_db)) {
				unset($this->_db);
			}
		} catch (Exception $e) {
			// TODO: ERROR: Log error to file.
			// DO NOTHING.
		}
	}
}
?>
