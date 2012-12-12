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
			<div id='login' style='width:100%'>
			    <h1>Login</h1>
			    <form action='<?= HTTP_ROOT; ?>/Users/old_login' method='post'>
			    <ul>
				<li><label for='user'>Username </label><input class='logreginput' type='text' name='user' class='userInput'/></li>
				<li><label for='password'>Password </label><input class='logreginput' type='password' name='password'/></li>
				<li><input name='submit' type='submit' value='submit' class='logregsubmit'/></li>
			    </ul>
			    </form>
			</div>
		    </div>
		</div>
    </body>
</html>