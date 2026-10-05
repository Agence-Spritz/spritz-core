<?php

if (!defined('ABSPATH')) exit;

/**
 * Listes JSON-LD saisies « une ligne par élément » dans le Customizer,
 * stockées sous forme de tableau (format attendu par json-ld/jsonld-generator.php).
 */
class Spritz_Customize_Lines_Setting extends WP_Customize_Setting
{
	public $type = 'option';

	/** @var string[] Clés de chaque ligne, dans l'ordre des colonnes séparées par « | » */
	public $columns = [];

	/** @var string[] Colonnes dont les valeurs multiples sont séparées par « ; » (stockées une par ligne) */
	public $multiline_columns = [];

	public function value()
	{
		$value = parent::value();

		// WordPress passe parfois une sentinelle (stdClass) pour détecter une option inexistante : la renvoyer telle quelle
		return is_object($value) ? $value : $this->to_text($value);
	}

	public function sanitize($value)
	{
		return sanitize_textarea_field((string) $value);
	}

	protected function update($value)
	{
		return update_option($this->id_data['base'], $this->to_rows($value));
	}

	public function _preview_filter($original)
	{
		$value = $this->post_value();
		return null === $value ? $original : $this->to_rows($value);
	}

	private function to_rows($text): array
	{
		$rows = [];
		foreach (preg_split('/\r\n|\r|\n/', (string) $text) as $line) {
			if (trim($line) === '') continue;

			$cells = array_map('trim', explode('|', $line));
			$row = [];
			foreach ($this->columns as $i => $key) {
				$cell = $cells[$i] ?? '';
				if (in_array($key, $this->multiline_columns, true)) {
					$cell = implode("\n", array_filter(array_map('trim', explode(';', $cell))));
				}
				$row[$key] = $cell;
			}
			$rows[] = $row;
		}
		return $rows;
	}

	private function to_text($rows): string
	{
		if (!is_array($rows)) return is_scalar($rows) ? (string) $rows : '';

		$lines = [];
		foreach ($rows as $row) {
			$cells = [];
			foreach ($this->columns as $key) {
				$cell = (string) ($row[$key] ?? '');
				if (in_array($key, $this->multiline_columns, true)) {
					$cell = implode('; ', array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $cell))));
				}
				$cells[] = $cell;
			}
			$lines[] = rtrim(implode(' | ', $cells), ' |');
		}
		return implode("\n", $lines);
	}
}
