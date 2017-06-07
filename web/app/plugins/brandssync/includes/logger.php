<?php

namespace BrandsSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Allows log files to be written to for debugging purposes
 *
 * @class \BrandsSync\Logger
 * @author WooThemes
 */
class Logger {

	/**
	 * Stores open file _handles.
	 *
	 * @var array
	 * @access private
	 */
	private $_handles;

	public $level;
	protected $level_value = array(
		0 => 'DEBUG',
		1 => 'INFO ',
		2 => 'WARN ',
		3 => 'ERROR',
	);

	const DEBUG = 0;
	const INFO = 1;
	const WARNING = 2;
	const ERROR = 3;

	/**
	 * Constructor for the logger.
	 *
	 * @param int $level
	 */
	public function __construct( $level = self::INFO ) {
		$this->_handles = array();
		if ( array_key_exists( (int) $level, $this->level_value ) ) {
			$this->level = $level;
		} else {
			$this->level = self::INFO;
		}
	}

	/**
	 * Destructor.
	 */
	public function __destruct() {
		foreach ( $this->_handles as $handle ) {
			@fclose( $handle );
		}
	}


	/**
	 * Open log file for writing.
	 *
	 * @access private
	 *
	 * @param mixed $handle
	 *
	 * @return bool success
	 */
	private function open( $handle ) {
		if ( isset( $this->_handles[ $handle ] ) ) {
			return true;
		}

		if ( $this->_handles[ $handle ] = fopen( $this->get_log_file_path( $handle ), 'a' ) ) {
			return true;
		}

		return false;
	}


	/**
	 * Add a log entry to chosen file.
	 *
	 * @param string $handle
	 * @param string $message
	 */
	public function add( $handle, $message ) {
		if ( $this->open( $handle ) && is_resource( $this->_handles[ $handle ] ) ) {
			$time = date_i18n( 'Y-m-d @ H:i:s -' ); // Grab Time
			@fwrite( $this->_handles[ $handle ], $time . " " . $message . "\n" );
		}

		do_action( 'brandssync_log_add', $handle, $message );
	}


	/**
	 * Clear entries from chosen file.
	 *
	 * @param mixed $handle
	 */
	public function clear( $handle ) {
		if ( $this->open( $handle ) && is_resource( $this->_handles[ $handle ] ) ) {
			@ftruncate( $this->_handles[ $handle ], 0 );
		}

		do_action( 'brandssync_log_clear', $handle );
	}

	/**
	 * @param $handle string
	 * @param $level int
	 * @param $message string
	 */
	public function log( $handle, $level, $message ) {
		if ( $this->level <= $level ) {
			$this->add( $handle, $this->level_value[ $level ] . ' ' . $message );
		}
	}

	public function info( $handle, $message ) {
		$this->log( $handle, self::INFO, $message );
	}

	public function debug( $handle, $message ) {
		$this->log( $handle, self::DEBUG, $message );
	}

	public function warn( $handle, $message ) {
		$this->log( $handle, self::WARNING, $message );
	}

	public function error( $handle, $message ) {
		$this->log( $handle, self::ERROR, $message );
	}

	private function get_log_file_path( $handle ) {
		global $brandssync;
		
		$dir = $brandssync->get_data_directory() . DIRECTORY_SEPARATOR . 'logs';
		if ( ! is_dir( $dir ) ) {
			mkdir( $dir, 0777, true );
		}

		return trailingslashit( $dir ) . $handle . '-' . date_i18n( 'Y-m-d' ) . '.log';
	}
}
