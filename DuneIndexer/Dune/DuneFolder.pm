package Dune::DuneFolder;

use strict;
use warnings;
use Text::Unidecode;

use Encode qw( encode );
#use Win32API::File qw(
#    CreateFileW OsFHandleOpen CloseHandle
#    FILE_GENERIC_READ FILE_GENERIC_WRITE
#    OPEN_EXISTING CREATE_ALWAYS FILE_SHARE_READ
#);
use File::Spec::Functions qw(catfile);
use Symbol;
use Win32::Unicode::Native;

=for comment
=cut

#
#
sub new {
    my $this = shift;
    my $data = shift;
    my $class = ref($this) || $this;
    my $obj = bless {
	BACKGROUNDIMAGE => {},
	ICONIMAGE => { icon => 'icon.aai', align => 'center', scalefactor => 1, selscalefactor => 1},
	AREA => {},
	ITEM => [],
	MEDIA => "",
	@_
    }, $class;
    return $obj;
    
    Win32::API->Import(Kernel32 => qq{BOOL CreateDirectoryW(LPWSTR lpPathNameW, VOID *p)}
);
    
}

sub area {
    my $self = shift;
 	my ($args) = @_;

	$self->{AREA}->{'x'} = exists($args->{'x'}) ? $args->{'x'} : 0; 
	$self->{AREA}->{'y'} = exists($args->{'y'}) ? $args->{'y'} : 0; 
	$self->{AREA}->{'width'} = exists($args->{'width'}) ? $args->{'width'} : 1920; 
	$self->{AREA}->{'height'} = exists($args->{'height'}) ? $args->{'height'} : 1080; 
	$self->{AREA}->{'rows'} = exists($args->{'rows'}) ? $args->{'rows'} : 1; 
	$self->{AREA}->{'cols'} = exists($args->{'cols'}) ? $args->{'cols'} : 1; 
	$self->{AREA}->{'iconselectionbox'} = exists($args->{'iconselectionbox'}) ? $args->{'iconselectionbox'} : 'yes'; 
	if(exists($args->{'selboxwidth'})) {
		$self->{AREA}->{'selboxwidth'} = $args->{'selboxwidth'};
	}
	if(exists($args->{'selboxheight'})) {
		$self->{AREA}->{'selboxheight'} = $args->{'selboxheight'};
	}
	$self->{AREA}->{'captions'} = exists($args->{'captions'}) ? $args->{'captions'} : 'no'; 
}

sub backgroundimage {
    my $self = shift;
 	my ($args) = @_;

#	print "Background: ".$args->{'text'}."\n";
	$self->{BACKGROUNDIMAGE}->{'image'} = $args->{'text'};
	$self->{BACKGROUNDIMAGE}->{'painticons'} = exists($args->{'painticons'}) ? $args->{'painticons'} : 'yes'; 
	$self->{BACKGROUNDIMAGE}->{'iconview'} = exists($args->{'iconview'}) ? $args->{'iconview'} : 'yes'; 
	$self->{BACKGROUNDIMAGE}->{'systemfiles'} = exists($args->{'systemfiles'}) ? $args->{'systemfiles'} : '*.aai,*.jpg'; 
}

sub iconimage {
    my $self = shift;
 	my ($args) = @_;

	$self->{ICONIMAGE}->{'icon'} = exists($args->{'text'}) && ($args->{'text'} ne '') ? $args->{'text'} : 'icon.aai'; 
	$self->{ICONIMAGE}->{'iconsel'} = exists($args->{'iconsel'}) && ($args->{'iconsel'} ne '') ? $args->{'iconsel'} : $self->{ICONIMAGE}->{'icon'}; 
	$self->{ICONIMAGE}->{'align'} = exists($args->{'align'}) ? $args->{'text'} : 'center'; 
	$self->{ICONIMAGE}->{'scalefactor'} = exists($args->{'scalefactor'}) ? $args->{'scalefactor'} : 1; 
	$self->{ICONIMAGE}->{'selscalefactor'} = exists($args->{'selscalefactor'}) ? $args->{'selscalefactor'} : 1; 

}

sub item {
    my $self = shift;
 	my ($args) = @_;

	$args->{'text'} = '' if !exists($args->{'text'});

#	print "Item: ".$args->{'text'}."\n";

	my $item = {};
	if(exists($args->{'caption'})) {
		$item->{'caption'} = $args->{'caption'};
	} elsif($args->{'text'} ne '' ) {
		$item->{'caption'} = exists($args->{'captionprefix'}) ? $args->{'captionprefix'}.$args->{'text'} : $args->{'text'};
	}
	my $icon = 'icon.aai';
	if(exists($args->{'icon'})) {
		$icon = $args->{'icon'};
		$item->{'icon_path'} = (exists($args->{'prefix'}) ? $args->{'prefix'} : '').$icon;
	}
	else {
		$item->{'icon_path'} = (exists($args->{'prefix'}) ? $args->{'prefix'}.$args->{'text'} : $args->{'text'}).'/'.$icon;
	}
	if(exists($args->{'iconsel'})) {
		$icon = $args->{'iconsel'};
		$item->{'icon_sel_path'} = (exists($args->{'prefix'}) ? $args->{'prefix'} : '').$icon;
	}
	else {
		$item->{'icon_sel_path'} = $item->{'icon_path'};
	}
	$item->{'icon_valign'} = exists($args->{'align'}) ? $args->{'text'} : 'center'; 
	$item->{'icon_scale_factor'} = exists($args->{'scalefactor'}) ? $args->{'scalefactor'} : 1;
	$item->{'icon_sel_scale_factor'} = exists($args->{'selscalefactor'}) ? $args->{'selscalefactor'} : 1;
	if($args->{'url'}) {
		$item->{'media_url'} = $args->{'url'}.$args->{'text'};
	}
	else {
		$item->{'media_url'} = exists($args->{'prefix'}) ? $args->{'prefix'}.$args->{'text'} : $args->{'text'};
	}

	if(!exists($args->{'action'}) || ($args->{'action'} eq 'play')) {
		$item->{'caption'} = 'Play';
		my $media = $args->{'filename'};
		$media =~ s/\\/\//g;
		$item->{'media_url'} = 'smb:'.$media;
	} 
	elsif($args->{'action'} eq 'browse') {
		$item->{'media_action'} = 'browse';
	}
	
	if(exists($item->{'caption'})) {
		$item->{'caption'} = unidecode($item->{'caption'});
	}
	
	push $self->{ITEM}, $item; 
}

