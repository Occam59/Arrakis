<?php
///////////////////////////////////////////////////////////////////////////

require_once 'arrakis_movie.php';
require_once 'arrakis_video_screen.php';

///////////////////////////////////////////////////////////////////////////

class ArrakisMovieScreen extends ArrakisVideoScreen
implements UserInputHandler
{
	const ID = 'movie';

	//    private $vod;

	public static function get_media_url_str($movie_id)
	{
		return MediaURL::encode(
				array
				(
						'screen_id'     => self::ID,
						'movie_id'   => $movie_id,
				));
	}

	///////////////////////////////////////////////////////////////////////


	//    public function __construct()
	public function __construct()
	{
		UserInputHandlerRegistry::get_instance()->register_handler($this);

		$this->genres_url = ArrakisConfig::MOVIE_GENRES_URL;
		$this->videos_url = ArrakisConfig::MOVIE_LIST_URL;
		$this->video_ids_url = ArrakisConfig::MOVIE_ID_LIST_URL;
		$this->video_id_prefix = MOVIE_PREFIX;					

		$this->popup_sort_options = ArrakisConfig::GET_MOVIE_SORT_OPTIONS();
		
		parent::__construct(self::ID);
	}

	///////////////////////////////////////////////////////////////////////

	public function get_action_map(MediaURL $media_url, &$plugin_cookies)
	{
		hd_print("Do get action map: ".$media_url->get_raw_string());
		$this->ensure_folder_items($media_url, $plugin_cookies);
		if($this->is_valid_movie($media_url))
		{
			$enter_key_action = UserInputHandlerRegistry::create_action($this, 'enter_key');
			$enter_key_action['caption'] = 'Enter';

			$parent_movie = $this->video_index[$media_url->movie_id];

			return array(
					GUI_EVENT_KEY_ENTER => $enter_key_action,
					GUI_EVENT_KEY_PLAY => ActionFactory::launch_media_url($parent_movie->get_media_url(), UserInputHandlerRegistry::create_action($this, 'refresh_movie')),
					GUI_EVENT_KEY_POPUP_MENU => UserInputHandlerRegistry::create_action($this, 'popup_menu'),
						
			);
		}
		else
		{
			return array(
					GUI_EVENT_KEY_PLAY => UserInputHandlerRegistry::create_action($this, 'play_selected'),
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

	///////////////////////////////////////////////////////////////////////

	protected function add_popup_items(&$menu_items, &$user_input, &$plugin_cookies)
	{	
		$menu_items[] = array( GuiMenuItemDef::is_separator => true);
		$movie = $this->get_movie_from_user_input($user_input);
		if($movie->is_watched()) 
		{
			$menu_items[] = array( GuiMenuItemDef::caption => 'Mark unwatched', GuiMenuItemDef::action => UserInputHandlerRegistry::create_action($this, 'mark_unwatched'));
		}
		else 
		{
			$menu_items[] = array( GuiMenuItemDef::caption => 'Mark watched', GuiMenuItemDef::action => UserInputHandlerRegistry::create_action($this, 'mark_watched'));
		}
	}
	
	///////////////////////////////////////////////////////////////////////
	
	private function get_movie_from_user_input(&$user_input)
	{
		$pml = MediaURL::decode($user_input->parent_media_url);
		$sml = MediaURL::decode($user_input->selected_media_url);
		$movie_id = isset($pml->movie_id) ? $pml->movie_id : $sml->movie_id;
		return $this->video_index[$movie_id];
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
			case 'enter_key':
				return $this->handle_enter_key($user_input, $plugin_cookies);
				break; 
			case 'play_selected':
				$movie = $this->get_movie_from_user_input($user_input);
				return ActionFactory::launch_media_url($movie->get_media_url(), UserInputHandlerRegistry::create_action($this, 'refresh_movie'));
				break; 
			case 'refresh_movie':
				$movie = $this->get_movie_from_user_input($user_input);
				$movie->refresh($plugin_cookies);
				break;
			case 'popup_sort_release_date':
			case 'popup_sort_lastplayed':
			case 'popup_sort_rating':
			case 'popup_sort_date_added':
				$this->sort_ascending = false;
				$this->sort_key = substr($user_input->control_id, 11);
				break;
			case 'mark_watched':
				$movie = $this->get_movie_from_user_input($user_input);
				$movie->set_watched(true, $plugin_cookies);
				break;
			case 'mark_unwatched':
				$movie = $this->get_movie_from_user_input($user_input);
				$movie->set_watched(false, $plugin_cookies);
				break;
			default:
				break; 
		}
		return parent::handle_user_input($user_input, $plugin_cookies);
	}
	
	///////////////////////////////////////////////////////////////////////
	
	
	private function handle_enter_key(&$user_input, &$plugin_cookies)
	{
		$movie = $this->get_movie_from_user_input($user_input);
		switch($user_input->selected_media_url)
		{
			case 'play':
				return ActionFactory::launch_media_url($movie->get_media_url(), UserInputHandlerRegistry::create_action($this, 'refresh_movie'));
			case 'trailer':
				return ActionFactory::launch_media_url($movie->get_expanded_trailer_url());
			case 'imdb':
				return ActionFactory::launch_media_url('www://'.$movie->get_IMDB_url());
			default;
				return ActionFactory::open_folder();
		}
	}
	
	///////////////////////////////////////////////////////////////////////
	
	public function get_all_folder_items(MediaURL $media_url, &$plugin_cookies)
	{
		hd_print("Get all folder items: ".$media_url->get_raw_string());
		$this->ensure_folder_items($media_url, $plugin_cookies);
		
		$items = array();
		if ($this->is_valid_movie($media_url))
		{
			$items = $this->load_single_movie($media_url->movie_id);
		}
		else
		{
			$items = $this->load_all_items($media_url, $plugin_cookies);
		}

		return $items;
	}

	///////////////////////////////////////////////////////////////////////

	private function is_movie(MediaURL $media_url) 
	{
		return strncmp($media_url->movie_id, MOVIE_PREFIX, 2) == 0 ? true : false;
	}
	
	///////////////////////////////////////////////////////////////////////

	protected function is_video(MediaURL $media_url) 
	{
		return $this->is_movie($media_url);
	}
	
    ///////////////////////////////////////////////////////////////////////

    private function load_single_movie($movie_id)
	{
		$items = array();
		$media_url_str = self::get_media_url_str($movie_id);
		$parent_movie = $this->video_index[$movie_id];

		$items[] = $this->add_fixed_item('play', 'Play');
		$items[] = $this->add_fixed_item(ArrakisPeopleScreen::get_media_url_str($movie_id.'&type=actor'), 'Actors');
		$items[] = $this->add_fixed_item(ArrakisPeopleScreen::get_media_url_str($movie_id.'&type=director'), 'Directors');
		if($parent_movie->get_trailer_url() != '') 
		{
			$items[] = $this->add_fixed_item('trailer', 'Trailer');
		}
		$items[] = $this->add_fixed_item('imdb', 'IMDB');

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
						ViewItemParams::icon_path => 'smb://BARNARD-1079/Media/_dune/00/97/'.$caption.'.aai',
						ViewItemParams::icon_sel_path => 'smb://BARNARD-1079/Media/_dune/00/97/'.$caption.'_sel.aai',
				)
		);
	}
	
	
	private function cmpcap($a, $b) {	return ($this->sort_ascending ? 1 : -1) * strcasecmp($this->video_index[$a]->get_caption(), $this->video_index[$b]->get_caption());	}
	private function cmprd($a, $b) {	return ($this->sort_ascending ? 1 : -1) * strcasecmp($this->video_index[$a]->get_release_date(), $this->video_index[$b]->get_release_date());	}
	private function cmpra($a, $b) {	return ($this->sort_ascending ? 1 : -1) * strcasecmp($this->video_index[$a]->get_rating(), $this->video_index[$b]->get_rating());	}
	private function cmplp($a, $b) {	return ($this->sort_ascending ? 1 : -1) * strcasecmp($this->video_index[$a]->get_lastplayed(), $this->video_index[$b]->get_lastplayed());	}
	private function cmpda($a, $b) {	return ($this->sort_ascending ? 1 : -1) * strcasecmp($this->video_index[$a]->get_date_added(), $this->video_index[$b]->get_date_added());	}
	
	///////////////////////////////////////////////////////////////////////
	private function load_all_items(MediaURL $media_url, &$plugin_cookies)
	{
		$items = array();
		$video_ids = $this->video_ids;
		
			if($this->first_time)
		{
			$this->first_time = false;
			$this->sort_key = isset($plugin_cookies->movie_sort) ? $plugin_cookies->movie_sort : 'unsorted';
			$this->sort_ascending = isset($plugin_cookies->movie_sort_ascending) ? ($plugin_cookies->movie_sort_ascending === 'yes') : true;
			$this->unwatched_only = !(isset($plugin_cookies->movie_show_watched) ? ($plugin_cookies->movie_show_watched === 'yes') : true);
		}
		
		switch ($this->sort_key)
		{
			case 'release_date':
				uasort($video_ids, array($this, 'cmprd'));
				break;
			case 'rating':
				uasort($video_ids, array($this, 'cmpra'));
				break;
			case 'lastplayed':
				uasort($video_ids, array($this, 'cmplp'));
				break;
			case 'date_added':
				uasort($video_ids, array($this, 'cmpda'));
				break;
			case 'name':
				uasort($video_ids, array($this, 'cmpcap'));
				break;
			default:
				break;
				
		}
			
		$idx = $plugin_cookies->{'screen.movie.view_idx'};
		$bFilter = ($this->filter != '');
		$bGenre = ($this->genre_filter != '');
		$genre = ' '.$this->genre_filter.' ';

		hd_print('Filter:'.$this->filter);
		foreach ($video_ids as $c)
		{
			$m = &$this->video_index[$c];
			if(		(!$bFilter || (stripos($m->get_caption(), $this->filter) > -1)) && 
					(!$bGenre || $m->is_genre($genre)) &&
					(!$this->unwatched_only || !$m->is_watched()))
			 {
				$media_url_str = self::get_media_url_str($m->get_id());
				$items[] = array
				(
						PluginRegularFolderItem::media_url => $media_url_str,
						PluginRegularFolderItem::caption => $m->get_caption(),
						PluginRegularFolderItem::view_item_params => array
						(
								ViewItemParams::icon_path => ($idx !=3) ? $m->get_poster_url() : $m->get_background_url(),
						)
				);
			}
		}

		return $items;
	}

	///////////////////////////////////////////////////////////////////////
	
	protected function get_new_video(&$xml)
	{
		return new ArrakisMovie($xml);
	}
	
	///////////////////////////////////////////////////////////////////////

	protected function get_video_id(MediaURL $media_url)
	{
		return isset($media_url->movie_id) ? $media_url->movie_id : 'all';
	}

	///////////////////////////////////////////////////////////////////////

	///////////////////////////////////////////////////////////////////////

	private function is_valid_movie(MediaURL $media_url)
	{
		return isset($media_url->movie_id) && isset($this->video_index[$media_url->movie_id]);
	}

	///////////////////////////////////////////////////////////////////////

	protected function do_get_folder_views(MediaURL $media_url, &$plugin_cookies)
	{
		hd_print("Do get folder views: ".$media_url->get_raw_string());
       $this->ensure_folder_items($media_url, $plugin_cookies);
		
		$background = $plugin_cookies->default_background;

		$views = array();
		$cols = 1;

		if ($this->is_valid_movie($media_url))
		{
			$parent_movie = $this->video_index[$media_url->movie_id];
			$background = $parent_movie->get_background_url();
			$cols = 5;
			$views = ArrakisConfig::GET_MOVIE_FOLDER_VIEWS($background, $cols);
		}
		else
		{
			$views = ArrakisConfig::GET_MOVIE_LIST_FOLDER_VIEWS($plugin_cookies);
		}

		hd_print("Do get folder views mib: $cols ".$background);

		return $views;
	}

}



///////////////////////////////////////////////////////////////////////////
?>
