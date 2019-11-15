<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
            
class Gold_Point_Admin {

    /** @var array manage/actions tabs */
    private $tabs;
    private $isList;

    private $manage_points_list_table;
    private $manage_user_points_list_table;
    private $manage_user_points_log_list_table;
    
    /**
	 * Setup admin class
	 */
	public function __construct() {
        $this->tabs = array(
            'manage'   => __( 'Manage Points', 'gold-point' ),
            'user'   => __( 'User Points', 'gold-point' ),
            'log'      => __( 'Points Log', 'gold-point' ),
            'settings' => __( 'Settings', 'gold-point' )
        );
        add_action( 'admin_menu', array( $this, 'gold_point_plugin_setup_menu' ) );
        add_action('admin_enqueue_scripts', array( $this, 'custom_datepicker') );
        // Add a custom field types.
		add_action( 'woocommerce_admin_field_my_conversion_ratio', array( $this, 'render_conversion_ratio_field' ) );
        add_action( 'woocommerce_admin_field_my_redeem_ratio', array( $this, 'render_redeem_ratio_field' ) );
        // save settings.
		add_action( 'admin_post_save_gold_point_settings', array( $this, 'save_settings' ) );

                 
    }
    function gold_point_plugin_setup_menu(){
        add_submenu_page( 'woocommerce', 'Gold Point', 'Gold Point', 'manage_woocommerce', 'gold-point-plugin', array( $this, 'show_page' ) );
    }

