<?= $this->header("Login or Register").$this->logo(); ?>
<div id='authent_wrapper'>
    <p style='clear:both;'>Welcome to a cleaner, more fully-featured and secure Oozle Dashboard.</p>
    <p>If you haven't registered under the new version of Oozle Dashboard, we'll need a couple of extra pieces of information. Click <a href='<?= HTTP_ROOT; ?>/Users/old_login'>here</a> to <a href='<?= HTTP_ROOT; ?>/Users/old_login'>login using your old credentials</a>.</p>
    <?php if (!empty($data)){ ?>
    <div id='authen_errors_container'>
	<ul id='authen_errors'>
	    <?php foreach ($data as $item){?>
	    <li><?= $item; ?></li>
	    <?php } ?>
	</ul>
    </div>
    <?php } ?>
    <div id='login_or_register'>
	<div id='login'>
	    <h1>Login</h1>
		<form action='<?= HTTP_ROOT; ?>/Users/login' method='post'>
		    <ul>
			<li><label for='user_email_address'>Email </label><input class='logreginput' type='text' name='user_email_address' class='userInput'/></li>
			<li><label for='user_password'>Password </label><input class='logreginput' type='password' name='user_password'/></li>
			<li><label for='stay_logged_in'>Stay Logged In</label><input style='float:right;' type='checkbox' name='stay_logged_in' value='true'/></li>
			<li><input type='submit' name='submit' value='submit' class='logregsubmit'/></li>
		    </ul>
		</form>
		</div>
		<div id='register'>
		    <h1>Register</h1>
		    <form action='<?= HTTP_ROOT; ?>/Users/register' method='post'>
			<input type='hidden' name='register' value='true'/>
			<?php if (isset($this->parameters['registration_status'])) echo "<p style='margin:0px auto 10px auto;font-family:sans-serif;width:90%;font-size:12px;color:red;'>".$this->parameters['registration_status']['data']."</p>"; ?>
			<ul>
			    <li><label for='user_full_name'>Full Name </label><input class='logreginput' value='' type='text' name='user_full_name' class='userInput'/></li>
			    <li><label for='user_email_address'>Email </label><input class='logreginput' value='' type='text' name='user_email_address' class='userInput'/></li>
			    <li><label for='user_password'>Password </label><input class='logreginput'  value='' type='password' name='user_password'/></li>
			    <li><label for='confirm_password'>Confirm Password </label><input class='logreginput' value='' type='password' name='confirm_password'/></li>
			    <li><label for='asana_api_key'>Asana API Key </label><input class='logreginput' value='' type='text' name='asana_api_key'/></li>
			    <li><a target='_new' class='get_asana_key' href='https://app.asana.com/-/account_api'>Click here to get your Asana API Key</a></li>
			    <li><span class='validation_message' style='color:red;font-size:11px;font-family:sans-serif;'></span><input name='submit' type='submit' value='submit' class='logregsubmit'/></li>
			</ul>
		    </form>
		</div>
	</div>
</div>
<?= $this->footer(); ?>