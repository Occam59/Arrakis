<?php
///////////////////////////////////////////////////////////////////////////

require_once 'arrakis_tvshow.php';
require_once 'arrakis_video_screen.php';

///////////////////////////////////////////////////////////////////////////

class ArrakisTVShowScreen extends ArrakisVideoScreen
implements UserInputHandler
{
	const ID = 'tvshow';
	
	//    private $vod;

	public static function get_media_url_str($tvshow_id)
	{
		return MediaURL::encode(
				array
				(
						'screen_id'     => self::ID,
						'tvshow_id'   => $tvshow_id,
				));
	}

	///////////////////////////////////////////////////////////////////////


	//    public function __construct()
	public function __construct()
	{
		UserInputHandlerRegistry::get_instance()->register_handler($this);

		$this->genres_url = ArrakisConfig::TVSHOW_GENRES_URL;
		$this->videos_url = ArrakisConfig::TVSHOW_LIST_URL;
		$this->video_ids_url = ArrakisConfig::TVSHOW_ID_LIST_URL;
		$this->video_id_prefix = TVSHOW_PREFIX;
		
		$this->popup_sort_options = ArrakisConfig::GET_TVSHOW_SORT_OPTIONS();
		
		parent::__construct(self::ID);
	}

	///////////////////////////////////////////////////////////////////////

	public function get_action_map(MediaURL $media_url, &$plugin_cookies)
	{
		hd_print("Do get action map: ".$media_url->get_raw_string());

		if($this->is_valid_tvshow($media_url))
		{
			return array(
					GUI_EVENT_KEY_ENTER => ActionFactory::open_folder(),
					GUI_EVENT_KEY_POPUP_MENU => UserInputHandlerRegistry::create_action($this, 'popup_menu'),
						
			);
		}
		else if( $this->is_valid_tvshow_season($media_url))
		{
			$play_selected_action = UserInputHandlerRegistry::create_action($this, 'play_selected');
			$play_selected_action['caption'] = 'Enter';
			return array(
					GUI_EVENT_KEY_ENTER => $play_selected_action,
					GUI_EVENT_KEY_PLAY => $play_selected_action,
					GUI_EVENT_KEY_POPUP_MENU => UserInputHandlerRegistry::create_action($this, 'popup_menu'),
						
			);
		}
		else
		{
			return array(
					GUI_EVENT_KEY_ENTER => ActionFactory::open_folder(),
					GUI_EVENT_KEY_POPUP_MENU => UserInputHandlerRegistry::create_action($this, 'popup_menu'),
			);
		}
	}

	///////////////////////////////////////////////////////////////////////

	public function get_handler_id()
	{
		return self::ID;
	}

	private function get_tvshow_from_user_input(&$user_input)
	{
		$pml = MediaURL::decode($user_input->parent_media_url);
		$sml = MediaURL::decode($user_input->selected_media_url);
		$tvshow_id = isset($pml->tvshow_id) ? $pml->tvshow_id : $sml->tvshow_id;
		if(strpos($tvshow_id, '.', 2) > 0)
		{
			$tvshow_id = substr($tvshow_id, 0, strrpos($tvshow_id, "."));
		}
		
		return $this->video_index[$tvshow_id];
	}
	
	///////////////////////////////////////////////////////////////////////

	protected function add_popup_items(&$menu_items, &$user_input, &$plugin_cookies)
	{
		$menu_items[] = array( GuiMenuItemDef::is_separator => true);
		$pml = MediaURL::decode($user_input->parent_media_url);
		$sml = MediaURL::decode($user_input->selected_media_url);
		if($this->is_valid_tvshow($pml) || $this->is_valid_tvshow($sml))
		{
			$tvshow = $this->get_tvshow_from_user_input($user_input);
			if($tvshow->show_has_watched_episodes($plugin_cookies))
			{
				$menu_items[] = array( GuiMenuItemDef::caption => 'Mark show unwatched', GuiMenuItemDef::action => UserInputHandlerRegistry::create_action($this, 'mark_show_unwatched'));
			}
			if($tvshow->show_has_unwatched_episodes($plugin_cookies))
			{
				$menu_items[] = array( GuiMenuItemDef::caption => 'Mark show watched', GuiMenuItemDef::action => UserInputHandlerRegistry::create_action($this, 'mark_show_watched'));
			}
		}
		
		if(		($this->is_valid_tvshow_season($pml) && ($this->get_tvshow_season($pml) != 'U') && ($this->get_tvshow_season($pml) != 'A')) || 
				($this->is_valid_tvshow_season($sml) && ($this->get_tvshow_season($sml) != 'U') && ($this->get_tvshow_season($sml) != 'A')) )
		{
			$media_url = $this->is_valid_tvshow_season($pml) ? $pml : $sml;
			$tvshow_id = $this->get_tvshow_id($media_url);
			$season = $this->get_tvshow_season($media_url);
			$tvshow = $this->video_index[$tvshow_id];
			if($tvshow->season_has_watched_episodes($season, $plugin_cookies))
			{
				$menu_items[] = array( GuiMenuItemDef::caption => 'Mark season unwatched', GuiMenuItemDef::action => UserInputHandlerRegistry::create_action($this, 'mark_season_unwatched'));
			}
			if($tvshow->season_has_unwatched_episodes($season, $plugin_cookies))
			{
				$menu_items[] = array( GuiMenuItemDef::caption => 'Mark season watched', GuiMenuItemDef::action => UserInputHandlerRegistry::create_action($this, 'mark_season_watched'));
			}
		}
		
		if($this->is_valid_tvshow_season($pml))
		{
			$tvshow_id = $this->get_tvshow_id($pml);
			$tvshow = $this->video_index[$tvshow_id];
			$episode = $tvshow->get_episode_from_media_url($user_input->selected_media_url, $plugin_cookies);
			if($episode->is_watched())
			{
				$menu_items[] = array( GuiMenuItemDef::caption => 'Mark episode unwatched', GuiMenuItemDef::action => UserInputHandlerRegistry::create_action($this, 'mark_episode_unwatched'));
			}
			else
			{
				$menu_items[] = array( GuiMenuItemDef::caption => 'Mark episode watched', GuiMenuItemDef::action => UserInputHandlerRegistry::create_action($this, 'mark_episode_watched'));
			}
		}
	}
	
	///////////////////////////////////////////////////////////////////////

	public function handle_user_input(&$user_input, &$plugin_cookies)
	{
		hd_print('handle_user_input:');
		foreach ($user_input as $key => $value)
			hd_print("  $key => $value");

		$pml = MediaURL::decode($user_input->parent_media_url);
		$this->ensure_folder_items($pml, $plugin_cookies);
		
		switch($user_input->control_id)
		{
			case 'play_selected':
				return ActionFactory::launch_media_url($user_input->selected_media_url, UserInputHandlerRegistry::create_action($this, 'refresh_show'));
				break; 
			case 'refresh_show':
				$tvshow = $this->get_tvshow_from_user_input($user_input);
				$tvshow->refresh($plugin_cookies);
				break;
			case 'popup_sort_release_date':
			case 'popup_sort_lastplayed':
			case 'popup_sort_lastaired':
			case 'popup_sort_oldestunwatched':
				$this->sort_ascending = false;
				$this->sort_key = substr($user_input->control_id, 11);
				break;
			case 'mark_show_watched':
			case 'mark_show_unwatched':
			case 'mark_season_watched':
			case 'mark_season_unwatched':
			case 'mark_episode_watched':
			case 'mark_episode_unwatched':
				$this->handle_watched($user_input, $plugin_cookies);
				default:
				break; 
		}
		return parent::handle_user_input($user_input, $plugin_cookies);
	}
	
	private function handle_watched(&$user_input, &$plugin_cookies)
	{
		$pml = MediaURL::decode($user_input->parent_media_url);
		$sml = MediaURL::decode($user_input->selected_media_url);

		$markwatched = false;
		switch($user_input->control_id)
		{
			case 'mark_show_watched':
				$markwatched = true;
			case 'mark_show_unwatched':
				$tvshow = $this->get_tvshow_from_user_input($user_input);
				$tvshow->mark_watched($markwatched, $plugin_cookies);
				break;
			case 'mark_season_watched':
				$markwatched = true;
			case 'mark_season_unwatched':
				$media_url = $this->is_valid_tvshow_season($pml) ? $pml : $sml;
				$tvshow_id = $this->get_tvshow_id($media_url);
				$season = $this->get_tvshow_season($media_url);
				$tvshow = $this->video_index[$tvshow_id];
				$tvshow->mark_watched($markwatched, $plugin_cookies, $season);
				break;
			case 'mark_episode_watched':
				$markwatched = true;
			case 'mark_episode_unwatched':
				$tvshow_id = $this->get_tvshow_id($pml);
				$tvshow = $this->video_index[$tvshow_id];
				$ep = $tvshow->get_episode_from_media_url($user_input->selected_media_url, $plugin_cookies);
				$season = $ep->get_season();
				$episode = $ep->get_episode();
				$tvshow->mark_watched($markwatched, $plugin_cookies, $season, $episode);
			default:
				break;
		}
			}
	///////////////////////////////////////////////////////////////////////
	
	public function get_all_folder_items(MediaURL $media_url, &$plugin_cookies)
	{
		hd_print("Get all folder items: ".$media_url->get_raw_string());
       $this->ensure_folder_items($media_url, $plugin_cookies);
        
		$items = array();
		if ($this->is_valid_tvshow($media_url))
		{
			$items = $this->load_single_tvshow($media_url, $plugin_cookies);
		}
		else if($this->is_valid_tvshow_season($media_url))
		{
			$items = $this->load_season($media_url, $plugin_cookies);
		}
		else
		{
			$items = $this->load_all_items($media_url, $plugin_cookies);
		}

		return $items;
	}

	///////////////////////////////////////////////////////////////////////
	
	private function get_tvshow_season(MediaURL $media_url)
	{
		$key = $media_url->tvshow_id;
		$i = strpos($key, '.', 2);
		$i = $i == -1 ? -1 : substr($key, $i+1);
		return $i;
	}
	
	
	///////////////////////////////////////////////////////////////////////

	private function is_valid_tvshow(MediaURL $media_url) 
	{
		return isset($media_url->tvshow_id) && isset($this->video_index[$media_url->tvshow_id]);
	}
	
	///////////////////////////////////////////////////////////////////////

	private function is_valid_tvshow_season(MediaURL $media_url) 
	{
		$tvshow_id = isset($media_url->tvshow_id) ? $media_url->tvshow_id : 'xxx.xxx';
		$tvshow_id = substr($tvshow_id, 0, strrpos($tvshow_id, "."));
		return isset($tvshow_id) && isset($this->video_index[$tvshow_id]);
	}

	///////////////////////////////////////////////////////////////////////

	private function is_tvshow(MediaURL $media_url) 
	{
		return strncmp($media_url->tvshow_id, TVSHOW_PREFIX, 2) == 0 ? true : false;
	}
	
	///////////////////////////////////////////////////////////////////////
	
	protected function is_video(MediaURL $media_url) 
	{
		return $this->is_tvshow($media_url);
	}
	
	///////////////////////////////////////////////////////////////////////

	private function get_tvshow_id(MediaURL $media_url) 
	{
		$key = $media_url->tvshow_id;
		return substr($key, 0, strrpos($key, '.'));
	}
	

    ///////////////////////////////////////////////////////////////////////

	private function load_single_tvshow(MediaURL $media_url, &$plugin_cookies)
	{
		$tvshow_id = $media_url->tvshow_id;
		$tvshow = $this->video_index[$tvshow_id];
		
		$seasons = $tvshow->get_seasons($plugin_cookies);
		$dir = $tvshow->get_background_url();
		$dir = substr($dir, 0, strrpos($dir, "/"));
		
		$items = array();
		if($tvshow->has_unwatched())
			$items[] = $this->add_fixed_item(self::get_media_url_str($tvshow_id.'.U'), 'U');

		foreach ($seasons as $season)
		{
			$items[] = array
			(
					PluginRegularFolderItem::media_url => self::get_media_url_str($tvshow_id.'.'.$season),
					PluginRegularFolderItem::caption => $season,
					PluginRegularFolderItem::view_item_params => array
					(
							ViewItemParams::icon_path => $dir.'/'.$season.'/icon.aai',
					)
			);
						}
		$items[] = $this->add_fixed_item(ArrakisPeopleScreen::get_media_url_str($tvshow_id.'&type=actor'), 'A');

		return $items;
	}
	
   	///////////////////////////////////////////////////////////////////////

	private function load_season(MediaURL $media_url, &$plugin_cookies)
	{
		$tvshow_id = $this->get_tvshow_id($media_url);
		$season = $this->get_tvshow_season($media_url);
		
		$tvshow = $this->video_index[$tvshow_id];
		$eps = $tvshow->get_episodes($season, $plugin_cookies);
		
		$items = array();
		foreach ($eps as $c)
		{
			$items[] = array
			(
					PluginRegularFolderItem::media_url => $c->get_media_url(),
					PluginRegularFolderItem::caption => $c->get_caption(),
					PluginRegularFolderItem::view_item_params => array
					(
							ViewItemParams::icon_path => $c->get_poster_url(),
					)
			);
		}
		
		return $items;
	}
	///////////////////////////////////////////////////////////////////////

	private function add_fixed_item($url, $caption) {
		return array
		(
				PluginRegularFolderItem::media_url => $url,
				PluginRegularFolderItem::caption => $caption,
				PluginRegularFolderItem::view_item_params => array
				(
						ViewItemParams::icon_path => 'smb://BARNARD-1079/Media/_dune/01/97/'.$caption.'.aai',
				)
		);
	}
	
	
	private function cmpcap($a, $b) {	return ($this->sort_ascending ? 1 : -1) * strcasecmp($this->video_index[$a]->get_caption(), $this->video_index[$b]->get_caption());	}
	private function cmprd($a, $b) {	return ($this->sort_ascending ? 1 : -1) * strcasecmp($this->video_index[$a]->get_release_date(), $this->video_index[$b]->get_release_date());	}
	private function cmplp($a, $b) {	return ($this->sort_ascending ? 1 : -1) * strcasecmp($this->video_index[$a]->get_lastplayed(), $this->video_index[$b]->get_lastplayed());	}
	private function cmpla($a, $b) {	return ($this->sort_ascending ? 1 : -1) * strcasecmp($this->video_index[$a]->get_lastaired(), $this->video_index[$b]->get_lastaired());	}
	private function cmpou($a, $b) {	return ($this->sort_ascending ? 1 : -1) * strcasecmp($this->video_index[$a]->get_oldestunwatched(), $this->video_index[$b]->get_oldestunwatched());	}
	
	///////////////////////////////////////////////////////////////////////
	private function load_all_items(MediaURL $media_url, &$plugin_cookies)
	{
		$items = array();
		$video_ids = $this->video_ids;
		
		if($this->first_time)
		{
			$this->first_time = false;
			$this->sort_key = isset($plugin_cookies->tvshow_sort) ? $plugin_cookies->tvshow_sort : 'unsorted';
			$this->sort_ascending = isset($plugin_cookies->tvshow_sort_ascending) ? ($plugin_cookies->tvshow_sort_ascending === 'yes') : true;
			$this->unwatched_only = !(isset($plugin_cookies->tvshow_show_watched) ? ($plugin_cookies->tvshow_show_watched === 'yes') : true);
		}
		
		
		switch ($this->sort_key)
		{
			case 'lastplayed':
				uasort($video_ids, array($this, 'cmplp'));
				break;
			case 'lastaired':
				uasort($video_ids, array($this, 'cmpla'));
				break;
			case 'oldestunwatched':
				uasort($video_ids, array($this, 'cmpou'));
				break;
			case 'release_date':
				uasort($video_ids, array($this, 'cmprd'));
				break;
			case 'name':
				uasort($video_ids, array($this, 'cmpcap'));
				break;
			default:
				break;
				
		}
			
		$idx = $plugin_cookies->{'screen.tvshow.view_idx'};
		$bFilter = ($this->filter != '');
		$bGenre = ($this->genre_filter != '');
		$genre = ' '.$this->genre_filter.' ';
		
		hd_print('Filter:'.$this->filter);
		foreach ($video_ids as $c)
		{
			$t = &$this->video_index[$c];
			if(		(!$bFilter || (stripos($t->get_caption(), $this->filter) > -1)) &&
					(!$bGenre || $t->is_genre($genre)) && 
					(!$this->unwatched_only || $t->has_unwatched()))
			 {
				$media_url_str = self::get_media_url_str($t->get_id());
				$items[] = array
				(
						PluginRegularFolderItem::media_url => $media_url_str,
						PluginRegularFolderItem::caption => $t->get_caption(),
						PluginRegularFolderItem::view_item_params => array
						(
								ViewItemParams::icon_path => ($idx !=2) ? $t->get_poster_url() : $t->get_background_url(),
						)
				);
			}
		}

		return $items;
	}

	///////////////////////////////////////////////////////////////////////
	
	protected function get_new_video(&$xml)
	{
		return new ArrakisTVShow($xml);
	}
	
	///////////////////////////////////////////////////////////////////////

	protected function get_video_id(MediaURL $media_url)
	{
		return isset($media_url->tvshow_id) ? $media_url->tvshow_id : 'all';
	}

	///////////////////////////////////////////////////////////////////////

	protected function do_get_folder_views(MediaURL $media_url, &$plugin_cookies)
	{
		hd_print("Do get folder views: ".$media_url->get_raw_string());
       $this->ensure_folder_items($media_url, $plugin_cookies);
		
		$background = $plugin_cookies->default_background;

		$views = array();

		if ($this->is_valid_tvshow($media_url))
		{
			$parent_tvshow = $this->video_index[$media_url->tvshow_id];
			$background = $parent_tvshow->get_background_url();
			$views = ArrakisConfig::GET_TVSHOW_FOLDER_VIEWS($background);
		}
		else if($this->is_valid_tvshow_season($media_url))
		{
			$tvshow_id = $this->get_tvshow_id($media_url);
			$parent_tvshow = $this->video_index[$tvshow_id];
			$background = $parent_tvshow->get_background_url();
			$background = str_replace(".aai", "2.aai", $background);
			$views = ArrakisConfig::GET_SEASON_FOLDER_VIEWS($background);
		}
		else
		{
			$views = ArrakisConfig::GET_TVSHOW_LIST_FOLDER_VIEWS($plugin_cookies);
		}

//		hd_print("Do get folder views mib: ".$background);

		return $views;
	}

}



///////////////////////////////////////////////////////////////////////////
?>
