<!DOCTYPE hml>
<html>
    <head>
	<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>
	<meta name='robots' content='noindex,nofollow' />
	<title>Login or Register</title>
	<link rel='stylesheet' href='<?= HTTP_ROOT; ?>/css/seo2.css' type='text/css'/>
    </head>
    <body>
	<div class='logo'><img src='http://www.oozlemedia.com/wp-content/themes/oozle/images/logo.png'/></div>
	<div id='authent_wrapper'>
	    <div id='login_or_register'>
		<div id='login' style='width:100%;'>
		    <p>You've successfully logged into the old system. Now, just a few more pieces of information (these can be the same as they were under version 2):</p>
		    <form action='<?= HTTP_ROOT; ?>/Users/register' method='post'>
			<ul>
			    <li><label for='user_email_address'>Email Address</label> <input type='text' name='user_email_address'/></li>
			    <li><label for='user_full_name'>Full Name</label> <input type='text' name='user_full_name'/></li>
			    <li><label for='user_password'>Password</label> <input type='password' name='user_password'/></li>
			    <li><label for='confirm_password'>Confirm Password</label> <input type='password' name='confirm_password'/></li>
			    <input type='hidden' name='asana_api_key' value='<?= $_SESSION['asana_api_key']; ?>'/>
			    <li><input type='submit' name='submit' value='submit'/></li>
			</ul>
		    </form>
		</div>
	    </div>
	</div>
    </body>
</html>