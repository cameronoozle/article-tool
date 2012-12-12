<td class='article_id'><input type='text' readonly='true' name='article_id' value='<?= $data->article_id; ?>'/></td>
<td class='project'><select name='project_id'><?= $this->project_selects($data->project_id); ?></select></td>
<td class='client'><select name='client_id'><?= $this->client_selects($data->client_id); ?></select></td>
<td class='keyword' contentEditable='true' autocomplete='true'><?= $data->keyword; ?></td>
<td class='target_url' contentEditable='true' autocomplete='true'><?= $data->target_url; ?></td>
<td class='content_network'><select name='content_network_id'><?= $this->content_network_selects($data->content_network_id); ?></select></td>
<td class='post_url' name='post_url' contentEditable='true'><?= $data->post_url; ?></td>
<td class='word_count' name='word_count' contentEditable='true'><?= $data->word_count; ?></td>
<td class='cost' name='cost' contentEditable='true'><?= $data->cost; ?></td>
<td class='article_status'><select name='article_status_id'><?= $this->article_status_selects($data->article_status_id); ?></select></td>
<td class='notes' contentEditable='true' name='notes'><?= $data->notes; ?></td>
<td class='assign'><?= $this->assign_text($data->task_id,$data->asana_team_member_id,$data->article_id,$data->team_member); ?></td>
<td class='delete'><span class='delete'></span></td>