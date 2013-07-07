use strict;
use warnings;
use feature 'state';

use Media::EmberMM;
use XML::LibXML;
use Encode qw( encode );
use Win32::API;
use Win32::Unicode::Native;
use Time::HiRes qw(gettimeofday tv_interval);
use Config::IniFiles;
my $cfg = Config::IniFiles->new( -file => "IndexDune.ini" );

our $modtime = {};

my  $t0 = [gettimeofday];

our $process_all = 1;
our $template_dir = $cfg->val('template', 'directory')."\\";

my $dboutname = $cfg->val('database', 'name');
my $hostname = $cfg->val('database', 'host');
my $port = $cfg->val('database', 'port');
my $user = $cfg->val('database', 'user');
my $pwd = $cfg->val('database', 'password');
my $dsn = "DBI:mysql:database=$dboutname;host=$hostname;port=$port";
our $outdir = $cfg->val('output', 'directory');
my $dir = '';

my $emm = Media::EmberMM->new($dsn, $user, $pwd);

my $doc = XML::LibXML::Document->new('1.0', 'utf-8');
my $root = $doc->createElement("menu_items");
$root = $doc->addChild($root);

$process_all = 1;
my $template = $cfg->val('template', 'menus');
process_template($template, $emm, $dir, $root);

print $doc->toFile('menu.xml', 1);

my $elapsed = tv_interval ( $t0);
print "Finished $elapsed\n";

sub process_template {
	my $template = shift;
	my $emm = shift;
	my $dir = shift;
	my $row = {};
	my $parser = XML::LibXML->new();
	my $doc    = $parser->parse_file($template);
	my $pargs = {};
	my $outnode = shift;

	process_node($dir, $pargs, $emm, $doc->firstChild, $row, $outnode);
}

sub process_node {
	my $dir = shift;
	my $pargs = shift;
	my $emm = shift;
	my $node = shift;
	my $row = shift;
	my $outnode = shift;
	
	my @nodes = $node->getChildrenByTagName('*');
	foreach my $child (@nodes) {
		my $type = $child->getAttribute('type');
#		print $child->nodeName.' '.$type."\n";
		if($type eq 'table') {
			if($outnode->nodeName ne 'menu_items' && $child->hasAttribute('dir')) {
				my $newnode = XML::LibXML::Element->new('menu_items');
				$outnode = $outnode->addChild($newnode);
			}
			process_table($dir, $pargs, $child, $outnode);
		} elsif($type eq 'image') {
			process_file_image($dir, $pargs, $emm, $child, $row, $outnode)
		} elsif($type eq 'folder') {
			process_node($dir, $pargs, $emm, $child, $row, $outnode);
		} elsif($type eq 'index') {
			if($outnode->nodeName ne 'menu_items' && $child->hasAttribute('dir')) {
				my $newnode = XML::LibXML::Element->new('menu_items');
				$outnode = $outnode->addChild($newnode);
			}
			process_index($dir, $pargs, $emm, $child, $row, $outnode)
		} else {
		}
	}
}

sub process_table {
	my $dir = shift;
	my $pargs = shift;
	my $node = shift;
	my $outnode = shift;
	
	my $table = $node->getAttribute('table');
	my $rows = $node->hasAttribute('rows') ? $node->getAttribute('rows') : 99999;
	my $skip = $node->hasAttribute('skip') ? $node->getAttribute('skip') : 0;
	
	my ($newdir, $newnode) = process_dir($dir, $node, $outnode);

	my $subdir = $node->hasAttribute('subdir') ? $node->getAttribute('subdir') : '';
	my $orderby = $node->hasAttribute('orderby') ? $node->getAttribute('orderby') : '';

	print "Table: ".$table."\n";

	my $row = {};
	my $args = getargs ($newdir, $pargs, $template_dir, $node, $row);

	$outnode = $newnode;
	$emm->clearquery($table);
	my $i=0;
	
	if($skip > 0) {
		while ((my $row = $emm->next($table, $orderby)) && $skip--) {}		
	}
	
	while ((my $row = $emm->next($table, $orderby)) && $rows--) {
		if($subdir ne '') {
			my $sd = $node->hasAttribute('format') ? sprintf($node->getAttribute('format'), $row->{$subdir}) : $row->{$subdir};
			if($outnode->nodeName ne 'menu_items') {
				$newnode = XML::LibXML::Element->new('menu_items');
				$outnode = $outnode->addChild($newnode);
			}
			$newdir = $dir."\\".$sd;
			$newnode = add_menu_item($outnode, substr($newdir, 1));
			print "$newdir\n";
		}
		$row->{'rownum'} = $i;
		$i++;

		process_node($newdir, $args, $emm, $node, $row, $newnode);
	}
}

