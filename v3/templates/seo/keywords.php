<?= $this->header("Keywords","<script src='".HTTP_ROOT."/js/keywords.js'></script>").$this->logo().$this->navbar(); ?>
<div id='bigData'>
	<p id='selectors'>
	    <span class='left'>
		Client: <select id='clientSelect'><?= $this->client_options("Content",(isset($_GET['client_id']) ? $_GET['client_id'] : 0)); ?></select>
	    </span>
	    <span class='right'>
		<img src='<?= HTTP_ROOT; ?>/images/ajax-loader.gif' id='ajaxLoading'/>
	    </span>
	</p>
	<div id='snippet_window'>
	    <table id='keywords_snippet' class='articles'>
		<thead>
		    <tr>
			<th class='client'>
			    <div>
				<span class="th_controls">
				    <span class="sortMe" rel='client'></span>
				    <span class="hideColumn"></span>
				</span>
			    </div>
			    <span class='th_heading'>Client</span>
			</th>
			<th class='keyword'>
			    <div>
				<span class="th_controls">
				    <span class="sortMe" rel='keyword'></span>
				    <span class="hideColumn"></span>
				</span>
			    </div>
			    <span class='th_heading'>Keyword</span>
			</th>
			<th class='num_articles'>
			    <div>
				<span class="th_controls">
				    <span class="sortMe" rel='num_articles'></span>
				    <span class="hideColumn"></span>
				</span>
			    </div>
			    <span class='th_heading'>Number of Articles</span>
			</th>
		    </tr>
		</thead>
		<tbody>
		    <?php foreach ($data as $row): ?>
		    <tr>
			<td class='client'><?= $row->client; ?><input type='hidden' name='client_id' value='<?= $row->client_id; ?>'/></td>
			<td class='keyword'><input name='keyword_id' type='hidden' value='<?= $row->keyword_id; ?>'/><?= $row->keyword; ?></td>
			<td class='num_articles'><?= $row->num_articles; ?></td>
		    </tr>
		    <?php endforeach; ?>
		</tbody>
	    </table>
	</div>
</div>
<div id='controls'>
	<p id='saveStuff'><span id='saveOutput'></span><input type='button' value='Save Now' id='saveButton'/><input type='button' value='Turn Off Autosave' class='turnOffAutosaveButton'/></p>
</div>
<?= $this->footer(); ?>