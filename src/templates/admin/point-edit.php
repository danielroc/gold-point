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
<?php wp_nonce_field('csrf_token'); ?>