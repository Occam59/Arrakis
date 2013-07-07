#! /usr/bin/perl

use strict;
use warnings;

use DBI;
use Time::HiRes qw(gettimeofday tv_interval);
use Text::Unidecode;
use Config::IniFiles;
my $cfg = Config::IniFiles->new( -file => "IndexDune.ini" );
 
my $dbfile = 'D:\\\Ember Media Manager\\Media.emm';
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

my @table_keys = ('ID', 'ID', 'TVShowID,Season', 'ID', 'ID', 'MovieID,ActorName,Role', 'MovieID,StreamID', 'MovieID,StreamID', 'MovieID,StreamID', 'ID', 'ID', 'TVShowID,ActorName,Role', 
	'TVEpID,StreamID', 'TVEpID,StreamID', 'TVEpID,StreamID', 'ID', 'ID');

for (my $t = 0; $t<@tables; $t++) {
	my $table = $tables[$t];
	my $keys = $table_keys[$t];
	my  $t1 = [gettimeofday];
	print "processing $table  ";
	my $cnt = process_table($dbin, $dbh, $table, $keys);
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
	my $keys = shift;
	
	my $sqlo = "SELECT * FROM ".$table.' ORDER BY '.$keys;
	$sqlo =~ s/ActorName/ASCII(ActorName), ASCII(Substring(ActorName, 2)), ASCII(Substring(ActorName, 3)), ASCII(Substring(ActorName, 4)), ASCII(Substring(ActorName, 5)), ASCII(Substring(ActorName, 6)), ASCII(Substring(ActorName, 7)), ASCII(Substring(ActorName, 8)), ASCII(Substring(ActorName, 9)), ActorName/;
	
	my $psqlo = $dbh->prepare($sqlo) or die "unable to prepare $sqlo";
	$psqlo->execute();

	my $sqli = "SELECT * FROM ".$table.' ORDER BY '.$keys;
	my $psqli = $dbin->prepare($sqli) or die "unable to prepare $sqli";
	$psqli->execute();
	
	my $datai = $psqli->fetchrow_hashref();
	my $datao = $psqlo->fetchrow_hashref();
	my @keyf = split ',', $keys;
	my @fields = keys %$datai;
	my $inssql = "insert into $table("
	      . join(',' => @fields)
	      . ')values('
	      . join( ',' => ('?') x @fields )
	      . ')';
	$inssql =~ s/,Lock,/,Lock2,/;
	my $delsql = "delete from $table ";
	my $sep = 'where ';
	for (my$i=0; $i < @keyf; $i++) {
		$delsql .= $sep.$keyf[$i].'=? ';
		$sep = 'and ';
	}
	$inssql =~ s/,Lock,/,Lock2,/;
	my $pinssql = $dbh->prepare($inssql) or die "unable to prepare $inssql";
	my $pdelsql = $dbh->prepare($delsql) or die "unable to prepare $delsql";
#	print "$inssql\n";
#	print "$delsql\n";
	
	my $i=0;
	while($datai && $datao)
	{
		my $rc = compare_row_keys(\@keyf, $datai, $datao);
		if($rc == 0) {
			if(compare_rows(\@fields, $datai, $datao)) {
				print "modify\n";
				delete_row($pdelsql, \@keyf, $datao);
				insert_row($pinssql, \@keyf, $datai);
			}
			$datao = $psqlo->fetchrow_hashref();
			$datai = $psqli->fetchrow_hashref();
		} elsif ($rc < 0) {
			insert_row($pinssql, \@keyf, $datai);
			$datai = $psqli->fetchrow_hashref();
		} else {
			delete_row($pdelsql, \@keyf, $datao);
			$datao = $psqlo->fetchrow_hashref();
		}
		$i++;
	}
	
	while($datai) {
		insert_row($pinssql, \@keyf, $datai);
		$datai = $psqli->fetchrow_hashref();
	}
	while($datao) {
		delete_row($pdelsql, \@keyf, $datao);
		$datao = $psqlo->fetchrow_hashref();
	}
	
	return $i;
}

sub insert_row
{
	my $pinssql = shift;
	my $keyf = shift;
	my $row = shift;

	print "insert ";
	print_row_keys($keyf, $row);		

	$pinssql->execute(values(%$row));
}

