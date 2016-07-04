<?php
/**
 * @var array $_errors
 */
?>

<h1><?= $current_module->language->account_confirmation->title ?></h1>

<? if($_errors): ?>
    
    <div class="framed_content state_ko">
        <span class="fa fa-warning"></span>
        <?= $current_module->language->errors->confirmation->errors_found->pre ?>
        <ul>
            <? foreach($_errors as $_error): ?>
                <li><?= $_error ?></li>
            <? endforeach; ?>
        </ul>
        <?= $current_module->language->errors->confirmation->errors_found->post ?>
    </div>
    
<? else: ?>
    
    <?= $current_module->language->account_confirmation->content ?>
    
<? endif; ?>