    function show_page(){
        $current_tab = ( empty( $_GET['tab'] ) ) ? 'manage' : urldecode( $_GET['tab'] );
        $this->isList = ( empty( $_GET['isList'] ) || ($_GET['isList']=='true') ) ? 'true' : 'false';
        ?>
        <div class="wrap">
            <!--- Tab -->
            <h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
            <?php
                // display tabs.
                foreach ( $this->tabs as $tab_id => $tab_title ) {
                    $class = ( $tab_id == $current_tab ) ? 'nav-tab nav-tab-active' : 'nav-tab';
                    $url   = add_query_arg( 'tab', $tab_id, admin_url( 'admin.php?page=gold-point-plugin' ) );
                    printf( '<a href="%s" class="%s">%s</a>', $url, $class, $tab_title );
                }
            ?>
            </h2> <?php 
                if ( 'log' === $current_tab )
                    $this->show_log_tab();
                elseif ( 'settings' === $current_tab )
                    $this->show_settings_tab();
                elseif ( 'manage' === $current_tab  ) 
                    $this->show_manage_tab();
                elseif ( 'user' === $current_tab )
                    $this->show_point_tab();
               
            ?>
        </div>            
        <?php 
    }
     function show_manage_tab(){
        global $gold_point;
        if ( ! is_object( $this->manage_points_list_table ) ) {
            require( $gold_point->get_plugin_path() . '/includes/class-gold-point-manage-point-list-table.php' );
            $this->manage_points_list_table = new Gold_Point_Manage_Point_List_Table();
        }
        
        $manage_table =$this->manage_points_list_table;
        $manage_table->prepare_items();
        ?><form method="post" id="mainform" action="" enctype="multipart/form-data"><div class="wrap"><?php
        // title/search result string.
        echo '<h2 class="wp-heading-inline" style="display:inline;">' . __( 'Manage Points', 'gold-point' ) . '</h2>';
        
        if($this->isList==='true'){ 

            $url   = add_query_arg( 'isList', 'false', admin_url( 'admin.php?page=gold-point-plugin' ) );
            printf( '<a href="%s" class="page-title-action">%s</a>', $url, '新增購物金' );
            echo '<input type="hidden" name="page" value="' . esc_attr( $_REQUEST['page'] ) . '" />';
            $manage_table->display();// display the list table.
        
        }elseif($this->isList==='false'){
            //post back
            if( isset($_POST['input-gold-name']) && $_POST['input-gold-name'] != null && check_admin_referer( 'csrf_token' )){
                if($_POST['input-gold-id']!=null)
                    $data['id']=$_POST['input-gold-id'];
                $data['name']=$_POST['input-gold-name'];
                $data['start_time']=$_POST['input-start-time'];
                $data['end_time']=$_POST['input-end-time'];
                $data['status']=$_POST['input-gold-status'];
                $data['valid_period']=$_POST['input-valid-period'];
                $data['valid_time_window']=$_POST['input-valid-time-window'];
                if($_POST['input-gold-id']!=null){
                    Gold_Point_Repository::updateGold($data, array('id' => $data['id']) );
                    echo '<div id="message" class="updated">
                        <p><strong>更新成功</strong> <a href="admin.php?page=gold-point-plugin">返回列表</a></p> </div>';
                } else {
                    $res = Gold_Point_Repository::addGold($data);
                    if($res){
                        echo '<div id="message" class="updated">
                        <p><strong>新增成功</strong> <a href="admin.php?page=gold-point-plugin">返回列表</a></p> </div>';
                    }
                }
            }
            if(isset($_GET['id']) && $_GET['id'] != null)
               $data = Gold_Point_Repository::getGold($_GET['id']);
               
            require( $gold_point->get_plugin_path() . '/templates/admin/point-edit.php' );
            
            
        }
        ?></div></form><?php	
    }
    function show_point_tab() {
        global $gold_point;
        if ( ! is_object( $this->manage_user_points_list_table ) ) {
            require( $gold_point->get_plugin_path() . '/includes/class-gold-point-manage-user-point-list-table.php' );
            $this->manage_user_points_list_table = new Gold_Point_Manage_User_Point_List_Table();
        }
        
        $user_table =$this->manage_user_points_list_table;
        $user_table->prepare_items();
        ?><form method="post" id="mainform" action="" enctype="multipart/form-data"><div class="wrap"><?php
        echo '<h2 class="wp-heading-inline" style="display:inline;">' . __( 'User Points', 'gold-point' ) . '</h2>';
        if($this->isList ==='true'){ 

            $refresh_url = add_query_arg( array('isList' => 'true', 'tab' => 'user', 'refresh' => 'true' ), admin_url( 'admin.php?page=gold-point-plugin' ) );
            printf( '<a href="%s" class="page-title-action">%s</a>', $refresh_url, '刷新用戶購物金' );
            echo '<input type="hidden" name="page" value="' . esc_attr( $_REQUEST['page'] ) . '" />';
            echo '<input type="hidden" name="tab" value="' . esc_attr( $_REQUEST['tab'] ) . '" />';
            $user_table->display();

        }elseif($this->isList==='false'){
            //post back
            if( isset($_POST['input-user-id']) && $_POST['input-user-id'] != null && check_admin_referer( 'csrf_token' )){

                $data['user_id'] = $_POST['input-user-id'];
                $data['total'] = $_POST['input-amount'];
                $data['point_id'] = $_POST['input-point-id'];
                $result = Gold_Point_Repository::addUserGold($data);
                if($result){
                    echo '<div id="message" class="updated">
                    <p><strong>新增成功</strong> <a href="admin.php?page=gold-point-plugin&tab=user">返回列表</a></p> </div>';
                }
             }
             if(isset($_GET['ID']) && $_GET['ID'] != null)
                $user_data = get_user_by( 'ID', $_GET['ID'] );

             require( $gold_point->get_plugin_path() . '/templates/admin/user-point-add.php' );
        }

        
        ?></div></form><?php	
    }
    function show_log_tab() {
        global $gold_point;
        if ( ! is_object( $this->manage_user_points_log_list_table ) ) {
            require( $gold_point->get_plugin_path() . '/includes/class-gold-point-manage-point-log-list-table.php' );
            $this->manage_user_points_log_list_table = new Gold_Point_Manage_Point_Log_List_Table();
        }
        
        $log_table =$this->manage_user_points_log_list_table;
        $log_table->prepare_items();
        ?><form method="post" id="mainform" action="" enctype="multipart/form-data"><div class="wrap"><?php
        echo '<h2>' . __( 'Points Log', 'gold-point' ) . '</h2>';

        echo '<input type="hidden" name="page" value="' . esc_attr( $_REQUEST['page'] ) . '" />';
		echo '<input type="hidden" name="tab" value="' . esc_attr( $_REQUEST['tab'] ) . '" />';
        $log_table->display();
        ?></div></form><?php
    }

