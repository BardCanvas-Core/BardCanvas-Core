<?php
/**
 * @var module $current_module
 * @var array  $_messages
 * @var array  $_errors
 * @var array  $_country_list
 * @var string $_current_user_country
 * @var string $_cancelation_redirect
 * @var string $_submit_button_caption
 * @var string $_form_title
 * @var bool   $_include_account_id
 */

use hng2_base\module;
?>

<h1 class="clearfix">
    <? if( ! empty($_cancelation_redirect) ): ?>
        <button class="pull-right" onclick="location.href='<?= $_cancelation_redirect ?>'">
            <span class="fa fa-reply"></span>
            <?= $language->return ?>
        </button>
    <? endif; ?>
    
    <?= empty($_form_title) ? $current_module->language->edit_account_form->form_title : $_form_title ?>
</h1>

<? if($_messages): ?>
    <div class="framed_content state_ok">
        <? foreach($_messages as $_message): ?>
            <span class="fa fa-info-circle"></span><?= $_message ?><br>
        <? endforeach; ?>
    </div>
<? endif; ?>

<? if($_errors): ?>
    <div class="framed_content state_ko">
        <span class="fa fa-warning"></span>
        <?= $current_module->language->errors->registration->errors_found_in_form->pre ?>
        <ul>
            <? foreach($_errors as $_error): ?>
                <li><?= $_error ?></li>
            <? endforeach; ?>
        </ul>
        <?= $current_module->language->errors->registration->errors_found_in_form->post ?>
    </div>
<? endif; ?>

<form name="account_data_form" id="account_data_form" class="userdata_form" method="post"
      action="<?=$_SERVER["PHP_SELF"]?>">
    
    <input type="hidden" name="mode" value="save">
    <? if($_include_account_id): ?>
        <input type="hidden" name="id_account" value="<?= $xaccount->id_account ?>">
    <? endif; ?>
    
    <div class="cell">
        <div class="field_caption">
            <?= $current_module->language->user_account_form->user_name ?>
        </div>
        <div class="field_input">
            <input type="text" name="user_name" readonly
                   value="<?= htmlspecialchars($xaccount->user_name) ?>">
        </div>
    </div>
    
    <div class="cell">
        <div class="field_caption">
            <?= $current_module->language->user_account_form->display_name ?>
        </div>
        <div class="field_input">
            <input type="text" name="display_name" maxlength="255"
                   value="<?= htmlspecialchars($xaccount->display_name) ?>">
        </div>
    </div>
    
    <div class="cell">
        <div class="field_caption">
            <?= $current_module->language->user_account_form->country ?>
        </div>
        <div class="field_input">
            <select name="country">
                <option value=""><?= htmlspecialchars(trim($current_module->language->user_account_form->country_select)) ?></option>
                <? foreach($_country_list as $key => $val): ?>
                    <option <? if($key == $_current_user_country) echo "selected"; ?> value="<?= $key ?>"><?= $val ?></option>
                <? endforeach; ?>
            </select>
        </div>
    </div>
    
    <div class="cell">
        <div class="field_caption">
            <?= $current_module->language->user_account_form->email ?>
        </div>
        <div class="field_input">
            <input type="text" name="email" maxlength="255" value="<?= htmlspecialchars($xaccount->email) ?>">
        </div>
    </div>
    
    <div class="cell">
        <div class="field_caption">
            <?= $current_module->language->user_account_form->alt_email ?>
        </div>
        <div class="field_input">
            <input type="text" name="alt_email" maxlength="255" value="<?= htmlspecialchars($xaccount->alt_email) ?>">
        </div>
    </div>
    
    <div class="cell">
        <div class="field_caption">
            <?= $current_module->language->user_account_form->password_change ?>
        </div>
        <div class="field_input">
            <input type="password" name="password">
        </div>
    </div>
    
    <div class="cell">
        <div class="field_caption">
            <?= $current_module->language->user_account_form->password2 ?>
        </div>
        <div class="field_input">
            <input type="password" name="password2">
        </div>
    </div>
    
    <div align="center">
        <? if( ! empty($_cancelation_redirect) ): ?>
            <button type="reset" class="big_form_submit_button"
                    onclick="location.href='<?= $_cancelation_redirect ?>'; return false;">
                <span class="fa fa-ban"></span>
                <?= $language->cancel ?>
            </button>
        <? endif; ?>
        
        <? if( ! empty($_submit_button_caption) ): ?>
            <button type="submit" class="big_form_submit_button">
                <span class="fa fa-check"></span>
                <?= $_submit_button_caption ?>
            </button>
        <? else: ?>
            <button type="submit" class="big_form_submit_button">
                <?= $current_module->language->user_account_form->submit ?>
                <span class="fa fa-play"></span>
            </button>
        <? endif; ?>
    </div>
    
</form>
