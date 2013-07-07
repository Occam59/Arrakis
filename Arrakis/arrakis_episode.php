<?php
///////////////////////////////////////////////////////////////////////////
require_once 'lib/default_dune_plugin.php';
require_once 'lib/utils.php';

define('EPISODE_PREFIX', 'e.');

class ArrakisEpisode
{
	private $id;
	private $episode;
	private $season;
    private $caption;
    private $poster_url;
    private $media_url;
    private $watched;
    private $lastplayed;
    private $aired;
    
    public function __construct(&$xml)
    {
        $this->id = EPISODE_PREFIX.strval($xml->id);
        $this->caption = strval($xml->caption);
        $this->episode = intval($xml->episode);
        $this->media_url = strval($xml->media_url);
        $this->poster_url = strval($xml->poster_url);
        $this->aired = strval($xml->aired);
        $this->season = intval($xml->season);
        $this->watched = intval($xml->watched) == 0 ? false: true;
        $this->lastplayed = intval($xml->lastplayed);
   	}
    
    public function get_poster_url()
    { return $this->poster_url; }

    public function get_caption()
    { return $this->caption; }

    public function get_episode()
    { return $this->episode; }
    
    public function get_id() 
    { return $this->id; }
    
    public function get_media_url()
    { return $this->media_url; }

    public function get_season()
    { return $this->season; }

    public function get_aired()
    { return $this->aired; }

    public function is_watched()
    { return $this->watched; }

    public function set_watched($watched)
    { $this->watched = $watched; }

    public function get_lastplayed()
    { return $this->lastplayed; }

}

///////////////////////////////////////////////////////////////////////////
?>
