<?= $this->header("Articles","<script src='".HTTP_ROOT."/js/articles.js'></script>").$this->logo().$this->navbar(); ?>
<div id='bigData'>
	<p id='selectors'>
	    <span class='left'>
		Month: <select id='month'><?= $this->month_options();  ?></select>
		Year: <select id='year'><?= $this->year_options(); ?></select>
		Client: <select id='clientSelect'><option></option><?= $this->client_options("Content"); ?></select>
	    </span>
	    <span class='right'>
		<img src='<?= HTTP_ROOT; ?>/images/ajax-loader.gif' id='ajaxLoading'/>
		<a href='import' id='importFromExcel'>Import from Excel</a>
		<button id='export'>Export to CSV</button>
		<button id='importKeywords'>Import Keywords</button>
	    </span>
	</p>
	<div id='snippet_window'>
	    <table id='articles_snippet' class='articles'>
		<thead>
		    <tr>
			<th class='article_id'>
			    <div>
				<span class="th_controls">
				    <span class="sortMe" rel='article_id'></span>
				    <span class="hideColumn"></span>
				</span>
			    </div>
			    <span class='th_heading'>Article ID</span>
			</th>
			<th class='project'>
			    <div>
				<span class="th_controls">
				    <span class="sortMe" rel='project'></span>
				    <span class="hideColumn"></span>
				</span>
			    </div>
			    <span class='th_heading'>Project</span>
			</th>
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
				    <span class="sortMe" rel='keywords.keyword'></span>
				    <span class="hideColumn"></span>
				</span>
			    </div>
			    <span class='th_heading'>Keyword</span>
			</th>
			<th class='target_url'>
			    <div>
				<span class="th_controls">
				    <span class="sortMe" rel='target_url'></span>
				    <span class="hideColumn"></span>
				</span>
			    </div>
			    <span class='th_heading'>Target URL</span>
			</th>
			<th class='content_network'>
			    <div>
				<span class="th_controls">
				    <span class="sortMe" rel='content_network'></span>
				    <span class="hideColumn"></span>
				</span>
			    </div>
			    <span class='th_heading'>Content Network</span>
			</th>
			<th class='post_url'>
			    <div>
				<span class="th_controls">
				    <span class="sortMe" rel='post_url'></span>
				    <span class="hideColumn"></span>
				</span>
			    </div>
			    <span class='th_heading'>Post URL</span>
			</th>
			<th class='word_count'>
			    <div>
				<span class="th_controls">
				    <span class="sortMe" rel='word_count'></span>
				    <span class="hideColumn"></span>
				</span>
			    </div>
			    <span class='th_heading'>Word Count</span>
			</th>
			<th class='written'>
			    <div>
				<span class="th_controls">
				    <span class="sortMe" rel='written'></span>
				    <span class="hideColumn"></span>
				</span>
			    </div>
			    <span class='th_heading'>Written</span>
			</th>
			<th class='status'>
			    <div>
				<span class="th_controls">
				    <span class="sortMe" rel='article_status_id'></span>
				    <span class="hideColumn"></span>
				</span>
			    </div>
			    <span class='th_heading'>Status</span>
			</th>
			<th class='notes'>
			    <div>
				<span class="th_controls">
				    <span class="sortMe" rel='notes'></span>
				    <span class="hideColumn"></span>
				</span>
			    </div>
			    <span class='th_heading'>Notes</span>
			</th>
			<th class='assign_td'>
			    <div>
				<span class="th_controls">
				    <span class="sortMe" rel='assign'></span>
				    <span class="hideColumn"></span>
				</span>
			    </div>
			    <span class='th_heading'>Assign</span>
			</th>
		    </tr>
		</thead>
		<tbody>
		    
		</tbody>
	    </table>
	</div>
</div>
<div id='controls'>
	<p id='saveStuff'><span id='saveOutput'></span><input type='button' value='Save Now' id='saveButton'/><input type='button' value='Turn Off Autosave' class='turnOffAutosaveButton'/></p>
</div>
<?= $this->footer(); ?>