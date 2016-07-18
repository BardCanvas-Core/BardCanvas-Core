<?
/**
 * Funciones de gráficos
 * TODO: revisar variables sin definir.
 *
 * @author   Alejandro Caballero - alexknight@hotpop.com
 * @requires funciones_varias->construye_nombre_de_archivo()
 * @updated  2015-10-07
 */

define("THUMBNAILER_USE_WIDTH",               "USE_WIDTH");
define("THUMBNAILER_USE_HEIGHT",              "USE_HEIGHT");
define("THUMBNAILER_USE_BOTH",                "USE_BOTH");
define("THUMBNAILER_USE_SMALLEST_CALC_WIDTH", "USE_SMALLEST_CALC_WIDTH");

define("THUMBNAILER_FORCE_OVERWRITE", true);
define("THUMBNAILER_NO_OVERWRITE",    false);

define("THUMBNAILER_CREATION_DESTINATION",    true);
define("THUMBNAILER_NO_CREATION_DESTINATION", false);

function gfuncs_getmakethumbnail(
    $sourcefile, $savepath, $xwidth, $xheight, $dimension_to_use,
    $force_overwrite = false, $jpeg_quality = 100
) {
    # Pre-checks:
    if(
        ($dimension_to_use == THUMBNAILER_USE_WIDTH && $xwidth <= 0) ||
        ($dimension_to_use == THUMBNAILER_USE_HEIGHT && $xheight <= 0) ||
        ($dimension_to_use == THUMBNAILER_USE_BOTH && $xwidth <= 0 && $xheight <= 0) ||
        ($dimension_to_use == THUMBNAILER_USE_SMALLEST_CALC_WIDTH && $xwidth <= 0)
    ) throw new \Exception("Error: Invalid width/height parameters passed to thumbnailer");
    
    # Inits...
    $source_filename = basename($sourcefile);
    $source_path = str_replace($source_filename, "", $sourcefile);
    
    switch( $dimension_to_use )
    {
        case THUMBNAILER_USE_WIDTH:
            $dimensions_string = $xwidth . "w," . $jpeg_quality;
            break;
        case THUMBNAILER_USE_HEIGHT:
            $dimensions_string = $xheight . "h," . $jpeg_quality;
            break;
        case THUMBNAILER_USE_SMALLEST_CALC_WIDTH:
            $dimensions_string = $xwidth . "b," . $jpeg_quality;
            break;
        default: # THUMBNAILER_USE_BOTH:
            $dimensions_string = $xwidth . "x" . $xheight . "," . $jpeg_quality;
            break;
    }
    
    list($file_name, $file_ext) = explode(".", $source_filename);
    $thumbnail_file = $file_name . "-thumbnail-" . $dimensions_string . "." . $file_ext;
    
    # Primero veamos si existe el archivo en la ruta...
    if( ! $force_overwrite && file_exists("$savepath/$thumbnail_file") )
        return $thumbnail_file;
    
    # No existe... veamos si existe la fuente...
    if( ! file_exists($sourcefile) ) throw new \Exception("Thumbnailer: {$sourcefile} doesn't exist.");
    
    # La fuente existe... armemos
    $directorio = $source_path;
    $img = $source_filename;
    $archivo_original = $directorio . $img;
    
    # Traigamos el tamaño
    list($width, $height, $type) = getimagesize($archivo_original);
    
    # Calculamos ancho y alto
    if( $dimension_to_use == THUMBNAILER_USE_SMALLEST_CALC_WIDTH )
    {
        if( $height > $width )
        {
            # La imagen es vertical
            $dimension_to_use = THUMBNAILER_USE_WIDTH;
        }
        else
        {
            # La imagen es horizontal...
            $xheight = $xwidth;
            $xwidth = 0;
            $dimension_to_use = THUMBNAILER_USE_HEIGHT;
        }
    }
    
    switch( $dimension_to_use )
    {
        case THUMBNAILER_USE_WIDTH:
            $dest_w = $xwidth;
            $aspecto = $height / $width;
            $dest_h = round($xwidth * $aspecto);
            break;
        case THUMBNAILER_USE_HEIGHT:
            $dest_h = $xheight;
            $aspecto = $width / $height;
            $dest_w = round($xheight * $aspecto);
            break;
        default: # THUMBNAILER_USE_BOTH:
            $dest_w = $xwidth;
            $dest_h = $xheight;
            break;
    }
    
    # Copiemos...
    $src = null;
    switch( $type )
    {
        #case 1: $src = imagecreatefromgif($archivo_original);  break;
        case 1:
            throw new \Exception("Thumbnailer: GIF files aren't supported by this function");
            break;
        case 2:
            $src = imagecreatefromjpeg($archivo_original);
            break;
        case 3:
            $src = imagecreatefrompng($archivo_original);
            break;
    }
    
    # Creamos la imagen destino en memoria
    $dest = imagecreatetruecolor($dest_w, $dest_h);
    
    # Resampleamos
    imagecopyresampled(
        $dest,
        $src,
        0,          # int dstX, 
        0,          # int dstY, 
        0,          # int srcX, 
        0,          # int srcY, 
        $dest_w,    # int dstW, 
        $dest_h,    # int dstH, 
        $width,     # int srcW, 
        $height     # int srcH 
    );
    
    # Creeemos destino si asi se desea
    if( ! is_dir($savepath) )
        if( ! @mkdir($savepath) ) throw new \Exception("Thumbnailer: Can't create target directory $savepath.");
    @chmod($savepath, 0777);
    
    # Guardamos el archivo destino y borramos el original...
    if( ! @imagejpeg($dest, "$savepath/$thumbnail_file", $jpeg_quality) )
        throw new \Exception("Thumbnailer: Can't save target file $dest");
    
    @imagedestroy($src);
    @imagedestroy($dest);
    @chmod("$savepath/$thumbnail_file", 0777);
    
    return $thumbnail_file;
}

