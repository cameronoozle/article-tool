        <div class='bug_overlay'></div>
        <div class='bug_lightbox_container'>
            <div class='bug_lightbox'>
                <span class='bug_closeout'></span>
                <div class='bug_inner_lightbox'>
                    <div class='bugdiv'>
                        <h3>Report a Bug</h3>
                        <p>Thanks for helping us to improve the Oozle Dashboard. What seems to be the trouble?</p>
                        <form name='bugreport' action='<?= HTTP_ROOT; ?>/api/All/System/submit_bug_report' method='post'>
                        <ul>
                            <input type='hidden' name='user_id' value='<?= \API\All\Users::sess_user_id(); ?>'/>
                            <li>Which browser are you using?</li>
                            <li><input type='text' name='browser'/></li>
                            <li>Which operating system are you using?</li>
                            <li><input type='text' name='operating_system'/></li>
                            <li>Your Email:</li>
                            <li><input type='text' name='user_email_address'/></li>
                            <li>Describe the Bug:</li>
                            <li><textarea name='bug_description' cols='40' rows='4'></textarea></li>
                            <li><input type='submit' name='submit' value='submit'/></li>
                        </ul>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>