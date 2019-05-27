<?php

class Transcribe_relationship_filter_ext
{
	public static $name         = 'Transcribe Relationship Filter';
	public $version             = '1.0';
	public static $author       = 'Justin MacNeil';
	public static $author_url   = 'https://justinmacneil.ca/';
	public static $description  = 'Filter relationship fields by matching Transcribe language';
	var $settings               = array();

	public function __construct($settings='')
	{
		$this->settings = $settings;
	}

	function activate_extension()
	{
		$this->settings = array(
			'include_field_ids' => '*',
			'exclude_field_ids' => '',
		);
		
		
		$data = array(
			'class'     => __CLASS__,
			'method'    => 'filter_relationships_display_field_options',
			'hook'      => 'relationships_display_field_options',
			'settings'  => serialize($this->settings),
			'priority'  => 10,
			'version'   => $this->version,
			'enabled'   => 'y'
		);
	
		ee()->db->insert('extensions', $data);
	}

	function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
	
		if ($current < '1.0')
		{
			// Update to version 1.0
		}
	
		ee()->db->where('class', __CLASS__);
		ee()->db->update(
			'extensions',
			array('version' => $this->version)
		);
	}

	function disable_extension()
	{
		ee()->db->where('class', __CLASS__);
		ee()->db->delete('extensions');
	}

	function filter_relationships_display_field_options($entries, $field_id, $settings)
	{
		$included_field_ids = explode(',', $this->settings['include_field_ids']);
		$excluded_field_ids = explode(',', $this->settings['exclude_field_ids']);

		foreach($included_field_ids as &$included_field_id) {
			$included_field_id = trim($included_field_id);
			if($included_field_id != '*') {
				$included_field_id = (int)$included_field_id;
			}
		}

		foreach($excluded_field_ids as &$excluded_field_id) {
			$excluded_field_id = (int)trim($excluded_field_id);
		}

		if(in_array($field_id, $excluded_field_ids)) {
			return;
		}

		if(!in_array($field_id, $included_field_ids) && !in_array('*', $included_field_ids)) {
			return;
		}

		$entry_language = null;
		$matching_language_entries = array();

		if(!ee('Addon')->get('transcribe')) {
			return;
		}

		$entry_language_query = ee()->db->select('language_id')
			->from('transcribe_entries_languages')
			->where('entry_id',  $settings['entry_id'])
			->get();
		$entry_language = $entry_language_query->row('language_id');

		if($entry_language) {
			$matching_language_entry_query = ee()->db->select('entry_id')
				->from('transcribe_entries_languages')
				->where('language_id', $entry_language)
				->get();

			foreach($matching_language_entry_query->result_array() as &$language_item) {
				$matching_language_entries[] = $language_item['entry_id'];
			}
			if(count($matching_language_entries) > 0) {
				$entries->filter('entry_id', 'IN', $matching_language_entries);
			}
		}
	}

	function settings()
	{

		$settings = array();

		$settings['include_field_ids'] = array('i', '', '*');
		$settings['exclude_field_ids'] = array('i', '');

		return $settings;
	}

}
