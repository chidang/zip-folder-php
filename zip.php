<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
class ZipHelper {

    /**
     * Add files and sub-directories in a folder to zip file.
     * @param string $folder
     * @param ZipArchive $zipFile
     * @param int $exclusiveLength Number of text to be exclusived from the file path.
     */
    private static function folderToZip($folder, &$zipFile, $exclusiveLength) {
        $handle = opendir($folder);
        while ($f = readdir($handle)) {
            if ($f != '.' && $f != '..') {
                $filePath = "$folder/$f";
                // Remove prefix from file path before add to zip.
                $localPath = substr($filePath, $exclusiveLength);
                if (is_file($filePath)) {
                    $zipFile->addFile($filePath, $localPath);
                } elseif (is_dir($filePath)) {
                    // Add sub-directory.
                    $zipFile->addEmptyDir($localPath);
                    self::folderToZip($filePath, $zipFile, $exclusiveLength);
                }
            }
        }
        closedir($handle);
    }

    /**
     * Zip a folder (include itself).
     * Usage:
     *   HZip::zipDir('/path/to/sourceDir', '/path/to/out.zip');
     *
     * @param string $sourcePath Path of directory to be zip.
     * @param string $outZipPath Path of output zip file.
     */
    public static function zipDir($sourcePath, $outZipPath) {
        $pathInfo = pathInfo($sourcePath);
        $parentPath = $pathInfo['dirname'];
        $dirName = $pathInfo['basename'];

        $z = new ZipArchive();
        $z->open($outZipPath, ZIPARCHIVE::CREATE);
        $z->addEmptyDir($dirName);
        self::folderToZip($sourcePath, $z, strlen("$parentPath/"));
        $z->close();
    }

    public static function getZipFile($dir) {
        $zipFiles = array();
        if (is_dir($dir)) {
            $objects = scandir($dir);

            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    $path = $dir . "/" . $object;
                    if (filetype($path) != "dir") {
                        $info = pathinfo($path);
                        if (strtolower($info['extension']) == 'zip') {
                            $zipFiles[] = $object;
                        }
                    }
                }
            }
        }
        return $zipFiles;
    }

}


set_time_limit(0);

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Zip Tool</title>
        <meta name="description" content="">
        <meta name="author" content="">
        <style type="text/css">
            input, select{
                border: 2px solid #ccc;
                height: 24px;
            }
            fieldset{
                width: 400px;
                border: 3px solid #ccc;
                padding: 30px;
            }
            .wrapper{
                margin: 0 auto;
                width: 500px;
            }
            .success{
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div class="wrapper">
            <h3>Zip File</h3>
            <?php if (!@$_POST): ?>
                <?php
                if (empty($_POST['path'])) {
                    $path = getcwd(); // dirname(dirname(__FILE__));
                } else {
                    $path = htmlentities(@$_POST['path']);
                }
                if (empty($_POST['zipPath'])) {
                    $zipPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'zip' . DIRECTORY_SEPARATOR;
                } else {
                    $zipPath = htmlentities(@$_POST['zipPath']);
                }
                ?>
                <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
                    <fieldset>
                        <label for="path">Base Path:</label><br/>
                        <input type="text" name="path" id="path" value="<?php echo $path; ?>" style="width: 350px" required/>
                        <br/>
                        <label for="folder">Folders (using ',' to  seperate multiple folder):</label><br/>
                        <input type="text" name="folder" id="folder" value="<?php echo (@$_POST['folder']) ? htmlentities(@$_POST['folder']) : ''; ?>" style="width: 350px" required/>
                        <br/>
                        <label for="zipPath">Zip To:</label><br/>
                        <input type="text" name="zipPath" id="zipPath" value="<?php echo (@$_POST['zipPath']) ? htmlentities(@$_POST['zipPath']) : $zipPath; ?>" style="width: 350px" required/>
                        <br/><br/>
                        <input type="submit" value="Submit" class="btn"/>
                    </fieldset>
                </form>
            <?php else: ?>
                <?php
                $path = htmlentities(@$_POST['path']);
                $path = rtrim($path, '\/');
                $base = $path . DIRECTORY_SEPARATOR;
                if (!is_dir($path)) {
                    echo '<div class="error">Error: Invalid path</div>';
                } else {
                    $folder = $_POST['folder'];

                    $drs = explode(',', $folder);
                    array_walk($drs, 'trim');
                    $error = array();
                    foreach ($drs as $dr) {
                        $dr = trim($dr);
                        $fl = $base . $dr;
                        if (!is_dir($fl)) {
                            $error[] = 'Error: ' . $dr . ' is not an directory';
                        }
                    }
                    if (!empty($error)) {
                        echo implode('<br/>', $error);
                    } else {
                        $zipPath = htmlentities(@$_POST['zipPath']);
                        if (!is_dir($zipPath)) {
                            mkdir($zipPath, 0775, true);
                        }
                        $zipPath = rtrim($zipPath, '\/');
                        $zipPath = $zipPath . DIRECTORY_SEPARATOR;
                        foreach ($drs as $dir) {
                            $dir = trim($dir);
                            $input = $base . $dir;
                            $output = $zipPath . $dir . '.zip';
                            
                            ZipHelper::zipDir($input, $output);
							echo $dir . ' - ziped <br/>';
                            ob_flush();
                            flush();
                        }

                        print '<div class="success">Zip completed.</div>';
                    }
                }
                ?>


            <?php endif; ?>
        </div>
    </body>
</html>