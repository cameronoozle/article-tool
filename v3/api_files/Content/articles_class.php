<?php
namespace API\Content {
    class Articles extends \Endpoint {
        private $insert_fields;
        private $join_fields;
        private $workspace_id;
        private $dupkeys;
        
        public function vampire_tasks(){
            $r = new \Requester();
            $db = $this->get_db();
            $i = $r->get("http://oozlemedia.net/seo/v2/api/articles/dump_tasks");
            $info = (json_decode($i['contents']));
            print_r($info);
            $query = "INSERT IGNORE INTO tasks (asana_task_id,asana_team_member_id) VALUES ";
            $arr = array();
            foreach ($info->data as $row){
                if (!empty($row->writing_team)){
                    $d = $db->query("SELECT asana_team_member_id FROM team_members WHERE team_member = '".$db->esc($row->writing_team)."'");
                    print_r($d);
                    if (count($d['rows']) > 0)
                        array_push($arr,"(".$row->task_id.",".$d['rows'][0]['asana_team_member_id'].")");
                }
            }
            $db->query(substr_replace($query,"",-1,1));
        }
        
        public function vampire(){
            $r = new \Requester();
            $db = $this->get_db();
            $i = $r->get("http://oozlemedia.net/seo/v2/api/articles/dump");
            $info = (json_decode($i['contents']));
//            print_r($info);
            $query = "INSERT INTO articles (
            article_id,client_id,team_member_id,content_network_id,
            keyword_id,project_id,task_id,target_url,post_url,outsource,month,
            word_count,cost,article_status_id,outsource_order_placed,date_last_updated,notes
            ) ";
            $subqueries = array();
            foreach ($info->data as $row){
                $subquery = "SELECT ".
                "'".$db->esc($row->article_id)."',".
                "'".$db->esc($row->client_id)."',".
                "(SELECT team_member_id FROM team_members WHERE team_member = '".$db->esc($row->writing_team_id)."'),".
                "(SELECT content_network_id FROM content_networks WHERE content_network = '".$db->esc($row->post_location)."'),".
                "'".$db->esc($row->keyword_id)."',".
                "'".$db->esc($row->project_id)."',".
                "(SELECT task_id FROM tasks WHERE asana_task_id = '".$db->esc($row->task_id)."'),".
                "'".$db->esc($row->target_url)."',".
                "'".$db->esc($row->post_url)."',".
                "'0',".
                "'".$db->esc($row->month)."',".
                "'".$db->esc($row->word_count)."',".
                "'".$db->esc($row->cost)."',";
                if (!empty($row->posted))
                    $subquery .= "4,";
                else if (!empty($row->received_or_completed))
                    $subquery .= "6,";
                else
                    $subquery .= "5,";
                $subquery .= "0,".
                "'".$db->esc($row->date_last_updated)."',".
                "'".$db->esc($row->notes)."' ".
                "FROM tasks";
                array_push($subqueries,$subquery);
            }
            $query = $query.implode(" UNION ",$subqueries);
//            echo $query."\n\n";
            $d = $db->query($query);
//            print_r($d);
        }
        
        public function __construct($parameters){
            $this->workspace_id = "626921128718";
            parent::__construct($parameters);
        }
        public function copy(){
            $reqs = new \Required_Parameters(array(),array("from"=>\Types::Datetime,"to"=>\Types::Datetime,"client_id"=>\Types::Int));
            return $this->validate_output($reqs,false,new \Permission(2,"Content"),array($this,"copy_callback"));
        }
        public function copy_callback(){
            $db = $this->get_db();
            $query = "INSERT INTO articles ".
            "(client_id,content_network_id,keyword_id,project_id,target_url,outsource,month,word_count,cost) ".
            "SELECT '".$db->esc($this->parameters['client_id'])."',content_network_id,keyword_id,project_id,".
            "target_url,outsource,'".$db->esc($this->parameters['to'])."',word_count,cost ".
            "FROM articles WHERE month = '".$db->esc($this->parameters['from'])."' AND client_id='".$db->esc($this->parameters['client_id'])."'";
            $db->query($query);
            return $this->success(array("Month successfully copied."));
        }
        
