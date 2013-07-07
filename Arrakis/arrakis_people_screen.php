<?php
///////////////////////////////////////////////////////////////////////////

require_once 'arrakis_person.php';
require_once 'lib/abstract_preloaded_regular_screen.php';
//require_once 'demo_vod_list_screen.php';

///////////////////////////////////////////////////////////////////////////

class ArrakisPeopleScreen extends AbstractPreloadedRegularScreen
implements UserInputHandler
{
    const ID = 'people';

    public static function get_media_url_str($people_id)
    {
        return MediaURL::encode(
            array
            (
                'screen_id'     => self::ID,
                'people_id'   => $people_id,
            ));
    }

    ///////////////////////////////////////////////////////////////////////

    private $people_list;
    private $people_index;
    private $http_time;
    private $last_key;
    private $last_type;
    
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
			$this->menu_index = null;
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
        $this->ensure_folder_items($media_url, $plugin_cookies);

        $people_list = $this->people_list;

        $items = array();
        
        $movie = (substr($media_url->people_id, 0, 2) == 'm.');

        foreach ($people_list as $c)
        {
//            hd_print($c->get_id());
        	$items[] = array
            (
                PluginRegularFolderItem::media_url => $movie ? ArrakisMovieScreen::get_media_url_str('p.'.$c->get_id().'&type='.$this->last_type) :
            		ArrakisTVShowScreen::get_media_url_str('p.'.$c->get_id().'&type='.$this->last_type),
                PluginRegularFolderItem::caption => $c->get_extended_name(),
                PluginRegularFolderItem::view_item_params => array
                (
                    ViewItemParams::icon_path => $c->get_thumb_url(),
                	ViewItemParams::item_detailed_icon_path => $c->get_thumb_url(),
                )
            );
        }

        return $items;
    }

    ///////////////////////////////////////////////////////////////////////

    private function ensure_folder_items(MediaURL $media_url, &$plugin_cookies)
    {
    	$key = $media_url->people_id;
    	if(substr($key, 0, 2) != "p." && $key != $this->last_key)
    	{
    		$this->last_key = $key;
    		$this->people_index = null;
    	}
    	if (is_null($this->people_index))
    		$this->fetch_people($media_url, $plugin_cookies);
   	}
    ///////////////////////////////////////////////////////////////////////

    private function fetch_people(MediaURL $media_url, &$plugin_cookies)
    {
		hd_print("fetch people: " . $media_url->get_raw_string());
		$id = $media_url->people_id;
		$this->last_type = substr($id, strpos($id, 'type=')+5);
    	$url = ArrakisConfig::PEOPLE_LIST_URL.'?people='.$id.'&time='.strval($this->http_time);
    	$doc = ArrakisConfig::http_get_arrakis_document($url, $plugin_cookies);
        
        if (is_null($doc))
            throw new Exception('Can not fetch playlist');

        $xml = simplexml_load_string($doc);

        if ($xml === false)
        {
            hd_print("Error: can not parse XML document.");
            hd_print("XML-text: $doc.");
            throw new Exception('Illegal XML document');
        }

        if ($xml->getName() !== 'people')
        {
            hd_print("Error: unexpected node '" . $xml->getName() . "'. Expected: 'people'");
            throw new Exception('Invalid XML document');
        }
        
        $this->people_list = array();
        $this->people_index = array();

        foreach ($xml->children() as $c)
        {
            $cat =
                new Person(
                    strval($c->name),
                    strval($c->role),
                    strval($c->thumb_url)
                );
            
//            hd_print($c->name);
            
            $this->people_list[] = $cat;

            $this->people_index[$cat->get_id()] = $cat;
        }
    }

    ///////////////////////////////////////////////////////////////////////


    protected function do_get_folder_views(MediaURL $media_url, &$plugin_cookies)
    {
        hd_print("Do get folder views: ".$media_url->get_raw_string());
        return ArrakisConfig::GET_PEOPLE_LIST_FOLDER_VIEWS($plugin_cookies);
    }
}

///////////////////////////////////////////////////////////////////////////
?>
