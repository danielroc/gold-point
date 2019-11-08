<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Gold_Point_Repository {


    public static function addGold($data) {
        global $wpdb, $gold_point;
        $table_gold = $gold_point->tablename_point; 
        $wpdb->insert( $table_gold, $data);
        return $wpdb->insert_id;
    }
    public static function getGold($key) {
        global $wpdb, $gold_point;
        $table_gold = $gold_point->tablename_point; 
        $where = "FROM {$table_gold} where id = {$key} ";
        $sql = "select * {$where} ";
        $result = $wpdb->get_row($sql, ARRAY_A, 0);
        return $result;
    }
    public static function updateGold($data,$where) {
        global $wpdb, $gold_point;
        $wpdb->update($gold_point->tablename_point, $data, $where); 
        return $wpdb->insert_id;
    }

    public static function getAllValidGold(){
        global $wpdb, $gold_point;

        $table_gold = $gold_point->tablename_point;    
        $where = "FROM {$table_gold} where 
        ( start_time <= NOW() and end_time >= NOW() ) or (valid_period !=0 and valid_period is not NULL)
        and status = 1";        
        $sql = "select * {$where}";
        $result = $wpdb->get_results($sql);
        return $result;
    }

    public static function getUserGold($user_id) {
        global $wpdb, $gold_point;

        $table_user_gold = $gold_point->tablename_user_point; 
        $groupby = 'user_id';
        $where = $wpdb->prepare( "AND user_id = %s", $user_id );
        $query = "select sum(user_points.total) as total FROM {$wpdb->prefix}users as users
                 LEFT JOIN {$table_user_gold} as user_points ON users.ID = user_points.user_id 
                  WHERE 1=1 {$where} GROUP BY {$groupby}";
        $result = $wpdb->get_row($query, ARRAY_A, 0);
        return $result;
    }
    //can be add or deducted
    public static function addUserGold($data){
        global $wpdb, $gold_point;

        $table_gold = $gold_point->tablename_point;
        $table_user_gold = $gold_point->tablename_user_point; 
        $table_gold_log = $gold_point->tablename_point_log; 
        
        if($data['total']<0){
            $where = " where user_id = {$data['user_id']} and point_id ={$data['point_id']}";
            $sql = "select id, total from {$table_user_gold} {$where}";
            $result=$wpdb->get_row($sql, ARRAY_A, 0);//$wpdb->prepare(}
            if($result['total'] + $data['total'] < 0){
                echo "不足額無法扣除";
                return 0;
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
            return 1;
        }
        else {
            $wpdb->query('ROLLBACK'); // something went wrong, Rollback
        }
    }
}