        public function import_keywords(){
            $reqs = new \Required_Parameters(array(),array("client_id"=>\Types::Int,"month"=>\Types::Datetime));
            return $this->validate_output($reqs,false,new \Permission(2,"Content"),array($this,"import_keywords_callback"));
        }
        
        public function import_keywords_callback(){
            $db = $this->get_db();
            $query = "INSERT INTO articles (client_id,keyword_id,month) ".
            "SELECT DISTINCT '".$db->esc($this->parameters['client_id'])."',keyword_id,'".$db->esc($this->parameters['month'])."' ".
            "FROM articles WHERE client_id = ".$db->esc($this->parameters['client_id'])." GROUP BY keyword_id";
            $db->query($query);
            return $this->success(array("Keywords successfully imported."));
        }
        
        public function search_content_networks(){
            return $this->validate_output(new \Required_Parameters(),false,new \Permission(1,"Content"),array($this,"search_content_networks_callback"));
        }
        public function search_content_networks_callback(){
            $db = $this->get_db();
            $d = $db->query("SELECT * FROM content_networks");
            return $this->success($d['rows']);
        }
        
        public function search_statuses(){
            $reqs = new \Required_Parameters();
            return $this->validate_output($reqs,false,new \Permission(1,"Content"),array($this,"search_statuses_callback"));
        }
        public function search_statuses_callback(){
            $db = $this->get_db();
            $d = $db->query("SELECT * FROM article_statuses");
            return $this->success($d['rows']);
        }
        
