<?php
class myClass {
    public $myvar;
    public function myFunc() {
        $this->myvar = 'Test str';
        return $this;
    }

    public function result() {
        echo $this->myFunc()->myvar;
    }
}

$nCls = new myClass;
$nCls->result();
?>