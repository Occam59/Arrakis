<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/abstract_preloaded_regular_screen.php';

///////////////////////////////////////////////////////////////////////////

abstract class ArrakisVideoScreen extends AbstractPreloadedRegularScreen
{

	protected $http_time;
	protected $sort_key;
	protected $sort_ascending;
	protected $filter;
	protected $genre_filter;
	protected $last_key;
	protected $unwatched_only;
	protected $context;
	protected $genres;
	protected $genres_url;
	protected $videos_url;
	protected $video_ids_url;
	protected $video_id_prefix;	
	protected $popup_sort_options;
	
	protected $video_list;
	protected $video_index;
	protected $video_ids;

	protected $first_time;

	protected abstract function is_video(MediaURL $media_url);
	protected abstract function get_new_video(&$xml);
	protected abstract function get_video_id(MediaURL $media_url);	
	protected abstract function add_popup_items(&$menu_items, &$user_input, &$plugin_cookies);
	
	///////////////////////////////////////////////////////////////////////
	
	//    public function __construct()
	public function __construct($id)
	{
		$this->http_time= time();
		$this->sort_key = "unsorted";
		$this->sort_ascending = true;
		$this->filter = '';
		$this->genre_filter = '';
		$this->unwatched_only = false;
		$this->last_key = 'top';
		$this->context = array();
		$this->genres = null;
		$this->first_time = true;
		
		parent::__construct($id);
	}

	///////////////////////////////////////////////////////////////////////

	///////////////////////////////////////////////////////////////////////
	
	protected function handle_popup_filter(&$user_input, &$plugin_cookies)
	{
        $defs = array();

        ControlFactory::add_text_field(
            $defs, 
            $this, 
            null,
        	$name            = 'apply_filter', 
            $title           = 'Filter',  
            $initial_value   = $this->filter,
            $numeric         = false, 
            $password        = false, 
            $has_osk         = false, 
            $always_active   = 0, 
            $width           = 500,
        	$apply_action	 = false,
        	$confirm_action  = false
        );

        ControlFactory::add_close_dialog_and_apply_button($defs, $this, array(), 
            'filter_ok', 'OK', 300, 'apply_filter');

        return ActionFactory::show_dialog('Filter', $defs, true);
	}
	
	///////////////////////////////////////////////////////////////////////
	
	protected function handle_popup_genre_filter(&$user_input, &$plugin_cookies)
	{
        $defs = array();
        
        if(is_null($this->genres))
        	$this->genres = ArrakisConfig::fetch_genres($this->genres_url, $plugin_cookies);

        ControlFactory::add_combobox(
            $defs, 
            $this, 
            null,
        	$name            = 'apply_genre_filter', 
            $title           = 'Genre',  
            $initial_value   = $this->genre_filter,
            $value_caption_pairs	= $this->genres, 
            $width           = 500,
        	$apply_action	 = false,
        	$confirm_action  = false
        );

        ControlFactory::add_close_dialog_and_apply_button($defs, $this, array(), 
            'genre_filter_ok', 'OK', 300, 'apply_genre_filter');

        return ActionFactory::show_dialog('Genre', $defs, true);
	}
	///////////////////////////////////////////////////////////////////////
	
	protected function handle_refresh(&$user_input, &$plugin_cookies)
	{
		$this->first_time = true;
		$this->http_time= time();
		$this->video_index = null;
		$this->sort_key = '';
		$this->sort_ascending = true;
		$this->unwatched_only = false;
		$this->filter = '';
		$this->genre_filter = '';
		$range = HD::create_regular_folder_range($this->get_all_folder_items(MediaURL::decode($user_input->parent_media_url), $plugin_cookies));
		return ActionFactory::update_regular_folder($range, true);
	}
	
	///////////////////////////////////////////////////////////////////////
	
	private function add_popup_sort(&$menu_items, $sort, $display)
	{
		if($this->sort_key != $sort)
		{
			$menu_items[] = array( GuiMenuItemDef::caption => $display, GuiMenuItemDef::action => UserInputHandlerRegistry::create_action($this, 'popup_sort_'.$sort));
		}
	}

	///////////////////////////////////////////////////////////////////////
	
