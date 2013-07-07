package Media::EmberMM;
use strict;
use warnings;

use DBI;

#
#
our %wcl = (
	'Movies2' => ['SELECT Movies.* FROM Movies, MoviesActors', ' WHERE MovieID=Movies.ID AND ActorName = "', 'MoviesActors', 'ActorName', 
		'" ORDER BY Substr(ReleaseDate, -4) desc,  Substr(ReleaseDate, -7,  2) desc, abs(ReleaseDate) desc'],
	'Movies3' => ['SELECT * FROM Movies', ' WHERE FIND_IN_SET(Left(SortTitle,1),"A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z")=', 'Alphabet', 'Index', ' ORDER BY SortTitle'],
	'TVShows2' => ['SELECT TVShows.* FROM TVShows, TVShowActors', ' WHERE TVShowID=TVShows.ID AND ActorName = "', 'TVShowActors', 'ActorName', 		'" ORDER BY Title'],
	'TVShows3' => ['SELECT * FROM TVShows', ' WHERE FIND_IN_SET(Left(Title,1),"A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z")=', 'Alphabet', 'Index', ' ORDER BY Title'],
	'TVShows4' => ['SELECT TVShows.* FROM TVShows, (SELECT TVShowID, MAX(TvEpPathID) AS ID1 FROM TVEps GROUP BY TVShowID) A WHERE A.TVShowID = TVShows.ID', ' ORDER BY A.ID1 DESC'],
	'MoviesActors2' => ['SELECT * FROM MoviesActors', ' WHERE MovieID=', 'Movies', 'ID', ' ORDER BY ID'],
	'MoviesVStreams' => ['SELECT * FROM MoviesVStreams', ' WHERE MovieID=', 'Movies', 'ID', ' ORDER BY StreamID'],
	'MoviesAStreams' => ['SELECT * FROM MoviesAStreams', ' WHERE MovieID=', 'Movies', 'ID', ' ORDER BY StreamID'],
	'MoviesSubs' => ['SELECT * FROM MoviesSubs', ' WHERE MovieID=', 'Movies', 'ID', ' ORDER BY StreamID'],
	'TVShowActors2' => ['SELECT * FROM TVShowActors', ' WHERE TVShowID=', 'TVShows4', 'ID', ' ORDER BY ID'],
	'TVSeasons' => ['SELECT TVShowID, Season FROM TVEps', ' WHERE TVEpPathID > 0 AND TVShowID=', 'TVShows4', 'ID', ' GROUP BY TVShowID, Season ORDER BY Season'],
	'TVEps' => ['SELECT TVEps.*,TVEpPath FROM TVEps, TVEpPaths', ' WHERE TVEps.TVEpPathId=TVEpPaths.ID AND TVShowID=', 'TVSeasons', 'TVShowID', ' AND Season=', 'TVSeasons', 'Season', ' ORDER BY Season, Episode'],
	'TVVStreams' => ['SELECT * FROM TVVStreams', ' WHERE TVEpID=', 'TVEps', 'ID', ' ORDER BY StreamID'],
	'TVAStreams' => ['SELECT * FROM TVAStreams', ' WHERE TVEpID=', 'TVEps', 'ID', ' ORDER BY StreamID'],
	'MoviesGenres2' => ['SELECT DISTINCT Genre FROM MoviesGenres', ' ORDER BY Genre'],
	'MoviesGenres3' => ['SELECT Movies.* FROM MoviesGenres, Movies', ' WHERE MoviesGenres.MovieID=Movies.ID AND MoviesGenres.Genre="', 'MoviesGenres2', 'Genre', '" ORDER BY Rating Desc'],
	'TVShowsGenres2' => ['SELECT DISTINCT Genre FROM TVShowsGenres', ' ORDER BY Genre'],
	'TVShowsGenres3' => ['SELECT TVShows.* FROM TVShowsGenres, TVShows', ' WHERE TVShowsGenres.TVShowID=TVShows.ID AND TVShowsGenres.Genre="', 'TVShowsGenres2', 'Genre', '" ORDER BY Rating Desc'],
	'TVLASTAIRED' => ['SELECT TVShowPath, MAX(Aired) from TVShows, TVEps  WHERE TVEps.TVShowId = TVShows.Id and TVEpPathId > 0', ' GROUP BY TVShowPath ORDER BY MAX(Aired) DESC']
);

our %decodes = (
	'MoviesAStreams' => [
		['Audio_Channel', { 1 => '1.0', 2 => '2.0', 6 => '5.1' , 4 => '', 5 => '', 7 => '' }]
	],
	'TVAStreams' => [
		['Audio_Channel', { 1 => '1.0', 2 => '2.0', 6 => '5.1' , 4 => '', 5 => '', 7 => '' }]
	]
);

sub new {
    my $this = shift;
    my $dsn = shift;
    my $user = shift;
    my $pwd = shift;
    my $class = ref($this) || $this;
	my $drh = DBI->install_driver("mysql");
    my $obj = bless {
	DBH => DBI->connect($dsn, $user, $pwd, {mysql_enable_utf8 => 1}),
	Alphabet => [0,0],
	Movies => [0,0],
	Movies2 => [0,0],
	Movies2 => [0,0],
	MoviesActors => [0,0],
	MoviesActors2 => [0,0],
	MoviesVStreams => [0,0],
	MoviesAStreams => [0,0],
	MoviesGenres2 => [0,0],
	MoviesGenres3 => [0,0],
	MoviesSubs => [0,0],
	TVSEASONS => [0,0],
	TVShows => [0,0],
	TVShows2 => [0,0],
	TVShows3 => [0,0],
	TVShows4 => [0,0],
	TVEps => [0,0],
	TVShowActors => [0,0],
	TVShowActors2 => [0,0],
	TVShowsGenres2 => [0,0],
	TVShowsGenres3 => [0,0],
	TVVStreams => [0,0],
	TVAStreams => [0,0],
	TVLASTAIRED => [0,0],
	@_
    }, $class;
    return $obj;
}

