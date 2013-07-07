<?php
///////////////////////////////////////////////////////////////////////////

require_once 'menu_item.php';
require_once 'lib/abstract_preloaded_regular_screen.php';
//require_once 'demo_vod_list_screen.php';

///////////////////////////////////////////////////////////////////////////

class ArrakisMenuScreen extends AbstractPreloadedRegularScreen
implements UserInputHandler
{
    const ID = 'menu';

    public static function get_media_url_str($menu_id)
    {
        return MediaURL::encode(
            array
            (
                'screen_id'     => self::ID,
                'menu_id'   => $menu_id,
            ));
    }

    ///////////////////////////////////////////////////////////////////////

    private $menuitem_list;
    private $menuitem_index;
    private $http_time;
    
    ///////////////////////////////////////////////////////////////////////

    public function __construct()
    {
		UserInputHandlerRegistry::get_instance()->register_handler($this);
		$this->http_time= time();
    	parent::__construct(self::ID);
    }

    ///////////////////////////////////////////////////////////////////////

    public function get_action_map(MediaURL $media_url, &$plugin_cookies)
    {
        return array(
            GUI_EVENT_KEY_ENTER => ActionFactory::open_folder(),
			GUI_EVENT_KEY_POPUP_MENU => UserInputHandlerRegistry::create_action($this, 'popup_menu'),
        );
    }

	///////////////////////////////////////////////////////////////////////

	public function get_handler_id()
	{
		return self::ID;
	}

	///////////////////////////////////////////////////////////////////////

	public function handle_user_input(&$user_input, &$plugin_cookies)
	{
		hd_print('handle_user_input:');
		foreach ($user_input as $key => $value)
			hd_print("  $key => $value");

		if ($user_input->control_id == 'popup_menu')
		{
			$refresh = UserInputHandlerRegistry::create_action($this, 'refresh');
			$caption = 'Refresh';
			$menu_items[] = array(
					GuiMenuItemDef::caption => $caption,
					GuiMenuItemDef::action => $refresh);

			return ActionFactory::show_popup_menu($menu_items);
		}
		else if ($user_input->control_id == 'refresh')
		{
			$this->http_time= time();
			$this->menuitem_index = null;
			$range = HD::create_regular_folder_range($this->get_all_folder_items(MediaURL::decode($user_input->parent_media_url), $plugin_cookies));
        	return ActionFactory::update_regular_folder($range, true);			
		}
		else if ($user_input->control_id == 'enter_key')
		{
			return ActionFactory::open_folder();
		}
		return null;
	}

    ///////////////////////////////////////////////////////////////////////

    public function get_all_folder_items(MediaURL $media_url, &$plugin_cookies)
    {
        hd_print("Get all folder items: ".$media_url->get_raw_string());
    	if (is_null($this->menuitem_index))
            $this->fetch_menu_items($plugin_cookies);

        $menuitem_list = $this->menuitem_list;

        if (isset($media_url->menu_id))
        {
        	if (!isset($this->menuitem_index[$media_url->menu_id]))
            {
                hd_print("Error: parent menuitem (id: " .
                    $media_url->menu_id . ") not found."); 
                throw new Exception('No parent menuitem found');
            }

            $parent_menuitem = $this->menuitem_index[$media_url->menu_id];
            $menuitem_list = $parent_menuitem->get_sub_items();
        }

        $items = array();

        foreach ($menuitem_list as $c)
        {
            $is_menu_list = is_null($c->get_sub_items());
            $id = $c->get_id();
            if($is_menu_list) {
            	if(substr($id, 0, 2) == '00') 
            	{
            		$media_url_str = ArrakisMovieScreen::get_media_url_str(LIST_PREFIX.$id);
            	}
            	else
            	{
            		$media_url_str = ArrakisTVShowScreen::get_media_url_str(LIST_PREFIX.$id);
            	}
            }
            else 
            {
            	$media_url_str = self::get_media_url_str($id);
            }
            
            $items[] = array
            (
                PluginRegularFolderItem::media_url => $media_url_str,
                PluginRegularFolderItem::caption => $c->get_caption(),
                PluginRegularFolderItem::view_item_params => array
                (
                    ViewItemParams::icon_path => $c->get_icon_path(),
                    ViewItemParams::icon_sel_path => $c->get_icon_sel_path(),
                	ViewItemParams::item_detailed_icon_path => $c->get_icon_path()
                )
            );
        }

        return $items;
    }

    ///////////////////////////////////////////////////////////////////////

    private function fetch_menu_items(&$plugin_cookies)
    {
//        $doc = HD::http_get_document(ArrakisConfig::MENU_ITEMS_URL.'?time='.$this->http_time);
        $doc = ArrakisConfig::http_get_arrakis_document(ArrakisConfig::MENU_ITEMS_URL.'?time='.$this->http_time, &$plugin_cookies);
        
        if (is_null($doc))
            throw new Exception('Can not fetch playlist');

        $xml = simplexml_load_string($doc);

        if ($xml === false)
        {
            hd_print("Error: can not parse XML document.");
            hd_print("XML-text: $doc.");
            throw new Exception('Illegal XML document');
        }

        if ($xml->getName() !== 'menu_items')
        {
            hd_print("Error: unexpected node '" . $xml->getName() . "'. Expected: 'menu_items'");
            throw new Exception('Invalid XML document');
        }
        
        $this->menuitem_list = array();
        $this->menuitem_index = array();

        $this->fill_items($xml->children(), $this->menuitem_list);
    }

    ///////////////////////////////////////////////////////////////////////

    private function fill_items($xml_items, &$obj_arr)
    {
        foreach ($xml_items as $c)
        {
            $cat =
                new MenuItem(
                    strval($c->id),
                    strval($c->caption),
                    strval($c->icon_url),
                    isset($c->icon_sel_url) ? strval($c->icon_sel_url) : strval($c->icon_url),
                	isset($c->background_url) ? strval($c->background_url) : "",
                	isset($c->cols) ? intval($c->cols) : 1
                );
//            hd_print("Menu item: " . strval($c->icon_url));
            
            if (isset($c->menu_items))
            {
                $sub_items = array();
                $this->fill_items($c->menu_items->children(), $sub_items);
                $cat->set_sub_items($sub_items);
            }

            $obj_arr[] = $cat;

            $this->menuitem_index[$cat->get_id()] = $cat;
        }
    }

    ///////////////////////////////////////////////////////////////////////

    protected function do_get_folder_views(MediaURL $media_url, &$plugin_cookies)
    {
        hd_print("Do get folder views: ".$media_url->get_raw_string());
    	if (is_null($this->menuitem_index))
            $this->fetch_menu_items($plugin_cookies);
    		
            $menuitem_list = $this->menuitem_list;

        $background = $plugin_cookies->default_background;
        $level = 0;
		$cols = 1;
        
        if (isset($media_url->menu_id))
        {
        	if (!isset($this->menuitem_index[$media_url->menu_id]))
            {
                hd_print("Error: parent menuitem (id: " .
                    $media_url->menu_id . ") not found."); 
                throw new Exception('No parent menuitem found');
            }

	        $parent_menuitem = $this->menuitem_index[$media_url->menu_id];
            $level = (1+strlen($media_url->menu_id))/3;
            $background = $parent_menuitem->get_background_path();
            $cols = $parent_menuitem->get_cols();
        }
//        hd_print("Do get folder views mib: $level, $cols ".$background);
                
        return ArrakisConfig::GET_MENU_FOLDER_VIEWS($background, $level, $cols);
    }
}

///////////////////////////////////////////////////////////////////////////
?>
