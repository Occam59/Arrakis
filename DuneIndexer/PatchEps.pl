use strict;
use warnings;

use DBI;
use Time::HiRes qw(gettimeofday tv_interval);
use Win32::Unicode::Native;

my $dboutname = 'dune';
my $hostname='192.168.1.86';
my $port = '3306';
my $dsn = "DBI:mysql:database=$dboutname;host=$hostname;port=$port";
my $dbh = DBI->connect($dsn, 'root', 'admin', {mysql_enable_utf8 => 1});
my $drh = DBI->install_driver("mysql");

my  $t0 = [gettimeofday];
my $elapsed;

our $files = {};
our $newfiles= {};

my $sqli = "SELECT * FROM TVEpPaths";
load_files($files, $dbh, $sqli, 'TVEpPath');

processDir('\\\\BARNARD-1079\\Media\\TVShows');

foreach my $f (keys $files) {
	print $f.' '.$files->{$f}."\n";
}
print "\n";

foreach my $f (keys $newfiles) {
	print "New: $f\n";
}


$elapsed = tv_interval ( $t0);
print "Finshed $elapsed\n";

sub processDir
{
	my $some_dir = shift;

	my $wdir = Win32::Unicode::Dir->new;
	$wdir->open($some_dir) or die $!;
	for ($wdir->fetch) {
      next if /^\.{1,2}$/;

      my $full_path = "$some_dir\\$_";
      if (file_type('f', $full_path)) {
			processFile($some_dir, $_);
      }
      elsif (file_type('d', $full_path)) {
			processDir($full_path);	
      }
  }
  $wdir->close or die $!;

}

sub processFile
{
	my $some_dir = shift;
	my $file = shift;
	
	my $s = $some_dir;
	$s = chop $s;
	
	
	if($file =~ /(\.db|\.nfo|\.jpg|srt|sub|png|info|tbn)/i) 
	{
		
	}
	elsif($file =~ /^(.*) - (S\d\d(E\d\d)+)( - )?(.*)\.([^\.]*)$/)
	{
		my $lf = $some_dir.'\\'.$file;
#		print "File: $file\n";
#		print $1.'**'.$2.'**'.$3.'**'.$4;		
		if(!exists($files->{$lf})) {
			$newfiles->{$lf} = 1;
		}
		else {
			delete $files->{$lf};
		}
	}
	else
	{
		print "File: $file\n";
	}
}

sub load_files {
	my $file = shift;
	my $dbh = shift;
	my $sqli = shift;
	my $field = shift;
	
	my $psqli = $dbh->prepare($sqli);
	$psqli->execute();
	while(my $data = $psqli->fetchrow_hashref()) {
		my $path = $data->{$field};
#		print "+$path\n";
		$files->{$path} = $data->{'ID'};
	}
}
