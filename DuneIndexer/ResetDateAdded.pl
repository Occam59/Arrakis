#! /usr/bin/perl

use strict;
use warnings;

use DBI;
use Time::HiRes qw(gettimeofday tv_interval);
use Text::Unidecode;
use Win32::Unicode::Native;
 
my $dbfile = 'D:\\\Ember Media Manager\\Media.emm';
my $dbin = DBI->connect("dbi:SQLite:dbname=$dbfile", "", "", {sqlite_unicode => 1});

my $sql = "SELECT ID,ListTitle,DateAdd,MoviePath FROM MOvies";
my $psql = $dbin->prepare($sql);
$psql->execute();

my $sql1 = "UPDATE Movies SET DateAdd = ? WHERE ID = ?";
my $ss = $dbin->prepare($sql1) or die "prepare $sql1\n" . $dbin->errstr . "\n";
	
while(my $data = $psql->fetchrow_hashref()) {
	my $id = $data->{ID};
	my $title = $data->{ListTitle};
	my $da = $data->{DateAdd};
	my $nda = (stat($data->{MoviePath}))[9];
	print "$id,$title, $da, $nda\n";
	$ss->execute($nda, $id);
}

	
