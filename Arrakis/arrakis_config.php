<?php


class ArrakisConfig
{
    const MENU_ITEMS_URL    	= '/GetMenus.php'; 
    const MOVIE_LIST_URL		= '/GetMoviesAll.php';
    const MOVIE_ID_LIST_URL		= '/GetMoviesIDs.php';
    const MOVIE_GENRES_URL		= '/GetMoviesGenres.php';
    const TVSHOW_LIST_URL		= '/GetTVShowsAll.php';
    const TVSHOW_ID_LIST_URL	= '/GetTVShowsIDs.php';
    const TVSHOW_GENRES_URL		= '/GetTVShowsGenres.php';
    const TVEPISODE_LIST_URL	= '/GetTVEpisodes.php?tvshow=';
    const PEOPLE_LIST_URL		= '/GetPeople.php';
    const MOVIE_MW_URL			= '/MoviesMW.php';
    const TVSHOW_MW_URL			= '/TVShowsMW.php';
    

    const YOUTUBE_API_URL = 'http://gdata.youtube.com/feeds/api';
    const YOUTUBE_DEV_KEY = 'AI39si5fQPGVdvQRcSilgV8XdGC1GqgqZ-OBWE0EzTBp_iMEQgakJ78DFfyElMiY_B-x6hIF53DaI9ZsidHbBsfXh4oilyHKOw';
    const YOUTUBE_PRODUCT = 'YouTube plugin for Dune HD';
    const YOUTUBE_PAGE_SIZE = 4;

    
    public static function http_get_arrakis_document($url, &$plugin_cookies) 
    {
        $web_url = isset($plugin_cookies->web_url) ? $plugin_cookies->web_url : '';
        $database_name = isset($plugin_cookies->database_name) ? $plugin_cookies->database_name : '';
        $database_user = isset($plugin_cookies->database_user) ? $plugin_cookies->database_user : '';
        $database_password = isset($plugin_cookies->database_password) ? $plugin_cookies->database_password : '';
        $url = $web_url.$url."&database_name=$database_name&database_user=$database_user&database_password=$database_password";
        return HD::http_get_document($url);
    }
    
    public static function http_get_arrakis_xml($url, &$plugin_cookies)
    {
    	$doc = ArrakisConfig::http_get_arrakis_document($url, $plugin_cookies);

        if (is_null($doc))
    		throw new Exception('Can not fetch videos');
    	
    	$xml = simplexml_load_string($doc);
    	
    	if ($xml === false)
    	{
    		hd_print("Error: can not parse XML document.");
    		hd_print("XML-text: $doc.");
    		throw new Exception('Illegal XML document');
    	}
    	
    	return $xml;
    } 
    
    ///////////////////////////////////////////////////////////////////////

	public static function fetch_genres($url, &$plugin_cookies)
	{
		$url = $url.'?time='.time();
		$doc = ArrakisConfig::http_get_arrakis_document($url, $plugin_cookies);

		if (is_null($doc))
			throw new Exception('Can not fetch genres');
		
		$xml = simplexml_load_string($doc);

		if ($xml === false)
		{
			hd_print("Error: can not parse XML document.");
			hd_print("XML-text: $doc.");
			throw new Exception('Illegal XML document');
		}

		if ($xml->getName() !== 'genres')
		{
			hd_print("Error: unexpected node '" . $xml->getName() . "'. Expected: 'movieids'");
			throw new Exception('Invalid XML document');
		}

		$genres = array();
		
		foreach ($xml->children() as $c)
		{
			$c = strval($c);
			$genres[$c] = $c;
		}
		hd_print("genres: " . count($genres));
		
		return $genres;
	}

    ///////////////////////////////////////////////////////////////////////

	public static function GET_MOVIE_SORT_OPTIONS()
	{
		return array (
				'unsorted'	=> 'Unsorted',
				'name' => 'Sort by Name',
				'lastplayed' => 'Sort by Last Played',
				'release_date' => 'Sort by Release Date',
				'rating' => 'Sort by Rating',
				'date_added' => 'Sort by Date Added');
		
	}
	
