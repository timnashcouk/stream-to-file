<?php
/**
 * Plugin Name:     Stream Plugin to Log File
 * Plugin URI:      https://timnash.co.uk
 * Description:     Sends Stream Records to a Log file
 * Author:          Tim Nash
 * Author URI:      https://timnash.co.uk
 * Version:         1.0.0
 **/

/**
 * Stream to File main class
 * @since 1.0.0
 */
class StreamToFile{

  /**
   * Stream Instance
   * @since 1.0.0
   * @var object
   */
  protected $stream;

  /**
   * Stream Options Array
   * @since 1.0.0
   * @var array
   */
  protected $options;

/**
 * Construct
 * Get current singleton instance of Stream, validate plugin exists and active
 * @since 1.0.0
 */
  public function __construct(){

     		if ( ! function_exists( 'wp_stream_get_instance' ) ) {
     			add_action( 'admin_notices', array( $this, 'stream_not_found_notice' ) );
     			return false;
     		}

     		$this->stream = wp_stream_get_instance();
     		$this->options = $this->stream->settings->options;

     		add_filter( 'wp_stream_settings_option_fields', array( $this, 'options' ) );

     		if ( empty( $this->options['file_location'] ) ) {
     			add_action( 'admin_notices', array( $this, 'location_undefined_notice' ) );
     		}
     		else {
     			add_action( 'wp_stream_record_inserted', array( $this, 'log' ), 10, 2 );
     		}
      }

      /**
       * Stream Option API Filter
       * @since 1.0.0
       * @param  array $fields
       * @return array
       */
      public function options( $fields ){
        $settings = [
          'title'  => esc_html__( 'Log File', 'stream_to_file' ),
          'fields' => [
            [
              'name'    => 'location',
              'title'   => esc_html__( 'File Location', 'stream_to_file'),
              'type'    => 'text',
              'desc'    => esc_html__( 'Path to logfile, make sure its writable by PHP', 'stream_to_file' ),
              'default' => ''
            ],
            [
              'name'    => 'format',
              'title'   => esc_html__( 'File Format', 'stream_to_file'),
              'type'    => 'text',
              'desc'    => esc_html__( 'Use {user_login} or {user_id}', 'stream_to_file' ),
              'default' => '{created} - {user_login} - {connector} {context} {action} - {summary}'
            ]
          ],
        ];
        $fields['file'] = $settings;
        return $fields;
      }

    /**
     * Record a Stream Record
     * @since 1.0.0
     * @param  string $record_id    Single Record ID
     * @param  array $record_array  Record Content
     */
      public function log( $record_id, $record_array ){
        $record = $record_array;
    		$record['record_id'] = $record_id;

    		if ( ! empty( $record['meta']['user_meta'] ) && is_serialized( $record['meta']['user_meta'] ) ) {
    			$record['meta']['user_meta'] = unserialize( $record['meta']['user_meta'] );
    		}
        $this->write_log( $record );
      }

      /**
       * Write Entry to file
       * @since 1.0.0
       * @param  array $record
       */
      protected function write_log( $record ){
        if( ! $this->options['file_location'] ) return;
        $log_msg = $this->format( $record );
        return file_put_contents('/'.$this->options['file_location'], $log_msg . "\n", FILE_APPEND);
      }

      /**
       * Format Log Output based on option
       * @since 1.0.0
       * @param  array $record
       * @return string
       */
      protected function format( $record ){
        $log = $this->options['file_format'];
        if( ! isset( $log ) ){
          $log = '{created} - {user_login} - {connector} {context} {action} - {summary}';
        }

        $entry = [];

        $entry['user_id']      = (isset( $record['user_id'] ))      ? $record['user_id']      : '' ;
        $entry['user_role']    = (isset( $record['user_role'] ))    ? $record['user_role']    : '' ;
        $entry['created']      = (isset( $record['created'] ))      ? $record['created']      : current_time( 'mysql' );
        $entry['summary']      = (isset( $record['summary'] ))      ? $record['summary']      : '' ;
        $entry['connector']    = (isset( $record['connector'] ))    ? $record['connector']    : '' ;
        $entry['context']      = (isset( $record['context'] ))      ? $record['context']      : '' ;
        $entry['action']       = (isset( $record['action'] ))       ? $record['action']       : '' ;
        $entry['ip']           = (isset( $record['ip'] ))           ? $record['ip']           : '' ;

        $entry['display_name'] = (isset( $record['meta']['user_meta']['display_name'] )) ? $record['meta']['user_meta']['display_name'] : '' ;
        $entry['user_email']   = (isset( $record['meta']['user_meta']['user_email'] ))   ? $record['meta']['user_meta']['user_email']   : '' ;
        $entry['user_login']   = (isset( $record['meta']['user_meta']['user_login'] ))   ? $record['meta']['user_meta']['user_login']   : 'N/A' ;

        $log = str_replace([
          '{user_id}',
          '{user_role}',
          '{created}',
          '{summary}',
          '{connector}',
          '{context}',
          '{action}',
          '{ip}',
          '{display_name}',
          '{user_email}',
          '{user_login}'
        ],[
          $entry['user_id'],
          $entry['user_role'],
          $entry['created'],
          $entry['summary'],
          $entry['connector'],
          $entry['context'],
          $entry['action'],
          $entry['ip'],
          $entry['display_name'],
          $entry['user_email'],
          $entry['user_login'],
        ], $log);

        return apply_filters( 'stream_to_file_log', $log, $record );
      }

      /**
       * Display notice if settings are empty
       * @since 1.0.0
       */
      public function location_undefined_notice() {
        $class = 'error';
        $message = 'Complete Setup visit <a href="' . admin_url( 'admin.php?page=wp_stream_settings' ) . '">Stream Settings</a> and set a File Location.';
        echo '<div class="' . $class . '"><p>' . $message . '</p></div>';
      }

      /**
       * Display notice if Stream not enabled
       * @since 1.0.0
       */
      public function stream_not_found_notice() {
        $class = 'error';
        $message = 'The "Stream to File" plugin requires the <a href="https://wordpress.org/plugins/stream/">Stream</a> plugin to be activated before it can be used';
        echo '<div class="' . $class . '"><p>' . $message . '</p></div>';
      }
}
/**
 * Annonymous function hooked into Init to load class post Stream
 * @since 1.0.0
 */
add_action( 'init', function(){
    $stream_to_file = new StreamToFile();
});