        public function save(){
            $reqs = new \Required_Parameters(array("month"=>\Types::Datetime,"article_id"=>\Types::Int));
            return $this->validate_output($reqs,true,new \Permission(1,"Content"),array($this,"save_callback"));
        }
        public function save_callback(){
            $db = $this->get_db();
            //Fields to insert that you can put directly into the table.
            $this->insert_fields = array("outsource"=>\Types::Bool,
                "month"=>\Types::Datetime,"word_count"=>\Types::Int,
                "cost"=>\Types::Float,"outsource_order_placed"=>\Types::Bool,
                "notes"=>\Types::String,"post_url"=>\Types::String,"target_url"=>\Types::String);
            //Fields that refer to other tables based on ID.
            $this->join_fields = array("client","team_member","content_network","project","task","article_status");
            $inserts = array();
            //You only want to update a field if the user submitted a parameter for that field, so the dupkeys array keeps track
            //of which parameters have been submitted so it only updates those fields and we don't get any fields inappropriately set to null.
            $this->dupkeys = array();
            //Use our callback so that we have all the insert queries we need, regardless of the dimensionality of the parameters.
            if (\Array_Manager::is_multidimensional($this->parameters)){
                foreach ($this->parameters as $parameters){
                    array_push($inserts,$this->subquery($parameters));
                }
            } else {
                array_push($inserts,$this->subquery($this->parameters));
            }
            //Even if we don't change any of the fields, we want the date_last_updated field to update so that we can get all of the inserted fields.
            array_push($this->dupkeys,"date_last_updated = NOW()");
            $query1 = "INSERT INTO articles (article_id,".
                implode(",",array_map(array($this,"add_id"),$this->join_fields)).",".
                \Handy::implode_keys(",",$this->insert_fields).",keyword_id) ".
                implode(" UNION ",$inserts)." ON DUPLICATE KEY UPDATE ".implode(",",$this->dupkeys);
            $d = $db->query($query1);
            //Get all of the fields inserted into the table.
            $d = $db->query("SELECT article_id FROM articles WHERE date_last_updated = (SELECT MAX(date_last_updated) FROM articles) LIMIT 1");
            return $this->success(array("rows"=>$d['rows'],"query"=>$query1));
        }
        private function subquery($parameters){
                //The arr is just an array of values to insert into the table straight up.
                $arr = array();
                $db = $this->get_db();
                //The insert fields can be updated directly within the table, because they don't refer to anything else.
                foreach ($this->insert_fields as $name=>$val){
                    if ((isset($parameters[$name]))&&(\Types::matches_type($parameters[$name],$val)))
                        $this->add_dupkey($name." = VALUES ($name)");
                }
                //The join fields: if a string or other numeric value has been input for the client, put it in there.
                //If a numeric ID has been specified, put it in there. String or ID, they all translate into IDs in the end, because of the sub_fk_query.
                foreach ($this->join_fields as $val){
                    if ((isset($parameters[$val]))||((isset($parameters[$val."_id"]))&&(is_numeric($parameters[$val."_id"])))){
                        $this->add_dupkey($val."_id = VALUES(".$val."_id)");
                    }
                }
                array_push($arr,((isset($parameters['article_id']))&&(is_numeric($parameters['article_id'])) ? $db->esc(intval($parameters['article_id'])) : "null"));
                //The sub_fkey_query method says either null,the id number, or select id number from table where string_val = string_val.
                for ($i=0;$i<count($this->join_fields);$i++){
                    array_push($arr,$this->sub_fkey_query($this->join_fields[$i],$parameters));
                }
                foreach ($this->insert_fields as $fname=>$fval){
                    if ((isset($parameters[$fname]))&&(\Types::matches_type($parameters[$fname],$fval))){
                        if ($fname == 'month'){
                            //Set the month timestamp to the beginning of the month specified.
                            $stamp = strtotime($parameters[$fname]);
                            array_push($arr,"'".$db->esc(date("Y-m-d H:i:s", strtotime(date('m',$stamp).'/01/'.date('Y',$stamp).' 00:00:00')))."'");
                        } else {
                            //Put the value into the query straight up.
                            array_push($arr,"'".$db->esc($parameters[$fname])."'");
                        }
                    } else {
                        array_push($arr,"null");
                    }
                }
                if (!empty($parameters['keyword'])){
                    $this->add_dupkey("keyword_id = VALUES(keyword_id)");
                    $query = "SELECT keyword_id FROM keywords WHERE keyword = '".$db->esc($parameters['keyword'])."'";
                    $d = $db->query($query);
                    if (count($d['rows']) > 0){
                        array_push($arr,"'".$db->esc($d['rows'][0]['keyword_id'])."'");
                    } else {
                        $query = "INSERT IGNORE INTO keywords (keyword) VALUES ('".$db->esc($parameters['keyword'])."')";
                        $d = $db->query($query);
                        array_push($arr,"'".$db->esc($d['insert_id'])."'");
                    }
                } else if ((!empty($parameters['keyword_id']))&&(is_numeric($parameters['keyword_id']))){
                    $this->add_dupkey("keyword_id = VALUES(keyword_id)");
                    array_push($arr,$db->esc($parameters['keyword_id']));
                } else {
                    array_push($arr,"null");
                }
                return "SELECT ".implode(",",$arr)." FROM clients LIMIT 1";
        }
        private function add_dupkey($str){
            //Kind of like INSERT IGNORE.
            if (!in_array($str,$this->dupkeys))
                array_push($this->dupkeys,$str);
        }
        private function sub_fkey_query($field,$parameters){
            //return null, id_number, or (SELECT id_number FROM table WHERE field=field)
            $db = $this->get_db();
            if ((isset($parameters[$field."_id"]))&&(is_numeric($parameters[$field."_id"])))
                return "'".$db->esc(intval($parameters[$field."_id"]))."'";
            else if (isset($parameters[$field]))
                return "(SELECT ".$field."_id FROM ".$field."s WHERE ".$field." = '".$db->esc($parameters[$field])."')";
            else
                return "null";
        }
        
        private function add_id($str){
            //This is just syntactical, so we can work with the names themselves programmatically while putting the IDs into the actual query.
            return $str."_id";
        }
        
        public function save_keyword(){
            $reqs = new \Required_Parameters(array(),array("keyword"=>\Types::String));
            return $this->validate_output($reqs,true,new \Permission(2,array("Content","SEO")),array($this,"save_keyword_callback"));
        }
        public function save_keyword_callback(){
            $db = $this->get_db();
            //We could insert a new keyword into the database and 
            $query = "INSERT INTO keywords VALUES () ON DUPLICATE KEY UPDATE keyword=VALUES(keyword)";
        }
        
