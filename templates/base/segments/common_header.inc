<?php
/**
 * Base template - Common header
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 *
 * @var string $_ROOT_URL
 */
?>

<!-- Base metas -->
<meta name="viewport"              content="width=device-width, initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta http-equiv="Content-Type"    content="text/html; charset=utf-8">
<meta charset="UTF-8">

<title><?= $template->get_page_title() ?></title>

<link rel="icon"          type="image/x-icon" href="<?= $template->url ?>/media/favicon.ico">
<link rel="shortcut icon" type="image/x-icon" href="<?= $template->url ?>/media/favicon.ico">

<!-- Defaults -->
<script type="text/javascript">
    var $_ROOT_URL              = '<?= $_ROOT_URL ?>';
    var $_PHP_SELF              = '<?= $_SERVER["PHP_SELF"] ?>';
    var CURRENT_USER_ID_ACCOUNT = '<?= $account->id_account ?>';
</script>
<script type="text/javascript" src="<?= $_ROOT_URL ?>/media/defaults~v<?=$config->scripts_version?>.js"></script>

<!-- jQuery -->
<script type="text/javascript" src="<?= $_ROOT_URL ?>/lib/jquery-1.11.0.min.js"></script>
<script type="text/javascript" src="<?= $_ROOT_URL ?>/lib/jquery-migrate-1.2.1.js"></script>
<script type="text/javascript" src="<?= $_ROOT_URL ?>/lib/jquery.cookie.js"></script>

<!-- jQuery UI -->
<script type="text/javascript"          src="<?= $_ROOT_URL ?>/lib/jquery-ui-1.10.4.custom.min.js"></script>
<link rel="stylesheet" type="text/css" href="<?= $_ROOT_URL ?>/lib/jquery-ui-themes-1.10.4/<?= $settings->get("engine.ui_theme", "blitzer") ?>/jquery-ui.css">
<script type="text/javascript"          src="<?= $_ROOT_URL ?>/lib/jquery.ui.touch-punch.min.js"></script>

<!-- FontAwesome -->
<link rel="stylesheet" type="text/css" href="<?= $_ROOT_URL ?>/lib/font-awesome-4.5.0/css/font-awesome.css">
<script src="//twemoji.maxcdn.com/2/twemoji.min.js"></script>
    
