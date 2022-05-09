<!doctype html>
<html>
    <head>
        <?php
            // use dirname to get the directory of the current file
            function currentDir(){
                $path = dirname(__FILE__);
                // $path here is now /path_to/your_dir
                
                // split directory into array of pieces
                $pieces = explode(DIRECTORY_SEPARATOR, $path);
                // $pieces = ['path_to', 'your_dir']
                
                // get the last piece, result is: your_dir
                return $pieces[count($pieces) - 1];
            }
    
            //*********************************************
            //**** main start of code -- all functons above
            //*********************************************

            $filename = 'title.txt';
    
            if (file_exists($filename)) {
                // "The file $filename exists";
                $txt_file = fopen($filename,'r');
                $title = fgets($txt_file); // get only the first line
            } else {
                // "The file $filename does not exist";
                // use dirname to get the directory of the current file
                $title = currentDir();
            }
        ?>

        <title><?php echo $title ?></title>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <link href='/rfc_lib/simplelightbox-master/dist/simple-lightbox.min.css' rel='stylesheet' type='text/css'>

        <script type="text/javascript" src="/rfc_lib/simplelightbox-master/dist/simple-lightbox.jquery.min.js"></script>

        <script>
            function reload() {
                var col_count = document.getElementById("colcount").value;
                var request_uri = location.pathname + location.search;
                var n = request_uri.indexOf("&col_count=");
                if (n > 0){
                    request_uri = request_uri.substr(0,n);
                }
                n = request_uri.indexOf("?");
                if (n <= 0){
                    request_uri += '?col_count=' + col_count;
                } else {
                    request_uri += '&col_count=' + col_count;
                }
                window.location.assign(request_uri);
            }
        </script>

        <?php
            $numberColumns = $_GET["col_count"];
            if ($numberColumns < 1 || $numberColumns > 30) {
                $numberColumns = 6;
            }
            $percentageWidth = (100 / $numberColumns) - 0.5;
        ?>

        <link href='/rfc_lib/style.css' rel='stylesheet' type='text/css'>
        <link href='/rfc_lib/coltable.css' rel='stylesheet' type='text/css'>

        <?php
        if ($percentageWidth != 18){
            echo "<style>";
            echo "    .container .gallery a img {width: " . $percentageWidth . "% !important;}";
            echo "</style>";
        }
        ?>
    </head>
    <body>
        <div class='container'>
            <div class="gallery">

            <?php
            // initial starting folder for gallery of images
            define("STARTING_FOLDER","gallery/");

            define("DEFAULT_THUMBNAIL_FOLDER", "thumbnail");
            define("DEFAULT_FOLDER_BACK_THUMBNAIL", "/rfc_lib/folderBackThumbnail.jpg");
            define("DEFAULT_FOLDER_THUMBNAIL", "/rfc_lib/folderThumbnail.jpg");

            define("DEFAULT_THUMBNAIL_WIDTH", 300);
            define("DEFAULT_THUMBNAIL_HEIGHT", 225);

            // check if thumbnail exist, if not create a new thumbnail from image
            function checkThumbnail($originalImageFilename, $thumbnailFilename) {
                if (!(file_exists($thumbnailFilename))) {
                    $thumbnail_path = createThumbnail($originalImageFilename, $thumbnailFilename);
                }

                return $thumbnailFilename;
            }

            // create thumbnail from original image resize and centered.
            function createThumbnail($originalImageFilename, $thumbnailFilename) {

                $dst_width = DEFAULT_THUMBNAIL_WIDTH;
                $dst_height = DEFAULT_THUMBNAIL_HEIGHT;

                $dst_x = 0;
                $dst_y = 0;
                $src_x = 0;
                $src_y = 0;

                list($old_width, $old_height) = getimagesize($originalImageFilename);

                $src_height = $old_height;
                $src_width = $old_width;

                if ($old_width < $old_height) {
                    $src_height = $old_width / $dst_width * $dst_height;
                    $src_y = ($old_height - $src_height) / 2;
                } elseif ($old_width > $old_height) {
                    $src_width = $old_height * $dst_width / $dst_height;
                    $src_x = ($old_width - $src_width) /2;
                }

                $dst_image = imagecreatetruecolor($dst_width, $dst_height);
                $src_image = imagecreatefromjpeg($originalImageFilename);

                imagecopyresampled($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_width, $dst_height, $src_width, $src_height);

                // Enable interlancing
                imageinterlace($dst_image, true);

                imagejpeg($dst_image, $thumbnailFilename);
                imagedestroy($src_image);
                imagedestroy($dst_image);

                return $thumbnailFilename;
            }

            // get this php file's URL
            function getURL($excludeParams = FALSE){
                if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'){
                    $url = "https://";
                } else {
                    $url = "http://";
                }

                // Append the host(domain name, ip) to the URL.
                $url.= $_SERVER['HTTP_HOST'];

                // Append the requested resource location to the URL
                $url.= $_SERVER['REQUEST_URI'];

                if ($excludeParams) {
                    $url = explode("?", $url)[0];
                }
                return $url;
            }

            // get the folder above the current folder.
            // return nothing if the parentFolder if it is the same as the starting folder.
            function getParentFolder($folderName, $initFolder) {
                $returnFolder = "";

                if (strlen($folderName) > strlen($initFolder)){
                    $j =  strlen($folderName) - 1;
                    $i = strrpos(substr($folderName, 0, $j), "/");

                    if ($i > 0 ){
                        $returnFolder = substr($folderName,0,$i + 1);
                    }
                }

                return $returnFolder;
            }

            // display the folder path(s) at the top with hyperlinks back to the folders above.
            // also display the input for "number of columns".
            function displayFolderLinks($folderName, $initialDir, $numberColumns){
                $folders = explode("/", $folderName);
                $folderPath = "";

                echo '<div class="row">' . PHP_EOL;
                
                // First Line - Column 1
                echo '                <div class="column1">';

                echo "<center><h2>Folder: ";

                foreach ($folders as $index=>$folder){
                    $folderPath = $folderPath . $folders[$index] . "/";

                    if ($index < count($folders)-1){
                        if (count($folders) > 2 && count($folders)-2 != $index){
                            // create link to folder
                            ?>
                            <a href="<?php echo getURL(TRUE) . "?dc=" . $folderPath . "&di=" . $initialDir . "&col_count=" . $numberColumns; ?>">
                            <?php echo $folders[$index];?></a>
                            <?php
                            echo " > ";
                        } else {
                            // is only top folder or current folder
                            echo $folders[$index];
                        }
                    }
                }
                echo "</center></h2></div>" . PHP_EOL;
                
                // First Line - Column 2
                echo '                <div class="column2">';
                echo '<input type="checkbox" id="showdescr">';
                echo '<label for="showdescr"> Show Descriptions</label>';
                echo"</div>" . PHP_EOL;

                // First Line - Column 3
                echo '                <div class="column3">';
                echo '<input type="text" id="colcount" onblur="reload()" value="' . $numberColumns . '"></div>';
                echo '<label for="colcount">Number of columns: </label>';
                echo"</div>" . PHP_EOL . PHP_EOL;
            }

            // create new image with watermark
            function createThumbnailWithWatermark ($SourceFile, $WaterMarkText, $DestinationFile) {
                list($width, $height) = getimagesize($SourceFile);
                $image_p = imagecreatetruecolor($width, $height);
                $image = imagecreatefromjpeg($SourceFile);
                imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width, $height);
                $font_color = imagecolorallocate($image_p, 0, 0, 0);
                $font_file = '/rfc_lib/fonts/arial.ttf';
                $font_size = intval(DEFAULT_THUMBNAIL_HEIGHT * 0.111); // calculate the font size as a percentage of the image height.

                $waterMarkObj = imageFormatString($image_p, $WaterMarkText, $font_file, $font_size, $font_color, $width, $height);

                // Enable interlancing
                imageinterlace($image_p, true);

                if ($DestinationFile <> '') {
                    // write image object to a destination file
                    imagejpeg ($image_p, $DestinationFile, 100);
                } else {
                    header('Content-Type: image/jpeg');
                    imagejpeg($image_p, null, 100);
                };
                imagedestroy($image);
                imagedestroy($image_p);
                return  $DestinationFile;
            }

            // Write text centered on an image object as a watermark.
            function imageFormatString(&$image_p, $text, $font_file, $font_size, $font_color, $maxWidth, $maxHeight){
                $marginTop = intval($maxHeight * 0.377);
                $marginSides = 20;
                $marginBottom = 20;

                $text = str_replace("_", " ", $text);   // convert underscore to spaces
                $words = explode(" ",$text);            // split text on spaces

                $wnum = count($words);

                $lines = array();
                $lineCount = 0;
                $line = '';
                $priorLine = '';
                $priorHeight = '';
                $priorWidth = '';
                $space = ' ';

                for($i=0; $i<$wnum; $i++){
                    if ($i == ($wnum - 1)) {$space = '';}

                    $line = $priorLine . $words[$i];
                    $dimensions = imagettfbbox($font_size, 0, $font_file, $line);
                    $lineWidth = $dimensions[2] - $dimensions[0];
                    $lineHeight = $dimensions[1] - $dimensions[7];

                    $addLine = FALSE;
                    if (($lineWidth > ($maxWidth - (2 * $marginSides))) && $lineCount <= 4) {
                        $lines[] = array($priorLine, $priorHeight, $priorWidth);
                        $priorLine = $words[$i] . $space;
                        $priorHeight = $lineHeight;
                        $priorWidth = $lineWidth - $priorWidth;
                        $lineCount++;
                        $addLine = TRUE;
                    } elseif ($i < ($wnum - 1)) {
                        $priorLine .= $words[$i] . $space;
                        if ($priorHeight < $lineHeight) {$priorHeight = $lineHeight;}
                        if ($priorWidth < $lineWidth) {$priorWidth = $lineWidth;}   //should always be true
                    }

                    if ($i == ($wnum - 1)){         // Last word
                        if ($addLine == TRUE){
                            $line = $words[$i];
                            $dimensions = imagettfbbox($font_size, 0, $font_file, $line);
                            $priorWidth = $dimensions[2] - $dimensions[0];
                            $priorHeight = $dimensions[1] - $dimensions[7];
                        } else {
                            if ($priorHeight < $lineHeight) {$priorHeight = $lineHeight;}
                            if ($priorWidth < $lineWidth) {$priorWidth = $lineWidth;}   //should always be true
                        }

                        $lines[] = array($line, $priorHeight, $priorWidth);
                        $lineCount++;
                    }
                }

                $lineHeight = ($maxHeight - $marginTop - $marginBottom) / 4;
                if ($lineCount <= 2){
                    $yStart = ($lineHeight * 2) + $marginTop;   // start on second line
                } else {
                    $yStart = $lineHeight + $marginTop;         // start on top line (under top margin)
                }

                foreach($lines as $lineArray){
                    $xStart = intval(($maxWidth - $lineArray[2]) / 2);
                    $waterMarkText = trim($lineArray[0]);
                    imagettftext($image_p, $font_size, 0, $xStart, $yStart, $font_color, $font_file, $waterMarkText);
                    $yStart += $lineHeight;
                }
            }

            // Get the image's EXIF title and comment.  The file name will be used as the default comment. 
            function getImageComment($currentDir, $file) {
                $fileName = $currentDir . $file;
                $comment = str_replace("_", " ", $file);
                $clearedComment = FALSE;

                $exif = exif_read_data($fileName, 'IFD0', true);

                $newLine = '';

                if (!($exif===false)){
                    foreach ($exif as $key => $section) {
                        if ($key == 'IFD0'){
                            foreach ($section as $name => $val) {
                                // Remove any characters in the hex ranges 0-31 and 128-255,
                                // leaving only the hex characters 32-127 in the resulting string
                                $val = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $val);

                                if ($name == 'Title' || $name == 'Comments') {
                                    if ($clearedComment == FALSE){
                                        $comment = '';
                                        $clearedComment = TRUE;
                                    }
                                    $comment .= $newLine . $val;
                                    $newLine = "&#10;";
                                }
                            }
                        }
                    }
                }

                return $comment;
            }

            // check if thumbnail folder exist else create it
            function checkThumbnumberFolder($thumbnailFolder){
                // check if thumbnail folder does not exist, creat it.
                if (!is_dir($thumbnailFolder)){
                    mkdir($thumbnailFolder, 0755);
                }
            }

            // get an array of sub folder(s) containing URL, Image and Title.
            function getFolderArray($folderName, $thumbnailFolder, $currentDir){
                checkThumbnumberFolder($thumbnailFolder);

                // is folder, therefore add "jpg" to name for image to display of/for folder
                $folderThumbnail = $thumbnailFolder . $folderName . ".jpg";

                // thumbnail does not exist, greate new thumbnail with watermark of folder name
                if (!file_exists($folderThumbnail)){
                    createThumbnailWithWatermark (DEFAULT_FOLDER_THUMBNAIL, $folderName, $folderThumbnail);
                }
                // return array( URL , Image , Title)
                return array($currentDir . $folderName . "/", $folderThumbnail, $currentDir . $folderName);
            }

            // get an array of image files containing URL, Image and Title.
            function getFileArray($file, $thumbnailFolder, $currentDir){
                checkThumbnumberFolder($thumbnailFolder);

                $thumbnail_path = $thumbnailFolder . $file;
                $thumbnail_path = checkThumbnail($currentDir . $file, $thumbnail_path);

                // return array( URL , Image , Title)
                return array($currentDir . $file, $thumbnail_path, getImageComment($currentDir, $file));
            }

            //*********************************************
            //**** main start of code -- all functons above
            //*********************************************

            $currentDir = $_GET["dc"];
            $initialDir = $_GET["di"];
            $parentFolder = getParentFolder($currentDir, $initialDir);

            if (strlen($currentDir) == 0){
                $currentDir = STARTING_FOLDER;  // initial starting folder for gallery of images
                $initialDir = $currentDir;
            }

            // Target directory
            if (is_dir($currentDir)){

                // check the folder can be accessed 
                if ($dirExist = opendir($currentDir)){
                    displayFolderLinks($currentDir, $initialDir, $numberColumns);

                    $count = 1;

                    // arrays containing URL, Image and Title.
                    $foldersArray = array();
                    $filesArry = array();
                    $mergedArray = array();

                    $thumbnailFolder = $currentDir . "/" . DEFAULT_THUMBNAIL_FOLDER . "/";

                    // if there is a parent folder add it to the folders array
                    if ($parentFolder != ""){
                        $foldersArray[] = array($parentFolder, DEFAULT_FOLDER_BACK_THUMBNAIL, $parentFolder);
                    }

                    // first add all the directories (folders) to an array excluding the thumbnail folder
                    $foldersOnly = glob($currentDir . '*',  GLOB_ONLYDIR);
                    foreach ($foldersOnly as $index=>$fo){
                        //get only folder name, not the path
                        $folderName = str_replace($currentDir, "", $foldersOnly[$index]);

                        //leave out the thumbnail folder
                        if (str_replace($currentDir,"",$foldersOnly[$index]) != DEFAULT_THUMBNAIL_FOLDER){
                            $foldersArray[] = getFolderArray($folderName, $thumbnailFolder, $currentDir);
                        }
                    }

                    // next add all the image files to an array
                    $files = glob($currentDir . '*.{jpeg,jpg,png,gif,JPEG,JPG,PNG,GIF}', GLOB_BRACE);
                    foreach($files as $file) {
                        $filesArry[] = getFileArray(str_replace($currentDir, "", $file), $thumbnailFolder, $currentDir);
                    }

                    // if there is a folder or image file sort and merge the arrays
                    if (count($foldersArray) > 0 || count($filesArry) > 0){
                        // sort the arrays
                        natcasesort($foldersArray);
                        natcasesort($filesArry);

                        // merge the arrays
                        $mergedArray = array_merge($foldersArray, $filesArry);
                    }

                    // build the gallery html
                    foreach($mergedArray as $item) {
                        $image_path = $item[0];
                        $thumbnail_path = $item[1];
                        $comment = $item[2];

                        if (is_dir($image_path)) {
                            ?>
                            <!-- Image -->
                            <a href="<?php echo getURL(TRUE) . "?dc=" . $image_path . "&di=" . $initialDir . "&col_count=" . $numberColumns; ?>">
                                <img src="<?php echo $thumbnail_path; ?>" alt="" title="<?php echo $comment; ?>"/>
                            </a>
                            <!-- --- -->
                            <?php
                        } else {
                            ?>
                            <!-- Image -->
                            <a href="<?php echo $image_path; ?>">
                                <img src="<?php echo $thumbnail_path; ?>" alt="" title="<?php echo $comment; ?>"/>
                            </a>
                            <!-- --- -->
                            <?php
                        }

                        // When we have the correct number of columns, Break
                        if( $count % $numberColumns == 0){
                        ?>
                            <div class="clear"></div>
                        <?php
                        }
                        $count++;
                    }
                }
                closedir($dirExist);
            }
            ?>
            </div>
        </div>


        <!-- Script -->
        <script type='text/javascript'>
        $(document).ready(function(){

            // Intialize gallery
            var gallery = $('.gallery a').simpleLightbox();
        });
        </script>
    </body>
</html>