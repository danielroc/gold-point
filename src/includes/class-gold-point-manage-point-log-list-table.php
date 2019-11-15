<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

/**
 * 
 * Extends WP_List_Table to display customer reward points
 *
 * @since 1.0
 * @extends \WP_List_Table
 */
class Gold_Point_Manage_Point_Log_List_Table extends WP_List_Table {

	public function __construct() {

		parent::__construct(
			array(
				'singular' => 'Point',
				'plural'   => 'Points',
				'ajax'     => false,
				'screen'   => 'woocommerce_page_WC_Points_Rewards_points_log',
			)
		);
	}

	/**
	 * Returns the column slugs and titles
	 *
	 * @see WP_List_Table::get_columns()
	 * @since 1.0
	 * @return array of column slug => title
	 */
	public function get_columns() {

        $columns = array(
            'cb'       => '<input type="checkbox" />',
            'id' => '流水號',
			'user_login' =>  '帳號',
			'user_email' =>  'E-mail',
            'total' => '金額',
            'point_name' => '購物金',
			'order_id' => '訂單編號',
			'create_time' => '建立時間',    
        );
        
		return $columns;
	}

	/**
	 * Returns the sortable columns and initial direction
	 *
	 * @see WP_List_Table::get_sortable_columns()
	 * @since 1.0
	 * @return array of sortable column slug => array( 'orderby', boolean )
	 *         where true indicates the initial sort is descending
	 */
	public function get_sortable_columns() {

		// really the only thing that makes sense to sort is the points column
		return array(
			'user_login' => array( 'user_login', false ),  // false because the inital sort direction is DESC so we want the first column click to sort ASC
            'id' => array( 'id', false ),
            'user_email' => array( 'user_email', false ),
            'point_name' => array( 'point_name', false ),
        );
	}

	/**
	 * Get content for the special checkbox column
	 *
	 * @see WP_List_Table::single_row_columns()
	 * @since 1.0
	 * @param object $row one row (item) in the table
	 * @return string the checkbox column content
	 */
	public function column_cb( $row ) {
		// Get row id if missing (allows bulk editing of zero-point users).
        //$user_id = $row->id;
        return '<input type="checkbox" name="log_id[]" value="' . $row->id . '" />';

		//return '<input type="checkbox" name="user_id[]" value="" />';
	}

	/**
	 * Get column content, this is called once per column, per row item ($user_points)
	 * returns the content to be rendered within that cell.
	 *
	 * @see WP_List_Table::single_row_columns()
	 * @since 1.0
	 * @param object $user_points one row (item) in the table
	 * @param string $column_name the column slug
	 * @return string the column content
	 */
	public function column_default( $item, $column_name ) {
		
		switch( $column_name ) { 
            case 'id':
            case 'user_login':
			case 'user_email':
			case 'point_name':
			case 'order_id':
				return $item->$column_name;
			case 'create_time':
				return $item->$column_name;
				/*$timestamp = strtotime( $item->$column_name );
				$time_diff = current_time( 'timestamp', true ) - $timestamp;
				if ( $time_diff > 0 && $time_diff < 24 * 60 * 60 ) {
					$h_time = sprintf( __( '%s ago', 'gold-point' ), human_time_diff( $timestamp, current_time( 'timestamp', true ) ) );
				} else {
					$h_time = date_i18n( wc_date_format(), $timestamp );
				}
				return $h_time;*/
			case 'total':
				if($item->$column_name>0)
					return '+'.$item->$column_name;
				else
					return $item->$column_name;	
        }
	}
	/**
	 * Get the current action selected from the bulk actions dropdown, verifying
	 * that it's a valid action to perform
	 *
	 * @see WP_List_Table::current_action()
	 * @since 1.0
	 * @return string|bool The action name or False if no action was selected
	 */
	public function current_action() {

		$current_action = parent::current_action();

		if ( $current_action && ! array_key_exists( $current_action, $this->get_bulk_actions() ) ) return false;

		return $current_action;
	}

	/**
	 * Handle actions for both individual items and bulk update
	 *
	 * @since 1.0
	 */
	public function process_actions() {
		global $wc_points_rewards;

		// get the current action (if any)
		$action = $this->current_action();

		// get the set of users to operate on
		$user_ids = isset( $_REQUEST['user_id'] ) ? array_map( 'absint', (array) $_REQUEST['user_id'] ): array();

		// no action, or invalid action
		if ( false === $action || empty( $user_ids ) || ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'wc_points_rewards_update' ) && ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-points' ) ) ) {
			return;
		}

		$success_count = $error_count = 0;