sub delete_row
{
	my $pdelsql = shift;
	my $keyf = shift;
	my $row = shift;
	my @vals;

	print "delete ";
	print_row_keys($keyf, $row);		

	for (my$i=0; $i < @$keyf; $i++) {
		push @vals, $row->{$keyf->[$i]};
	}
	$pdelsql->execute(@vals);
}

sub print_row_keys
{
	my $keys = shift;
	my $row1 = shift;

	my $i = 0;
	while ($i < @$keys)
	{
		my $k = $keys->[$i];
		print "$k = ".$row1->{$k}.' : ' if defined($row1->{$k});
		$i++; 
	}
	print "\n";
}

sub compare_row_keys
{
	my $keys = shift;
	my $row1 = shift;
	my $row2 = shift;
	my $rc = 0;
	my $i = 0;
	while (($i < @$keys) && !$rc)
	{
		my $k = $keys->[$i];
		$rc = $row1->{$k} eq $row2->{$k} ? 0 : ($row1->{$k} lt $row2->{$k} ? -1 : 1);
#		print "$k : $rc : ".$row1->{$k}.' : '.$row2->{$k}."\n" if $rc;
		$i++; 
	}
	return $rc;
}
sub compare_rows
{
	my $fields = shift;
	my $row1 = shift;
	my $row2 = shift;
	
	return -1 if !$row2;
	return 1 if !$row1;

	foreach my $f (@$fields) {
		my $f2 = $f eq 'Lock' ? 'Lock2' : $f;
#		print "$f : ".$row1->{$f}.' : '.$row2->{$f2}."\n";
		if(
			(!defined($row1->{$f}) && !defined($row2->{$f2})) || 
			(defined($row1->{$f}) && defined($row2->{$f2}) && ($row1->{$f} eq $row2->{$f2}))
			) {
			
		} 	else
		{
			return 1;
		}	
#		return 1 if $row1->{$f} ne $row2->{$f2};
	}
	return 0;
}

sub process_delim_field {
	my $dbin = shift;
	my $dbh = shift;
	my $tabletype = shift;
	my $coltype = shift;
	
	my  $t1 = [gettimeofday];
	print "processing $tabletype $coltype ";
	
	my $sql = 'TRUNCATE '.$tabletype.'s'.$coltype.'s';
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
	$elapsed = tv_interval ($t1);
	print "$i  $elapsed\n";
	return $i;
}

sub process_actors {
	my $dbin = shift;
	my $dbh = shift;

	my  $t1 = [gettimeofday];
	print "processing actors ";
	
#	my $sql = 'TRUNCATE Actors';
#	print"$sql\n";
#	my $psql = $dbh->prepare($sql) or die("Can't truncate Actors");
#	$psql->execute();

	my $sql = 'SELECT Name, thumb FROM Actors order by name, thumb desc';
#	print"$sql\n";
	my $psql = $dbin->prepare($sql);
	$psql->execute();
	my $lastname = '';
	my $actors = {};
	
	while(my $row = $psql->fetchrow_hashref()) {
		if($lastname ne $row->{'Name'}) {
			$actors->{$row->{'Name'}} = $row->{'thumb'};
			$lastname = $row->{'Name'};
		}
	}
	
	$psql = $dbh->prepare($sql);
	$psql->execute();
	while(my $row = $psql->fetchrow_hashref()) {
		my $name = $row->{'Name'};
		delete $actors->{$name} if exists($actors->{$name});
	}
	
	$sql = 'INSERT INTO Actors(Name, thumb, SortName) values (?, ?, ?)';
#	print"$sql\n";

	my $ss = $dbh->prepare($sql)
	      or die "Actors prepare $sql\n" . $dbh->errstr . "\n";

	my $i=0;
	foreach my $k (keys %$actors) {
		my @values = ($k, $actors->{$k}, makepropername($k));
		$ss->execute(@values);
		$i++;
	}

	$elapsed = tv_interval ($t1);
	print "$i  $elapsed\n";
	return $i;
}

sub process_directors {
	my $dbh = shift;
	
	my  $t1 = [gettimeofday];
	print "processing directors ";

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

	$elapsed = tv_interval ($t1);
	print "$i  $elapsed\n";
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

