<?php
///////////////////////////////////////////////////////////////////////////
require_once 'lib/default_dune_plugin.php';
require_once 'lib/utils.php';
require_once 'arrakis_episode.php';

define('TVSHOW_PREFIX', 't.');

class ArrakisTVShow
{
    private $id;
    private $poster_url;
    private $caption;
    private $background_url;
    private $release_date;
    private $season_list;
    private $episode_list;
    private $episode_index;
	private $unwatched_count;
	private $genres;
	private $lastplayed;
	private $lastaired;
	private $oldestunwatched;
	
    public function __construct(&$xml)
    {
    	$this->load_from_xml($xml);
    }
    
    private function load_from_xml(&$xml)
    {
        $this->id = TVSHOW_PREFIX.strval($xml->id);
        $this->caption = strval($xml->caption);
        $this->poster_url = strval($xml->poster_url);
        $this->background_url = strval($xml->background_url);
        $this->release_date = strval($xml->release_date);
        $this->episode_list = null;
        $this->season_list = null;
        $this->unwatched_count = 0;
        $this->genres = ' '.strval($xml->genres).' ';
        $this->lastplayed = 0;
        $this->lastaired = '';
        $this->oldestunwatched = '';
        if(isset($xml->episodes)) 
        {
        	$this->load_episodes($xml->episodes);	
        }
    }
    
    public function refresh(&$plugin_cookies)
    {
    	$url_prefix = ArrakisConfig::TVSHOW_LIST_URL.'?tvshow='.substr($this->id, 2);
    	$url = $url_prefix.'&time='.time();
    	$url = str_replace(" ", "%20", $url);
    	$xml = ArrakisConfig::http_get_arrakis_xml($url, $plugin_cookies);
    	 
    	$this->load_from_xml($xml->tvshow);
    }
    
    public function get_id()
    { return $this->id; }

    public function get_poster_url()
    { return $this->poster_url; }

    public function get_caption()
    { return $this->caption; }

    public function get_background_url()
    { return $this->background_url; }

    public function get_release_date()
    { return $this->release_date; }

    public function has_unwatched()
    { return ($this->unwatched_count > 0); }

    public function get_lastplayed()
    { return $this->lastplayed; }

    public function get_lastaired()
    { return $this->lastaired; }

    public function get_oldestunwatched()
    { return $this->oldestunwatched; }

    public function is_genre($genre)
    {
    	return stripos($this->genres, $genre) > -1 ? true : false;
    }
    
    ///////////////////////////////////////////////////////////////////////
    
    public function get_seasons(&$plugin_cookies)
    { 
		if(is_null($this->season_list))
			$this->fetch_episodes($plugin_cookies);
		
    	return $this->season_list; 
    }

    ///////////////////////////////////////////////////////////////////////
    
    public function get_episode_from_media_url($url, &$plugin_cookies)
    {
    	if(is_null($this->episode_list))
    		$this->fetch_episodes($plugin_cookies);
    	
    	foreach($this->episode_list as $e)
    	{
    		if($e->get_media_url() === $url)
    			return $e;
    	}
    	return null;
    }
    
    ///////////////////////////////////////////////////////////////////////
    
    public function show_has_watched_episodes(&$plugin_cookies)
    {
    	if(is_null($this->episode_list))
    		$this->fetch_episodes($plugin_cookies);
    	foreach($this->episode_list as $e)
    	{
    		if($e->is_watched())
    			return true;
    	}
    	return false;
    }
    
    public function show_has_unwatched_episodes(&$plugin_cookies)
    {
    	if(is_null($this->episode_list))
    		$this->fetch_episodes($plugin_cookies);
    	foreach($this->episode_list as $e)
    	{
    		if(!$e->is_watched())
    			return true;
    	}
    	return false;
    }
    
    public function season_has_watched_episodes($season, &$plugin_cookies)
    {
    	$eps = $this->get_episodes($season, $plugin_cookies);
    	foreach($eps as $e)
    	{
    		if($e->is_watched())
    			return true;
    	}
    	return false;
    }
    
