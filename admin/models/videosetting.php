<?php
/**
 * video setting model file to update the  setting for  video gallery.
 *  
 * @category Apptha
 * @package Contus video Gallery 
 * @version 2.7 
 * @author Apptha Team <developers@contus.in> 
 * @copyright Copyright (C) 2014 Apptha. All rights reserved. @license GNU General Public License http://www.gnu.org/copyleft/gpl.html
 */

	class SettingsModel {
	    
		public function __construct() {
			global $wpdb;
			$this->_settingstable = $wpdb->prefix . 'hdflvvideoshare_settings';
		}


		/**
		 * Stores setting data into database
		 *
		 * @param $settingsdata        	
		 * @param $settingsdataformat
         * @return int|false - Number of row, that has been updated        	
		 */
		public function update_settings($settingsdata, $settingsdataformat) {
		    global $wpdb;
			return $wpdb->update ( $this->_settingstable, $settingsdata, array (
					'settings_id' => 1 
			), $settingsdataformat );
		}


		/**
		 * Get setting value for settings page
         * 
         * @return object {
         *     @option string uploads - Relative directory for Videogallery video files
         * }
		 */
		public function get_settingsdata() {
            global $wpdb;
			$query = 'SELECT * FROM ' . $this->_settingstable . ' WHERE settings_id = 1';
			return $wpdb->get_row ( $query );
		}
	}
