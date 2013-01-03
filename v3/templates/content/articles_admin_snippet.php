<tr>
    <td class='article_id'><input type='text' readonly='true' name='article_id' value='<?= $data->article_id; ?>'/></td>
    <td class='month'><?= date("F Y",strtotime($data->month));?></td>
    <td class='project'>
        <input type='hidden' name='asana_project_id' value='<?= $data->asana_project_id; ?>'/>
        <select name='project_id'><?= $this->project_options($data->project_id,4); ?></select></td>
    <td class='client'><select name='client_id'><?= $this->client_options("Content",$data->client_id); ?></select></td>
    <td class='keyword' autocomplete='true' sloppy='true' contentEditable='true' autocomplete='true' name='keyword'><?= $data->keyword; ?></td>
    <td class='target_url' autocomplete='true' sloppy='true' contentEditable='true' autocomplete='true' name='target_url'><?= $data->target_url; ?></td>
    <td class='content_network'><select name='content_network_id'><?= $this->content_network_options($data->content_network_id); ?></select></td>
    <td class='post_url' autocomplete='true' sloppy='true' name='post_url' contentEditable='true' name='post_url'><?= $data->post_url; ?></td>
    <td class='word_count' name='word_count' contentEditable='true' name='word_count'><?= $data->word_count; ?></td>
    <td class='cost' name='cost' contentEditable='true'><?= $data->cost; ?></td>
    <td class='written'><input type='checkbox' value='1' name='written'<?= $data->written == 1 ? " checked='checked'" : ""; ?>/></td>
    <td class='article_status'><select name='article_status_id'><?= $this->status_options($data->article_status_id); ?></select></td>
    <td class='notes'><pre name='notes' contentEditable='true'><?= $data->notes; ?></pre></td>
    <td class='due_date'>
        <input type='hidden' name='due_on' value='<?php $stamp = strtotime($data->due_on); echo (!empty($stamp) ? $data->due_on : ""); ?>'/>
        <input type='text' name='due_mirror' value='<?php echo (!empty($stamp) ? date("d/m/Y",$stamp) : ""); ?>' size='6'/>
        <!-- date("d/m/Y",strtotime($data->due_on)); -->
    </td>
    <td class='assign_td'>
        <?= $this->assign_text($data); ?>
        <img src='<?= HTTP_ROOT; ?>/images/ajax-circle-loader.gif' class='ajax_circle'/>
        <input type='hidden' name='asana_task_id' value='<?= (!empty($data->asana_task_id) ? $data->asana_task_id : ""); ?>'/>
    </td>
    <td class='delete'>
        <input type='image' src='<?= HTTP_ROOT; ?>/images/delete.png' class='delete' href='<?= HTTP_ROOT; ?>/api/Content/Articles/delete?article_id=<?= $data->article_id; ?>'/>
        <img src='<?= HTTP_ROOT; ?>/images/ajax-circle-loader.gif' class='ajax_circle'/>
    </td>
</tr>