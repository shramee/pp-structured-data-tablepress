<?php
/**
 * Plugin Name: PootlePress SEO: Structured data
 * Plugin URI: http://shramee.me/
 * Description: Adds json-ld data for 1 day WP courses
 * Author: Shramee
 * Version: 1.0.0
 * Author URI: http://shramee.me/
 * @developer shramee <shramee.srivastav@gmail.com>
 */
/**
 * Created by PhpStorm.
 * User: shramee
 * Date: 27/12/16
 * Time: 10:07 AM
 */
class PootlePress_Structured_Data {
	/** @var PootlePress_Structured_Data Instance */
	private static $_instance;

	private $_json_ld_output_done = false;
	private $json_ld = false;

	/** @return PootlePress_Structured_Data Instance */
	public static function instance() {
		if ( empty( self::$_instance ) ) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	function __construct() {
		add_filter( 'tablepress_table_output',  array( $this, 'courses_structured_data' ), 10, 2 );
		add_action( 'the_content',  array( $this, 'json_out' ), 25, 2 );
	}

	function structured_data_for_course( $date, $stock, $city, $country = 'UK' ) {
		return array(
			'@context'    => 'http://schema.org',
			'@type'       => 'EducationEvent',
			'name'        => 'WordPress in 1 day',
			'description' => 'Learn WordPress in 1 day course at PootlePress will give you the knowledge to create, manage and run a great WordPress website.',
			'organizer'   => 'PootlePress',
			'performer'   => 'Jamie Marsland',
			'url'         => 'http://www.pootlepress.com/wordpress-training',
			'image'       => 'http://www.pootlepress.com/wp-content/uploads/2012/06/IMG_1986.jpg',
			'startDate'   => "{$date}T10:00",
			'endDate'     => "{$date}T16:00",
			'location'    => array(
				'@type'   => 'Place',
				'name'    => "$city",
				'address' => "$city, $country",
			),
			'eventStatus' => 'EventScheduled',
			'offers'      =>
				array(
					'@type'              => 'Offer',
					'price'              => '95.00',
					'priceCurrency'      => 'GBP',
					'url'                => 'http://www.pootlepress.com/wordpress-training#panel-2-7-0-0',
					'availability'       => $stock ?
						'http://schema.org/InStock' :
						'http://schema.org/SoldOut',
					'availabilityStarts' => '2017-01-01',
					'inventoryLevel'     => $stock,
				),
		);
	}

	function courses_structured_data( $output, $table ) {
		if ( 1 == $table['id'] && ! $this->_json_ld_output_done ) {
			$now = time();
			$courses = array();

			foreach ( $table['data'] as $row ) {

				$course_timestamp = strtotime( $row['0'] . ' ' . date( 'Y' ) );

				if ( $course_timestamp - $now < -10 * MONTH_IN_SECONDS ) {
					// If course seems to have happened more than 10 months ago... We may need to increase the year by 1.
					$course_timestamp = strtotime( $row['0'] . ' ' . ( date( 'Y' ) + 1 ) );
				}

				if( $course_timestamp ) {
//					echo $row['0'] . ' ETA = ' . date( 'Y-m-d', $course_timestamp );
					$stock = is_numeric( trim( $row['2'] ) ) ?
						trim( $row['2'] ) :
						0;
					$courses[] = $this->structured_data_for_course( date( 'Y-m-d', $course_timestamp ), $stock, $row['1'] );
					$this->_json_ld_output_done = true;
				}
			}

			if ( $courses ) {
				$json_ld = json_encode( $courses );
				$this->json_ld = $json_ld;
			}
		}

		return $output;
	}
	
	function json_out( $content ) {
		if ( $this->json_ld ) {
			$content .= "<script type='application/ld+json'>{$this->json_ld}</script>";
		}
		return $content;
	}
}

PootlePress_Structured_Data::instance();