	protected function handle_user_input(&$user_input, &$plugin_cookies)
	{
		switch($user_input->control_id)
		{
			case 'popup_menu':
				return $this->handle_popup_menu($user_input, $plugin_cookies);
				break;
			case 'refresh':
				return $this->handle_refresh($user_input, $plugin_cookies);
				break;
			case 'popup_sort_unsorted':
			case 'popup_sort_name':
				$this->sort_ascending = true;
				$this->sort_key = substr($user_input->control_id, 11);
				break;
			case 'popup_sort_ascending':
				$this->sort_ascending = true;
				break;
			case 'popup_sort_descending':
				$this->sort_ascending = false;
				break;
			case 'popup_watch_all':
				$this->unwatched_only = false;
				break;
			case 'popup_watch_unwatched':
				$this->unwatched_only = true;
				break;
			case 'popup_filter':
				return $this->handle_popup_filter($user_input, $plugin_cookies);
				break;
			case 'filter_ok':
				$this->filter = $user_input->apply_filter;
				break;
			case 'clear_filter':
				$this->filter = '';
				break;
			case 'popup_genre_filter':
				return $this->handle_popup_genre_filter($user_input, $plugin_cookies);
				break;
			case 'genre_filter_ok':
				$this->genre_filter = $user_input->apply_genre_filter;
				break;
			case 'clear_genre_filter':
				$this->genre_filter = '';
				break;
			default:
				break;
		}
		$range = HD::create_regular_folder_range($this->get_all_folder_items(MediaURL::decode($user_input->parent_media_url), $plugin_cookies));
		return ActionFactory::update_regular_folder($range, true);
	}

	///////////////////////////////////////////////////////////////////////
	
	private function handle_popup_menu(&$user_input, &$plugin_cookies)
	{
		$menu_items[] = array( GuiMenuItemDef::caption => 'Refresh', GuiMenuItemDef::action => UserInputHandlerRegistry::create_action($this, 'refresh'));
		if(!$this->is_video(MediaURL::decode($user_input->parent_media_url)))
		{
			$menu_items[] = array( GuiMenuItemDef::is_separator => true);
			foreach ($this->popup_sort_options as $key => $value)
				$this->add_popup_sort($menu_items, $key, $value);

			if($this->sort_key != 'unsorted')
			{
				$menu_items[] = array( GuiMenuItemDef::is_separator => true);
				if($this->sort_ascending)
				{
					$menu_items[] = array( GuiMenuItemDef::caption => 'Sort Descending', GuiMenuItemDef::action => UserInputHandlerRegistry::create_action($this, 'popup_sort_descending'));
				}
				else
				{
					$menu_items[] = array( GuiMenuItemDef::caption => 'Sort Ascending', GuiMenuItemDef::action => UserInputHandlerRegistry::create_action($this, 'popup_sort_ascending'));
				}
			}
			$menu_items[] = array( GuiMenuItemDef::is_separator => true);
			if($this->unwatched_only)
			{
				$menu_items[] = array( GuiMenuItemDef::caption => 'Include watched', GuiMenuItemDef::action => UserInputHandlerRegistry::create_action($this, 'popup_watch_all'));
			}
			else
			{
				$menu_items[] = array( GuiMenuItemDef::caption => 'Only unwatched', GuiMenuItemDef::action => UserInputHandlerRegistry::create_action($this, 'popup_watch_unwatched'));
			}
			$menu_items[] = array( GuiMenuItemDef::is_separator => true);
			$caption = $this->filter != '' ? 'Filter: '.$this->filter : 'Filter';
			$menu_items[] = array( GuiMenuItemDef::caption => $caption, GuiMenuItemDef::action => UserInputHandlerRegistry::create_action($this, 'popup_filter'));
			if($this->filter != '')
			{
				$menu_items[] = array( GuiMenuItemDef::caption => 'Clear Filter', GuiMenuItemDef::action => UserInputHandlerRegistry::create_action($this, 'clear_filter'));
			}
			$caption = $this->genre_filter != '' ? 'Genre: '.$this->genre_filter : 'Genre';
			$menu_items[] = array( GuiMenuItemDef::caption => $caption, GuiMenuItemDef::action => UserInputHandlerRegistry::create_action($this, 'popup_genre_filter'));
			if($this->genre_filter != '')
			{
				$menu_items[] = array( GuiMenuItemDef::caption => 'Clear Genre', GuiMenuItemDef::action => UserInputHandlerRegistry::create_action($this, 'clear_genre_filter'));
			}
		}

		$this->add_popup_items($menu_items, $user_input, $plugin_cookies);
		return ActionFactory::show_popup_menu($menu_items);
	}