sub next {
    my $self = shift;
    my $table = shift;
	my $orderby = shift;

    if(!$self->{$table}[0]) {
		$self->prepare_query($table, $orderby);
    }
    my $row = $self->{$table}[0]->fetchrow_hashref();
    $self->{$table}[1] = $row;
    
    if(defined($row)) {
	    $self->translate_row($table, $row);
    }
    return $row; 
}

sub translate_row {
    my $self = shift;
    my $table = shift;
	my $row = shift;
	
    if(exists($decodes{$table})) {
    	my $tdec = $decodes{$table};
		foreach my $col (@{$tdec}) {
			my @cd = @{$col};
#			print @{$col}[0].'='.$row->{@{$col}[0]}."\n";
			if(exists(@{$col}[1]->{$row->{@{$col}[0]}})) {
				$row->{@{$col}[0]} = @{$col}[1]->{$row->{@{$col}[0]}};
#				print @{$col}[0].'='.$row->{@{$col}[0]}."\n";
			}
		}
    }
    
    if(($table eq 'Movies' || $table =~ /Movies.$/ || $table eq 'MoviesGenres3' ) && ($row->{'MoviePath'} =~ /\\([^\\]+)\\[^\\]+\.(\w+)$/))  {
#    	print "$1\n";
    	$row->{dirname} = $1;
    	$row->{Container} = $2;
    	if(exists($row->{'Rating'})) {
	    	$row->{RoundRating} = int($row->{'Rating'}+0.5);
    	}
    	if($row->{Certification} =~ /Australia:([^\/]+)( \/|$)/i) {
    		$row->{Certification} = 'australia_'.$1;
    	}
    	if($row->{Certification} =~ /USA:([^\/]+)( \/|$)/i) {
    		$row->{Certification} = $1;
    		$row->{Certification} =~ s/-//g;
    		$row->{Certification} = 'mpaa'.$row->{Certification};
    	}
    	$row->{Runtime} = substr($row->{Runtime}, 0, index($row->{Runtime}, 'min')+3);
    } 
    elsif(($table eq 'TVShows' || $table =~ /TVShows.$/ || $table eq 'TVShowsGenres3') && ($row->{'TVShowPath'} =~ /\\([^\\]+)$/))  {
    	$row->{dirname} = $1;
    	if(exists($row->{'Rating'})) {
	    	$row->{RoundRating} = int($row->{'Rating'}+0.5);
    	}
    }
	elsif($table eq 'TVLASTAIRED' && ($row->{'TVShowPath'} =~ /\\([^\\]+)$/))  {
    	$row->{dirname} = $1;
#    	print "$1\n";
    }
    elsif($table eq 'TVEps' && ($row->{'TVEpPath'} =~ /\.(\w+)$/))  {
    	$row->{Container} = $1;
    	if(exists($row->{'Rating'}) && defined($row->{'Rating'}) && $row->{'Rating'} ne "") {
	    	$row->{RoundRating} = int($row->{'Rating'}+0.5);
    	}
    }
    elsif($table eq 'TVVStreams') {
    	if(exists($row->{'Video_Duration'}) && $row->{'Video_Duration'})  {
			$row->{'Video_Duration'} = int(($row->{'Video_Duration'}+30)/60)."min";
    	}
    	my $i = $row->{Video_AspectDisplayRatio};
    	$row->{'Aspect'} = abs($i-4/3) < 0.1 ? "4:3" : (abs($i-16/9) < 0.1 ? "16:9" : sprintf("%.2f:1")); 
   	}
    elsif($table eq 'MoviesVStreams')  {
    	my $i = $row->{Video_AspectDisplayRatio};
    	$row->{'Aspect'} = abs($i-4/3) < 0.1 ? "4:3" : (abs($i-16/9) < 0.1 ? "16:9" : sprintf("%.2f:1", $i)); 
   	}
    
	return $row;	
}

sub prepare_query {
    my $self = shift;
    my $table = shift;
	my $orderby = shift;

   	my $sql = "SELECT * FROM ".$table;
	if(exists($wcl{$table})) {
		my $sqlt = $wcl{$table};
		$sql = @{$sqlt}[0];
		for(my $i=1; $i < @{$sqlt} -3 ; $i= $i+3) {
			$sql .= @{$sqlt}[$i];
			$sql .= $self->{@{$sqlt}[$i+1]}[1]->{@{$sqlt}[$i+2]};
		}
		if($orderby eq '') {
			$sql .= @{$sqlt}[@{$sqlt}-1];
		}
	}
	if($orderby ne '') {
		$sql .= ' ORDER BY '.$orderby;
	}
#	print $sql."\n";

	$self->{$table}[0] = $self->{DBH}->prepare($sql);
	$self->{$table}[0]->execute();
}

sub clearquery {
    my $self = shift;
    my $table = shift;
#    print "Clearing $table\n";
    $self->{$table}[0] = 0; 
}

sub DESTROY {
	my $self = shift;
	
	return if ${^GLOBAL_PHASE} eq 'DESTRUCT';
	
	$self->{DBH}->disconnect();

}

return 1;