     function show_settings_tab() {
        // echo "N/A";
        ?>
		<form method="post" action="admin-post.php" enctype="multipart/form-data">
			<input type="hidden" name="action" value="save_gold_point_settings" />
			<?php
				wp_nonce_field( 'gold-point-save-settings-verify' );
				$this->render_settings();
			?>
			<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'woocommerce' ) ?>" />
		</form>
		<?php
    }
    public static function get_settings() {
        
        $settings = array(
            array(
				'title' => __( 'Points Settings', 'gold-point' ),
				'type'  => 'title',
				'id'    => 'gold_point_settings_start'
            ),    
            array(
                'title'    => __( 'Enable reward', 'gold-point' ),
                'desc'     => __( 'Enable points awarded based on product price.', 'gold-point' ),
                'id'       => 'gold_point_enable_reward',
                'default'  => 'no',
                'type'     => 'checkbox',
                'desc_tip' => __( '', 'woocommerce' ),
            ),        
            // earn points conversion.
			array(
				'title'    => __( 'Earn Rate', 'gold-point' ),
				'desc_tip' => __( 'Number of points awarded based on product price.', 'gold-point' ),
				'id'       => 'gold_point_earn_points_ratio',
				'default'  => '1:100',
				'type'     => 'my_conversion_ratio'
            ),
            array(
				'title'    => __( 'Redeem Rate', 'gold-point' ),
				'desc_tip' => __( 'Number of points awarded based on product price.', 'gold-point' ),
				'id'       => 'gold_point_redeem_points_ratio',
				'default'  => '1:1',
				'type'     => 'my_redeem_ratio'
            ),

            array( 'type' => 'sectionend', 'id' => 'wc_points_rewards_points_actions_end' ),
        );
        return $settings ;
    }    
    
    public function render_settings() {
        
        woocommerce_admin_fields( $this->get_settings() );
    }
    public function render_conversion_ratio_field( $field ) {
       // print_r($field);
        //id  gold_point_earn_points_ratio
        $ratio = get_option( $field['id'], $field['default'] );
        list( $points, $dollar ) = explode( ':', $ratio );
        ?>
        <tr valign="top">
					<th scope="row" class="titledesc">
						<label for=""><?php echo wp_kses_post( $field['title'] ); ?></label>
					</th>
					<td class="forminp forminp-text">
						<fieldset>
                            <input type="text" value="<?php echo esc_attr( $points );?>"/> Points for
                            <input type="text" value="<?php echo esc_attr( $dollar );?>"/> Dollars Spent
                        </fieldset>
					</td>
				</tr>
        <?php
    }
    public function render_redeem_ratio_field( $field ) {
        $ratio = get_option( $field['id'], $field['default'] );
        list( $points, $dollar ) = explode( ':', $ratio );
        ?>
        <tr valign="top">
					<th scope="row" class="titledesc">
						<label for=""><?php echo wp_kses_post( $field['title'] ); ?></label>
					</th>
					<td class="forminp forminp-text">
						<fieldset>
                            <input type="text" value="<?php echo esc_attr( $points );?>"/> Points equals to
                            <input type="text" value="<?php echo esc_attr( $dollar );?>"/> Dollars
                        </fieldset>
					</td>
				</tr>
        <?php
    }
    public function save_settings() {
        // Check the nonce.
        check_admin_referer( 'gold-point-save-settings-verify' );
        // Save the settings.
        woocommerce_update_options( $this->get_settings() );
        // Go back to the settings page.
		wp_redirect( admin_url( 'admin.php?page=gold-point-plugin&tab=settings' ) );
		exit;

    }
    function custom_datepicker() {
        wp_enqueue_style( 'jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css' );
        wp_enqueue_script('jquery-ui-core'); 
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script( 'jquery-ui-timepicker-addon', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.js' );
        wp_enqueue_style( 'jquery-ui-timepicker-addon', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.css' );
    }

}