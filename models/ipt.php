<?php

class IPT {
    public function Unlock_IPT() {
        pg_query_params("DELETE FROM semaphor", array());
        return -1;
    }
}
?>