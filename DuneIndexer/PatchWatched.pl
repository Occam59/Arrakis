use strict;
use warnings;

use DBI;
use Time::HiRes qw(gettimeofday tv_interval);

my $dboutname = 'dune';
my $hostname='192.168.1.86';
my $port = '3306';
my $dsn = "DBI:mysql:database=$dboutname;host=$hostname;port=$port";
my $dbh = DBI->connect($dsn, 'root', 'admin', {mysql_enable_utf8 => 1});
my $drh = DBI->install_driver("mysql");

my  $t0 = [gettimeofday];
my $elapsed;

my $files = {};

#my $table = 'TVEpPaths';
#my $tablefile = 'TVEpPath';
#my $tableref = 'TVEpPathID';
#my $wtable = 'TVWatched';
my $table = 'Movies';
my $tablefile = 'MoviePath';
my $tableref = 'MovieID';
my $wtable = 'MoviesWatched';


my $sqli = "SELECT * FROM $table";
load_files($files, $dbh, $sqli, $tablefile);
#$sqli = "SELECT * FROM Movies";
#load_files($files, $dbh, $sqli, 'MoviePath');

my $sqltv = "UPDATE $wtable SET Filename = ?, $tableref = ? WHERE ID = ?";
my $psqltv = $dbh->prepare($sqltv);

my $sql = "select * from $wtable";
my $psql = $dbh->prepare($sql);
$psql->execute();

while(my $data = $psql->fetchrow_hashref()) {
#my $data = $psql->fetchrow_hashref; {
	my $strPath = $data->{'FileName'};
	my $spath = substr($strPath, rindex($strPath, '\\')+1);
	$spath = strip_title($spath);
	my $id = $data->{'ID'};
	if(exists($files->{$spath})) {
		my $fp = $files->{$spath.'---1'};
		my $ep = $files->{$spath};
		if($fp ne $strPath || $data->{$tableref} != $ep) {
			print "+$strPath, $spath, $fp, $id, ".$data->{$tableref}."\n";
#			$psqltv->execute($fp, $ep, $id );
		}
	}
	else {
		print "-$strPath, $spath, $id,"."\n";
	}
}

$elapsed = tv_interval ( $t0);
print "Finished $elapsed\n";

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
#		print "$path\n";
		$files->{$path} = $data->{'ID'};
		$files->{$path.'---1'} = $data->{$field};
	}
}

sub strip_title {
	my $path = shift;
#	$path =~ s/\./ /g;
	if($path =~ /^(.+\(\d\d\d\d)[\),]/i ) {
		$path = lc($1);
	}
	elsif($path =~ /^(.+) - (S\d\dE\d\d)/i ) {
		$path = lc($1.' - '.$2);
	}
	elsif($path =~ /^(.+)\s(S\d\dE\d\d)/i) {
		$path = lc($1.' - '.$2);
	} 
	if($path =~ /^(.+) (S\d\d) (E\d\d)/i ) {
		$path = lc($1.' - '.$2.$3);
	}
	elsif($path =~ /^(.+)[\s\.]\[(\d)x(\d\d)\]/i) {
		$path = lc($1.' - S0'.$2.'E'.$3);
	} 
	elsif($path =~ /^(.+)[\s\.](\d)x(\d\d)/i) {
		$path = lc($1.' - S0'.$2.'E'.$3);
	}		
	elsif($path =~ /^(.*)\.[^\.]+$/i )
	{
		$path = lc($1);
	}
	$path =~ s/- -/-/g;
#	print $path."\n" if $path =~ /elementary/;
	return $path;
}

