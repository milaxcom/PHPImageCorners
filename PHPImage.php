<?
class PHPImage{
	public $image_folder	= "";			/** Папка с рисунками. */
	public $image			= NULL;			/** Созданный/загруженний рисунок. */
	public $fromFile		= FALSE;		/** Рисунок загружен из файла. */
	/**
	 * Информация об загруженном/созданном рисунке. (array)
	 *	0		- ширина
	 *	1		- высота
	 *	mime	- миме тип
	 *	type	- расширение/окончание функций
	 */
	public $Info			= FALSE;
	public $mime_map		= array(		/** MIME type masks. */
		"image/jpeg"			=> "jpeg",
		"image/png"				=> "png",
		"image/gif"				=> "gif",
		"image/tiff	"			=> "tiff",
		"image/x-ms-bmp"		=> "wbmp"
	);
	public $def_type		= "png";		/** Расширение по умолчанию. */
	public $def_mime		= "image/png";	/** Миме тип по умолчанию. */
	public $def_image		= array(		/** Параметры рисунка по умолчанию. */
		"width"		=> 100,	//(int) 1-***	(ширина)
		"height"	=> 100,	//(int) 1-***	(высота)
		"R"			=> 0,	//(int)0-255	(красный)
		"G"			=> 0,	//(int)0-255	(зеленый)
		"B"			=> 0,	//(int)0-255	(голубой)
		"alpha"		=> 0	//(int)0-127	(прозрачность)
	);
	
	public $color			= FALSE;		/** Параметры следующего цвета для рисования. */
	
	/* Проверка НЕдоступности ресурса загруженного изображения. */
	public	function isNO(){return(!is_resource($this->image));}
	public	function isFile(){return(is_resource($this->image)AND$this->fromFile===TRUE);}
	
	/**
	 * КОНСТРУКТОР
	 * Загрузка указанного изображения или создание нового.
	 * @param	string	$image	[путь/имя файла с рисунком, относительно заданного в {$image_folder} пути. ]
	 * @param	bool	$image	[создать новый, пустой рисунок]
	 **/
	public function __construct( $image = FALSE ) {
		/** Блок загрузки из файла. */
		if ( is_string($image) AND ($image="{$this->image_folder}{$image}") AND file_exists($image) ) {
			$this->Info				= getimagesize($image);
			if ( isset($this->mime_map[$this->Info["mime"]]) AND ($this->Info["type"] = $this->mime_map[$this->Info["mime"]]) ) {
				$function			= "imagecreatefrom{$this->Info["type"]}";
				if ( function_exists($function) AND is_resource($this->image = @$function($image)) ) {
					if ( $this->Info["type"] == "png" ) {
						imagealphablending(	$this->image, TRUE );
						imagesavealpha(		$this->image, TRUE );
					}
					return ($this->fromFile=TRUE);
				}
			}
		/** Блок загрузки параметров пустого изображения. */
		}elseif( is_array($image) AND count($image) > 0 ){
			foreach( $image as $i => $value ) {
				if( is_int($i) AND is_int($value) )
					switch($i){
						case 0: $this->def_image["width"]	= $value; break;
						case 1: $this->def_image["height"]	= $value; break;
						case 2: $this->def_image["R"]		= $value; break;
						case 3: $this->def_image["G"]		= $value; break;
						case 4: $this->def_image["B"]		= $value; break;
						case 5: $this->def_image["alpha"]	= $value; break;
					}
			}
		}
		/** Блок создания пустого изображения. */
		$def_image			= $this->def_image;
		$this->Info			= array(
								$def_image["width"],
								$def_image["height"],
								"mime"	=> $this->def_mime,
								"type"	=> $this->def_type
							);
		$this->image		=  imagecreatetruecolor( $def_image["width"], $def_image["height"] );
		$this->color( array($def_image["R"], $def_image["G"], $def_image["B"], $def_image["alpha"]) );
		imagefilledrectangle( $this->image, 0, 0, $def_image["width"], $def_image["height"], $this->color );
		if ( $def_image["alpha"] > 0 ) {
			$this->color( array(0,0,0,0) )->alphaColor();
			imagealphablending(		$this->image,	FALSE	);
			imagesavealpha(			$this->image,	TRUE	);
		}
	}
	
	/**
	 * Выводит изображение в браузер.
	 */
	public function write() {
		if($this->isNO())return $this;
		header("Content-Type: {$this->Info["mime"]}");
		$function			= "image{$this->Info["type"]}";
		$function( $this->image );
		$this->clear();
	}
	
	/**
	 * Сохранение в файл.
	 */
	public function saveToFile( $file = FALSE, $quality = FALSE ) {
		if($this->isNO() OR !is_string($file))return $this;
		$quality			= (is_int($quality) AND $quality>=0 AND $quality <=9) ? $quality : FALSE;
		$function			= "image{$this->Info["type"]}";
		$file				= mb_eregi(".".$this->Info["type"]."$", $file)
								? $file : mb_ereg_replace( "\.+$", "", $file ).".".$this->Info["type"];
		if( $quality !== FALSE )	$function( $this->image, $file, $quality );
		else						$function( $this->image, $file );
		$this->clear();
	}
	/**
	 * Освобождает память, занятую изображением.
	 */
	public function clear() {
		if($this->isNO())return;
		imagedestroy($this->image);
	}

