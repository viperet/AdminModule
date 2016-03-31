<?php

class ImageResizer {

static function checkSize($src, $w, $h) {
	$size = getimagesize($src);
	return $size[0]>=$w && $size[1]>=$h;
}

public function resizeImgAdvanced($src,$dest,$ext='jpg',$params){
   if($src=='') return;
   $phpThumb = new phpThumb();

   $phpThumb->setSourceData($src);

   $phpThumb->setParameter('config_cache_directory', NULL);

   // set parameters (see "URL Parameters" in phpthumb.readme.txt)
   foreach($params as $key => $value) 	$phpThumb->setParameter($key, $value);
   $phpThumb->setParameter('config_output_format', $ext);

   // generate & output thumbnail
   $output_filename = $dest;
   if ($phpThumb->GenerateThumbnail()) { // this line is VERY important, do not remove it!
    if ($phpThumb->RenderToFile($output_filename)) {
   // do something on success
   $err = "ok";

} else {
   // do something with debug/error messages
   $err = 'Failed:<pre>'.implode("\n\n", $phpThumb->debugmessages).'</pre>'."\n";
}
   }  else {
       // do something with debug/error messages
       $err = 'Failed:<pre>'.$phpThumb->fatalerror."\n\n".implode("\n\n", $phpThumb->debugmessages)."\n";
   }
return $err;
}
} 

?>