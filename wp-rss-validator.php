<?php 
/* 
Plugin Name: RSS Validator
Plugin URI: http://andrewnorcross.com/plugins/
Description: Checks your RSS feed against the W3 RSS validator for errors
Version: 1.0
Author: Andrew Norcross
Author URI: http://andrewnorcross.com

    Copyright 2012 Andrew Norcross

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/ 

// http://feed2.w3.org/docs/soap.html

// Start up the engine 
class RSSValidator
{
	/**
	 * Static property to hold our singleton instance
	 * @var RSSValidator
	 */
	static $instance = false;


	/**
	 * This is our constructor, which is private to force the use of
	 * getInstance() to make this a Singleton
	 *
	 * @return RSSValidator
	 */
	private function __construct() {
		add_action ( 'admin_enqueue_scripts',	array(&$this, 'scripts_styles'	), 10);
		add_action ( 'wp_dashboard_setup',		array(&$this, 'add_dashboard' 	));

	}

	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return RSSValidator
	 */
	 
	public static function getInstance() {
		if ( !self::$instance )
			self::$instance = new self;
		return self::$instance;
	}

	/**
	 * Scripts and stylesheets
	 *
	 * @since 1.0
	 */

	public function scripts_styles() {
		
		$current_screen = get_current_screen();
		if ( 'dashboard' == $current_screen->base ) {
			wp_enqueue_style( 'validator-style', plugins_url('/lib/css/rss-validator.css', __FILE__) );
			wp_enqueue_script( 'ap-main-ajax', plugins_url('/lib/js/validator.init.js', __FILE__) , array('jquery'), null, true );
		}

	}

	/**
	 * Call the dashboard widget
	 *
	 * @return RSSValidator
	 */


    public function add_dashboard() {
        wp_add_dashboard_widget('rkv_rss_validator', 'RSS Feed Validation', array( &$this, 'rss_validation_widget' ));
    }


	/**
	 * Build out dashboard widget
	 *
	 * @return RSSValidator
	 */

	public function rss_validation_widget() {

		
		// display actual feed URL
		$feed = get_bloginfo('rss2_url');
		echo '<p><strong>Your RSS feed</strong>: <em>'.$feed.'</em></p>';

		// run the online validator
		$check = $this->check_rss();

		if(isset($check['success']) && $check['success'] == false ) {
			echo $check['errmsg'];
			return;
		}

		// break out data

		// OK, looks like things went OK. let's see what we have
		echo '<div class="validator_results">';
		
			// set up headers of the display
			echo '<ul class="titles">';
				echo '<li class="status">Feed Status:</li>';
				echo '<li class="errors">Errors:</li>';
				echo '<li class="warnings">Warnings:</li>';
			echo '</ul>';	

			echo '<ul class="feed_data">';
			
			if(isset($check['validity']) ) {
				$validity = ($check['validity'][0] == 'true' ? 'Valid' : 'Invalid');
				echo '<li class="status"><span class="val">'.$validity.'</span></li>';
			}

			if(isset($check['error_count']))
				echo '<li class="error_count"><span class="val">'.$check['error_count'][0].'</span></li>';

			if(isset($check['warn_count']))
				echo '<li class="warn_count"><span class="val">'.$check['warn_count'][0].'</span></li>';
			
			echo '</ul>';
		
		
		echo '</div>';

		// show optional error messages
		if(isset($check['error_list']) && !empty($check['error_list'][0]) ) {
			echo '<div class="issue_details error_details" rel="errors" style="display:none">';

			$errors = $check['error_list'][0];				
			foreach ( $errors as $error ) :
				// get variables for each warning
				$line = $error->line;
				$text = $error->text;

				echo '<p><strong>Line '.$line.':</strong> '.$text.'</p>';
		
			endforeach;

			echo $this->check_link();

			echo '</div>';
		}
			
		// show optional warning messages
		if(isset($check['warn_list']) && !empty($check['warn_list'][0]) ) {
			echo '<div class="issue_details warning_details" name="warnings" style="display:none">';

			$warnings = $check['warn_list'][0];				
			foreach ( $warnings as $warning ) :
				// get variables for each warning
				$line = $warning->line;
				$text = $warning->text;
				
				echo '<p><strong>Line '.$line.':</strong> '.$text.'</p>';
				
			endforeach;
			
			echo $this->check_link();				
			
			echo '</div>';
		}		



	}

	/**
	 * Check RSS feed on W3 API
	 *
	 * @return RSSValidator
	 */

	public function check_rss () {

		$feed		= get_bloginfo('rss2_url');
//		$feed		= 'http://restlesslikeme.com/feed/';

		$request	= new WP_Http;
		$url		= 'http://validator.w3.org/feed/check.cgi?url='.urlencode($feed).'&output=soap12';
		$response	= wp_remote_get ( $url );

		// check for bad response from W3 API

		$check_return = array();

		if( is_wp_error( $response ) ) {
			$check_return['success']	= false;
			$check_return['errmsg']		= '<p>Sorry, there was an error with your request.</p>';
		}

		if(!is_wp_error( $response ) ) {		
			$feed_data	= $response['body'];
			$return_xml	= simplexml_load_string( (string) $feed_data );
			
			$return_xml->registerXPathNamespace( 'm', 'http://www.w3.org/2005/10/feed-validator' );

		
		// get my variables
			$check_return['success'] 		= true;
			$check_return['validity']		= $return_xml->xpath( '//m:feedvalidationresponse/m:validity' );
		// counts
			$check_return['error_count']	= $return_xml->xpath( '//m:feedvalidationresponse/m:errors/m:errorcount' );
			$check_return['warn_count']		= $return_xml->xpath( '//m:feedvalidationresponse/m:warnings/m:warningcount' );
		// individual issue lists
			$check_return['error_list']		= $return_xml->xpath( '//m:feedvalidationresponse/m:errors/m:errorlist' );
			$check_return['warn_list']		= $return_xml->xpath( '//m:feedvalidationresponse/m:warnings/m:warninglist' );
		
			return $check_return;

		}

	}

	/**
	 * Create the link to manually check feed
	 *
	 * @return RSSValidator
	 */

	public function check_link () {
	
		// set varibles
		$feed = get_bloginfo('rss2_url');
//		$feed		= 'http://restlesslikeme.com/feed/';

		$checklink = '<p><a href="http://feed1.w3.org/check.cgi?url='.urlencode($feed).'" target="_blank" title="See complete details on W3 Validator site">See complete details on W3 Validator site</a></p>';

		return $checklink;

	}

/// end class
}


// Instantiate our class
$RSSValidator = RSSValidator::getInstance();