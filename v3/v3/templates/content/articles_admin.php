<?= $this->header("Articles Admin","<script src='".HTTP_ROOT."/js/articles_admin.js'></script>").$this->logo(); ?>
<div id='outputInfo' style='padding:10px;'>
    <ul style='list-style-type:none;float:left;margin:0px 0px 0px 0px;'>
	<li><span class='light'>Total Cost:</span> $<span class='total_cost'></span></li>
	<li><span class='light'>Total Articles:</span> <span class='total_articles'></span></li>
	<li><span class='light'>Pending:</span></li>
    </ul>
    <ul style='list-style-type:none;margin:0px 0px 0px 10px;float:right;'>
	<li><span class='light'>In Gallery:</span></li>
	<li><span class='light'>Live:</span></li>
	<li><span class='light'>Other:</span></li>
    </ul>
</div>
<?= $this->navbar(); ?>

<div id='bigData'>
	<p id='selectors'>
	    <span class='left'>
		Month: <select id='month'><?= $this->month_options();  ?></select>
		Year: <select id='year'><?= $this->year_options(); ?></select>
		Client: <select id='clientSelect'><option></option><?= $this->client_options("Content",0,$this->parameters['month']); ?></select>
	    </span>
	    <span class='right'>
		<span style='display:inline-block;position:relative;width:220px;height:19px;'>
        	    <img src='<?= HTTP_ROOT; ?>/images/ajax-loader.gif' id='ajaxLoading' style='position:absolute;left:0px;top:0px;'/>
		</span>
		<span class='copy_link'>Copy</span>
		<a href='import' id='importFromExcel'>Import from Excel</a>
		<button id='export'>Export to CSV</button>
		<button id='importKeywords'>Import Keywords</button>
	    </span>
	</p>
	<div id='snippet_window'>
	    <table id='articles_admin_snippet' class='articles'>
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
				    <span class="sortMe" rel='clients.client'></span>
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
			<th class='cost'>
			    <div>
				<span class="th_controls">
				    <span class="sortMe" rel='cost'></span>
				    <span class="hideColumn"></span>
				</span>
			    </div>
			    <span class='th_heading'>Cost</span>
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
			<th class='article_status'>
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
				    <span class="sortMe" rel='articles.notes'></span>
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
			<th class='delete'>
			    <div>
				<span class="th_controls">
				    <span class="hideColumn"></span>
				</span>
			    </div>
			    <span class='th_heading'>Delete</span>
			</th>
		    </tr>
		    <tbody>
			
		    </tbody>
		</thead>
	    </table>
	</div>
</div>
<div id='controls'>
    <p style='display:inline-block;float:left;'><button class='addRowsButton'>Add</button> <input class='addRowsUpDown' type='number' min='1' max='20' value='1' step='1'/> rows</p>
    <p id='saveStuff'><span id='saveOutput'></span><input type='button' value='Save Now' id='saveButton'/><input type='button' value='Turn Off Autosave' class='turnOffAutosaveButton'/></p>
</div>
<ul class='autocompleteResults deformat shadow'></ul>
<div class='overlay'></div>
<div class='lightbox_container'>
    <div class='lightbox'>
	<span class='closeout'></span>
	<div class='inner_lightbox'>
	    <div class='copydiv'>
		<h3>Copy</h3>
		<p>Copy To: <select id='copytomonth'><?= $this->month_options();  ?></select> Year: <select id='copytoyear'><?= $this->year_options(); ?></select><button class='copytobutton'>Go!</button></p>
		<p>Copy From: <select id='copyfrommonth'><?= $this->month_options();  ?></select> Year: <select id='copyfromyear'><?= $this->year_options(); ?></select><button class='copyfrombutton'>Go!</button></p>
		<p class='copyoutput'></p>
	    </div>
	</div>
    </div>
</div>

<?= $this->footer(); ?>