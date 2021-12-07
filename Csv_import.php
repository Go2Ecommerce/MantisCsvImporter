<?php
class Csv_importPlugin extends MantisPlugin
{
	function register() {
		$this->name = 'CsvImporter Go2Ecommerce';
		$this->description = 'Plugin to import issues from csv';
		$this->version = '1.0.0';
		$this->requires = array( 'MantisCore' => '2.0.0' );
		$this->author = 'michal@go2ecommerce.pl';
		$this->contact = '';
		$this->url = 'https://agencja-ecommerce.pl';
		$this->page = 'config';
	}

	function config() {
		return array(
			'import_issues_threshold'	=> MANAGER ,
			);
	}

	function hooks() {
		return array(
			'EVENT_MENU_MAIN' => 'menu',
		);
	}

	function menu() {
        $t_menu[] = array(
            'title' => plugin_lang_get( 'manage_issues_link' ),
            'url' => plugin_page( 'import_issues_page_init' ),
            'access_level' => plugin_config_get( 'import_issues_threshold' ),
            'icon' => 'fa-file'
        );
        return $t_menu;
    }
}
