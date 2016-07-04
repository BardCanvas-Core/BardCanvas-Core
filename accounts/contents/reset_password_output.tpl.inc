<?php
/**
 * Password reset output
 *
 * @package    HNG2
 * @subpackage accounts
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *             
 * @var array $errors
 * @var array $messages
 */
?>

<h1><?= $current_module->language->reset_form->reset_ok ?></h1>

<? if($errors): ?>
    <div class="framed_content state_ko">
        <span class="fa fa-warning"></span>
        <?= $current_module->language->reset_form->errors_found ?>
        <ul>
            <? foreach($errors as $_error): ?>
                <li><?= $_error ?></li>
            <? endforeach; ?>
        </ul>
    </div>
<? endif; ?>

<? if($messages): ?>
    <div class="framed_content state_ok">
        <? foreach($messages as $_message): ?>
            <span class="fa fa-info-circle"></span><?= $_message ?><br>
        <? endforeach; ?>
    </div>
<? endif; ?>
