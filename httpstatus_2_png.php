#!/usr/bin/php
<?php
/**
 *	Create a graph based on the first 8 bits of an IPv4 address
 *
 *	Inspiration from http://www.seehuhn.de/pages/internet
 *	As seen in HDMoore http://www.youtube.com/watch?v=b-uPh99whw4
 *
 *	@param 	filename 	the log file to parse
 *	@param	type			what type of graph to create, based on http response code (20, 40, 50)
 *
 *	@version 20121009
 * 	@author Koen Van Impe <koen.vanimpe@cudeso.be>
 *	@license New BSD : http://www.vanimpe.eu/license
 *
 */

error_reporting(E_ERROR | E_WARNING | E_PARSE);

// Check for the correct numbers of paramters; display usage
if ($argc != 3) {
	?>
Create a graph based on the first 8 bits of an IPv4 address
  
Usage: 
 php <?php echo $argv[0]; ?> <logfile> <http_response_code> > <image>
  logfile	:	full path of the logfile to parse
  http_response_code : what http response code to plot (20x, 40x or 50x)
  image : image to export to, fe. image.png

Example:
 php <?php echo $argv[0]; ?> access.log 40x > image.png
	<?php
	die();
}

// Variable init
$ip_array = array();
$logfile = $argv[1];
$color_param = (int) $argv[2]; // strip the x from 50x
$ip = 0;

// Define graph settings
$basegraph_x = 800;
$basegraph_y = 800;

$im = ImageCreate($basegraph_x,$basegraph_y);
$background = ImageColorAllocate($im,0xa5,0x9a,0x7e);
$black = ImageColorAllocate($im,0x00,0x00,0x00);
$red = ImageColorAllocate($im,0x99,0x0f,0x06);
$green = ImageColorAllocate($im,0x63,0x99,0x3e);
$blue = ImageColorAllocate($im,0x28,0x4f,0x99);
ImageFilledRectangle($im,0,0,$basegraph_x,$basegraph_y,$background);

if ($color_param == "50") $color = $blue;
elseif ($color_param == "40") $color = $red;
else $color = $green;

// Execute the command, save the output, then walk through the output
$color_param_esc = "^".$color_param;
exec("cat " . escapeshellarg($logfile) . " | awk '{ print $9 \" \" $1; }' |grep " . escapeshellarg($color_param_esc), $output);
if (is_array($output) and count($output) > 0) {
	foreach($output as $line) {
		if (strlen($line) > 0) {
			$arr_line = explode(" ", $line);
			
			// Build up the array width future "width" of the circles
			$ipstr = substr($arr_line[1], 0, strpos($arr_line[1], ".") );
			if ($ip_array[$ipstr]["qt"] == 0) $ip_array[$ipstr]["qt"] = 3;		// Minimal width
			else if ($ip_array[$ipstr]["qt"] > 85) {
				for ($overflow=1;$overflow<=255;$overflow++) {
					$ip_array[$ipstr]["qt"] = round($ip_array[$ipstr]["qt"] / 2);
				}
			}		
			else $ip_array[$ipstr]["qt"] = $ip_array[$ipstr]["qt"] + 1;			
		}
	}			
}

// Walk through the array, set the coordinates and labels
for($y=0;$y<=15;$y++) {
	for($x=0;$x<=15;$x++) {
		$ipstr = (string) $ip;		

		if (strlen($ipstr) == 1)	$x_offset = 3;
		elseif (strlen($ipstr) == 2)	$x_offset = 8;
		else $x_offset = 11;

		$ip_array[$ipstr]["x_offset"] = $x_offset;
		$ip_array[$ipstr]["x"] = 15 + ($x * 50);
		$ip_array[$ipstr]["y"] = 15 + ($y * 50);			

		if (isset($ip_array[$ipstr]["qt"])) $width = (int) $ip_array[$ipstr]["qt"];
		else $width = 0;
		
		ImageArc($im, $ip_array[$ipstr]["x"] + $x_offset , $ip_array[$ipstr]["y"] + 7, $width, $width, 0, 360, $color);
		ImageFill($im, $ip_array[$ipstr]["x"] + $x_offset, $ip_array[$ipstr]["y"] + 7, $color);

		$ip++;	
	}	
}

// Put the labels on the graph
for ($ip = 1; $ip <= 255 ; $ip++) {
	$ipstr = (string) $ip;
	// Print "ip"-label after the Arc, otherwise the label gets ImageFilled
	ImageString($im, 4, (int) $ip_array[$ipstr]["x"], (int) $ip_array[$ipstr]["y"], $ipstr, $black);
}	
$title = date("Ymd H:i") . " / " . $argv[2];
ImageString($im, 2, 3, 0,  $title, $black);

// Export the image
Header('Content-Type: image/png');
ImagePNG($im);

?>