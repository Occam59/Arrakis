<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/abstract_controls_screen.php';

///////////////////////////////////////////////////////////////////////////

class ArrakisSetupScreen extends AbstractControlsScreen
{
    const ID = 'setup';

    ///////////////////////////////////////////////////////////////////////

    public function __construct()
    {
        parent::__construct(self::ID);
    }

    public function do_get_control_defs(&$plugin_cookies)
    {
        $defs = array();

        $show_arrakis = isset($plugin_cookies->show_arrakis) ? $plugin_cookies->show_arrakis : 'yes';
        $web_url = isset($plugin_cookies->web_url) ? $plugin_cookies->web_url : '';
        $database_name = isset($plugin_cookies->database_name) ? $plugin_cookies->database_name : '';
        $database_user = isset($plugin_cookies->database_user) ? $plugin_cookies->database_user : '';
        $database_password = isset($plugin_cookies->database_password) ? $plugin_cookies->database_password : '';
        $default_background = isset($plugin_cookies->default_background) ? $plugin_cookies->default_background : '';
        $movie_sort = isset($plugin_cookies->movie_sort) ? $plugin_cookies->movie_sort : 'unsorted';
        $movie_sort_ascending = isset($plugin_cookies->movie_sort_ascending) ? $plugin_cookies->movie_sort_ascending : 'yes';
        $movie_show_watched = isset($plugin_cookies->movie_show_watched) ? $plugin_cookies->movie_show_watched : 'yes';
        $tvshow_sort = isset($plugin_cookies->tvshow_sort) ? $plugin_cookies->tvshow_sort : 'unsorted';
        $tvshow_sort_ascending = isset($plugin_cookies->tvshow_sort_ascending) ? $plugin_cookies->tvshow_sort_ascending : 'yes';
        $tvshow_show_watched = isset($plugin_cookies->tvshow_show_watched) ? $plugin_cookies->tvshow_show_watched : 'yes';
        
        $show_ops = array();
        $show_ops['yes'] = 'Yes';
        $show_ops['no'] = 'No';
        
        $this->add_combobox($defs, 'show_arrakis', 'Show Arrakis in main screen:', $show_arrakis, $show_ops, 0, true);
        
        $this->add_text_field($defs, 'web_url', 'Web server url', $web_url, false, false, false, true, 800, true, false);
        $this->add_text_field($defs, 'database_name', 'Database name', $database_name, false, false, false, true, 200, true, false);
        $this->add_text_field($defs, 'database_user', 'Database user', $database_user, false, false, false, true, 200, true, false);
        $this->add_text_field($defs, 'database_password', 'Database password', $database_password, false, true, false, true, 200, true, false);
        $this->add_text_field($defs, 'default_background', 'Default background image', $default_background, false, false, false, true, 800, true, false);
        
        $this->add_combobox($defs, 'movie_sort', 'Movie sort option:', $movie_sort, ArrakisConfig::GET_MOVIE_SORT_OPTIONS(), 0, true);
        $this->add_combobox($defs, 'movie_sort_ascending', 'Movie sort ascending:', $movie_sort_ascending, $show_ops, 0, true);
        $this->add_combobox($defs, 'movie_show_watched', 'Movie show watched:', $movie_show_watched, $show_ops, 0, true);
        
        $this->add_combobox($defs, 'tvshow_sort', 'TVShow sort option:', $tvshow_sort, ArrakisConfig::GET_TVSHOW_SORT_OPTIONS(), 0, true);
        $this->add_combobox($defs, 'tvshow_sort_ascending', 'TVShow sort ascending:', $tvshow_sort_ascending, $show_ops, 0, true);
        $this->add_combobox($defs, 'tvshow_show_watched', 'TVShow show watched:', $tvshow_show_watched, $show_ops, 0, true);
        
        
        return $defs;
    }

    public function get_control_defs(MediaURL $media_url, &$plugin_cookies)
    {
        return $this->do_get_control_defs($plugin_cookies);
    }

    public function handle_user_input(&$user_input, &$plugin_cookies)
    {
        hd_print('Setup: handle_user_input:');
        foreach ($user_input as $key => $value)
            hd_print("  $key => $value");

        if ($user_input->action_type === 'confirm')
        {
            $control_id = $user_input->control_id;
            $new_value = $user_input->{$control_id};
            hd_print("Setup: changing $control_id value to $new_value");
            if ($control_id === 'show_arrakis')
                $plugin_cookies->show_arrakis = $new_value;
            else if ($control_id === 'web_url')
                $plugin_cookies->web_url = $new_value;
            else if ($control_id === 'database_name')
                $plugin_cookies->database_name = $new_value;
            else if ($control_id === 'database_user')
                $plugin_cookies->database_user = $new_value;
            else if ($control_id === 'database_password')
                $plugin_cookies->database_password = $new_value;
            else if ($control_id === 'default_background')
                $plugin_cookies->default_background = $new_value;
            else if ($control_id === 'movie_sort')
                $plugin_cookies->movie_sort = $new_value;
            else if ($control_id === 'movie_sort_ascending')
                $plugin_cookies->movie_sort_ascending = $new_value;
            else if ($control_id === 'movie_show_unwatched')
                $plugin_cookies->movie_show_unwatched = $new_value;
            else if ($control_id === 'tvshow_sort')
                $plugin_cookies->tvshow_sort = $new_value;
            else if ($control_id === 'tvshow_sort_ascending')
                $plugin_cookies->tvshow_sort_ascending = $new_value;
            else if ($control_id === 'tvshow_show_unwatched')
                $plugin_cookies->tvshow_show_unwatched = $new_value;
        }

        return ActionFactory::reset_controls(
            $this->do_get_control_defs($plugin_cookies));
    }
}

///////////////////////////////////////////////////////////////////////////
?>
