package Dune::DuneImage;
use strict;
use warnings;
use feature 'state';

use Image::Magick;
use Win32::Unicode::Native;

sub new {
    my $this = shift;
    my $template_dir = shift;
    my $width = shift;
    my $height = shift;
    my $class = ref($this) || $this;
    my $obj = bless {
	TEMPLATE_DIR => $template_dir,
	IMAGE => Image::Magick->new,
	@_
    }, $class;
    my $rc = $obj->{IMAGE}->Set(size=>$width.'x'.$height, alpha=>'Transparent');
	warn "$rc" if "$rc";	
	$rc = $obj->{IMAGE}->ReadImage('xc:none');
	warn "$rc" if "$rc";	
    return $obj;
}

sub image {
    my $self = shift;
 	my ($args) = @_;

    my $im = read_imagefile($args);
    return 0 if(!$im);
    
    my $x = exists($args->{'x'}) ? $args->{'x'} : 0;
    my $y = exists($args->{'y'}) ? $args->{'y'} : 0;
    my $w = $args->{'width'};
    my $h = $args->{'height'};
    
    my $rc = $im->Scale(width=>$w, height=>$h);
	warn "$rc" if "$rc";	
	
	$rc = $self->{IMAGE}->Composite(image=>$im, x=>$x, y=>$y, compose=>'Over');
	warn "$rc" if "$rc";	
}

sub mask {
    my $self = shift;
 	my ($args) = @_;

    my $im = read_imagefile($args);
	return 0 if(!$im);

	$self->scale_image($im);	
		
	my $rc = $self->{IMAGE}->Composite(image=>$im, compose=>'Dstin');
	warn "$rc" if "$rc";
}

sub read_imagefile {
 	my ($args) = @_;

	return 0 if !exists($args->{'filename'});
    my $filename = $args->{'filename'};
	return 0 if $filename eq '';

	state $image_cache = {};

	my $im = 0;	
	if($filename =~ /template/i) {
		if(exists($image_cache->{$filename})) {
			$im = $image_cache->{$filename};
		}
		else {
		    $im = Image::Magick->new;
		    my $rc = $im->Read($filename);
			if("$rc") {
				warn "$filename: $rc";
				$im=0;
			}
			$image_cache->{$filename} = $im;
		}
	} else {
	    $im = Image::Magick->new;
	    my $rc = $im->Read($filename);
		warn "$filename: $rc" if "$rc";
	}
	return $im;
}

sub scale_image {
	my $self = shift;
	my $im = shift;

	my $size = $self->{IMAGE}->Get('size');

	my $rc = $im->Scale(geometry=>$size);
	warn "$rc" if "$rc";
}

sub frame {
    my $self = shift;
 	my ($args) = @_;

    my $im = read_imagefile($args);
    return 0 if(!$im);
    
	$self->scale_image($im);	
		
	my $rc = $self->{IMAGE}->Composite(image=>$im, compose=>'Dissolve');
	warn "$rc" if "$rc";
}

sub menutext {
    my $self = shift;
 	my ($args) = @_;
 	
 	return 0 if $args->{'text'} eq '';

	$args->{'x'} = exists($args->{'x'}) ? $args->{'x'} : 0;
	$args->{'y'} = exists($args->{'y'}) ? $args->{'y'} : 0;

	$self->set($args);

	my $text = $self->get_background_menu_text($args);
   	$self->write_text($text, $args->{'othercolor'}, $args);

   	$self->write_text($args->{'text'}, $args->{'color'}, $args);
}



sub get_background_menu_text {
    my $self = shift;
 	my ($args) = @_;

	my $text = $args->{'text'};
	my $pretext = "";
	my $posttext = "";
	my @menu = split(/,/, $args->{'othertext'});
	my $me = shift @menu;
	my $spacing = ' ' x (exists($args->{'spacing'}) ? $args->{'spacing'} : 60);

	while($me ne $text)
	{
		$pretext .= $me.$spacing;
		$me = shift @menu;
	}	
	while(@menu > 0)
	{
		$me = shift @menu;
		$posttext .= $spacing.$me;
	}

	my $textl = ($self->{IMAGE}->QueryFontMetrics(	text => $text ))[4];
	my $prel = ($self->{IMAGE}->QueryFontMetrics(	text => $pretext ))[4];
	my $postl = ($self->{IMAGE}->QueryFontMetrics(	text => $posttext ))[4];
	my $spacel = ($self->{IMAGE}->QueryFontMetrics(	text => "          "))[4]/10;

	if($prel < $postl) {
		my $i=($postl-$prel)/$spacel;
		$pretext = (' ' x $i).$pretext;
	}
	elsif($prel > $postl) {
		my $i=($prel-$postl)/$spacel;
			$posttext .= ' ' x $i;
	}
	
	my $i = $textl/$spacel;
	$text = ' ' x $i;
	
	return $pretext.$text.$posttext;
}

