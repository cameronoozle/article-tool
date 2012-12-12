<?=
$this->header("SEO Clients","<script src='".HTTP_ROOT."/js/seo_clients.js'></script>").$this->logo().$this->navbar();
?>
<div id='bigData'>
    <p id='selectors'>
        <span class='left'>
            <?php $stamp = strtotime($this->parameters['month']); ?>
            Month: <select id='month'><?= $this->month_options(date("F",$stamp));  ?></select>
            Year: <select id='year'><?= $this->year_options(date("Y",$stamp)); ?></select>
        </span>
    </p>
    <p style='display:inline-block;margin:auto;text-align:center;background-color:white;border-radius:5px;padding:5px;margin:5px;'>To change the budget for a client, edit it in Sugar.</p>
    <table id='clients_table' class='clients'>
        <thead>
            <tr>
                <th class='client_id'>
                    <span class='th_heading'>Client ID</span>
                </th>
                <th class='client_budget_id'>
                    <span class='th_heading'>Budget ID</span>
                </th>

                <th class='client'>
                    <span class='th_heading'>Client</span>
                </th>


                <th class='client_budget'>
                    <span class='th_heading'>Budget</span>
                </th>

                <th class='seo_percentage'>
                    <span class='th_heading'>SEO Percentage</span>
                </th>
                <th class='seo_budget'>
                    <span class='th_heading'>SEO Budget</span>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $client){ ?>
            <tr>
                <td class='client_id'><input name='client_id' readonly='true' value='<?= $client->client_id; ?>' size='1'/></td>
                <td class='client_budget_id'><input name='client_budget_id' readonly='true' value='<?= $client->client_budget_id; ?>' size='1'/></td>
                <td class='client'><?= $client->client; ?></td>
                <td class='budget' name='budget'><?= $client->budget; ?></td>
                <td class='seo_percentage' contentEditable='true' name='seo_percentage'><?= $client->seo_percentage; ?></td>
                <td class='seo_budget'></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
<div id='controls'>
	<p id='saveStuff'><span id='saveOutput'></span><input type='button' value='Save Now' id='saveButton'/><input type='button' value='Turn Off Autosave' class='turnOffAutosaveButton'/></p>
</div>
<?= $this->footer(); ?>