    public function season_has_unwatched_episodes($season, &$plugin_cookies)
    {
    	$eps = $this->get_episodes($season, $plugin_cookies);
    	foreach($eps as $e)
    	{
			if(!$e->is_watched())
				return true;
    	}
    	return false;
    }
    
    ///////////////////////////////////////////////////////////////////////
    
    public function mark_watched($bWatched, &$plugin_cookies, $season=null, $episode=null)
    {
    	$url = ArrakisConfig::TVSHOW_MW_URL.'?tvshow='.substr($this->id,2);
    	if(!is_null($season))
    	{
    		$url .= '&season='.$season;
    	}
        if(!is_null($episode))
    	{
    		$url .= '&episode='.$episode;
    	}
    	$watched = $bWatched ? 'true' : 'false';
    	$url .='&watched='.$watched;
    	$doc = ArrakisConfig::http_get_arrakis_document($url, $plugin_cookies);
    	hd_print($doc);
    	 
    	foreach($this->episode_list as $c)
    	{
    		if(	(is_null($season) || $c->get_season() == $season) &&
    			(is_null($episode) || $c->get_episode() == $episode) &&
    			((!$bWatched) == $c->is_watched()) )
    		{
    			$c->set_watched($bWatched);
    			$this->unwatched_count += $bWatched ? -1 : 1;
    		}
    	}
    	 
    }
    
    ///////////////////////////////////////////////////////////////////////
    
    public function get_episodes($season, &$plugin_cookies)
    { 
		if(is_null($this->episode_list))
			$this->fetch_episodes($plugin_cookies);

		if($season == 'U')
			return $this->get_unwatched_episodes($plugin_cookies);
		
		$eps = array();
		
		foreach($this->episode_list as $c)
		{
			if($c->get_season() == $season)	
			{
				$eps[] = $c;
			}
		}
		
		return $eps; 
    }

    ///////////////////////////////////////////////////////////////////////
    
    private function get_unwatched_episodes(&$plugin_cookies)
    { 
		if(is_null($this->episode_list))
			$this->fetch_episodes($plugin_cookies);
		
		$eps = array();
		
		foreach($this->episode_list as $c)
		{
			if(!$c->is_watched())
			{
				$eps[] = $c;
			}
		}
		
		return $eps;
    }

    ///////////////////////////////////////////////////////////////////////
    
    private function fetch_episodes(&$plugin_cookies)
    {
//    	hd_print("fetch tvshows: " . $media_url->get_raw_string());
    	$id = $this->id;
    	$url = ArrakisConfig::TVEPISODE_LIST_URL.substr($id, 2).'&time='.strval(time());
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
    
    	if ($xml->getName() !== 'episodes')
    	{
    		hd_print("Error: unexpected node '" . $xml->getName() . "'. Expected: 'episodes'");
    		throw new Exception('Invalid XML document');
    	}
    
    	$this->load_episodes($xml);
    }
    
    private function load_episodes(&$xml)
    {
    	$this->episode_list = array();
    	$this->episode_index = array();
    	$this->season_list = array();
    	
    	$ls = -1;
    	foreach ($xml->children() as $c)
    	{
    		$cat = new ArrakisEpisode($c);
    		if(!$cat->is_watched()) 
    		{
    			$this->unwatched_count++;
    			if(strcmp($cat->get_aired(), $this->oldestunwatched) < 0  || $this->oldestunwatched === '')
    				$this->oldestunwatched = $cat->get_aired();	
    		}
    		else 
    		{
    			if($cat->get_lastplayed() > $this->lastplayed)
    				$this->lastplayed = $cat->get_lastplayed();	
    		}
   			if(strcmp($cat->get_aired(), $this->lastaired) > 0)
   				$this->lastaired = $cat->get_aired();	
    		$s = $cat->get_season();
    		if($ls != $s)
    		{
    			$this->season_list[] = sprintf("%02d", $s);
    			$ls = $s;
    		}
    		$this->episode_list[] = $cat;
    		$this->episode_index[$cat->get_id()] = $cat;
    	}
//    	hd_print("episodes: " . count($this->episode_list));
    }
}

///////////////////////////////////////////////////////////////////////////
?>
