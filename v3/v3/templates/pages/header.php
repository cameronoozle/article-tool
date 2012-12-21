<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>
        <meta name='robots' content='noindex,nofollow' />
        <title><?= $title; ?></title>
        <script type='text/javascript' src='<?= HTTP_ROOT; ?>/js/jquery.js'></script>
        <script type='text/javascript' src='<?= HTTP_ROOT; ?>/js/custom_jquery.js'></script>
        <script type='text/javascript' src='<?= HTTP_ROOT; ?>/js/autosave.js'></script>
        <script type='text/javascript' src='<?= HTTP_ROOT; ?>/js/seo_client_autosave.js'></script>
        <script type='text/javascript' src='<?= HTTP_ROOT; ?>/js/autocomplete.js'></script>
        <script type='text/javascript' src='<?= HTTP_ROOT; ?>/js/spreadsheetnav.js'></script>
        <script type='text/javascript' src='<?= HTTP_ROOT; ?>/js/jquery_tmpl.js'></script>
        <script type='text/javascript' src='<?= HTTP_ROOT; ?>/js/jquery_forms.js'></script>
        <script type='text/javascript' src='<?= HTTP_ROOT; ?>/js/all.js'></script>
        <script type='text/html' id='assign_admin_template'>
            <?php include('templates/content/assign_admin_jq.php'); ?>
        </script>
        <script type='text/html' id='articles_admin_snippet_template'>
            <?php
            ob_start();
            include("templates/content/articles_admin_snippet_jq.php");
            echo preg_replace('~>\s+<~', '><', ob_get_clean())
            ?>
        </script>
        <script type='text/html' id='stats_snippet_template'><?php include('templates/content/stats_snippet_jq.php'); ?></script>
        <?= $addscript; ?>
        <script type='text/javascript'>
            root = '<?= HTTP_ROOT; ?>';
            var oozleController = new oozle.Controller();
        </script>
        <link rel='stylesheet' href='<?= HTTP_ROOT; ?>/css/seo2.css' type='text/css'/>
    </head>
    <body>