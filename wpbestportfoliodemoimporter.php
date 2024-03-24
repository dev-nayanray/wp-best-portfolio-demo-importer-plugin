<?php
/*
Plugin Name: Wp Best Portfolio Demo Importer
Description: Import demo content for Your Theme.
Version: 1.0
Author: Nayan Ray
License: GPL v2 or later
*/

// Include the main class file
require_once plugin_dir_path(__FILE__) . 'inc/class-demo-importer.php';

// Instantiate the class
new wpbestportfolio_Theme_Demo_Importer();