sub media {
    my $self = shift;
 	my ($args) = @_;

#	print "Media: ".$args->{'text'}."\n";
	$self->{MEDIA} = $args->{'text'};
}


sub write {
	my $self = shift; 
	my $filename = shift;
	
#    my $os_fh = CreateFileW(encode('UCS-2le', "$filename\0"), FILE_GENERIC_WRITE, FILE_SHARE_READ, [], CREATE_ALWAYS, 0, [], ) or die "cannot open > $filename: $! $^E";
#	my $fh = gensym();
#	OsFHandleOpen($fh, $os_fh, 'w') or die "cannot open > $filename: $!";

#	open(my $fh, ">", encode('UCS-2le', $filename)) or die "cannot open > $filename: $!";
	open(my $fh, ">", $filename) or die "cannot open > $filename: $!";
        
  	binmode($fh, ":utf8");
        
	if(exists($self->{BACKGROUNDIMAGE}->{'image'})) {
		print $fh "background_order=before_all\n";
		print $fh "background_path=".$self->{BACKGROUNDIMAGE}->{'image'}."\n";
		print $fh "use_icon_view=".$self->{BACKGROUNDIMAGE}->{'iconview'}."\n";
	}

	if(exists($self->{ICONIMAGE}->{'iconsel'})) {
		print $fh "icon_sel_path=".$self->{ICONIMAGE}->{'iconsel'}."\n";
	}

	print $fh "icon_path=".$self->{ICONIMAGE}->{'icon'}."\n";
	print $fh "icon_valign=".$self->{ICONIMAGE}->{'align'}."\n";
	print $fh "icon_scale_factor=".$self->{ICONIMAGE}->{'scalefactor'}."\n";
	print $fh "icon_sel_scale_factor=".$self->{ICONIMAGE}->{'selscalefactor'}."\n";
	
	if($self->{MEDIA} ne '') {
		my $media = $self->{MEDIA};
		$media =~ s/\\/\//g;
		
		$media = 'smb:'.$media;
		print $fh "media_url=$media\n";
	}
	
	if(exists($self->{AREA}->{'x'})) {
		print $fh "num_cols=".$self->{AREA}->{'cols'}."\n";
		print $fh "num_rows=".$self->{AREA}->{'rows'}."\n";
		print $fh "content_box_x=".$self->{AREA}->{'x'}."\n";
		print $fh "content_box_y=".$self->{AREA}->{'y'}."\n";
		print $fh "content_box_width=".$self->{AREA}->{'width'}."\n";
		print $fh "content_box_height=".$self->{AREA}->{'height'}."\n";
		print $fh "content_box_padding_left=0\n";
		print $fh "content_box_padding_right=0\n";
		print $fh "content_box_padding_top=0\n";
		print $fh "content_box_padding_bottom=0\n";
		if(exists($self->{AREA}->{'selboxwidth'})) {
			print $fh "icon_selection_box_width=".$self->{AREA}->{'selboxwidth'}."\n";
		}
		if(exists($self->{AREA}->{'selboxheight'})) {
			print $fh "icon_selection_box_height=".$self->{AREA}->{'selboxheight'}."\n";
		}
	}
	
	if(exists($self->{BACKGROUNDIMAGE}->{'image'})) {
		print $fh "paint_icons=".$self->{BACKGROUNDIMAGE}->{'painticons'}."\n";
		print $fh "paint_captions=".$self->{AREA}->{'captions'}."\n";
		print $fh "paint_help_line=no\n";
		print $fh "paint_path_box=no\n";
		print $fh "paint_content_box_background=no\n";
		print $fh "paint_scrollbar=no\n";
		print $fh "paint_icon_selection_box=".$self->{AREA}->{'iconselectionbox'}."\n";
		print $fh "system_files=".$self->{BACKGROUNDIMAGE}->{'systemfiles'}."\n";
	}
	
	my $i=0;
	
	if(@{$self->{ITEM}} > 0) {
		print $fh "sort_field=unsorted\n";
	}
	
	foreach my $item (@{$self->{ITEM}}) {
		foreach my $j (keys(%{$item})) {
			if($self->{BACKGROUNDIMAGE}->{'painticons'} eq 'yes' || !($j =~ /icon/)) {
				print $fh 'item.'.$i.'.'.$j.'='.$item->{$j}."\n";
			}
		}
		$i++;
	}

#	$fh->autoflush;
#	CloseHandle($os_fh);
	close $fh;
}


return 1;