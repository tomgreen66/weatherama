<?php

################################## IMAGEMAGIK MAGIK UPLOAD CLASS ###################################
/*

EXAMPLE USAGE

include('sanitize.class.php'); // needed for cleaning image name
include('upload.class.php');

if($_POST['submit'] == 'Upload') {
	$upload = new fileUpload($_FILES['image']);
	
	$upload->setDir('/var/www/mysite/files/test/');
	
	$arrayInfo = array('100' => 'constW',
					   '200' => 'constH',
					   '150x200' => 'crop',
					   '300x300' => 'fitDimensionsPad',
					   'original' => 'none');
	
	$upload->setOptions($arrayInfo);
	
	//$upload->setBGcolour("#000000"); // optional only needed if method is 'fitDimensionsPad'
	//$upload->setQuality(90); // optional - has default
	//$upload->setOutputType('jpg'); // optional - has default
	$result = $upload->upload();
	
	if($result) {
		echo $result;
	}else{
		
		echo 'error';
	
	}
}


*/
#####################################################################################################


class Upload {
	
	
	/**
	  * original file from upload form ($_FILES['var_name'])
	**/
	private $originalFile = array();
	
	/**
	  * the generated filename that is output
	**/
	private $fileName;
	
	/**
	  * the server root to the base directory - sub folders are automaticly created inside of here
	**/
	private $basePath;

	/**
	  * for error checking - store any errors that happen
	**/
	private $errors = false;
	
	/**
	  * for restricting to a certain type
	**/
	private $restrictions = false;
	
	/**
	  * array of options to resize / create images as
	  *
	  * see example usage
	**/ 
	private $options = array();
	
	                              	 
    /**
      * list of types to be processed 
    **/
    
    private $typeProcess = array(	'image' => array('image/bmp',
    												 'image/gif',
    												 'image/jpeg',
    												 'image/pjpeg',
    												 'image/png',
    												 'image/x-png',
    												 'image/tiff',
    												 'image/x-tiff',
    												 'image/x-windows-bmp',
    												 'jpg','bmp','tif','gif','jpeg','png'
    												 
    												 ),
    								
    								'video' => array('video/avi',
    												 'video/3gpp',
    												 'video/dv',
    												 'video/mpeg',
    												 'video/mp4',
    												 'video/ogg',
    												 'video/quicktime',
    												 'video/mp4v-es',
    												 'video/vnd.vivo',
    												 'video/x-mng',
    												 'video/x-ms-asf',
    												 'video/x-ms-wmv',
    												 'video/x-msvideo',
    												 'video/x-sgi-movie'),
    												 
    								'sound' => array('audio/mpeg'), //  audio/mpeg is for mpga mpega mp2 mp3 m4a files
    								
    								'pdf'   => array('application/pdf'),
    								
    								'misc'  => array('text/plain', 
    												 'text/richtext', 
    												 'application/rtf', 
    												 'application/msword', 
    												 'application/vnd.ms-powerpoint',
    												 'application/vnd.ms-excel', 
    											 	 'application/x-zip', 
    											 	 'application/zip', 
    												 'application/vnd.openxmlformats-officedocument.wordprocessingml.document docx', 
    												 'application/vnd.openxmlformats-officedocument.presentationml.presentation pptx', 
    												 'application/vnd.ms-powerpoint.presentation.macroEnabled.12 pptm', 
    												 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet xlsx',
    												 
    												 // file extensions
    												 'xls',
    												 'xlt',
    												 'xlsx',
    												 'ppt',
    												 'pps',
    												 'docx',
    												 'doc',
    												 'mov',
    												 'xml')
								); 
	
	/**
	  * output type of final images
	**/
	private $ext;
	
	/**
	  * output quality of final images - only jpg / png
	**/
	private $quality = 90;
	
	/**
	  * the background colour for images that get padded
	**/
	private $bgColour = '#FFFFFF';
	
	/**
	  * stores the action for this file
	**/
	private $action;
	
