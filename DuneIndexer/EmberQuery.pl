use strict;
use warnings;

use DBI;
use Time::HiRes qw(gettimeofday tv_interval);

my $dbfile = 'D:\\\Ember Media Manager\\Media.emm';
my $dbin = DBI->connect("dbi:SQLite:dbname=$dbfile", "", "", {sqlite_unicode => 1});
my $dboutname = 'dune';

my  $t0 = [gettimeofday];
my $elapsed;

my @tables = ('Movies', 'TVShows', 'TVSeason', 'TVEps', 'TVEpPaths', 'MoviesActors', 'MoviesAStreams', 'MoviesVStreams', 'MoviesSubs', 'MoviesPosters', 'MoviesFanart', 'TVShowActors', 
	'TVAStreams', 'TVVStreams', 'TVSubs', 'Sources', 'TVSources', 'Actors');

my $sql = "select TVShows.Title, TVEps.HasPoster, count(*) from TVSHows, TVEps where TVEps.TVSHowID=TVSHows.ID AND TVEpPathID>0 AND TVEps.HasPoster= 0 group by TVShows.Title, TVEps.HasPoster";
my $psql = $dbin->prepare($sql);
$psql->execute();

while(my $data = $psql->fetchrow_hashref()) {
	my @cols = keys %$data;
	my @vals = values %$data;
	foreach my $v (@vals) {
		print "$v, "
	}
	print "\n";
}

$elapsed = tv_interval ( $t0);
print "Finished $elapsed\n";

$dbin->disconnect();

