<?php if (!defined('FW')) die('Forbidden');

/**
 * PHP Version: 7.4 or higher
 */

/**
 * Used to define extension in framework Available Extensions list
 * @since 2.5.12
 */
class FW_Available_Extension extends FW_Type {
	/**
	 * Extension (directory) name
	 */
	private string $name;

	/**
	 * @var null|string Parent extension name
	 */
	private ?string $parent = null;

	/**
	 * @var bool If visible in extensions list
	 */
	private bool $display = true;

	/**
	 * @var string
	 */
	private string $title;

	/**
	 * @var string
	 */
	private string $description;

	/**
	 * @var string Image url
	 */
	private string $thumbnail = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIW2PUsHf9DwAC8AGtfm5YCAAAAABJRU5ErkJgggAA';

	/**
	 * @var array{source: string, opts: array<mixed>}
	 * @see FW_Ext_Download_Source::get_type() is id
	 * @see FW_Ext_Download_Source
	 */
	private array $download_source = [];

	/**
	 * @return bool
	 * @since 2.6.0
	 */
	public function is_valid(): bool {
		return (
			!empty($this->name) && is_string($this->name)
			&&
			!empty($this->title) && is_string($this->title)
			&&
			!empty($this->description) && is_string($this->description)
			&&
			!empty($this->download_source)
			&&
		    is_bool($this->display)
			&&
			($this->parent === null || is_string($this->parent))
		);
	}

	/**
	 * @return string
	 * @internal
	 */
	final public function get_type(): string {
		return $this->get_name();
	}

	public function get_name(): string {
		return $this->name;
	}

	public function set_name(string $name): void {
		$this->name = $name;
	}

	public function get_parent(): ?string {
		return $this->parent;
	}

	public function set_parent(?string $parent): void {
		$this->parent = $parent;
	}

	public function get_display(): bool {
		return $this->display;
	}

	public function set_display(bool $display): void {
		$this->display = $display;
	}

	public function get_title(): string {
		return $this->title;
	}

	public function set_title(string $title): void {
		$this->title = $title;
	}

	public function get_description(): string {
		return $this->description;
	}

	public function set_description(string $description): void {
		$this->description = $description;
	}

	public function get_thumbnail(): string {
		return $this->thumbnail;
	}

	public function set_thumbnail(string $thumbnail): void {
		$this->thumbnail = $thumbnail;
	}

	/**
	 * @return array{source: string, opts: array<mixed>}
	 */
	public function get_download_source(): array {
		return $this->download_source;
	}

	/**
	 * @param string $id
	 * @param array<mixed> $data
	 */
	public function set_download_source(string $id, array $data): void {
		$this->download_source = [
			'source' => $id,
			'opts' => $data
		];
	}
}