	/**
	  * _constructor
	  *
	  * @file: array; $_FILES['image'] array
	**/
	public function __construct($file) {
		$this->setFile($file);
		
	}
	
	
	/**
	  * process the upload based on options that are set
	  *
	  * @returns: string; the new image / file name
	**/
	public function upload() {

		$this->action = $this->fileInfo();
		
		//check restrictions
		if($this->restrictions === false || (is_array($this->restrictions) && in_array($this->action, $this->restrictions)) ){
		
			if($this->action) {
				$this->{$this->action}(); 
			}else{
				$this->checkDir($this->basePath);
				@move_uploaded_file($this->originalFile['tmp_name'], $this->basePath . $this->fileName);
				
			}
		}else{
		
			$this->errors .= 'The type of file ('.$this->action.') you tried to upload is restricted. '."\r\n";
		}
		/*
		if(strlen($this->errors) < 1){
			return $this->fileName;
		}else{
			return nl2br($this->errors);
		}
		*/
		
	}
	
	
	
	
	
	/****************************** GETTER AND SETTERS *************************
	 **** SELF EXPLANATORY
	 */
	
	public function setFile($file) {
		$this->originalFile = $file;
	}
	
	
	public function setDir($path) {
		$this->basePath = $path;
	}
	
	
	public function setOptions($array) {
		$this->options = $array;
	}
	
	public function setRestrictions($array) {
		$this->restrictions = $array;
	}
	
	public function setBGcolour($hex) {
		$this->bgColour = $hex;
	}
	
	public function setOutputType($str) {
		$this->ext = $str;
	}
	
	public function setQuality($int) {
		$this->quality = $int;
	}
	
	public function getFileType() {
		return $this->action;
	}
	
	public function getFileName() {
		return $this->fileName;
	}
	
	public function getErrors() {
		return nl2br($this->errors);
	}
	
	
	
	/****************************** PRIVATE FUNCTIONS ***************************/
	
	/**
	  * get the mime type / file extension to work out what action we are performing on this file
	  *
	  * @returns: string; pdf | img; member function to use
	**/
	private function fileInfo() {
		// is allowed file?
		// let php try and get it first
		$mimeType = false; //mime_content_type($this->originalFile['tmp_name']);
		if(!$mimeType || $mimeType == "application/octet-stream") {
			// if useless then try whatever the browser sent us
			$mimeType = $this->originalFile['type'];
		}
		if(!$mimeType || $mimeType == "application/octet-stream") {
			// if no luck then try the file extension
			$mimeType = $this->getExt($this->originalFile['name']);
		}
		
		// no allowed mime type
		if(!$mimeType || $mimeType == "application/octet-stream") {
			$mimeType = false;
		}
		
		$return = false;
		
		foreach($this->typeProcess as $type => $mimeArray){
			if(in_array($mimeType, $mimeArray)){
				$return = $type;
				break;
			}
		}
	
		if($return)	{
			return $return;
		}else{
			$this->errors .= 'The type of file ('.$mimeType.') you tried to upload is not in our allowed types. '."\r\n";
		
		}
	}
	
	
	private function numTobaseConvert($num,$codeset){
		$base = strlen($codeset);
		$converted = '';
		$n = $num;
		while ($n > 0) {
			$converted = substr($codeset, ($n % $base), 1) . $converted;
			$n = floor($n/$base);
		}
		return $converted;
	}
	
	/**
	  * generate the safe filename
	  * ensure no duplicate files and strips unwanted chars
	  *
	  * @returns: string; new file name
	**/
	private function createFileName() {
		//$clean = new sanitize;
		//remove ext
		
		$nameNoExt = substr($this->originalFile['name'], 0,strrpos($this->originalFile['name'],'.'));   
		
		//sanitise only allow alpha numeric
		$name = preg_replace("/[^a-zA-Z0-9]/", '', $nameNoExt);
		$now = date('ymdHis');
		
		$codeset = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$nowStr = $this->numTobaseConvert($now,$codeset);
		
		$this->filenameNoExt = $nowStr . '-' . $name;
		$this->fileName = $this->filenameNoExt . '.' . $this->ext;

		if(file_exists($this->basePath . $this->fileName)) {
			sleep(1);
			$this->createFileName();
		}
		
	}
	
	
	/**
	  * get the file extension of a file
	  *
	  * @file: string; filename to process
	  * @returns: string; extension without '.'
	**/
	private function getExt($file) {
		$ext = strtolower(substr(strrchr($file,'.'),1));
		return $ext;
	}
	
	
	/**
	  * see if a directory exists and create it if not
	  * will create directories recursively
	  * NOTE chmod is needed after createing dir as mkdir does not always give correct permissions
	**/
	private function checkDir($dir) {
		if(!is_dir($dir)) {
			mkdir($dir, 0777, true);
			//chmod($dir, 0777);
		}
	}
	
	
	
