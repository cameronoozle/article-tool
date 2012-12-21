<ul class='navbar shadow'>
    <?php foreach ($data as $dept){ ?>
        <li class='menu'>
            <?= $dept->department_name; ?>
            <ul class='shadow'>
                <?php foreach ($dept->modules as $module){ ?>
                <li class='menu_item'><a href='<?= HTTP_ROOT."/".preg_replace("/\s/","_",$dept->department_name)."/".strtolower(preg_replace("/\s/","_",$module)); ?>'><?= $module; ?></a></li>
                <?php } ?>
            </ul>
        </li>
    <?php } ?>
    <span class='bug_report_link menu'>Report a Bug</span>
</ul>