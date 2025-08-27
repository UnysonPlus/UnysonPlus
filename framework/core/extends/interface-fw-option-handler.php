<?php 
/**
 * PHP Version: 7.4 or higher
 */
if (!defined('FW')) die('Forbidden');

/**
 * @deprecated since 2.5.0
 * Will be removed soon https://github.com/ThemeFuse/Unyson/issues/1937
 */
interface FW_Option_Handler
{
    /**
     * Get option value
     *
     * @param string $option_id
     * @param array  $option
     * @param array  $data
     * @return mixed
     */
    public function get_option_value(string $option_id, array $option, array $data = []): mixed;

    /**
     * Save option value
     *
     * @param string $option_id
     * @param array  $option
     * @param mixed  $value
     * @param array  $data
     * @return void
     */
    public function save_option_value(string $option_id, array $option, mixed $value, array $data = []): void;
}