sub add_menu_item {
	my $outnode = shift;
	my $text = shift;
	
	$text =~ s/\\/\//g;
	
	my $newnode;
	$newnode = XML::LibXML::Element->new('menu_item');
	$newnode = $outnode->appendChild($newnode);
	$newnode->appendTextChild('id', $text);
	$newnode->appendTextChild('caption', $text);

	return $newnode;
}

sub process_index {
	my $dir = shift;
	my $pargs = shift;
	my $emm = shift;
	my $node = shift;
	my $row = shift;
	my $outnode = shift;
	print "Index: ".$node->nodeName."\n";

	my ($newdir, $newnode) = process_dir($dir, $node, $outnode);

	my $args = getargs ($newdir, $pargs, $template_dir, $node, $row, $newnode);
	process_node($newdir, $args, $emm, $node,  $row, $newnode);
}
	
sub process_file_image {
	my $dir = shift;
	my $pargs = shift;
	my $emm = shift;
	my $node = shift;
	my $row = shift;
	my $outnode = shift;

	my $name = decode_attr($node, 'name', $node->nodeName, $row);
	if(!($name =~ /(icon|background)/))
	{
		$outnode = add_menu_item($outnode, $dir.'\\'.$name);
	}
	my $filename = $dir.'\\'.$name.'.aai';
	my $args = getargs ($dir, $pargs, $template_dir, $node, $row);
	print $outnode->nodeName."\n";
	$filename = 'smb:'.$outdir.$filename;
	$filename =~ s/\\/\//g;
	$outnode->appendTextChild($node->nodeName.'_url', $filename);
	process_node($dir, $args, $emm, $node, $row, $outnode);
	print "writing $filename\n";
}
sub process_dir {
	my $dir = shift;
	my $node = shift;
	my $outnode = shift;

	my $newnode = $outnode;
	if($node->hasAttribute('dir')) {
		$dir .= "\\".$node->getAttribute('dir');
#		print "Directory: $dir\n";
		$newnode = add_menu_item($outnode, substr($dir,1))
	}
	return ($dir, $newnode, $outnode);
}

sub decode_attr {

	my $node = shift;
	my $attr = shift;
	my $name = shift;
	my $row = shift;
	
	if($node->hasAttribute($attr)) {
		$name = $node->getAttribute($attr);
		if(exists($row->{$name})) {
			$name = $row->{$name};
			if($node->hasAttribute('format')) {
				$name = sprintf($node->getAttribute('format'), $name);
			}
		}
	}
	return $name;
}
	
