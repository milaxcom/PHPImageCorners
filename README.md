PHPImageCorners
===============

Editing the corners of the image using php


### EXAMPLE
```PHP
<?php
/** Load Library. */
include "PHPImage.php";

/** Create Instance class and load specified image. */
$Img	= PHPImageLoad( "01.jpg" );
/** Download and apply the given mask around the edges. */
$Img->cornerMask( "r15.png" );
/** Print to brouser. */
$Img->write();

/* OR */
PHPImageLoad( "01.jpg" )->cornerMask( "r15.png" )->write();

/* If you need save image to file, use `saveToFile("new_01.jpeg")` instead `write()`. */
PHPImageLoad( "01.jpg" )->cornerMask( "r15.png" )->saveToFile("new_01.jpeg");
?>
```