	public static function GET_TVSHOW_SORT_OPTIONS()
	{
		return array (
							'unsorted'	=> 'Unsorted',
							'name' => 'Sort by Name',
							'lastplayed' => 'Sort by Last Played',
							'lastaired' => 'Sort by Last Aired',
							'oldestunwatched' => 'Sort by Oldest Unwatched',
							'release_date' => 'Sort by Release Date');
	}
	
	///////////////////////////////////////////////////////////////////////
    // Folder views.

    public static function GET_PEOPLE_LIST_FOLDER_VIEWS(&$plugin_cookies)
    {
        return array(
            array
            (
                PluginRegularFolderView::async_icon_loading => true,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 1,
                    ViewParams::num_rows => 14,
                	ViewParams::background_order => 'before_all',
                	ViewParams::background_path => $plugin_cookies->default_background,
                    ViewParams::paint_details => true,
                	ViewParams::paint_help_line => false,
                	ViewParams::paint_path_box => false,
                	ViewParams::zoom_detailed_icon => true,
                		
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => false,
                    ViewItemParams::item_paint_caption => true,
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array
                (
                    ViewItemParams::icon_path => 'plugin_file://icons/mov_unset.png',
                    ViewItemParams::item_detailed_icon_path => 'missing://',
                ),
            ),
        );
    }
    
    public static function GET_MENU_FOLDER_VIEWS($background, $level, $cols)
    {
        $views = array(
            array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => $cols,
                    ViewParams::num_rows => 1,
                	ViewParams::background_order => 'before_all',
                	ViewParams::background_path => $background,
                    ViewParams::paint_details => false,
                    ViewParams::paint_help_line => false,
                	ViewParams::paint_path_box => false,
                	ViewParams::paint_scrollbar => false,
                	ViewParams::paint_icon_selection_box => $cols > 1 ? true : false,
                	ViewParams::zoom_detailed_icon => true,
                	ViewParams::content_box_x => $level < 2 ? 0 : 200,
                	ViewParams::content_box_y => ($level == 0) ? 720 : (($level == 1) ? 820 : 866),
                	ViewParams::content_box_width => $level < 2 ? 1920 : 1520,
                	ViewParams::content_box_height => $level == 0 ? 120 : 48,
                	ViewParams::content_box_padding_left => 0,
                	ViewParams::content_box_padding_right => 0,
                	ViewParams::content_box_padding_top => 0,
                	ViewParams::content_box_padding_bottom => 0,
                		
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_padding_top => 0,
                    ViewItemParams::item_padding_bottom => 0,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.0,
                    ViewItemParams::icon_sel_scale_factor => 1.0,
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array
                (
                    ViewItemParams::icon_path => 'plugin_file://icons/mov_unset.png',
                    ViewItemParams::item_detailed_icon_path => 'missing://',
                ),
            ),
       );
       return $views; 
    }

    public static function GET_MOVIE_LIST_FOLDER_VIEWS(&$plugin_cookies)
    {
        return array(
            array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 7,
                    ViewParams::num_rows => 3,
                	ViewParams::background_order => 'before_all',
                	ViewParams::background_path => $plugin_cookies->default_background,
                    ViewParams::paint_details => false,
                    ViewParams::paint_help_line => true,
                	ViewParams::paint_path_box => false,
                	ViewParams::zoom_detailed_icon => true,
                	ViewParams::content_box_x => 0,
                	ViewParams::content_box_y => 0,
                	ViewParams::content_box_width => 1920,
                	ViewParams::content_box_height => 1080,
                	ViewParams::content_box_padding_left => 0,
                	ViewParams::content_box_padding_right => 0,
                	ViewParams::content_box_padding_top => 0,
                	ViewParams::content_box_padding_bottom => 0,
                		
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_padding_top => 0,
                    ViewItemParams::item_padding_bottom => 0,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 0.85,
                    ViewItemParams::icon_sel_scale_factor => 1.1,
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array
                (
                    ViewItemParams::icon_path => 'plugin_file://icons/mov_unset.png',
                    ViewItemParams::item_detailed_icon_path => 'missing://',
                ),
            ),
            array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 5,
                    ViewParams::num_rows => 2,
                	ViewParams::background_order => 'before_all',
                	ViewParams::background_path => $plugin_cookies->default_background,
                    ViewParams::paint_details => false,
                    ViewParams::paint_help_line => true,
                	ViewParams::paint_path_box => false,
                	ViewParams::zoom_detailed_icon => true,
                	ViewParams::content_box_x => 0,
                	ViewParams::content_box_y => 0,
                	ViewParams::content_box_width => 1920,
                	ViewParams::content_box_height => 1080,
                	ViewParams::content_box_padding_left => 0,
                	ViewParams::content_box_padding_right => 0,
                	ViewParams::content_box_padding_top => 0,
                	ViewParams::content_box_padding_bottom => 0,
                		
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_padding_top => 0,
                    ViewItemParams::item_padding_bottom => 0,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.3,
                    ViewItemParams::icon_sel_scale_factor => 1.6,
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array
                (
                    ViewItemParams::icon_path => 'plugin_file://icons/mov_unset.png',
                    ViewItemParams::item_detailed_icon_path => 'missing://',
                ),
            ),
            array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 15,
                    ViewParams::num_rows => 1,
                	ViewParams::background_order => 'before_all',
                	ViewParams::background_path => $plugin_cookies->default_background,
                    ViewParams::paint_details => false,
                    ViewParams::paint_help_line => true,
                	ViewParams::paint_path_box => false,
                	ViewParams::paint_scrollbar => false,
