use strict;
use warnings;

use DBI;
use Time::HiRes qw(gettimeofday tv_interval);

my $dbfile = 'boxee.db';
my $dbin = DBI->connect("dbi:SQLite:dbname=$dbfile", "", "", {sqlite_unicode => 1});

my $dboutname = 'dune';
my $hostname='192.168.1.86';
my $port = '3306';
my $dsn = "DBI:mysql:database=$dboutname;host=$hostname;port=$port";
my $dbh = DBI->connect($dsn, 'root', 'admin', {mysql_enable_utf8 => 1});
my $drh = DBI->install_driver("mysql");

my  $t0 = [gettimeofday];
my $elapsed;

my $files = {};

my $sqli = "SELECT * FROM TVEpPaths";
load_files($files, $dbh, $sqli, 'TVEpPath');
$sqli = "SELECT * FROM Movies";
load_files($files, $dbh, $sqli, 'MoviePath');

my $sqltv = "INSERT INTO TVWatched (Filename, PlayCount, LastPlayed, LastPosition, TVEpPathID) VALUES (?, ?, ?, ?, ?)";
my $psqltv = $dbh->prepare($sqltv);
my $sqlm = "INSERT INTO MoviesWatched (Filename, PlayCount, LastPlayed, LastPosition, MovieID) VALUES (?, ?, ?, ?, ?)";
my $psqlm = $dbh->prepare($sqlm);

my $sql = "select * from watched";
my $psql = $dbin->prepare($sql);
$psql->execute();

while(my $data = $psql->fetchrow_hashref()) {
	my $strPath = $data->{strPath};
	if($strPath =~ /BARNARD/ && $strPath =~ /Media\/Videos\//i) {
		my $ilp = $data->{iLastPlayed};
		$strPath = substr($strPath, rindex($strPath, '/')+1);
		my $spath = strip_title($strPath);
		my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($ilp);
		my $id = exists($files->{$spath}) ? $files->{$spath} : -1; 
		my $fp = ($data->{fPositionInSeconds} > 0 ) ? int($data->{fPositionInSeconds}) : 0;
		if($data->{strPath} =~ /TVShow/) {
			$psqltv->execute($strPath, $data->{iPlayCount}, $data->{iLastPlayed}, $fp, $id);
			print "TV: ".$strPath.'   '.($year+1900).'-'.$mon.'-'.$mday.' '.$hour.':'.$min.'  '.$id."\n";
		}
		elsif($data->{strPath} =~ /Movie/) {
			$psqlm->execute($strPath, $data->{iPlayCount}, $data->{iLastPlayed}, $fp, $id);
			print "Movie: ".$strPath.'   '.($year+1900).'-'.$mon.'-'.$mday.' '.$hour.':'.$min.'  '.$id."\n";
		}
	}
}

$elapsed = tv_interval ( $t0);
print "Finished $elapsed\n";

$dbin->disconnect();

sub load_files {
	my $file = shift;
	my $dbh = shift;
	my $sqli = shift;
	my $field = shift;
	
	my $psqli = $dbh->prepare($sqli);
	$psqli->execute();
	while(my $data = $psqli->fetchrow_hashref()) {
		my $path = $data->{$field};
		$path = substr($path, rindex($path, '\\')+1);
		$path = strip_title($path);
		$files->{$path} = $data->{'ID'};
	}
}

sub strip_title {
	my $path = shift;
	if($path =~ /^(.*) - (.*)( - (.*)|\..*)$/ ) {
		$path = lc($1.' - '.$2);
	}
	elsif($path =~ /^(.*)\.[^\.]+$/ )
	{
		$path = lc($1);
	}
#	print $path."\n" if $path =~ /elementary/;
	return $path;
}

