<?php
/**
 * Base template - Common header
 *
 * @package    HNG2
 * @subpackage core
 * @author     Alejandro Caballero - lava.caballero@gmail.com
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
    var $_FULL_ROOT_PATH          = '<?= $config->full_root_path ?>';
    var $_PHP_SELF                = '<?= $_SERVER["PHP_SELF"] ?>';
    var $_CURRENT_USER_ID_ACCOUNT = '<?= $account->id_account ?>';
    var $_CURRENT_USER_LANGUAGE   = '<?= $_COOKIE["{$config->website_key}_UL"] ?>';
    var $_CURRENT_USER_LEVEL      = parseInt('<?= $account->level ?>');
    var $_CURRENT_USER_IS_ADMIN   = <?= $account->_is_admin ? "true" : "false" ?>;
    var $_TEMPLATE_URL            = '<?= $template->url ?>';
    var $_SCRIPTS_VERSION         = '<?= $config->scripts_version ?>';
</script>
<script type="text/javascript" src="<?= $config->full_root_path ?>/media/defaults~v<?=$config->scripts_version?>.js"></script>

<!-- jQuery -->
<script type="text/javascript" src="<?= $config->full_root_path ?>/lib/jquery-1.11.0.min.js"></script>
<script type="text/javascript" src="<?= $config->full_root_path ?>/lib/jquery-migrate-1.2.1.js"></script>
<script type="text/javascript" src="<?= $config->full_root_path ?>/lib/jquery.cookie.js"></script>

<!-- jQuery UI -->
<script type="text/javascript"          src="<?= $config->full_root_path ?>/lib/jquery-ui-1.10.4.custom.min.js"></script>
<link rel="stylesheet" type="text/css" href="<?= $config->full_root_path ?>/lib/jquery-ui-themes-1.10.4/<?= $settings->get("engine.ui_theme", "blitzer") ?>/jquery-ui.css">
<script type="text/javascript"          src="<?= $config->full_root_path ?>/lib/jquery.ui.touch-punch.min.js"></script>

<!-- UI Additions -->
<link rel="stylesheet" type="text/css" href="<?= $config->full_root_path ?>/lib/font-awesome-4.6.3/css/font-awesome.css">