        public function search(){
            $reqs = new \Required_Parameters(array(),array("month"=>\Types::Datetime));
            return $this->validate_output($reqs,false,new \Permission(1,"Content"),array($this,"search_callback"));
        }
        public function search_callback(){
            return $this->search_callback2(false);
        }
        public function search_admin(){
            $reqs = new \Required_Parameters(array(),array("month"=>\Types::Datetime));
            return $this->validate_output($reqs,false,new \Permission(2,"Content"),array($this,"search_admin_callback"));
        }
        public function search_admin_callback(){
            return $this->search_callback2(true);
        }        
        public function search_callback2($admin){
            $db = $this->get_db();
            $wheres = array("month = '".$db->esc($this->parameters['month'])."'");
            $opts = array("task","project","keyword","content_network","team_member","client");
            foreach ($opts as $opt){
                if ((isset($this->parameters[$opt."_id"]))&&(is_numeric($this->parameters[$opt."_id"])))
                    array_push($wheres,"articles.".$opt."_id = '".$db->esc(intval($this->parameters[$opt."_id"]))."'");
                if (isset($this->parameters[$opt]))
                    array_push($wheres,$opt." = '".$db->esc($this->parameters[$opt])."'");
            }
            $others = array("outsource"=>\Types::Bool,"word_count"=>\Types::Int,"cost"=>\Types::Float,"outsource_order_placed"=>\Types::Bool,
                            "date_last_update"=>\Types::Datetime,"notes"=>\Types::String,"post_url"=>\Types::String,"target_url"=>\Types::String);
            foreach ($others as $name=>$val){
                if ((isset($this->parameters[$name]))&&(\Types::matches_type($this->parameters[$name],$val)))
                    array_push($wheres,"articles.".$opt." = '".$this->parameters[$name]."'");
            }
            if (!$admin){
                //Projects must be assigned for writers to see them.
                array_push($wheres,"project IS NOT NULL");
                //Writers cannot see tasks that are assigned to other people.
                array_push($wheres,"(tasks.asana_team_member_id = '".\API\All\Users::asana_team_member_id()."' OR task_id IS NULL)");
            }
            $joins = array("client","content_network","keyword","project","task");
            $query = "SELECT *,clients.client,articles.client_id,articles.notes,keywords.keyword FROM articles ";
            $orders = array("article_id","project","client","keyword","target_url","content_network","post_url","word_count","article_status_id","notes","cost");
            foreach ($joins as $join) $query .= "LEFT JOIN ".$join."s USING (".$join."_id) ";
            $query .= "LEFT JOIN team_members USING (asana_team_member_id) ";
            $query .= "WHERE ".implode(" AND ",$wheres)." ORDER BY ".
            ((isset($this->parameters['order_by']))&&(in_array($this->parameters['order_by'],$orders)) ?
                $this->parameters['order_by'] :
                "clients.client"
            )." ".((isset($this->parameters['order_dir'])&&($this->parameters['order_dir'] == "desc")) ? strtoupper($this->parameters['order_dir']) : "ASC")." ".$this->pagify();
            $d = $db->query($query);
            return $this->success($d['rows']);
        }
        public function delete(){
            $reqs = new \Required_Parameters(array(),array("article_id"=>\Types::Int));
            return $this->validate_output($reqs,false,new \Permission(2,"Content"),array($this,"delete_callback"));            
        }
        public function delete_callback(){
            $db = $this->get_db();
            $d = $db->query("DELETE FROM articles WHERE article_id = ".$db->esc($this->parameters['article_id']));
            return $this->success(array("Article successfully deleted"));
        }
        public function assign(){
            $reqs = new \Required_Parameters(array(),array("article_id"=>\Types::Int));
            return $this->validate_output($reqs,false,new \Permission(1,"Content"),array($this,"assign_callback"));
        }
        public function assign_callback(){
            $db = $this->get_db();
            $d = $db->query("SELECT client,word_count,project,keyword,content_network,notes,target_url,post_url FROM articles ".
                            "LEFT JOIN clients USING (client_id) ".
                            "LEFT JOIN projects USING (project_id) ".
                            "LEFT JOIN keywords USING (keyword_id) ".
                            "LEFT JOIN content_networks USING (content_network_id) ".
                            "WHERE article_id = ".$db->esc($this->parameters['article_id'])." LIMIT 1");
            if (count($d['rows']) > 0){
                $notes = "";
                foreach ($d['rows'][0] as $name=>$val){
                    $notes .= $name.": ".$val."\n";
                }
                $tparams = array(
                    "asana_workspace_id"=>$this->workspace_id,
                    "notes"=>$notes,
                    "assignee"=>\API\All\Users::asana_team_member_id(),
                    "name"=>$d['rows'][0]['client']." Content");
                foreach (array("client","article_id","keyword","content_network","target_url","word_count","notes") as $val)
                    if (!empty($this->parameters[$val]))
                        $tparams["notes"] .= $val.": ".$this->parameters[$val]."\n";
                $tasks = new \API\All\Tasks($tparams);
                $data = $tasks->create();
                if (!empty($this->parameters['asana_project_id'])){
                    $d = $tasks->add_project($data->data->asana_task_id,$this->parameters['asana_project_id']);
                }
                if ($data->status == 'success')
                    $db->query("UPDATE articles SET task_id = ".$db->esc($data->data->task_id).", notes='".$db->esc($notes)."' WHERE article_id = ".$db->esc($this->parameters['article_id']));
                $data->params = $this->parameters;
                return $data;
            } else {
                return $this->error(array("Article not found."));
            }
        }
        public function stats(){
            $reqs = new \Required_Parameters(array(),array("month"=>\Types::Datetime,"admin"=>\Types::Bool));
            return $this->validate_output($reqs,false,new \Permission(1,"Content"),array($this,"stats_callback"));
        }
        public function stats_callback(){
            $db = $this->get_db();
            $opts = array("client_id"=>\Types::Int,"month"=>\Types::Datetime);
            $wheres = array();
            foreach ($this->parameters as $name=>$value){
                if ((isset($opts[$name]))&&\Types::matches_type($value,$opts[$name]))
                    array_push($wheres,$name." = '".$db->esc($value)."'");
            }
            if ($this->parameters['admin'] == 0)
                array_push($wheres,"project_id != 0");
            $whereclause = implode(" AND ",$wheres);
            $query1 =
            "SELECT LOWER(REPLACE(article_status,' ','_')) article_status, COUNT(article_id) num_articles ".
            "FROM article_statuses ".
            "LEFT JOIN (SELECT * FROM articles WHERE ".$whereclause.") articles USING (article_status_id) GROUP BY article_status_id";
            $d1 = $db->query($query1);
            $output = array();
            $output['total_articles'] = 0;
            for ($i=0;$i<count($d1['rows']);$i++){
                $output[$d1['rows'][$i]['article_status']] = $d1['rows'][$i]['num_articles'];
                $output['total_articles'] += $d1['rows'][$i]['num_articles'];
            }
            $query2 = "SELECT SUM(cost) total_cost FROM articles WHERE ".$whereclause;
            $d2 = $db->query($query2);
            $output['total_cost'] = number_format($d2['rows'][0]['total_cost'],2,".","");
            return $this->success($output);
        }
        public function assign_admin(){
            $reqs = new \Required_Parameters(array(),array("article_id"=>\Types::Int,"team_member_id"=>\Types::Int));
            return $this->validate_output($reqs,false,new \Permission(2,"Content"),array($this,"assign_admin_callback"));
        }
        public function assign_admin_callback(){
            $db = $this->get_db();
            $d = $db->query("SELECT asana_team_member_id FROM team_members WHERE team_member_id = ".$db->esc($this->parameters['team_member_id']));
            $z = $db->query("SELECT article_id,client,word_count,project,keyword,content_network,notes,target_url,post_url FROM articles ".
                            "LEFT JOIN clients USING (client_id) ".
                            "LEFT JOIN projects USING (project_id) ".
                            "LEFT JOIN keywords USING (keyword_id) ".
                            "LEFT JOIN content_networks USING (content_network_id) ".
                            "WHERE article_id = ".$db->esc($this->parameters['article_id'])." LIMIT 1");
            if (count($d['rows']) > 0){
                if (count($z['rows']) > 0){
                    $tparams = array(
                        "asana_workspace_id"=>$this->workspace_id,
                        "notes"=>"",
                        "assignee"=>$d['rows'][0]['asana_team_member_id'],
                        "name"=>$z['rows'][0]['client']." Content");
                    foreach (array("client","article_id","keyword","content_network","target_url","word_count","notes") as $val)
                        if (!empty($z['rows'][0][$val]))
                            $tparams["notes"] .= $val.": ".$z['rows'][0][$val]."\n";
                    $tasks = new \API\All\Tasks($tparams);
                    $data = $tasks->create();
                    if (!empty($this->parameters['asana_project_id'])){
                        $d = $tasks->add_project($data->data->asana_task_id,$this->parameters['asana_project_id']);
                        $data->d = $d;
                    }
                    if ($data->status == 'success')
                        $db->query("UPDATE articles SET task_id = ".$db->esc($data->data->task_id)." WHERE article_id = ".$db->esc($this->parameters['article_id']));
                    $data->params = $this->parameters;
                    return $data;
                } else {
                    return $this->error(array("No such article."));
                }
            } else {
                return $this->error(array("Invalid User ID"));
            }
        }
        public function reassign(){
            $reqs = new \Required_Parameters(array(),array("article_id"=>\Types::Int,"team_member_id"=>\Types::Int,"asana_team_member_id"=>\Types::Int,"asana_task_id"=>\Types::Int));
            return $this->validate_output($reqs,false,new \Permission(2,"Content"),array($this,"reassign_callback"));
        }
        public function reassign_callback(){
            $tasks = new \API\All\Tasks(array("asana_task_id"=>$this->parameters['asana_task_id'],"assignee"=>$this->parameters['asana_team_member_id']));
            return $tasks->update();
        }
        public function unassign(){
            $reqs = new \Required_Parameters(array(),array("article_id"=>\Types::Int,"asana_task_id"=>\Types::Int));
            return $this->validate_output($reqs,false,new \Permission(1,"Content"),array($this,"unassign_callback"));
        }
        public function unassign_callback(){
            $db = $this->get_db();
            $d = $db->query("SELECT task_id FROM articles LEFT JOIN tasks USING (task_id) WHERE asana_team_member_id = ".\API\All\Users::asana_team_member_id()." AND asana_task_id = ".$db->esc($this->parameters['asana_task_id']));
            if (count($d['rows']) > 0){
                $tasks = new \API\All\Tasks(array("asana_task_id"=>$this->parameters['asana_task_id'],"assignee"=>"null"));
                $data = $tasks->update();
                if ($data->status == 'success'){
                    $d = $db->query("UPDATE articles SET task_id = NULL WHERE article_id = ".$db->esc($this->parameters['article_id']));
                    return $this->success(array("Article successfully unassigned."));
                } else {
                    return $data;
                }
            } else {
                return $this->error(array("You do not have permission to unassign this task."));
            }
        }
        public function unassign_admin(){
            $reqs = new \Required_Parameters(array(),array("article_id"=>\Types::Int,"asana_task_id"=>\Types::Int));
            return $this->validate_output($reqs,false,new \Permission(2,"Content"),array($this,"unassign_admin_callback"));
        }
        public function unassign_admin_callback(){
            $tasks = new \API\All\Tasks(array("asana_task_id"=>$this->parameters['asana_task_id'],"assignee"=>"null"));
            $data = $tasks->update();
            $db = $this->get_db();
            if ($data->status == 'success'){
                $d = $db->query("UPDATE articles SET task_id = NULL WHERE article_id = ".$db->esc($this->parameters['article_id']));
                return $this->success(array("Article successfully unassigned."));
            } else {
                return $data;
            }
        }
        public function refresh(){
            $reqs = new \Required_Parameters();
            return $this->validate_output($reqs,true,new \Permission(1,"Content"),array($this,"refresh_callback"),false);
        }
        public function refresh_callback(){
            $d = $this->get_db()->query("UPDATE articles SET notes = (SELECT notes FROM tasks WHERE tasks.task_id = articles.task_id)");
            return $this->success(array("Articles successfully refreshed"));
        }
    }
}
?>