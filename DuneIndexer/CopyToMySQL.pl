#! /usr/bin/perl

use strict;
use warnings;

use DBI;
use Time::HiRes qw(gettimeofday tv_interval);
use Text::Unidecode;
use Config::IniFiles;
my $cfg = Config::IniFiles->new( -file => "IndexDune.ini" );
 
my $dbfile = $cfg->val('ember', 'database');
my $dbin = DBI->connect("dbi:SQLite:dbname=$dbfile", "", "", {sqlite_unicode => 1});

my $dboutname = $cfg->val('database', 'name');
my $hostname = $cfg->val('database', 'host');
my $port = $cfg->val('database', 'port');
my $user = $cfg->val('database', 'user');
my $pwd = $cfg->val('database', 'password');
my $dsn = "DBI:mysql:database=$dboutname;host=$hostname;port=$port";
my $dbh = DBI->connect($dsn, $user, $pwd, {mysql_enable_utf8 => 1});
my $drh = DBI->install_driver("mysql");

my  $t0 = [gettimeofday];
my $elapsed;

my @tables = ('Movies', 'TVShows', 'TVSeason', 'TVEps', 'TVEpPaths', 'MoviesActors', 'MoviesAStreams', 'MoviesVStreams', 'MoviesSubs', 'MoviesPosters', 'MoviesFanart', 'TVShowActors', 
	'TVAStreams', 'TVVStreams', 'TVSubs', 'Sources', 'TVSources');

foreach my $table (@tables) {
	my  $t1 = [gettimeofday];
	print "processing $table  ";
	my $cnt = process_table($dbin, $dbh, $table);
	$elapsed = tv_interval ($t1);
	print "$cnt  $elapsed\n";
}

process_delim_field($dbin, $dbh, 'Movie', 'Genre');
process_delim_field($dbin, $dbh, 'Movie', 'Director');
process_delim_field($dbin, $dbh, 'TVShow', 'Genre');

process_actors($dbin, $dbh);
process_directors($dbh);

$elapsed = tv_interval ( $t0);
print "Finished $elapsed\n";

$dbin->disconnect();
$dbh->disconnect();

sub process_table {
	my $dbin = shift;
	my $dbh = shift;
	my $table = shift;
	
	my $sql = "DELETE FROM ".$table;
	my $psql = $dbh->prepare($sql);
	$psql->execute();

	$sql = "SELECT * FROM ".$table;
	$psql = $dbin->prepare($sql);
	$psql->execute();
	
	my $data = $psql->fetchrow_hashref();
	
	my @s = keys %$data;
	$sql = "insert into $table("
	      . join(',' => @s)
	      . ')values('
	      . join( ',' => ('?') x @s )
	      . ')';

	$sql =~ s/,Lock,/,Lock2,/;
	      
#	print"$sql\n";
	my $ss = $dbh->prepare($sql)
	      or die "[$table] prepare $sql\n" . $dbh->errstr . "\n";
	#
	my $i=1;
	$ss->execute(values(%$data));
	while($data = $psql->fetchrow_hashref()) {
		$ss->execute(values(%$data));
		$i++;
	}
	return $i;
}

sub process_delim_field {
	my $dbin = shift;
	my $dbh = shift;
	my $tabletype = shift;
	my $coltype = shift;
	
	my $sql = 'DELETE FROM '.$tabletype.'s'.$coltype.'s';
#	print"$sql\n";
	my $psql = $dbh->prepare($sql);
	$psql->execute();

	$sql = 'SELECT ID, '.$coltype.' FROM '.$tabletype.'s';
#	print"$sql\n";
	$psql = $dbin->prepare($sql);
	$psql->execute();
	
	$sql = 'insert into '.$tabletype.'s'.$coltype.'s ('.$tabletype.'ID, '.$coltype.') values (?, ?)';
#	print"$sql\n";

	my $ss = $dbh->prepare($sql)
	      or die "[$tabletype] prepare $sql\n" . $dbh->errstr . "\n";

	my $i=1;
	while(my $row = $psql->fetchrow_hashref()) {
		my $id = $row->{'ID'};
		my $str = $row->{$coltype};
		my @arr = split(/\//, $str);
		foreach my $s (@arr) {
			$s =~ s/^ //g;
			$s =~ s/ $//g;
			my @values = ($id, $s);
			$ss->execute(@values);
			$i++;
		}
	}
	return $i;
}

sub process_actors {
	my $dbin = shift;
	my $dbh = shift;
	
	my $sql = 'DELETE FROM Actors';
#	print"$sql\n";
	my $psql = $dbh->prepare($sql);
	$psql->execute();

	$sql = 'SELECT Name, thumb FROM Actors';
#	print"$sql\n";
	$psql = $dbin->prepare($sql);
	$psql->execute();
	
	$sql = 'INSERT INTO Actors(Name, thumb, SortName) values (?, ?, ?)';
#	print"$sql\n";

	my $ss = $dbh->prepare($sql)
	      or die "Actors prepare $sql\n" . $dbh->errstr . "\n";

	my $i=0;
	while(my $row = $psql->fetchrow_hashref()) {
		my @values = ($row->{'Name'}, $row->{'thumb'}, makepropername($row->{'Name'}));
		$ss->execute(@values);
		$i++;
	}
	return $i;
}

sub process_directors {
	my $dbh = shift;
	
	my $sql = 'SELECT Director As Name FROM MoviesDirectors LEFT JOIN Actors On Director=Name WHERE Name is null';
#	print"$sql\n";
	my $psql = $dbh->prepare($sql);
	$psql->execute();
	
	$sql = 'INSERT INTO Actors(Name, thumb, SortName) values (?, ?, ?)';
#	print"$sql\n";

	my $ss = $dbh->prepare($sql)
	      or die "Actors prepare $sql\n" . $dbh->errstr . "\n";

	my $i=0;
	while(my $row = $psql->fetchrow_hashref()) {
		my @values = ($row->{'Name'}, '', makepropername($row->{'Name'}));
		$ss->execute(@values);
		$i++;
	}
	return $i;
}



sub makepropername {
	my $name = shift;
	
	my $i = 0;
	$name = unidecode($name);
	my $suffix = "";

	if($name =~/(.+) (1st|2nd|3rd|4tf|5th|"\S+"|'\S+'|\(\S+\))$/)
	{
		$name = $1;
		$suffix = ' '.$2;
	}


	if($name =~ /^(.* )([A|E]l |Ap |Ben |Dell([a|e])? |Dalle |Dela |Del |De (La |Los |)?|[D|L|O]'|St\.? |San |Den |Von (Der )?|Van (De(n|r)? )?)(\S+)$/i)
	{
		$i = length($1);
	}
	elsif($name =~ /^(.* )(\S+)$/)
	{
		$i = length($1);
	}
	if($i > 0) {
		$name = substr($name, $i).$suffix.', '.substr($name, 0, $i-1);
	}
	return $name;
}