	/**
	  * process as image file
	  * cycles through options array
	**/
	private function image() {
		// good to make gif stay as gifs... preserve line art gifs, no jpg compression artefacts
		switch( $this->getExt($this->originalFile['name']) ){
			case 'gif':
				$this->ext = 'gif';
				break;
			case 'png':
				$this->ext = 'png';
				break;
			case 'jpg':
			case 'jpeg':
				$this->ext = 'jpg';
				break;
			case 'tif':
				$this->ext = 'tif';
				break;
			case 'bmp':
				$this->ext = 'bmp';
				break;
			default:
				$this->ext = 'jpg';

		}
		
		$this->createFileName();
		
		foreach($this->options as $size => $method) {
			$outPath = $this->basePath . $size . '/';
			$return = $this->resize($this->originalFile['tmp_name'], $outPath, $method, $size);
		}
		
	}
	
	
	/**
	  * process as pdf file
	  * cycles through options array
	  * NOTE '[0]' is appended to the tmp_name as a string because imagemagick uses this as the ([0] = front / first) page number of the pdf to be rendered
	**/
	private function pdf() {
		$this->ext = 'pdf';
		$this->createFileName();
		foreach($this->options as $size => $method) {
			$outPath = $this->basePath . $size . '/';
			if($method != 'none') {
				$this->fileName = $this->filenameNoExt . '.jpg';
			}else{
				$this->fileName = $this->filenameNoExt . '.pdf';
			}
			$this->resize($this->originalFile['tmp_name'].'[0]', $outPath, $method, $size);
		}
	}
	
	/**
	  * process as vid file
	  * store into our video directory
	  *  
	**/
	private function video() {
		//store into our video directory
		$this->ext = $this->getExt($this->originalFile['name']);
		$this->createFileName();
		
		$this->checkDir($this->basePath);
		$this->checkDir($this->basePath. 'originals/');
		
		if(@move_uploaded_file($this->originalFile['tmp_name'], $this->basePath . 'originals/' . $this->fileName)) {
			chmod($this->basePath . 'originals/' . $this->fileName, 0777);	
		}else{
			$this->errors .= 'There was a problem moving the uploaded file '.$this->originalFile['tmp_name'].' to '. $this->basePath . 'original/'. $this->fileName."\r\n";
		}
		
	}	
	
	/**
	  * process as snd file
	  * store into our sound directory
	  * 
	**/
	private function sound() {
		$this->ext = $this->getExt($this->originalFile['name']);
		$this->createFileName();
		
		$this->checkDir($this->basePath);
		$this->checkDir($this->basePath. 'originals/');
		
		if(@move_uploaded_file($this->originalFile['tmp_name'], $this->basePath  . 'originals/'. $this->fileName)) {
			chmod($this->basePath . 'originals/' . $this->fileName, 0777);
		}else{
			$this->errors .= 'There was a problem moving the uploaded file '.$this->originalFile['tmp_name'].' to '. $this->basePath . 'original/'. $this->fileName."\r\n";
		}
	}
	
	/**
	  * process misc file types (just upload)
	**/
	private function misc() {
		$this->ext = $this->getExt($this->originalFile['name']);
		$this->createFileName();
		$this->checkDir($this->basePath);
		$this->checkDir($this->basePath. 'original/');
		
		if(@move_uploaded_file($this->originalFile['tmp_name'], $this->basePath . 'original/' . $this->fileName)) {
			chmod($this->basePath . 'original/' . $this->fileName, 0777);
		}else{
			$this->errors .= 'There was a problem moving the uploaded file '.$this->originalFile['tmp_name'].' to '. $this->basePath . 'original/'. $this->fileName."\r\n";
		}
	}
	
	
	
