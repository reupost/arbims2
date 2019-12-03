<?php

class SessionMsgHandler {
    
    public function SetSessionMsg($data) {
        if (!isset($data['session_id']) || !isset($data['data_type']) || !isset($data['data_value'])) return 0; //incorrect data passed
        $state = "success";
        if (isset($data['state'])) $state = $data['state'];
        pg_query_params("INSERT INTO session_data (session_id, data_type, data_value, state) VALUES ($1, $2, $3, $4)", array($data['session_id'], $data['data_type'], $data['data_value'], $state));
        return -1;
    }
    
    public function GetSessionMsg($session_id, $data_type, $then_delete = false) {
        $res = pg_query_params("SELECT data_value, state FROM session_data WHERE session_id = $1 AND data_type = $2", array($session_id, $data_type));
        $vals = array();
        while ($row = pg_fetch_array($res, null, PGSQL_ASSOC)) {
            $vals[] = array($row['data_value'], $row['state']);
        }
        if ($then_delete) {
            pg_query_params("DELETE FROM session_data WHERE session_id = $1 AND data_type = $2", array($session_id, $data_type));
        }
        return $vals;
    }
    
    public function GetSessionMsgMerged($session_id, $data_type, $then_delete = false) {
        $msgs = $this->GetSessionMsg($session_id, $data_type, $then_delete);
        $merged = "";
        $state = "success";
        foreach ($msgs as $msg) {
            $merged .= $msg[0] . " ";
            if ($msg[1] != "success") $state = $msg[1];
        }
        $merged = trim($merged);
        return array("msg" => $merged, "state" => $state);
    }
}

?>