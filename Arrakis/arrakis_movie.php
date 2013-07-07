<?php
///////////////////////////////////////////////////////////////////////////
require_once 'lib/default_dune_plugin.php';
require_once 'lib/utils.php';

define('MOVIE_PREFIX', 'm.');


class ArrakisMovie
{
    private $id;
    private $poster_url;
    private $caption;
    private $trailer_url;
    private $media_url;
    private $background_url;
    private $release_date;
    private $date_added;
    private $rating;
    private $watched;
    private $IMDB;
    private $genres;
    private $lastplayed;
    
    public function __construct(&$xml)
    {
    	$this->load_from_xml($xml);
    }
    
    private function load_from_xml(&$xml)
    {
    	$this->id = MOVIE_PREFIX.strval($xml->id);
    	$this->caption = strval($xml->caption);
    	$this->trailer_url = strval($xml->trailer_url);
    	$this->media_url = strval($xml->media_url);
    	$this->poster_url = strval($xml->poster_url);
    	$this->background_url = strval($xml->background_url);
    	$this->release_date = strval($xml->release_date);
    	$this->date_added = strval($xml->date_added);
    	$this->rating = strval($xml->rating);
    	$this->IMDB = strval($xml->IMDB);
    	$this->watched = intval($xml->watched);
    	$this->genres = ' '.strval($xml->genres).' ';
    	$this->lastplayed = intval($xml->lastplayed);
    }
    
    public function refresh(&$plugin_cookies)
    {
    	$url_prefix = ArrakisConfig::MOVIE_LIST_URL.'?movie='.substr($this->id, 2);
    	$url = $url_prefix.'&time='.time();
    	$url = str_replace(" ", "%20", $url);
    	$xml = ArrakisConfig::http_get_arrakis_xml($url, $plugin_cookies);
    	
    	$this->load_from_xml($xml->movie);
    }
    
    public function get_id()
    { return $this->id; }

    public function get_poster_url()
    { return $this->poster_url; }

    public function get_caption()
    { return $this->caption; }

    public function get_trailer_url()
    { return $this->trailer_url; }
    
    public function get_expanded_trailer_url() 
    {
    	$url = $this->trailer_url;
    	$youtube = 'http://www.youtube.com/watch?v=';
	    if(!strncmp($url, $youtube, strlen($youtube)))
	    {
	    	$url = $this->retrieve_playback_url($url);
	    }
    	return $url;
    }
    

    public function get_media_url()
    { return $this->media_url; }

    public function get_background_url()
    { return $this->background_url; }

    public function get_release_date()
    { return $this->release_date; }

    public function get_date_added()
    { return $this->date_added; }

    public function get_rating()
    { return $this->rating; }

    public function get_lastplayed()
    { return $this->lastplayed; }

    public function is_genre($genre)
    {
    	return stripos($this->genres, $genre) > -1 ? true : false;
    }
    
    public function get_IMDB_url()
    { return 'http://www.imdb.com/title/tt'.$this->IMDB; }
    
    public function is_watched()
    { return $this->watched; }
    
	///////////////////////////////////////////////////////////////////////////
    
    public function set_watched($bWatched, &$plugin_cookies)
    {
    	$watched = $bWatched ? 'true' : 'false';
    	$url = ArrakisConfig::MOVIE_MW_URL.'?movie='.substr($this->id,2).'&watched='.$watched;
    	$doc = ArrakisConfig::http_get_arrakis_document($url, $plugin_cookies);
    	hd_print($doc);
    	$this->watched = $bWatched;
    }

    ///////////////////////////////////////////////////////////////////////////
    
    
    private function retrieve_playback_url($id) {
		// hack! but it seems to be helpfull, no more plugin restarts
		$video_quality = 'hd1080';
		$mp4 =  'http-mp4';
	
		$doc =
		HD::http_get_document(
	//			'http://www.youtube.com/watch?v='.$id,
				$id.'&time='.time(),
				array(
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_MAXREDIRS => 10,
						CURLOPT_HEADER => true,
						CURLOPT_HTTPHEADER => array('X-GData-Key', ArrakisConfig::YOUTUBE_DEV_KEY),
				));
		//hd_print("----- doc: $doc");
		hd_print("--> Retrieving playback URL for $id...");
	
		if (preg_match("/ytplayer.config = ({.*});/", $doc, $m) !== 1) {
			hd_print("--> Can't find ytplayer.config.");
			throw new Exception('Invalid movie meta-data');
		}
	
		$cfg = json_decode($m[1]);
	
		$str = $cfg->args->url_encoded_fmt_stream_map;
		$lst = explode(',', $str);
	
		$first_found = "";
		$last_found = "";
		$first_quality = "";
		$last_quality = "";
	
		foreach ($lst as $l) {
			$str = urldecode($l);
			// fix sig to signature
			$str = str_replace("&sig=", "&signature=", $str);
			// fix %2C to ,
			$str = str_replace("%2C", ",", $str);
			// create args array
			parse_str($str, $str_args);
			// itag used to decode mp4 stream
			// FORMAT_TYPE={'18':'mp4','22':'mp4','34':'flv','35':'flv','37':'mp4','38':'mp4','43':'webm','44':'webm','45':'webm','46':'webm'};
			$itag = $str_args['itag'];
			$quality = $str_args['quality'];
			if (in_array($itag, array('18', '22', '37', '38')))
			{
				// create stream url
				$url = $str_args['url'];
				foreach ($str_args as $key => $value) {
					if ($key !== 'url') {
						$url .= "&{$key}={$value}";
					}
					hd_print("-----> {$key} => {$value}");
				}
	
				if ($mp4 === 'http-mp4')
				{
					$playback_url = str_replace('http://', 'http://mp4://', $url);
				}
				else
				{
					$playback_url = $url;
				}
				hd_print("---> itag: $itag, quality: $quality, url: $playback_url");
	
				if ($first_found === "") {
					$first_found = $playback_url;
					$first_quality = $quality;
				}
				$last_found = $playback_url;
				$last_quality = $quality;
	
				if (($quality === $video_quality) || (($quality !== 'medium') && ($video_quality === 'hdonly'))) {
					hd_print("-----> returned qaulity as requested: $video_quality");
					return $playback_url;
				}
			}
		}
	
		if (($last_found !== "") && ($video_quality !== 'hdonly')) {
			if ($video_quality === 'hd1080') {
				hd_print("-----> returned qaulity $first_quality instead of requested: $video_quality");
				return $first_found;
			} else {
				hd_print("-----> returned qaulity $last_quality instead of requested: $video_quality");
				return $last_found;
			}
		} else {
			// hd_print("--> video: $id; playback url: ''");
			hd_print("--> video: $id; no mp4-stream.");
			return false;
		}
	}

}

///////////////////////////////////////////////////////////////////////////
?>