	/**
	  * resize the image based on options already set
	  * handles images and pdf front pages
	  *
	  * @original: string; the original file ($_FILES['image']['tmp_name'])
	  * @destination: string; the directory where the image will end up
	  * @method: string; the method to resize the image
	**/
	private function resize($original, $destination, $method, $size) {
		$this->checkDir($destination);
		
		$destinationFile = $destination . $this->fileName;
		
		switch($method) {
			
			case 'constW' :
			
				$command = 'convert -colorspace RGB -density 72 -quality ' . $this->quality . ' -resize ' . $size . ' -opaque transparent -background "' . $this->bgColour . '" '. $original . ' ' . $destinationFile;
				
			break;
			
			case 'constH' :
			
				$command = 'convert -colorspace RGB -density 72 -quality ' . $this->quality . ' -resize x' . $size . ' -opaque transparent -background "' . $this->bgColour . '" '. $original . ' ' . $destinationFile;
				
			break;
			
			case 'crop' :

			if (strpos($size, 'x') === false) {
					$size = $size . 'x' . $size;
				}
				
				$pieces = explode("x", $size);
				
				$Xsize = $pieces[0];
				$Ysize = $pieces[1];

				$imgSize = getimagesize($original);
				if($imgSize[0] > $imgSize[1]) {
					//$resize = '-resize x' . $Ysize;
					$factor = $imgSize[1] / $Ysize;
					if ($imgSize[0] / $factor < $Xsize) $resize = '-resize ' . $Xsize;
					else $resize = '-resize x' . $Ysize;
				}else{
					$factor = $imgSize[0] / $Xsize;
					if ($imgSize[1] / $factor < $Ysize) $resize = '-resize x' . $Ysize;
					else $resize = '-resize ' . $Xsize;
				}
								
				$command = 'convert -colorspace RGB -density 72 -quality ' . $this->quality . ' ' . $resize . ' -opaque transparent -background "' . $this->bgColour . '" -gravity center -crop ' . $size . '+0+0 '. $original . ' ' . $destinationFile;

			break;
			
			case 'cropTo' :
				
				if (strpos($size, 'x') === false) {
					$size = $size . 'x' . $size;
				}
				
				$pieces = explode("x", $size);
				
				$Xsize = $pieces[0];
				$Ysize = $pieces[1];

				$imgSize = getimagesize($original);
				if($imgSize[0] > $imgSize[1]) {
					//$resize = '-resize x' . $Ysize;
					$factor = $imgSize[1] / $Ysize;
					if ($imgSize[0] / $factor < $Xsize) $resize = '-resize ' . $Xsize;
					else $resize = '-resize x' . $Ysize;
				}else{
					$factor = $imgSize[0] / $Xsize;
					if ($imgSize[1] / $factor < $Ysize) $resize = '-resize x' . $Ysize;
					else $resize = '-resize ' . $Xsize;
				}
								
				$command = 'convert -colorspace RGB -density 72 -quality ' . $this->quality . ' ' . $resize . ' -opaque transparent -background "' . $this->bgColour . '" -gravity center -crop ' . $size . '+0+0 '. $original . ' ' . $destinationFile;


			break;			
			
			case 'fitDimensionsPad' :
				
				$command = 'convert -colorspace RGB -density 72 -quality ' . $this->quality . ' -resize ' . $size . ' -opaque transparent -background "' . $this->bgColour . '" -gravity center -extent ' . $size . '+0+0 '.$original.' '.$destinationFile;
			
			break;
			case 'fitBox' :
				
				$command = 'convert -colorspace RGB -density 72 -quality ' . $this->quality . ' -resize ' . $size.'x'.$size . ' -opaque transparent -background "' . $this->bgColour . '" ' . $original.' '.$destinationFile;
			
			break;
			case 'none' :
			default :
				
				// not creating a thumbnail / image from page so
				// check if the original is a pdf and correct the extensions of the main file
				if(substr($original, -3) == '[0]' && $this->getExt($this->originalFile['name']) == "pdf") {
					$original = str_replace('[0]', '', $original);
					$destinationFile  = str_replace('.'.$this->ext, '.pdf', $destinationFile);
					$this->fileName   = str_replace('.'.$this->ext, '.pdf', $this->fileName);
				}
				
				if(!@move_uploaded_file($original, $destinationFile)) {
					$this->errors .= 'We couldnt upload your file to '.$destinationFile."\r\n";
				}else{
					chmod($destinationFile, 0777);
					
					// copy the orig file to the tinymce directory
				//	$tinymcePath  = SERVER_PATH.TINYMCE_IMAGE_DIR.'/'.$this->fileName;
				//	echo $destinationFile.', '.$tinymcePath;
				//	copy($destinationFile, $tinymcePath);
				//	chmod($tinymcePath, 0777);
					
				}
			break;
			
			
		}
		

		if(isset($command) && $command) {
			$return = 0;
			system($command, $return);
			
			if($return === 1) {
				$this->errors .= 'There was a problem issuing ImageMagick the command '.$command."\r\n";
			}else{
				chmod($destinationFile, 0777);
			}
		}
		
	}
	
	
	
}
?>