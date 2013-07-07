<?php
///////////////////////////////////////////////////////////////////////////
require_once 'lib/default_dune_plugin.php';
require_once 'lib/utils.php';

class Person
{
    private $id;
    private $role;
    private $thumb_url;
    
    public function __construct($id, $role, $thumb_url)
    {
        $this->id = $id;
        $this->thumb_url = $thumb_url;
        $this->role = $role;
    }

    public function get_id()
    { return $this->id; }

    public function get_extended_name()
    { 
    	$name = $this->id;
    	if($this->role != "") {
    		$name .= " as ".$this->role;
    	} 
    	return  $name;
    }

    public function get_role()
    { return $this->role; }

    public function get_thumb_url()
    { return $this->thumb_url; }

}

///////////////////////////////////////////////////////////////////////////
?>
