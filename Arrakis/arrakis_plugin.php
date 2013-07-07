<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/default_dune_plugin.php';
require_once 'lib/utils.php';

require_once 'arrakis_config.php';

require_once 'arrakis_setup_screen.php';
require_once 'arrakis_menu_screen.php';
require_once 'arrakis_movie_screen.php';
require_once 'arrakis_tvshow_screen.php';
require_once 'arrakis_people_screen.php';

///////////////////////////////////////////////////////////////////////////

class ArrakisPlugin extends DefaultDunePlugin
{
    public function __construct()
    {
        $this->add_screen(new ArrakisSetupScreen());
        $this->add_screen(new ArrakisMenuScreen());
        $this->add_screen(new ArrakisMovieScreen());
        $this->add_screen(new ArrakisTVShowScreen());
        $this->add_screen(new ArrakisPeopleScreen());
    }
}

///////////////////////////////////////////////////////////////////////////
?>
