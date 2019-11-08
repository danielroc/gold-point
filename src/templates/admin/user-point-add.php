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
                $gold=Gold_Point_Repository::getAllValidGold();
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
<?php wp_nonce_field('csrf_token'); ?>