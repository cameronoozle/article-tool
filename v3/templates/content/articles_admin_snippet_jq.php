<tr>
    <td class='article_id'><input type='text' readonly='true' name='article_id' value=''/></td>
    <td class='project'><select name='project_id'><?= $this->project_options(0,4); ?></select></td>
    <td class='client'><select name='client_id'><?= $this->client_options("Content",0); ?></select></td>
    <td class='keyword' autocomplete='true' sloppy='true' contentEditable='true' autocomplete='true' name='keyword'></td>
    <td class='target_url' autocomplete='true' sloppy='true' contentEditable='true' autocomplete='true' name='target_url'></td>
    <td class='content_network'><select name='content_network_id'><?= $this->content_network_options(0); ?></select></td>
    <td class='post_url' autocomplete='true' sloppy='true' name='post_url' contentEditable='true' name='post_url'></td>
    <td class='word_count' name='word_count' contentEditable='true' name='word_count'></td>
    <td class='cost' name='cost' contentEditable='true'></td>
    <td class='cost'><input type='checkbox' name='written' value='1'/></td>
    <td class='article_status'><select name='article_status_id'><?= $this->status_options(5); ?></select></td>
    <td class='notes'><pre name='notes' contentEditable='true'></pre></td>
    <td class='assign_td'><select name='team_member_id'><?= $this->team_member_options(0,4); ?></select><span class='assign link'>Assign</span><img src='<?= HTTP_ROOT; ?>/images/ajax-circle-loader.gif' class='ajax_circle'/></td>
    <td class='delete'>
        <input type='image' src='<?= HTTP_ROOT; ?>/images/delete.png' class='delete'/>
        <img src='<?= HTTP_ROOT; ?>/images/ajax-circle-loader.gif' class='ajax_circle'/>
    </td>
</tr>