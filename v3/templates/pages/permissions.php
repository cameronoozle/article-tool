<?= $this->header("Permissions","<script src='".HTTP_ROOT."/js/permissions.js'></script>").$this->logo().$this->navbar(); ?>
<div id='bigData'>
    <div class='permissions_box'>
        <h1>Set Permissions</h1>
        <p>
        <?php foreach ($data->pay_grades as $pay_grade): ?>
        <span class='shadow' style='margin-right:10px;padding:5px;background-color:white;border:1px #333333 solid;'><?= $pay_grade->pay_grade_id." - ".$pay_grade->pay_grade." "; ?></span>        
        <?php endforeach; ?>
        </p>
        <table>
            <tr>
                <th>User</th>
                <th>Pay Grade</th>
            </tr>
            <?php foreach ($data->users as $user): ?>
            <tr>
                <td>
                    <?= $user->user_full_name; ?>
                    <input type='hidden' name='permission_id' value='<?= $user->permission_id; ?>'/>
                    <input type='hidden' name='department_id' value='<?= $user->department_id; ?>'/>
                </td>
                <td>
                    <select name='pay_grade_id'>
                        <?php for ($i=1;$i<4;$i++): ?>
                        <option <?= ($i == $user->pay_grade_id ? " selected='selected'" : ""); ?>><?= $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <p style='text-align:center;'><input type='submit' name='submit' value='save'/></p>
    </div>
</div>
<?= $this->footer(); ?>