sub getargs {
	my $dir = shift;
	my $pargs = shift;
    my $template_dir = shift;
    my $node = shift;
    my $row = shift;

    my $args = get_default_args($pargs, $row);
    
	foreach my $n ($node->findnodes("@*")) {
		$args->{$n->nodeName} = $n->nodeValue;
		print "***$n\n";
	}

	if(exists($args->{'type'})) {
		my $type = $args->{'type'};
		my $target = (	$node->nodeName eq 'text' || 
						$node->nodeName eq 'menutext' || 
						$node->nodeName eq 'media' || 
						$node->nodeName eq 'area' || 
						$node->nodeName eq 'item' || 
						$node->nodeName eq 'index' || 
						$node->nodeName eq 'backgroundimage'
						) ? 'text' : 'filename';
						
		if(($node->nodeName eq 'text') && exists($args->{'table'})) {
			$args->{'text'} = gettextfromtable($emm, $node, $type);
		} 	
		elsif($type eq 'static') {
			$args->{$target} = $node->textContent;
		} 	
		elsif($type eq 'output') {
			$args->{$target} = $dir.'\\'.$node->textContent;
		} 	
		elsif(exists($args->{'media'})) {
			$args->{'filename'} = $row->{$args->{'media'}};
		} 	
		elsif(exists($row->{$type})) {
			$args->{$target} = (exists($args->{'format'}) && ($row->{$type} ne '')) ? sprintf($args->{'format'}, $row->{$type}) : $row->{$type};
		}	
		else {
			$args->{$target} = '';
		}
		
		decode_arg($args, $row, 'caption');
		decode_arg($args, $row, 'icon');

		
		if($node->nodeName eq 'menutext') {
			$args->{'othertext'} = get_other_text($node);
		}
		
		if(($target eq 'filename') && ($args->{$target} ne '')) {
			if(!($args->{$target} =~ /\.(png|jpg|aai|tbn)$/)) {
				$args->{$target}.= '.png';
			}
			if(!($args->{$target} =~ /\\/)) {
				my $sd = $type eq 'static' ? '' : $type.'\\';
				$args->{$target} = $template_dir.$sd.$args->{$target};
			}				
		}
	}
	return $args;
}

sub get_default_args {
	my $pargs = shift;
	my $row = shift;

	my $args = {};

	if(exists($pargs->{'height'})) {
#		print "height: ".$args->{'height'}."\n";
		$args->{'height'} = $pargs->{'height'};
	}
	if(exists($pargs->{'width'})) {
		$args->{'width'} = $pargs->{'width'};
	}

	if(exists($pargs->{'direction'})) {
		if($pargs->{'direction'} eq 'horizontal') {
			$args->{'x'} = $pargs->{'x'} + $row->{'rownum'} * $pargs->{'spacing'};
		}
		else
		{
			$args->{'y'} = $pargs->{'y'} + $row->{'rownum'} * $pargs->{'spacing'};
		}
	}
	
	return $args;
}

sub get_other_text {
	my $node = shift;
	my $text = "";

	my @nodes = $node->parentNode->parentNode->parentNode->getChildrenByTagName('*');
	foreach my $n (@nodes) {
		my @nodes2 = $n->getChildrenByTagName('*');
		foreach my $n2 (@nodes2) {
			my @nodes3 = $n2->getChildrenByTagName('menutext');
			foreach my $n3 (@nodes3) {
				$text .= $n3->textContent.",";
			}
		}
	}
	chomp $text;
	return $text;
}

sub decode_arg {
	my $args = shift;
	my $row = shift;
	my $type = shift;

	if(exists($args->{$type}) && exists($row->{$args->{$type}})) {
		my $newtype = $args->{$type};
		$args->{$type} = (exists($args->{'format'}) && ($row->{$newtype} ne '')) ? sprintf($args->{'format'}, $row->{$newtype}) : $row->{$newtype};
		$args->{$type} .= '.aai' if $type =~ /icon/;
	}	
}

sub gettextfromtable {
	my $emm = shift;
	my $node = shift;
	my $type = shift;

	my $table = $node->getAttribute('table');
	my $rows = $node->hasAttribute('lines') ? $node->getAttribute('lines') : 99999;
	my $sep = $node->hasAttribute('separator') ? $node->getAttribute('separator') : "\n";

	$emm->clearquery($table);
	my $text ='';
	while ((my $row = $emm->next($table, '')) && $rows--) {
		$text .= $row->{$type}.$sep;
	}
	chop $text;
#	print $text."\n";
	return $text;
}