function gfuncs_getmakePNGthumbnail(
    $sourcefile, $savepath, $xwidth, $xheight, $dimension_to_use,
    $force_overwrite = false, $png_compression = 1, $create_destination = false
) {
    # Pre-checks:
    if( ($dimension_to_use == THUMBNAILER_USE_WIDTH && $xwidth <= 0) ||
        ($dimension_to_use == THUMBNAILER_USE_HEIGHT && $xheight <= 0) ||
        ($dimension_to_use == THUMBNAILER_USE_BOTH && $xwidth <= 0 && $xheight <= 0) ||
        ($dimension_to_use == THUMBNAILER_USE_SMALLEST_CALC_WIDTH && $xwidth <= 0)
    ) throw new \Exception("Error: Invalid width/height parameters passed to PNG thumbnailer");
    
    # Inits...
    $source_filename = basename($sourcefile);
    $source_path = str_replace($source_filename, "", $sourcefile);
    
    switch( $dimension_to_use )
    {
        case THUMBNAILER_USE_WIDTH:
            $dimensions_string = $xwidth . "w," . $png_compression;
            break;
        case THUMBNAILER_USE_HEIGHT:
            $dimensions_string = $xheight . "h," . $png_compression;
            break;
        case THUMBNAILER_USE_SMALLEST_CALC_WIDTH:
            $dimensions_string = $xwidth . "b," . $png_compression;
            break;
        default: # THUMBNAILER_USE_BOTH:
            $dimensions_string = $xwidth . "x" . $xheight . "," . $png_compression;
            break;
    }
    
    # list($file_name, $file_ext) = explode(".", $source_filename);
    $filename_parts = explode(".", $source_filename);
    unset($filename_parts[count($filename_parts) - 1]);
    $file_name = implode(".", $filename_parts);
    $thumbnail_file = $file_name . "-thumbnail-" . $dimensions_string . ".png";
    
    # Primero veamos si existe el archivo en la ruta...
    if( ! $force_overwrite && file_exists("$savepath/$thumbnail_file") )
        return $thumbnail_file;
    
    # No existe... veamos si existe la fuente...
    if( ! file_exists($sourcefile) ) throw new \Exception("Thumbnailer: {$sourcefile} doesn't exist.");
    
    # La fuente existe... armemos
    $directorio = $source_path;
    $img = $source_filename;
    $archivo_original = $directorio . $img;
    # echo "\ngfuncs-- $archivo_original\n";
    
    # Traigamos el tamaño
    list($width, $height, $type) = getimagesize($archivo_original);
    # $caca = getimagesize($archivo_original);
    # echo "\ntamaño de $archivo_original := " . print_r($caca, true) . "\n";
    
    # Calculamos ancho y alto
    if( $dimension_to_use == THUMBNAILER_USE_SMALLEST_CALC_WIDTH )
    {
        if( $height > $width )
        {
            # La imagen es vertical
            $dimension_to_use = THUMBNAILER_USE_WIDTH;
        }
        else
        {
            # La imagen es horizontal...
            $xheight = $xwidth;
            $xwidth = 0;
            $dimension_to_use = THUMBNAILER_USE_HEIGHT;
        } # end if
    }
    
    switch( $dimension_to_use )
    {
        case THUMBNAILER_USE_WIDTH:
            $dest_w = $xwidth;
            $aspecto = $height / $width;
            $dest_h = round($xwidth * $aspecto);
            break;
        case THUMBNAILER_USE_HEIGHT:
            $dest_h = $xheight;
            $aspecto = $width / $height;
            $dest_w = round($xheight * $aspecto);
            break;
        default: # THUMBNAILER_USE_BOTH:
            $dest_w = $xwidth;
            $dest_h = $xheight;
            break;
    }
    
    # Copiemos...
    $src = null;
    switch( $type )
    {
        #case 1: $src = imagecreatefromgif($archivo_original);  break;
        case 1:
            $src = imagecreatefromgif($archivo_original);
            break;
        case 2:
            $src = imagecreatefromjpeg($archivo_original);
            break;
        case 3:
            $src = imagecreatefrompng($archivo_original);
            break;
    }
    
    # Creamos la imagen destino en memoria
    $dest = imagecreatetruecolor($dest_w, $dest_h);
    
    imagealphablending($dest, false);
    $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
    imagefill($dest, 0, 0, $transparent);
    imagesavealpha($dest, true);
    imagealphablending($dest, true);
    
    # Resampleamos
    imageantialias($src, true);
    imageantialias($dest, true);
    imagecopyresampled(
        $dest,
        $src,
        0,          # int dstX, 
        0,          # int dstY, 
        0,          # int srcX, 
        0,          # int srcY, 
        $dest_w,    # int dstW, 
        $dest_h,    # int dstH, 
        $width,     # int srcW, 
        $height     # int srcH 
    );
    
    # Creeemos destino si asi se desea
    if( ! is_dir($savepath) && $create_destination )
        if( ! @mkdir($savepath) ) throw new \Exception("Thumbnailer: Can't create target directory $savepath.");
    @chmod($savepath, 0777);
    
    # Guardamos el archivo destino y borramos el original...
    if( ! @imagepng($dest, "$savepath/$thumbnail_file", $png_compression) )
        throw new \Exception("Thumbnailer: Can't save target file $dest");
    
    @imagedestroy($src);
    @imagedestroy($dest);
    @chmod("$savepath/$thumbnail_file", 0777);
    
    return $thumbnail_file;
}
