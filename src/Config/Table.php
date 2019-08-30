<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@revive.today>
 * @license MIT
 */

namespace Kebabble\Config;

use Kebabble\Library\Slack;

/**
 * Handles additional admin table display information.
 */
class Table {
	/**
	 * Slack communication access.
	 *
	 * @var Slack
	 */
	protected $slack;

	/**
	 * Constructor.
	 *
	 * @param Slack $slack Slack communication access.
	 */
	public function __construct( Slack $slack ) {
		$this->slack = $slack;
	}

	/**
	 * Defines the nice header name for the custom table columns.
	 *
	 * @param string[] $columns WordPress columns.
	 * @return string[] The inputted array is returned with modifications.
	 */
	public function orders_column_definition( array $columns ):array {
		$columns['kebabble_channel'] = 'Channel';

		return $columns;
	}

	/**
	 * Adds custom Kebabble-related data to the post admin table interface.
	 *
	 * @param string  $column WordPress columns.
	 * @param integer $post_id Post ID of each row of the admin table.
	 * @return void Apparently it magically understands the changes.
	 */
	public function orders_table_data( string $column, int $post_id ):void {
		if ( $column === 'kebabble_channel' ) {
			$chosen   = get_post_meta( $post_id, 'kebabble-slack-channel', true );
			$channels = $this->slack->channels();
			$selected = 'Unknown';
			foreach ( $channels as $channel ) {
				if ( $channel['key'] === $chosen ) {
					$selected = $channel['channel'];
				}
			}
			echo esc_textarea( $selected );
		}
	}
}
