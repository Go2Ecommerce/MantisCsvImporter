<?php
class CsvImportPlugin extends MantisPlugin
{
	function register() {
		$this->name = 'CsvImporter Go2Ecommerce';
		$this->description = 'Plugin to import issues from csv';
		$this->version = '1.0.0';
		$this->requires = array( 'MantisCore' => '2.0.0' );
		$this->author = 'Bug 4220 Team';
		$this->contact = '';
		$this->url = '';
		$this->page = 'config';
	}

	function config() {
		return array(
			'import_issues_threshold'	=> MANAGER ,
			);
	}

	function hooks() {
		return array(
			'EVENT_MENU_MANAGE' => 'csv_import_menu',
		);
	}

	function csv_import_menu() {
		return array(
			'<a href="' . plugin_page( 'import_issues_page_init' ) . '">' . plugin_lang_get( 'manage_issues_link' ) . '</a>',
		);
	}
}