		// process the users
		foreach ( $user_ids as $user_id ) {

			// perform the action
			switch ( $action ) {
				case 'update':
					if ( WC_Points_Rewards_Manager::set_points_balance( $user_id, $_REQUEST['points_balance'][ $user_id ], 'admin-adjustment' ) ) {
						$success_count++;
					} else {
						$error_count++;
					}
				break;
			}
		}

		// build the result message(s)
		switch ( $action ) {
			case 'update':
				if ( $success_count > 0 ) {
					$wc_points_rewards->admin_message_handler->add_message( sprintf( _n( '%d customer updated.', '%s customers updated.', $success_count, 'gold-point' ), $success_count ) );
				}
				if ( $error_count > 0 ) {
					$wc_points_rewards->admin_message_handler->add_message( sprintf( _n( '%d customer could not be updated.', '%s customers could not be updated.', $error_count, 'gold-point' ), $error_count ) );
				}
			break;
		}
	}

	/**
	 * Output any messages from the bulk action handling
	 *
	 * @since 1.0
	 */
	public function render_messages() {
		//global $wc_points_rewards;

		//if ( $wc_points_rewards->admin_message_handler->message_count() > 0 ) {
			echo '<div id="moderated" class="updated"><ul><li><strong>' . implode( '</strong></li><li><strong>', 'oooo' ) . '</strong></li></ul></div>';
		//}
	}

	/**
	 * Gets the current orderby, defaulting to 'id' if none is selected
	 *
	 * @since 1.0
	 */
	private function get_current_orderby() {

		$orderby = ( isset( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'logs.id';

		// order by points or default of user ID
		switch ( $orderby ) {
            case 'user_login': return 'user_login';
            case 'user_email': return 'user_email';
			case 'create_time': return 'create_time';
			case 'point_name': return 'point_name';
			default: return 'logs.id';
		}
	}

	/**
	 * Gets the current orderby, defaulting to 'DESC' if none is selected
	 *
	 * @since 1.0
	 */
	private function get_current_order() {
		return isset( $_GET['order'] ) ? $_GET['order'] : 'DESC';
	}

	/**
	 * Generates queries to get our list table items.
	 */
	private function get_items() {
		global $wpdb;

		$per_page = 20;
		$offset =  ( $this->get_pagenum() - 1 ) * $per_page;
		switch ( $this->get_current_order() ) {
			case 'asc':
				$order = 'ASC';
				break;
			default:
				$order = 'DESC';
				break;
		}
        $orderby_column = $this->get_current_orderby();
		// Do we need to filter by customer?
		$where = '';

		if ( isset( $_GET['_user_login'] ) ) {
			$where .= $wpdb->prepare( "AND user_login like %s", '%'.$_GET['_user_login'].'%' );
		}
		if ( isset( $_GET['_point_name'] ) ) {
			$where .= $wpdb->prepare( "AND name like %s", '%'.$_GET['_point_name'].'%' );
		}
		if ( isset( $_GET['_user_id'] ) && $_GET['_user_id'] > 0 ) {
			$where = $wpdb->prepare( "AND users.ID = %d", $_GET['_user_id'] );
		}
		if ( isset( $_GET['_point_id'] ) && $_GET['_point_id'] > 0 ) {
			$where = $wpdb->prepare( "AND gold.id = %d", $_GET['_point_id'] );
		}
       
		 // Build a query we can use for count and results
		 $query = "FROM {$wpdb->prefix}wc_gold_points_user_points_log as logs 
		 JOIN {$wpdb->prefix}users as users ON logs.user_id = users.ID
		 JOIN {$wpdb->prefix}wc_gold_points as gold ON logs.point_id = gold.id
		 WHERE 1=1 {$where}
		 ORDER BY {$orderby_column} {$order}";
            
         return array(
             //'count'   => 1,
             'count'   => $wpdb->get_var( "SELECT COUNT( DISTINCT logs.id ) {$query}" ),
             'results' => $wpdb->get_results( $wpdb->prepare( "SELECT logs.id as id, user_login, user_email, total, gold.name as point_name, order_id, logs.create_time {$query} LIMIT %d, %d", $offset, $per_page ) )
         );
	}

	/**
	 * Prepare the list of user points items for display
	 *
	 * @see WP_List_Table::prepare_items()
	 * @since 1.0
	 */
	public function prepare_items() {
		global $wpdb;

		$this->process_actions();
		$per_page = 100; //$this->get_items_per_page( 'wc_points_rewards_manage_points_customers_per_page' );

		$items       = $this->get_items();
		$this->items = $items['results'];
		$count       = $items['count'];

		$this->set_pagination_args(
			array(
				'total_items' => $count,
				'per_page'    => $per_page,
				'total_pages' => ceil( $count / $per_page ),
			)
		);
	}

	/**
	 * Adds in any query arguments based on the current filters
	 *
	 * @since 1.0
	 * @param array $args associative array of WP_Query arguments used to query and populate the list table
	 * @return array associative array of WP_Query arguments used to query and populate the list table
	 */
	private function add_filter_args( $args ) {
		global $wpdb;

		// filter by customer
		if ( isset( $_GET['_user_login'] ) && $_GET['_user_login'] > 0 ) {
			$args['include'] = array( $_GET['_user_login'] );
		}
		return $args;
	}

	/**
	 * The text to display when there are no user pointss
	 *
	 * @see WP_List_Table::no_items()
	 * @since 1.0
	 */
	public function no_items() {
		if ( isset( $_REQUEST['s'] ) ) : ?>
			<p>沒有結果</p>
		<?php else : ?>
			<p>沒有結果</p>
		<?php endif;
	}
	function getGold($key) {
		global $wpdb;
		$table_gold = $wpdb->prefix . "wc_gold_points"; 
		$where = ' where id = '.$key;
		$sql = 'select * from '.$table_gold. $where;
		// echo $sql;
		$result = $wpdb->get_row($sql, ARRAY_A, 0);
		return $result;
	}
	/**
	 * Extra controls to be displayed between bulk actions and pagination, which
	 * includes our Filters: Customers, Products, Availability Dates
	 *
	 * @see WP_List_Table::extra_tablenav();
	 * @since 1.0
	 * @param string $which the placement, one of 'top' or 'bottom'
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' == $which ) {
			echo '<div class="alignleft actions">';

			$user_string = '';
			$customer_id = '';
			$user = null;
			$gold = null;
			if ( ! empty( $_GET['_user_id'] ) ) {
				$customer_id = absint( $_GET['_user_id'] );
				$user        = get_user_by( 'id', $customer_id );
			}
			if ( ! empty( $_GET['_point_id'] ) ) {
				$gold = $this->getGold($_GET['_point_id']);
			}
			if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) { ?>
				<input value="<?php if($user !== null){ echo $user->user_login;}else if(isset($_GET['_user_login'])){echo $_GET['_user_login'];} ?>" id="user_login" style="width: 300px;" class="wc-customer-search" name="_user_login" placeholder="用戶名稱" data-allow_clear="true" />
				<input value="<?php if($gold !== null){ echo $gold['name'];}else if(isset($_GET['_point_name'])){echo $_GET['_point_name'];} ?>" id="point_name" style="width: 300px;" class="wc-customer-search" name="point_name" placeholder="購物金名稱" data-allow_clear="true" />
			<?php } 

			submit_button( '搜尋', 'button', false, false, array( 'id' => 'post-query-submit' ) );
			submit_button( '重置', 'button', false, false, array( 'id' => 'reset-query-submit' ) );
			echo '</div>';

			// javascript
			wc_enqueue_js( "
				// Handle 'Filter' button separately from form so the filter parameters will not be via 'post' method.
				$( '#post-query-submit' ).on( 'click', function() {
					var user_login = $( '#user_login' ).val();
					var point_name = $( '#point_name' ).val();
					if ( null === user_login &&  null === point_name) {
						location.href = location.href.replace( /&?_user_login=([^&]$|[^&]*)/i, \"\" );
						location.href = location.href.replace( /&?_user_id=([^&]$|[^&]*)/i, \"\" );
						location.href = location.href.replace( /&?point_name=([^&]$|[^&]*)/i, \"\" );
					} else {
						//input user_login
						var afterUri = location.href.replace( /&?_user_id=([^&]$|[^&]*)/i, \"\" );
						afterUri = afterUri.replace( /&?_point_id=([^&]$|[^&]*)/i, \"\" );
						location.href = afterUri + '&_user_login=' + user_login + '&_point_name=' + point_name;
					}
					
					return false;
				} );

				$( '#reset-query-submit' ).on( 'click', function() {
					var afterUri = location.href.replace( /&?_user_login=([^&]$|[^&]*)/i, \"\" );
					afterUri = afterUri.replace( /&?_point_name=([^&]$|[^&]*)/i, \"\" );
					afterUri = afterUri.replace( /&?_point_id=([^&]$|[^&]*)/i, \"\" );
					location.href = afterUri.replace( /&?_user_id=([^&]$|[^&]*)/i, \"\" );
                    return false;
                } );
			" );
		}
	}


} // end \WC_Pre_Orders_List_Table class
