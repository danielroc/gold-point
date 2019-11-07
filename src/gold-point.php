<?php
/**
 * @version 0.1.0
 *
 * Plugin Name: Gold Point 
 * Plugin URI: #
 * Description: An eCommerce Points module plugin for multi-purpose points to reward customers on purchases, register or marketing events.
 * Version: 0.1.0
 * Author: Daniel Lee
 * Author URI: #
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Gold_Point' ) ){
    class Gold_Point {
       
        /** @var string the plugin path */
        private $plugin_path;
    
        private static $instance;

        /** @var array manage/actions tabs */
        private $tabs;
        private $isList;
    
        private $manage_points_list_table;
        private $manage_user_points_list_table;

        /**
        * Returns the *Singleton* instance of this class.
        *
        * @return Singleton The *Singleton* instance.
        */
        public static function get_instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        protected function __construct() {
            
            $this->tabs = array(
                'manage'   => __( 'Manage Points', 'woocommerce-points-and-rewards' ),
                'point'   => __( 'User Points', 'woocommerce-points-and-rewards' ),
                'log'      => __( 'Points Log', 'woocommerce-points-and-rewards' ),
                'settings' => __( 'Settings', 'woocommerce-points-and-rewards' )
            );

            add_action( 'plugins_loaded', array( $this, 'init' ) );
            add_action( 'admin_menu', array( $this, 'gold_point_plugin_setup_menu' ) );
            add_action('admin_enqueue_scripts', array( $this, 'custom_datepicker') );
            add_shortcode( 'gold_point_short_code_user_balance', array( $this, 'gold_point_user_balance') );
            
        }
        function gold_point_user_balance() {
            global $wpdb;
            $user_id  = get_current_user_id();
            $data = $this->getUserGold($user_id);//$user_id.
            $output = sprintf("<a>購物金(%d)</a>",$data['total']);
            return $output;
        }
        public function init() {
            $this->schema_check();
        
        }
        function gold_point_plugin_setup_menu(){
            add_submenu_page( 'woocommerce', 'Gold Point', 'Gold Point', 'manage_woocommerce', 'gold-point-plugin', array( $this, 'gold_page' ) );
        }
        function gold_page(){

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
                    // display tab content, default to 'Manage' tab.
                    if ( 'log' === $current_tab )
                        $this->show_log_tab();
                    elseif ( 'settings' === $current_tab )
                       $this->show_settings_tab();
                    elseif ( 'manage' === $current_tab )
                        $this->show_manage_tab();
                    elseif ( 'point' === $current_tab )
                        $this->show_point_tab();
                ?>
            </div>            
        <?php }
       
        private function show_manage_tab() {
            
            require( $this->get_plugin_path() . '/includes/class-gold-point-manage-point-list-table.php' );
            $this->manage_points_list_table = new Gold_Point_Manage_Point_List_Table();
            $this->manage_points_list_table->prepare_items(); //page helper
            ?>
            <!--- Content -->
            <form method="post" id="mainform" action="" enctype="multipart/form-data">
            <div class="wrap">
                <h2 class="wp-heading-inline" style="display:inline;">購物金管理</h2>
            <?php  
                if($this->isList==='true'){ 
                    $url   = add_query_arg( 'isList', 'false', admin_url( 'admin.php?page=gold-point-plugin' ) );
                    if($this->isList==='true'){
                        printf( '<a href="%s" class="page-title-action">%s</a>', $url, '新增購物金' );
                    }
                    // display any action messages.
                    $this->manage_points_list_table->render_messages();
                    echo '<input type="hidden" name="page" value="' . esc_attr( $_REQUEST['page'] ) . '" />';
                    $this->manage_points_list_table->display();
                }
            if($this->isList==='false'){ 
                
                if(isset($_GET['id']) && $_GET['id'] != null)
                    $data = $this->getGold($_GET['id']);
                
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
                        $this->updateGold($data, array('id' => $data['id']) );
                        echo '<div id="message" class="updated">
                            <p><strong>更新成功</strong> <a href="admin.php?page=gold-point-plugin">返回列表</a></p> </div>';
                    } else {
                        $res = $this->addGold($data);
                        if($res){
                            echo '<div id="message" class="updated">
                            <p><strong>新增成功</strong> <a href="admin.php?page=gold-point-plugin">返回列表</a></p> </div>';
                        }
                    }
                }
               
                ?>
                <hr class="wp-header-end" />
                <h2>新增/編輯購物金</h2>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th><label for="input-gold-name">購物金名稱</label></th>
                            <td><input id="input-gold-name" name="input-gold-name" required value="<?php if(isset($data['name'])){echo $data['name'];}?>" /></td>
                            <input type="hidden" id="input-gold-id" name="input-gold-id" value="<?php if(isset($data['id'])){echo $data['id'];}?>"/>
                        </tr>
                        <tr valign="top">
                            <th><label for="input-gold-status">狀態</label></th>
                            <td>
                                <select name="input-gold-status">
                                <option value="1" <?php if(isset($data['status'])){ echo ($data['status']==1) ?' selected':'';} ?>>啟用</option>
                                <option value="0" <?php if(isset($data['status'])){ echo ($data['status']==0||$data['status']==null) ?' selected':'';} ?>>停用</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="input-start-time">開始時間</label></th>
                            <td><input id="input-start-time" class="date-picker" name="input-start-time" value="<?php if(isset($data['start_time'])){ echo $data['start_time']; }?>"  /></td>
                        </tr>
                        <tr>
                            <th><label for="input-end-time">結束時間</label></th>
                            <td><input id="input-end-time" class="date-picker" name="input-end-time" value="<?php if(isset($data['end_time'])){ echo $data['end_time']; }?>"  /></td>
                        </tr>
                        <tr>
                            <th><label for="input-valid-time">發放後有效時間</label></th>
                            <td>
                            <select id="input-valid-period" name="input-valid-period">
                                <option value="0"></option>
                                <?php
                                $periods = array(
                                    'DAY'   => 'Day(s)',
                                    'WEEK'  => 'Week(s)',
                                    'MONTH' => 'Month(s)',
                                    'YEAR'  => 'Year(s)'
                                );
								for ( $num = 1; $num < 100; $num++ ) :
                                        $selected = '';
                                        if ( $num == $data['valid_period'] ) {
                                            $selected = ' selected="selected" ';
                                        }
                                ?>
                                    <option value="<?php echo esc_attr( $num ); ?>" <?php echo $selected; ?>><?php echo $num; ?></option> 
                                <?php endfor; ?>
                            </select>
                            
                            <select id="input-valid-time-window" name="input-valid-time-window">
                                <option value="NULL"></option>
                                <?php
                                    foreach ( $periods as $period_id => $period_text ) :
                                        $selected = '';
                                        if ( $period_id == $data['valid_time_window'] ) {
                                            $selected = ' selected="selected" ';
                                        }
                                ?>
                                    <option value="<?php echo esc_attr( $period_id ); ?>" <?php echo $selected; ?>><?php _e( $period_text, 'woocommerce-points-and-rewards' ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">欲指定發放後有效時間，請勿填入開始/結束時間</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="input-end-time">發放數量</label></th>
                            <td></td>
                        </tr>
                        <tr>
                            <th><label for="input-end-time">使用數量</label></th>
                            <td></td>
                        </tr>
                        <tr>
                            <th><label for="input-end-time">剩餘數量</label></th>
                            <td></td>
                        </tr>
                        <tr valign="top">
                            <td>
                                <input type="submit" name="save" value="儲存" class="button-primary" />
                            </td>
                        </tr>
                    </tbody>
                </table>
                <script type="text/javascript">
                     jQuery(document).ready(function($){
                        $("input[class='date-picker']").datetimepicker({
                            timeFormat: "hh:mm",
                            dateFormat : 'yy-mm-dd'
                        });
                    });
                </script>
            </div>    
                    <?php 
                        wp_nonce_field('csrf_token');
                    ?>
            </form>
            <?php
                
            }
        }
        
        private function show_point_tab() {
            $current_tab = 'point';
            require( $this->get_plugin_path() . '/includes/class-gold-point-manage-user-point-list-table.php' );
            $this->manage_user_points_list_table = new Gold_Point_Manage_User_Point_List_Table();
            $this->manage_user_points_list_table->prepare_items();
            ?>
            <!--- Content -->
            <form method="post" id="mainform" action="" enctype="multipart/form-data">
            <div class="wrap">
                <h2 class="wp-heading-inline" style="display:inline;">用戶購物金管理</h2>
                <?php
                if($this->isList==='true'){ 
                    $refresh_url = add_query_arg( array('isList' => 'true', 'tab' => 'point', 'refresh' => 'true' ), admin_url( 'admin.php?page=gold-point-plugin' ) );
				    printf( '<a href="%s" class="page-title-action">%s</a>', $refresh_url, '刷新用戶購物金' );
                    //$url   = add_query_arg( array('isList' => 'false', 'tab' => $current_tab), admin_url( 'admin.php?page=gold-point-plugin' ) );
                    $this->manage_user_points_list_table->render_messages();
                    echo '<input type="hidden" name="page" value="' . esc_attr( $_REQUEST['page'] ) . '" />';
                    $this->manage_user_points_list_table->display();
                }
                if($this->isList==='false'){ 
                    //query by user id
                    if(isset($_GET['ID']) && $_GET['ID'] != null)
                        $user_data = get_user_by( 'ID', $_GET['ID'] );//$this->readUser($_GET['id']);
                       
                    if( isset($_POST['input-user-id']) && $_POST['input-user-id'] != null && check_admin_referer( 'csrf_token' )){
                       // echo "ready to save";
                        $data['user_id']=$_POST['input-user-id'];
                        $data['total']=$_POST['input-amount'];
                        $data['point_id']=$_POST['input-point-id'];
                        //print_r($data);
                        $this->addUserGold($data);
                    }
                    ?>
                    <hr class="wp-header-end" />
                    <h2>新增(刪除)用戶購物金</h2>
                    <table class="form-table">
                    <tbody>
                        <tr>
                            <th><label for="">帳號</label></th>
                            <td><?php echo $user_data->user_login; ?></td>
                            <input type="hidden" id="input-user-id" name="input-user-id" value="<?php echo $user_data->ID; ?>"/>
                        </tr>
                        <tr>
                            <th><label for="">E-mail</label></th>
                            <td><?php echo $user_data->user_email; ?></td>
                        </tr>
                        <tr>
                            <th><label for="">顯示名稱</label></th>
                            <td><?php echo $user_data->display_name; ?></td>
                        </tr>
                    </tbody>
                    </table>
                    <h2>匯入/扣除</h2>
                    <div id="pricing_options-description"><p>匯入或扣除用戶之指定購物金</p></div>
                    <table class="form-table">
                    <tbody>
                        
                        <tr>
                            <th><label for="input-point-id">購物金</label></th>
                            <td><select id="input-point-id" name="input-point-id">
                                    <?php 
                                    $gold=$this->getAllValidGold();
                                    foreach($gold as $key=>$value){
                                        echo "<option value=".$value->id.">".$value->name."</option>";
                                    }
                                    ?>
                                </select>
                                <p class="description">僅能針對效期內購物金操作</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="input-amount">數量</label></th>
                            <td>
                                <input id="input-amount" name="input-amount" value="" required />
                                <p class="description">負數表扣除，若不夠扣則忽略</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="input-note">備註</label></th>
                            <td><input id="input-note" name="input-note" value="" /></td>
                        </tr>
                        <tr valign="top">
                            <td>
                                <input type="submit" name="save" value="儲存" class="button-primary" />
                            </td>
                        </tr>
                    </tbody>
                    </table>
                    <?php
                }
                ?>
                <?php 
                    wp_nonce_field('csrf_token');
                ?>
                </div>
            </form>
            <?php
        }

        private function show_log_tab() {
            $current_tab = 'log';
            require( $this->get_plugin_path() . '/includes/class-gold-point-manage-point-log-list-table.php' );
            $this->manage_user_points_log_list_table = new Gold_Point_Manage_Point_Log_List_Table();
            $this->manage_user_points_log_list_table->prepare_items();
            ?>
            <hr class="wp-header-end" />
            <!--- Content -->
            <form method="post" id="mainform" action="" enctype="multipart/form-data">
            <div class="wrap">
                <h2 class="wp-heading-inline">購物金記錄</h2>
                <?php
                if($this->isList==='true'){ 
                    $url   = add_query_arg( array('isList' => 'false', 'tab' => $current_tab), admin_url( 'admin.php?page=gold-point-plugin' ) );
                    echo '<input type="hidden" name="page" value="' . esc_attr( $_REQUEST['page'] ) . '" />';
                    $this->manage_user_points_log_list_table->display();
                }
                if($this->isList==='false'){ 
                    
                }
        }

        private function show_settings_tab() {
            echo "N/A";
        }

        function schema_check () {
            global $wpdb;
            
            $table_gold = $wpdb->prefix . "wc_gold_points"; 
            $table_user_gold = $table_gold. "_user_points"; 
            $table_gold_log = $table_user_gold . "_log"; 
            
            $charset_collate = $wpdb->get_charset_collate();
            //PRIMARY KEY  (id), id bigint(20) NOT NULL AUTO_INCREMENT,
            //CONSTRAINT composite_key_user_gold PRIMARY KEY(user_id,point_id)
            $sql = "CREATE TABLE $table_gold (
                        id bigint(20) NOT NULL AUTO_INCREMENT,
                        name varchar (100) NOT NULL,
                        status int(11) NOT NULL COMMENT '1:啟用,0:停用',
                        create_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                        update_time  datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        start_time datetime  NOT NULL,
                        end_time datetime  NOT NULL,
                        valid_period int(11) NOT NULL DEFAULT 0,
                        valid_time_window varchar (10),
                        PRIMARY KEY  (id)
                    ) $charset_collate;
                    
                    CREATE TABLE $table_user_gold (
                       
                        user_id bigint(20) NOT NULL,
                        point_id bigint(20) NOT NULL,
                        total bigint(20) NOT NULL,
                        create_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                        update_time  datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY  (user_id, point_id)
                    ) $charset_collate;
                    
                    CREATE TABLE $table_gold_log (
                        id bigint(20) NOT NULL AUTO_INCREMENT,
                        user_id bigint(20) NOT NULL,
                        total bigint(20) NOT NULL,
                        point_id bigint(20) NOT NULL,
                        type varchar(255),
                        order_id bigint(20),
                        create_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                        PRIMARY KEY  (id)
                    ) $charset_collate;
                    
                    ";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }
        function getAllValidGold(){
            global $wpdb;
            $table_gold = $wpdb->prefix . "wc_gold_points";    
            $where = "FROM {$table_gold} where 
            ( start_time <= NOW() and end_time >= NOW() ) or (valid_period !=0 and valid_period is not NULL)
            and status = 1";        
            $sql = "select * {$where}";
            $result = $wpdb->get_results($sql);
            return $result;
        }
        function getAllGold(){
            global $wpdb;
            $table_gold = $wpdb->prefix . "wc_gold_points";            
            $sql = "select * from {$table_gold}";
            $result = $wpdb->get_results($sql);
            return $result;
        }
        function getUserGold($user_id) {
            global $wpdb;
            $groupby = 'user_id';
            $where = $wpdb->prepare( "AND user_id = %s", $user_id );
            $query = "select sum(user_points.total) as total FROM {$wpdb->prefix}users as users
                     LEFT JOIN {$wpdb->prefix}wc_gold_points_user_points as user_points ON users.ID = user_points.user_id 
                      WHERE 1=1 {$where} GROUP BY {$groupby}";
            $result = $wpdb->get_row($query, ARRAY_A, 0);
            //$result = $wpdb->get_results($query);
            return $result;
        }
        function getGold($key) {
            global $wpdb;
            $table_gold = $wpdb->prefix . "wc_gold_points"; 
            $where = "FROM {$table_gold} where id = {$key} ";
            $sql = "select * {$where} ";
            $result = $wpdb->get_row($sql, ARRAY_A, 0);
            return $result;
        }
        function refreshUserGold($user_id){
           //todo
            
        }
        //can be add or deducted
        function addUserGold($data){
            global $wpdb;
            $table_gold = $wpdb->prefix . "wc_gold_points";
            $table_user_gold = $table_gold. "_user_points"; 
            $table_gold_log = $table_user_gold . "_log"; 
            
            if($data['total']<0){
                $where = " where user_id = {$data['user_id']} and point_id ={$data['point_id']}";
                $sql = "select id, total from {$table_user_gold} {$where}";
                $result=$wpdb->get_row($sql, ARRAY_A, 0);//$wpdb->prepare(}
                if($result['total'] + $data['total'] < 0){
                    echo "不足額無法扣除";
                    return;
                }
            }
            //get period condition
            $where = " where id = {$data['point_id']}";
            $sql = "select valid_period,valid_time_window from {$table_gold} {$where}";
            $result=$wpdb->get_row($sql, ARRAY_A, 0);
            $period = $result['valid_period'];
            $period_unit = $result['valid_time_window'];

            $wpdb->query('START TRANSACTION');
            $insert_result = $wpdb->insert( $table_gold_log, $data);
            
            //第一次result2 select不到
            $groupby = 'user_id, point_id';
            $where = "{$table_gold_log} as log join {$table_gold} gold on log.point_id = gold.id
                      where user_id = {$data['user_id']} and point_id ={$data['point_id']} ";
            if($period != 0 && $period_unit != null){
                $where .= " and gold.valid_period !=0 and gold.valid_period is not NULL and 
                NOW() <= date_add(log.create_time, interval gold.valid_period {$period_unit} )";
            }else{
                $where .= " and gold.start_time <= NOW() and gold.end_time >= NOW() ";
            }
    
            $where .= "group by {$groupby}";
            $sql = "select user_id, point_id, sum(total) as totalsum from {$where}";//echo $sql;
            $agg_result=$wpdb->get_row($sql, ARRAY_A, 0);
            // print_r($agg_result);
           
            if($insert_result) {//&& $agg_result
                $update_data['total'] = $agg_result['totalsum'];
                $update_data['user_id'] = $data['user_id'];
                $update_data['point_id'] = $data['point_id'];
                $wpdb->replace($table_user_gold,$update_data);
                $wpdb->query('COMMIT'); 
            }
            else {
                $wpdb->query('ROLLBACK'); // something went wrong, Rollback
            }
        }
        function addGold($data) {
            global $wpdb;
            $table_gold = $wpdb->prefix . "wc_gold_points"; 
            $wpdb->insert( $table_gold, $data); 
            return $wpdb->insert_id;
        }
        function updateGold($data,$where) {
            global $wpdb;
            $table_gold = $wpdb->prefix . "wc_gold_points"; 
            $wpdb->update( $table_gold, $data, $where); 
            return $wpdb->insert_id;
        }
        public function get_plugin_path() {

            if ( $this->plugin_path ) {
                return $this->plugin_path;
            } 
            $this->plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );
    
            return $this->plugin_path;
        }
        function custom_datepicker() {
            wp_enqueue_style( 'jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css' );
            wp_enqueue_script('jquery-ui-core'); 
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_script( 'jquery-ui-timepicker-addon', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.js' );
            wp_enqueue_style( 'jquery-ui-timepicker-addon', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.css' );
        }

    }
    $GLOBALS['gold_point'] = Gold_Point::get_instance();
}

