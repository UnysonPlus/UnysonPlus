<?php if (!defined('FW')) die('Forbidden');

/**
 * PHP Version: 7.4 or higher
 */

class _FW_Available_Extensions_Register extends FW_Type_Register {
	/**
	 * @param FW_Type $type
	 * @return bool
	 */
	protected function validate_type(FW_Type $type) {
		return $type instanceof FW_Available_Extension;
	}
}