sub set {
    my $self = shift;
 	my ($args) = @_;

	my $font = $args->{'font'};
	$font =~ s/ /_/g;

	$args->{'align'} = exists($args->{'align'}) ? $args->{'align'} : 'left';
	$args->{'style'} = 'normal' if !exists($args->{'style'});

	$self->{IMAGE}->Set(font => $self->{TEMPLATE_DIR}.$font.'.ttf',
		pointsize => $args->{'size'},
		density => 95, 
		style => $args->{'style'},
		geometry => $args->{'width'}.'x'.$args->{'height'},
		align => $args->{'align'}
	);

}

sub text {
    my $self = shift;
 	my ($args) = @_;
 	
# 	print "Text: ".$args->{'text'}."\n";
 	
 	return 0 if $args->{'text'} eq '';
 	
 	$self->set($args);

	$args->{'align'} = exists($args->{'align'}) ? $args->{'align'} : 'left';
	$args->{'style'} = 'normal' if !exists($args->{'style'});
	
	
	$args->{'x'} = exists($args->{'x'}) ? $args->{'x'} : 0;
	$args->{'y'} = exists($args->{'y'}) ? $args->{'y'} : 0;
	
    $args->{text} = $self->wraptext($args);
    
    if(exists($args->{'valign'}) && exists($args->{'height'}) && $args->{'valign'} eq 'center') {
		my $h  = ($self->{IMAGE}->QueryFontMetrics(text => $args->{'text'}))[5];
		my ($x_ppem, $y_ppem, $ascender, $descender, $width, $height, $max_advance) = $self->{IMAGE}->QueryFontMetrics(text => $args->{'text'});
		$args->{'y'} += ($args->{'height'} - $h*1.2)/2;
    }
	
	
   	$self->write_text($args->{'text'}, $args->{'color'}, $args);
}

sub write_text() {
    my $self = shift;
	my $text = shift;
	my $color = shift;
 	my ($args) = @_;

   	my $rc = $self->{IMAGE}->Annotate(
		text => $text, 
		fill => $color,
		x => $args->{'x'} + ($args->{'align'} eq 'right' ? $args->{'width'} : ($args->{'align'} eq 'center' ? $args->{'width'}/2 : 0)),
		y => $args->{'y'} + $args->{'size'} *95/72, 
		skewX => ($args->{'style'} eq 'italic' ? -20.0 : 0),
		align => $args->{'align'}
		);
	warn "$rc" if "$rc";	
}

sub write {
	my $self = shift; 
	my $filename = shift;
	my $rc = $self->{IMAGE}->write($filename);
	warn "$rc" if "$rc";	
}

#
sub wraptext
{
	my $self = shift;
	my ($args) = @_;
	my $img = $self->{IMAGE};
	my $maxwidth = $args->{'width'};
	my $text = $args->{'text'};
	my $lines = exists($args->{lines}) ? $args->{lines}: 1; 

   # figure out the width of every character in the string
   #
#	print "$text\n";
   
   my %widths = map(($_ => ($img->QueryFontMetrics(	text => $_ ))[4]),
      keys %{{map(($_ => 1), split //, $text)}});

   my @newtext = "";
   my $pos = 0;
   for (split //, $text) {
      # check to see if we're about to go out of bounds
      if ($widths{$_}*1.2 + $pos > $maxwidth) {
         $pos = 0;
         my @word;
         # if we aren't already at the end of the word,
         # loop until we hit the beginning
         if ( $newtext[-1] ne " "
              && $newtext[-1] ne "-"
              && $newtext[-1] ne "\n") {
            unshift @word, pop @newtext
               while ( @newtext && $newtext[-1] ne " "
                       && $newtext[-1] ne "-"
                       && $newtext[-1] ne "\n")
         }

         # if we hit the beginning of a line,
         # we need to split a word in the middle
         if ($newtext[-1] eq "\n" || @newtext == 0) {
            push @newtext, @word, "\n";
            $lines--;
         } else {
            push @newtext, "\n";
            $lines--;
            push @newtext, @word if $lines > 0;
            $pos += $widths{$_} for (@word);
         }
      }
      if($lines > 0){
	      push @newtext, $_;
	      $pos += $widths{$_}*1.2;
	      if($newtext[-1] eq "\n") {
	      	$pos = 0;
	      	$lines--;
	      }
      }
   }
   if($lines == 0) {
   		pop @newtext;
#   		pop @newtext;
   		push @newtext, "...";
   }
   return join "", @newtext;
}

return 1;