//         			ViewParams::zoom_detailed_icon => true,
                	ViewParams::animation_enabled => true,
                	ViewParams::scroll_animation_enabled => true,
                	ViewParams::orientation => 'horizontal',
                	ViewParams::icon_selection_box_width => 500,
                	ViewParams::icon_selection_box_height => 666,
                	ViewParams::cycle_mode_enabled => true,
                	ViewParams::content_box_x => 0,
                	ViewParams::content_box_y => 190,
                	ViewParams::content_box_width => 1920,
                	ViewParams::content_box_height => 700,
//                	ViewParams::content_box_padding_left => 0,
//                	ViewParams::content_box_padding_right => 0,
//                	ViewParams::content_box_padding_top => 0,
//                	ViewParams::content_box_padding_bottom => 0,
                		
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
//                    ViewItemParams::item_padding_top => 0,
//                    ViewItemParams::item_padding_bottom => 0,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
//                	ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_paint_caption => false,
//                    'item_paint_unselected_caption' => true,
                	ViewItemParams::icon_scale_factor => 1.0,
                    ViewItemParams::icon_sel_scale_factor => 2.0,
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array
                (
                    ViewItemParams::icon_path => 'plugin_file://icons/mov_unset.png',
                    ViewItemParams::item_detailed_icon_path => 'missing://',
                ),
            ),
        		array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 1,
                    ViewParams::num_rows => 1,
                	ViewParams::background_order => 'before_all',
                	ViewParams::background_path => $plugin_cookies->default_background,
                	ViewParams::paint_details => false,
                    ViewParams::paint_help_line => true,
                	ViewParams::paint_path_box => false,
                	ViewParams::paint_icon_selection_box => false,
                	ViewParams::zoom_detailed_icon => true,
                	ViewParams::content_box_x => 0,
                	ViewParams::content_box_y => 0,
                	ViewParams::content_box_width => 1920,
                	ViewParams::content_box_height => 1080,
                	ViewParams::content_box_padding_left => 0,
                	ViewParams::content_box_padding_right => 0,
                	ViewParams::content_box_padding_top => 0,
                	ViewParams::content_box_padding_bottom => 0,
                		
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_padding_top => 0,
                    ViewItemParams::item_padding_bottom => 0,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1,
                    ViewItemParams::icon_sel_scale_factor => 1,
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array
                (
                    ViewItemParams::icon_path => 'plugin_file://icons/mov_unset.png',
                    ViewItemParams::item_detailed_icon_path => 'missing://',
                ),
            ),
        );
    }
    
    public static function GET_MOVIE_FOLDER_VIEWS($background, $cols)
    {
        $views = array(
            array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => $cols,
                	ViewParams::num_rows => 1,
                	ViewParams::background_order => 'before_all',
                    ViewParams::background_path => $background,
                    ViewParams::paint_details => false,
                    ViewParams::paint_help_line => false,
                	ViewParams::paint_path_box => false,
                	ViewParams::optimize_full_screen_background => true,
                	ViewParams::paint_scrollbar => false,
                	ViewParams::paint_icon_selection_box => false,
                	ViewParams::zoom_detailed_icon => false,
                	ViewParams::content_box_x => 1300,
                	ViewParams::content_box_y => 640,
                	ViewParams::content_box_width => 600,
                	ViewParams::content_box_height => 60,
                	ViewParams::content_box_padding_left => 0,
                	ViewParams::content_box_padding_right => 0,
                	ViewParams::content_box_padding_top => 0,
                	ViewParams::content_box_padding_bottom => 0,
                		
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_padding_top => 0,
                    ViewItemParams::item_padding_bottom => 0,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.0,
                    ViewItemParams::icon_sel_scale_factor => 1.0,
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array
                (
                    ViewItemParams::icon_path => 'plugin_file://icons/mov_unset.png',
                    ViewItemParams::item_detailed_icon_path => 'missing://',
                ),
            ),
        );
        
       return $views; 
    }
    public static function GET_TVSHOW_LIST_FOLDER_VIEWS(&$plugin_cookies)
    {
        return array(
            array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 3,
                    ViewParams::num_rows => 6,
                	ViewParams::background_order => 'before_all',
                	ViewParams::background_path => $plugin_cookies->default_background,
                    ViewParams::paint_details => false,
                    ViewParams::paint_help_line => true,
                	ViewParams::paint_path_box => false,
                	ViewParams::zoom_detailed_icon => true,
                	ViewParams::icon_selection_box_width => 640,
                	ViewParams::icon_selection_box_height => 120,
                	ViewParams::content_box_x => 0,
                	ViewParams::content_box_y => 0,
                	ViewParams::content_box_width => 1920,
                	ViewParams::content_box_height => 1080,
                	ViewParams::content_box_padding_left => 0,
                	ViewParams::content_box_padding_right => 0,
                	ViewParams::content_box_padding_top => 0,
                	ViewParams::content_box_padding_bottom => 0,
                		
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_padding_top => 0,
                    ViewItemParams::item_padding_bottom => 0,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.0,
                    ViewItemParams::icon_sel_scale_factor => 1.1,
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array
                (
                    ViewItemParams::icon_path => 'plugin_file://icons/mov_unset.png',
                    ViewItemParams::item_detailed_icon_path => 'missing://',
                ),
            ),
            array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 2,
                    ViewParams::num_rows => 5,
                	ViewParams::background_order => 'before_all',
                	ViewParams::background_path => $plugin_cookies->default_background,
                    ViewParams::paint_details => false,
                    ViewParams::paint_help_line => true,
                	ViewParams::paint_path_box => false,
                	ViewParams::zoom_detailed_icon => true,
                	ViewParams::icon_selection_box_width => 880,
                	ViewParams::icon_selection_box_height => 155,
                	ViewParams::content_box_x => 0,
                	ViewParams::content_box_y => 0,
                	ViewParams::content_box_width => 1920,
                	ViewParams::content_box_height => 1080,
                	ViewParams::content_box_padding_left => 0,
                	ViewParams::content_box_padding_right => 0,
                	ViewParams::content_box_padding_top => 0,
                	ViewParams::content_box_padding_bottom => 0,
                		
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_padding_top => 0,
                    ViewItemParams::item_padding_bottom => 0,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.3,
                    ViewItemParams::icon_sel_scale_factor => 1.5,
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array
                (
                    ViewItemParams::icon_path => 'plugin_file://icons/mov_unset.png',
                    ViewItemParams::item_detailed_icon_path => 'missing://',
                ),
            ),
            array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 1,
                    ViewParams::num_rows => 1,
                	ViewParams::background_order => 'before_all',
                	ViewParams::background_path => $plugin_cookies->default_background,
                    ViewParams::paint_details => false,
                    ViewParams::paint_help_line => true,
                	ViewParams::paint_path_box => false,
                	ViewParams::paint_icon_selection_box => false,
                	ViewParams::content_box_x => 0,
                	ViewParams::content_box_y => 0,
                	ViewParams::content_box_width => 1920,
                	ViewParams::content_box_height => 1080,
                	ViewParams::content_box_padding_left => 0,
                	ViewParams::content_box_padding_right => 0,
                	ViewParams::content_box_padding_top => 0,
                	ViewParams::content_box_padding_bottom => 0,
                		
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_padding_top => 0,
                    ViewItemParams::item_padding_bottom => 0,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1,
                    ViewItemParams::icon_sel_scale_factor => 1,
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array
                (
                    ViewItemParams::icon_path => 'plugin_file://icons/mov_unset.png',
                    ViewItemParams::item_detailed_icon_path => 'missing://',
                ),
            ),
        );
    }
    
    public static function GET_TVSHOW_FOLDER_VIEWS($background)
    {
        $views = array(
            array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 10,
                	ViewParams::num_rows => 1,
                	ViewParams::background_order => 'before_all',
                    ViewParams::background_path => $background,
                    ViewParams::paint_details => false,
                    ViewParams::paint_help_line => false,
                	ViewParams::paint_path_box => false,
                	ViewParams::optimize_full_screen_background => true,
                	ViewParams::paint_scrollbar => false,
                	ViewParams::paint_icon_selection_box => true,
                	ViewParams::icon_selection_box_width => 64,
                	ViewParams::icon_selection_box_height => 46,
                	ViewParams::zoom_detailed_icon => false,
                	ViewParams::content_box_x => 800,
                	ViewParams::content_box_y => 713,
                	ViewParams::content_box_width => 750,
                	ViewParams::content_box_height => 60,
                	ViewParams::content_box_padding_left => 0,
                	ViewParams::content_box_padding_right => 0,
                	ViewParams::content_box_padding_top => 0,
                	ViewParams::content_box_padding_bottom => 0,
                		
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_padding_top => 0,
                    ViewItemParams::item_padding_bottom => 0,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 0.85,
                    ViewItemParams::icon_sel_scale_factor => 1.1,
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array
                (
                    ViewItemParams::icon_path => 'plugin_file://icons/mov_unset.png',
                    ViewItemParams::item_detailed_icon_path => 'missing://',
                ),
            ),
        );
       return $views; 
    }

    public static function GET_SEASON_FOLDER_VIEWS($background)
    {
    	$views = array(
    			array
    			(
    					PluginRegularFolderView::async_icon_loading => false,
    
    					PluginRegularFolderView::view_params => array
    					(
    							ViewParams::num_cols => 1,
    							ViewParams::num_rows => 1,
    							ViewParams::background_order => 'before_all',
    							ViewParams::background_path => $background,
    							ViewParams::paint_details => false,
    							ViewParams::paint_help_line => false,
    							ViewParams::paint_path_box => false,
    							ViewParams::optimize_full_screen_background => true,
    							ViewParams::paint_scrollbar => false,
    							ViewParams::paint_icon_selection_box => false,
    							ViewParams::zoom_detailed_icon => false,
    							ViewParams::content_box_x => 0,
    							ViewParams::content_box_y => 720,
    							ViewParams::content_box_width => 1590,
    							ViewParams::content_box_height => 340,
    							ViewParams::content_box_padding_left => 0,
    							ViewParams::content_box_padding_right => 0,
    							ViewParams::content_box_padding_top => 0,
    							ViewParams::content_box_padding_bottom => 0,
    
    					),
    
    					PluginRegularFolderView::base_view_item_params => array
    					(
    							ViewItemParams::item_padding_top => 0,
    							ViewItemParams::item_padding_bottom => 0,
    							ViewItemParams::icon_valign => VALIGN_CENTER,
    							ViewItemParams::item_paint_caption => false,
    							ViewItemParams::icon_scale_factor => 1.0,
    							ViewItemParams::icon_sel_scale_factor => 1.0,
    					),
    
    					PluginRegularFolderView::not_loaded_view_item_params => array
    					(
    							ViewItemParams::icon_path => 'plugin_file://icons/mov_unset.png',
    							ViewItemParams::item_detailed_icon_path => 'missing://',
    					),
    			),
    	);
    	return $views;
    }
    
}

?>
