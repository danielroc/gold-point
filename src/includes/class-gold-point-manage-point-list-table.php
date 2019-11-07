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
class Gold_Point_Manage_Point_List_Table extends WP_List_Table {

	/**
	 * Setup list table
	 *
	 * @see WP_List_Table::__construct()
	 * @since 1.0
	 * @return \WC_Points_Rewards_Manage_Points_List_Table
	 */
	public function __construct() {

		parent::__construct(
			array(
				'singular' => 'Point',
				'plural'   => 'Points',
				'ajax'     => false,
				'screen'   => 'gold_point',
			)
		);
	}

	/**
	 * Gets the bulk action available for user points: update
	 *
	 * @see WP_List_Table::get_bulk_actions()
	 * @since 1.0
	 * @return array associative array of action_slug => action_title
	 */
	/* public function get_bulk_actions() {

		$actions = array(
			'update' => __( 'Update', 'woocommerce-points-and-rewards' ),
		);
		return $actions;
	}
    */

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
            'id' => 'id',
            'name' =>  '購物金名稱',
			'status' => '狀態',
			'time_period' => '有效時間',
            'start_time' => '開始時間',
			'end_time' => '結束時間',
			'act' => '操作',
            
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
			'name' => array( 'name', false ),  // false because the inital sort direction is DESC so we want the first column click to sort ASC
            'id' => array( 'id', false ),
            'status' => array( 'status', false ),
            'start_time' => array( 'start_time', false ),
            'end_time' => array( 'end_time', false ),
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
        return '<input type="checkbox" name="gold_id[]" value="' . $row->id . '" />';

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
            case 'start_time':
            case 'end_time':
				return $item->$column_name;
			case 'time_period':
				if($item->valid_period != null && $item->valid_period != 0){
					return $item->valid_period.' '.$item->valid_time_window;
				}
				return'';
			case 'name':
				$url   = add_query_arg( array('isList' => 'true', 'tab' => 'log','_point_id' => $item->id), admin_url( 'admin.php?page=gold-point-plugin' ) );
				return sprintf('<a href="%s">%s</a>', $url, $item->$column_name) ;
            case 'status':
                if($item->$column_name==1)
                    return '啟用';
                if($item->$column_name==null || $item->$column_name==0)
					return '停用';
			case 'act':
				$url   = add_query_arg( array('isList' => 'false', 'id' => $item->id), admin_url( 'admin.php?page=gold-point-plugin' ) );
                return '<a href="'.$url.'">編輯</a>';	
			default:
                return 'unknown';
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
					$wc_points_rewards->admin_message_handler->add_message( sprintf( _n( '%d customer updated.', '%s customers updated.', $success_count, 'woocommerce-points-and-rewards' ), $success_count ) );
				}
				if ( $error_count > 0 ) {
					$wc_points_rewards->admin_message_handler->add_message( sprintf( _n( '%d customer could not be updated.', '%s customers could not be updated.', $error_count, 'woocommerce-points-and-rewards' ), $error_count ) );
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
		global $wc_points_rewards;

		if ( $wc_points_rewards->admin_message_handler->message_count() > 0 ) {
			echo '<div id="moderated" class="updated"><ul><li><strong>' . implode( '</strong></li><li><strong>', $wc_points_rewards->admin_message_handler->get_messages() ) . '</strong></li></ul></div>';
		}
	}

	/**
	 * Gets the current orderby, defaulting to 'id' if none is selected
	 *
	 * @since 1.0
	 */
	private function get_current_orderby() {

		$orderby = ( isset( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'id';

		// order by points or default of user ID
		switch ( $orderby ) {
            case 'name': return 'name';
            case 'start_time': return 'start_time';
            case 'end_time': return 'end_time';
			default: return 'id';
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

		$per_page = $this->get_items_per_page( 'wc_points_rewards_manage_points_customers_per_page' );
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
        //search condition
		if ( isset( $_GET['_name'] ) ) {
			$where = $wpdb->prepare( "AND name like %s", '%'.$_GET['_name'].'%' );
		}
       
		 // Build a query we can use for count and results
         $query = "FROM {$wpdb->prefix}wc_gold_points as gold_points WHERE 1=1 {$where} ORDER BY {$orderby_column} {$order}";
         //echo  $query;
         return array(
             //'count'   => 1,
             'count'   => $wpdb->get_var( "SELECT COUNT( DISTINCT id ) as found_user_points {$query}" ),
             'results' => $wpdb->get_results( $wpdb->prepare( "SELECT * {$query} LIMIT %d, %d", $offset, $per_page ) )
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
		$per_page = $this->get_items_per_page( 'wc_points_rewards_manage_points_customers_per_page' );

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
		if ( isset( $_GET['_name'] ) && $_GET['_name'] > 0 ) {
		$args['include'] = array( $_GET['_name'] );
		}
        print_r($args);
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
			<p>NOT FOUND</p>
		<?php else : ?>
			<p>NOT FOUND</p>
		<?php endif;
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

			$name = '';
			if ( ! empty( $_GET['_name'] ) ) {
				//$name = absint( $_GET['_name'] );
				// $user        = get_user_by( 'id', $customer_id );
				//$user_string = esc_html( $user->display_name ) . ' (#' . absint( $user->ID ) . ' &ndash; ' . esc_html( $user->user_email );
			}
			if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) { ?>
				<input value="<?php if(isset($_GET['_name'])){echo $_GET['_name'];} ?>" id="name" style="width: 300px;" class="wc-customer-search" name="_name" placeholder="購物金名稱" data-allow_clear="true" />
				
			<?php } 

            submit_button( '搜尋', 'button', false, false, array( 'id' => 'post-query-submit' ) );
            submit_button( '重置', 'button', false, false, array( 'id' => 'reset-query-submit' ) );
			echo '</div>';

			// javascript
			wc_enqueue_js( "
				// Handle 'Filter' button separately from form so the filter parameters will not be via 'post' method.
				$( '#post-query-submit' ).on( 'click', function() {
					var name = $( '#name' ).val();
					if ( null === name ) {
						// Clear _customer_user parameter from url (in case user has intentionally cleared filter).
						location.href = location.href.replace( /&?_name=([^&]$|[^&]*)/i, \"\" );
					} else {
						location.href = location.href + '&_name=' + name;
					}
					
					return false;
                } );
                $( '#reset-query-submit' ).on( 'click', function() {
                    location.href = location.href.replace( /&?_name=([^&]$|[^&]*)/i, \"\" );
                    return false;
                } );
			" );
		}
	}


} // end \WC_Pre_Orders_List_Table class
