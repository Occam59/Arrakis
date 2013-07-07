<?php
///////////////////////////////////////////////////////////////////////////

define('LIST_PREFIX', 's.');

class MenuItem
{
    private $id;
    private $cols;
    private $caption;
    private $icon_url;
    private $icon_sel_url;
    private $background_url;
    
    private $sub_items;

    public function __construct($id, $caption, $icon_url, $icon_sel_url, $background_url, $cols)
    {
        $this->id = $id;
        $this->caption = $caption;
        $this->icon_url = $icon_url;
        $this->icon_sel_url = $icon_sel_url;
        $this->background_url = $background_url;
        $this->cols = $cols;
        $this->sub_items = null;
    }

    public function get_id()
    { return $this->id; }

    public function get_cols()
    { return $this->cols; }

    public function get_caption()
    { return $this->caption; }

    public function get_icon_path()
    { return $this->icon_url; }

    public function get_icon_sel_path()
    { return $this->icon_sel_url; }

    public function get_background_path()
    { return $this->background_url; }

    public function set_sub_items($arr)
    { $this->sub_items = $arr; }

    public function get_sub_items()
    { return $this->sub_items; }
}

///////////////////////////////////////////////////////////////////////////
?>
