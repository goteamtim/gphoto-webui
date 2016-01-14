<?php
require_once("CameraRaw.php");



//time gphoto2 --quiet --capture-image-and-download --filename "./images/capture-%Y%m%d-%H%M%S-%03n.%C"
//exec ("gphoto2 --set-config uilock=1",$output);
//echo join("\n",$output);
//exec ("gphoto2  --capture-image",$output);
//echo join("\n",$output);
//exec ("gphoto2 --set-config uilock=1",$output);
//echo join("\n",$output);

$initialTimeout = 6; 
$interval = 4; 
$numOfShots = 4; 
$showBatteryStatus = true; 


//Variables above here
$action = '';

if (isset($_GET['action'])){
	$action = $_GET['action'];
}


$returnObj;

try{
	switch($action){

		case "saveSettings":
			//Gather all the information from the GET method and save the settings for the user in localstorage
			//Still need to update the information here
			//This actually stores the updated data
			fwrite($fp,serialize($store));
			//Close the file when finished
			break;

		case "checkBattery":
			exec ("gphoto2 --get-config batterylevel | grep Current | cut -c 9-13",$output);
			echo json_encode($output[0]);
			break;

		case "takePicture":
			exec ("gphoto2 --capture-image-and-download --filename \"./images/capture-%Y%m%d-%H%M%S-%03n.%C\" --interval=" + $interval,$output);
			echo json_encode(true);					
			break;
	
		case "deleteFile":
			$file = $_GET['file'];
			$path_parts = pathinfo('images/'.$file);
			unlink('images/'.$file);				
			unlink('images/thumbs/'.$path_parts['basename'].'.jpg');				
			header('Content-Type: application/json');
			echo json_encode(true);					
			break;
			
		case "getImage":	
			$file = $_GET['file'];
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="'.$file.'"');
			header('Content-Length: '.filesize('images/'.$file));
			$fp = fopen('images/'.$file, 'rb');
			fpassthru($fp);
			exit;
			break;
		
		
		case "getCamera":

			exec ("gphoto2 --auto-detect", $output);
			$returnObj->camera = trim(explode("usb", $output[count($output) - 1])[0]);
			header('Content-Type: application/json');
			echo json_encode($returnObj);
	
			break;
		
		case "getImages":
	
			$files = array();
			$imageDir = opendir('images');
			while (($file = readdir($imageDir)) !== false) {			
				if(!is_dir('images/'.$file)){
					$path_parts = pathinfo('images/'.$file);				
					if (!file_exists('images/thumbs/'.$path_parts['basename'].'.jpg')){
						try { //try to extract the preview image from the RAW
							CameraRaw::extractPreview('images/'.$file, 'images/thumbs/'.$path_parts['basename'].'.jpg');
						} catch (Exception $e) { //else resize the image...
							$im = new Imagick('images/'.$file);
							$im->setImageFormat('jpg');
							$im->scaleImage(1024,0);					
							$im->writeImage('images/thumbs/'.$path_parts['basename'].'jpg');
							$im->clear();
							$im->destroy();
						}
					}				
					$returnFile;
					$returnFile->name = $path_parts['basename'];
					$returnFile->sourcePath = 'images/'.$file;
					$returnFile->thumbPath = 'images/thumbs/'.$path_parts['basename'].'.jpg';
				
					array_push($files,$returnFile);
				
					unset($returnFile);
				}
			}
			closedir($imageDir);
			$returnObj = $files;
			header('Content-Type: application/json');
			echo json_encode($returnObj);
			break;
		default:
			break;
	}
} catch (Exception $e) { //else resize the image...
	
}

?>