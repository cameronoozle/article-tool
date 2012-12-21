<?php
namespace UI {
    class Content extends Page {
        public function __construct($parameters){
            parent::__construct($parameters);
        }
        
        public function articles(){
            $perm = new \Permission(1,"Content");
            if ($perm->has_permission()){
                include('templates/content/articles.php');
            } else {
                $data = array("You do not have permission to access this module.");
                include('templates/error.php');
            }
        }
        public function articles_snippet(){
            $perm = new \Permission(1,"Content");
            if ($perm->has_permission()){
                $api = new \API\Content\Articles($this->parameters);
                $model = $api->search();
                $predata = $model->data;
                if ($model->status == 'success'){
                    foreach ($predata as $data){
                        include('templates/content/articles_snippet.php');
                    }
                } else {
                    $data = $predata;
                    include('templates/error.php');
                }
            } else {
                $data = array("You do not have permission to access this module.");
                include('templates/error.php');
            }
        }
        
        protected function assign_text($data){
            ob_start();
            if (!empty($data->task_id)){
                ?>Assigned to <span class='assigned_to_name'><?= $data->team_member; ?></span>.<br/>
                <span href='<?= HTTP_ROOT; ?>/api/Content/Articles/unassign_admin?article_id=<?= $data->article_id; ?>&asana_task_id=<?= $data->asana_task_id; ?>'
                    class='unassign link'>Unassign</span><br/>
                <select name='team_member_id'><?= $this->team_member_options($data->team_member_id,4); ?></select>
                <span class='reassign link'
                    href='<?= HTTP_ROOT; ?>/api/Content/Articles/reassign?article_id=<?= $data->article_id;?>&asana_team_member_id=<?= $data->asana_team_member_id;?>&asana_task_id=<?= $data->asana_task_id;?>'>Reassign</span>
                <?php
            } else {
                ?><select name='team_member_id'><?= $this->team_member_options($data->team_member_id,4); ?></select><span class='assign link'>Assign</span><?php
            }
            return ob_get_clean();
        }
        
        public function articles_admin_snippet(){
            ob_start();
            $perm = new \Permission(2,"Content");
            if ($perm->has_permission()){
                $api = new \API\Content\Articles($this->parameters);
                $model = $api->search_admin();
                $predata = $model->data;
                if ($model->status == 'success'){
                    foreach ($predata as $data){
                        include('templates/content/articles_admin_snippet.php');
                    }
                } else {
                    $data = $predata;
                    include('templates/error.php');
                }
            } else {
                $data = array("You do not have permission to access this module.");
                include('templates/error.php');
            }
            echo preg_replace('~>\s+<~', '><', ob_get_clean());
        }
        public function articles_admin(){
            $perm = new \Permission(2,"Content");
            if ($perm->has_permission()){
                include('templates/content/articles_admin.php');
            } else {
                $data = array("You do not have permission to access this module.");
                include('templates/error.php');
            }
        }
        public function permissions(){
            $this->permissions_callback(4,"Content");
        }
    }
}
?>