    ///////////////////////////////////////////////////////////////////////

	protected function save_context($key)
    {
    	$context = array( 	'sort_key' => $this->sort_key,
    						'sort_ascending' => $this->sort_ascending, 
    						'unwatched_only' => $this->unwatched_only,
    						'filter' => $this->filter,
    						'genre_filter' => $this->genre_filter,
    						'video_ids' => $this->video_ids
						);
    	
//    	hd_print("save context $key sort_key ".$this->sort_key);
    	$this->context[$key] = $context;
    }

    ///////////////////////////////////////////////////////////////////////

    protected function restore_context($key)
    {
    	if(isset($this->context[$key]))
    	{
    		$context = $this->context[$key];
    		$this->sort_key = $context['sort_key'];
    		$this->sort_ascending = $context['sort_ascending'];
    		$this->unwatched_only = $context['unwatched_only'];
    		$this->filter = $context['filter'];
    		$this->genre_filter = $context['genre_filter'];
    		$this->video_ids = $context['video_ids'];
    	}
    	else 
    	{
    		$this->video_ids = null;
    		$this->sort_key = "unsorted";
    		$this->sort_ascending = true;
    		$this->unwatched_only = false;
    		$this->filter = '';
    		$this->genre_filter = '';
    	}
    	$this->last_key = $key;
//    	hd_print("restore context $key sort_key ".$this->sort_key);
    }
    
    ///////////////////////////////////////////////////////////////////////

	protected function fetch_ids(MediaURL $media_url, &$plugin_cookies)
	{
		hd_print("fetch ids: " . $media_url->get_raw_string());

		$url_prefix = $this->video_ids_url.'?'.$media_url->screen_id.'='.$this->get_video_id($media_url);
		$url = $url_prefix.'&time='.strval($this->http_time);
		$url = str_replace(" ", "%20", $url);
		$xml = ArrakisConfig::http_get_arrakis_xml($url, $plugin_cookies);

		$this->video_ids = array();
		
		foreach ($xml->children() as $c)
		{
			$this->video_ids[] = $this->video_id_prefix.strval($c);
		}
		hd_print("ids: " . count($this->video_ids));
	}
	
	///////////////////////////////////////////////////////////////////////
	
	protected function ensure_folder_items(MediaURL $media_url, &$plugin_cookies)
    {
    	$key = $this->get_video_id($media_url);
    	if(!$this->is_video($media_url) && $key != $this->last_key)
    	{
    		$this->save_context($this->last_key);
    		$this->restore_context($key);
       	}
    	if (is_null($this->video_index))
    		$this->fetch_videos($media_url, $plugin_cookies);
    	if (is_null($this->video_ids))
    		$this->fetch_ids($media_url, $plugin_cookies);
    }
	
    ///////////////////////////////////////////////////////////////////////
	
	protected function fetch_videos(MediaURL $media_url, &$plugin_cookies)
	{
		hd_print("fetch videos: " . $media_url->get_raw_string());
		$type = isset($media_url->screen_id) ? $media_url->screen_id : $media_url->get_raw_string();
		$url_prefix = $this->videos_url.'?'.$type.'='.$this->get_video_id($media_url);
		$url = $url_prefix.'&time='.strval($this->http_time);
		$url = str_replace(" ", "%20", $url);
		$xml = ArrakisConfig::http_get_arrakis_xml($url, $plugin_cookies);
	
		$this->video_list = array();
		$this->video_index = array();
		$this->video_ids = array();
	
		foreach ($xml->children() as $c)
		{
			$cat = $this->get_new_video($c);
			$this->video_list[] = $cat;
			$this->video_index[$cat->get_id()] = $cat;
			$this->video_ids[] = $cat->get_id();
		}
		hd_print("video item: " . count($this->video_list));
	}
	
}


///////////////////////////////////////////////////////////////////////////
?>
