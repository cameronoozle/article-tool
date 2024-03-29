<tr>
    <td class='article_id'><input type='text' readonly='true' name='article_id' value='<?= $data->article_id; ?>'/></td>
    <td class='project'><select name='project_id'><?= $this->project_options($data->project_id,4); ?></select></td>
    <td class='client'><select name='client_id'><?= $this->client_options("Content",$data->client_id); ?></select></td>
    <td class='keyword' autocomplete='true' sloppy='true' contentEditable='true' autocomplete='true' name='keyword'><?= $data->keyword; ?></td>
    <td class='target_url' autocomplete='true' sloppy='true' contentEditable='true' autocomplete='true' name='target_url'><?= $data->target_url; ?></td>
    <td class='content_network'><select name='content_network_id'><?= $this->content_network_options($data->content_network_id); ?></select></td>
    <td class='post_url' autocomplete='true' sloppy='true' name='post_url' contentEditable='true' name='post_url'><?= $data->post_url; ?></td>
    <td class='word_count' name='word_count' contentEditable='true' name='word_count'><?= $data->word_count; ?></td>
    <td class='cost' name='cost' contentEditable='true'><?= $data->cost; ?></td>
    <td class='article_status'><select name='article_status_id'><?= $this->status_options($data->article_status_id); ?></select></td>
    <td class='notes'><pre name='notes' contentEditable='true'><?= $data->notes; ?></pre></td>
    <td class='assign_td'><?= $this->assign_text($data); ?><img src='<?= HTTP_ROOT; ?>/images/ajax-circle-loader.gif' class='ajax_circle'/></td>
    <td class='delete'>
        <input type='image' src='<?= HTTP_ROOT; ?>/images/delete.png' class='delete'/>
        <img src='<?= HTTP_ROOT; ?>/images/ajax-circle-loader.gif' class='ajax_circle'/>
    </td>
</tr>