	/**
	 * Изменение размера изображения.
	 */
	public function resize( $width = 1, $height = FALSE ) {
		if($this->isNO())return $this;
		if ( !is_numeric($width) OR ((double)$width<=0) )				return $this;
		if ( !is_array($this->Info) )									$this->Info	= array();
		if ( !isset($this->Info[0]) OR !is_numeric($this->Info[0]) )	$this->Info[0]	= 1;
		if ( !isset($this->Info[1]) OR !is_numeric($this->Info[1]) )	$this->Info[1]	= 1;
		@list($w, $h)	= $this->Info;
		if ( !is_numeric($height) OR ((double)$height<=0) ) {
			$height		= $h * $width;	
			$width		= $w * $width;
		}
		$new_image		= imagecreatetruecolor( $width, $height );
		imagecopyresampled( $new_image, $this->image, 0, 0, 0, 0, $width, $height, $w, $h );
		$this->image	= $new_image;
		return $this;
	}
	
	/**
	 * Установка следующего цвета для рисования.
	 * @param	array	$color	[R;G;B;alpha]
	 */
	public function color( $color = array( 0, 0, 0, 0 ) ) {
		if($this->isNO() OR !is_array($color))return $this;
		$C				=	array(0,0,0,0);
		foreach( $color as $i => $value ){
			if( is_int($i) AND is_int($value) AND $value >= 0)
			$C[$i]		= $value;
		}
		if( ($this->color = imagecolorexactalpha($this->image,$C[0],$C[1],$C[2],$C[3])) >-1 )
			return $this;
		$this->color	= imagecolorallocatealpha( $this->image, $C[0], $C[1], $C[2], $C[3] );
		return $this;
	}
	
	/**
	 * Заменяет установленный цвет на указанный.
	 */
	public function setColor( $color = array(0,0,0,0) ){
		if($this->isNO() OR !is_array($color))return $this;
		$C				=	array(0,0,0,0);
		foreach( $color as $i => $value ){
			if( is_int($i) AND is_int($value) AND $value > 0)
			$C[$i]		= $value;
		}
		imagecolorset( $this->image, $this->color, $C[0], $C[1], $C[2] );
		return $this;
	}
	
	/**
	 * Рисование закрашенного эллипса.
	 */
	public function addRow( $param = array(1,1,0,0) ) {
		if($this->isNO() OR !is_array($param))return $this;
		imagefilledellipse( $this->image, $param[0], $param[1], $param[2], $param[3], $this->color );
		return $this;
	}

	/**
	 * Определяет цвет как прозрачный
	 */
	public function alphaColor(){
		if($this->isNO())return $this;
		imagecolortransparent(	$this->image,	$this->color );
		return $this;
	}
	
	/**
	 * Наложить угловую маску.
	 */
	public function cornerMask( $mask = FALSE ){
		if($this->isNO())return $this;
		$mask	= PHPImageLoad( $mask );
		if(!$mask->isFile())return $this;
		
		// Размеры
		$I		= array( $this->Info[0],	$this->Info[1]		);
		$M		= array( $mask->Info[0]/2,	$mask->Info[1]/2	);
		
		imagecopy( $this->image, $mask->image, 0,			0,				$M[0],	$M[1],	$M[0],	$M[1] );
		imagecopy( $this->image, $mask->image, $I[0]-$M[0],	0,				0,		$M[1],	$M[0],	$M[1] );
		imagecopy( $this->image, $mask->image, $I[0]-$M[0],	$I[1]-$M[1],	0,		0,		$M[0],	$M[1] );
		imagecopy( $this->image, $mask->image, 0,			$I[1]-$M[1],	$M[0],	0,		$M[0],	$M[1] );
		$mask->clear();
		return $this;
	}
	
	/**
	 * Рисует пиксел установленного цвета в установленном месте.
	 */
	public function addPixel( $x = 0, $y = 0 ) {
		if($this->isNO())return $this;
		$x	= is_int($x) ? $x : 0;
		$y	= is_int($y) ? $y : 0;
		imagesetpixel( $this->image, $x, $y, $this->color );
		return $this;
	}
	
	
	/** TEST
	 * Создает новый рисунок попиксильно считывая загруженный.
	 * (только для загруженных из файла)
	 */
	public function reCreate() {
		if(!$this->isFile())return $this;
		$NewImage			= PHPImageLoad( array($this->Info[0], $this->Info[1]) );
		$NewImage->Info		= $this->Info;
		if ( $this->Info["type"] == "png" ) {
			imagealphablending(	$NewImage, TRUE );
			imagesavealpha(		$NewImage, TRUE );
		}
		for ( $w = 0; $w < $this->Info[0]; $w++ ) {
			for ( $h = 0; $h < $this->Info[1]; $h++ ) {
				$I			= imagecolorat( $this->image, $w, $h );
				$C			= imagecolorsforindex( $this->image, $I );
				$NewImage
					->color( array($C["red"],$C["green"],$C["blue"],$C["alpha"]) )
					->addPixel( $w, $h );
			}
		}
		$this->clear();
		$this->image	= $NewImage->image;
		return $this;
	}
	
	/** TEST
	 * Получение карты цветов.
	 */
	public function colorMap(){
		if($this->isNO())return $this;
		$Colors	= array();
		for ( $w=0; $w<imagesx($this->image);$w++) {
			for ( $h=0; $h<imagesy($this->image);$h++) {
				$index				= imagecolorat( $this->image, $w, $h );
				if ( isset($Colors[ $index ]) ) continue;
				$Colors[ $index ]	= imagecolorsforindex( $this->image, $index );
			break;
			}
		}
		print_r( $Colors );
	}
}

	
function PHPImageLoad( $image = FALSE ) {
	return new PHPImage($image);
}
?>
