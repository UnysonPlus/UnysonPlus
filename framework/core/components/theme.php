<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Theme
 */
class _FW_Component_Theme extends FW_Component
{
	/**
	 * @internal
	 */
	protected function _init()
	{
		add_action( 'admin_notices', array( $this, '_action_admin_notices' ) );
	}

	/**
	 * @internal
	 */
	public function _action_admin_notices()
	{
		// check if requirements are not met
		$check = fw()->theme->manifest->check_requirements();

		if ( true !== $check && current_user_can( 'activate_plugins' ) ) {
			$this->_print_not_met_requirements_notice( $check );
		}
	}

	/**
	 * Print unmet requirements
	 *
	 * @param array $requirements
	 */
	private function _print_not_met_requirements_notice( $requirements )
	{
		echo '<div class="error">';
		foreach ( $requirements as $requirement ) {
			echo '<p>' . $requirement . '</p>';
		}
		echo '</div>';
	}
}
