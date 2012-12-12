<tr>
    <td class='article_id'><input name='article_id' type='text' readonly='true' value='<?= $data->article_id; ?>'/></td>
    <td class='project'>
        <input type='hidden' name='asana_project_id' value='<?= $data->asana_project_id; ?>'/>
        <?= $data->project; ?>
    </td>
    <td class='client'><?= $data->client; ?></td>
    <td class='keyword'><?= $data->keyword; ?></td>
    <td class='target_url'><?= $data->target_url; ?></td>
    <td class='content_network'><?= $data->content_network; ?></td>
    <td class='post_url' contentEditable='true' name='post_url'><?= $data->post_url; ?></td>
    <td class='word_count'><?= $data->word_count; ?></td>
    <td class='status'><select name='article_status_id'><?= $this->status_options($data->article_status_id); ?></select></td>
    <td class='notes'><textarea name='notes'><?= $data->notes; ?></textarea></td>
    <td class='assign_td'>
        <?php if (!empty($data->asana_team_member_id)): ?>
        This article is assigned to you.
        <span article_id='<?= $data->article_id; ?>' class='unassign link' href='<?= HTTP_ROOT; ?>/api/Content/Articles/unassign?article_id=<?= $data->article_id; ?>&asana_task_id=<?= $data->asana_task_id; ?>'>
            Unassign
        </span>
        <?php else: ?>
        <span article_id='<?= $data->article_id; ?>' class='assign link' href='<?= HTTP_ROOT; ?>/api/Content/Articles/assign?article_id=<?= $data->article_id; ?>'>Assign to me</span>
        <?php endif; ?>
        <img src='<?= HTTP_ROOT; ?>/images/ajax-circle-loader.gif' class='ajax_circle'/>
    </td>
</tr>