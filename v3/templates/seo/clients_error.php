<?=
$this->header("SEO Clients","<script src='".HTTP_ROOT."/js/seo_clients.js'></script>").$this->logo().$this->navbar();
?>
<div id='bigData'>
    <p id='selectors'>
        <span class='left'>
            Month: <select id='month'><?= $this->month_options();  ?></select>
            Year: <select id='year'><?= $this->year_options(); ?></select>
        </span>
    </p>
    <div class='error shadow' style='border-radius:10px; background-color:white; border:1px black solid;'>
        <ul class='deformat'>
            <li>You must specify a month.</li>
        </ul>
    </div>
<?= $this->footer(); ?>
