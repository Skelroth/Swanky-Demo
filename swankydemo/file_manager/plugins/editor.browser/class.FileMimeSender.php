<?php
/*
 * Copyright 2007-2011 Charles du Jeu <contact (at) cdujeu.me>
 * This file is part of AjaXplorer.
 *
 * AjaXplorer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AjaXplorer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with AjaXplorer.  If not, see <http://www.gnu.org/licenses/>.
 *
 * The latest code can be found at <http://www.ajaxplorer.info/>.
 */

defined('AJXP_EXEC') or die( 'Access not allowed');

/**
 * Extract the mimetype of a file and send it to the browser
 * @package AjaXplorer_Plugins
 * @subpackage Editor
 */
class FileMimeSender extends AJXP_Plugin {
    
    public function switchAction($action, $httpVars, $filesVars) {

        if(!isSet($this->actions[$action]))
            return false;

        $repository = ConfService::getRepositoryById($httpVars["repository_id"]);

        if(!$repository->detectStreamWrapper(true))
            return false;
        
        if(AuthService::usersEnabled()){
            $loggedUser = AuthService::getLoggedUser();
            if($loggedUser === null && ConfService::getCoreConf("ALLOW_GUEST_BROWSING", "auth")){
                AuthService::logUser("guest", null);
                $loggedUser = AuthService::getLoggedUser();
            }
            if(!$loggedUser->canSwitchTo($repository->getId())){
                echo("You do not have permissions to access this resource");
                return false;
            }
        }
        
        $streamData = $repository->streamData;
        $destStreamURL = $streamData["protocol"] . "://" . $repository->getId();
        
        if($action == "open_file") {
            $file = AJXP_Utils::decodeSecureMagic($httpVars["file"]);
            if(!file_exists($destStreamURL . $file)){
                echo("File does not exist");
                return false;
            }

            $filesize = filesize($destStreamURL . $file);
            $fp = fopen($destStreamURL . $file, "rb");
            
            //Get mimetype with fileinfo PECL extension
            if(class_exists("finfo")) {
                $finfo = new finfo(FILEINFO_MIME);
                $fileMime = $finfo->buffer(fread($fp, 100));
            }
            //Get mimetype with (deprecated) mime_content_type
            elseif(function_exists("mime_content_type")) {
                $fileMime = @mime_content_type($fp);
            }
            //Guess mimetype based on file extension
            else {
                $fileExt = substr(strrchr(basename($file), '.'), 1);
                if(empty($fileExt))
                    $fileMime = "application/octet-stream";
                else {
                    $regex = "/^([\w\+\-\.\/]+)\s+(\w+\s)*($fileExt\s)/i";
                    $lines = file( $this->getBaseDir()."/resources/other/mime.types");
                    foreach($lines as $line) {
                        if(substr($line, 0, 1) == '#')
                            continue; // skip comments
                        $line = rtrim($line) . " ";
                        if(!preg_match($regex, $line, $matches))
                            continue; // no match to the extension
                        $fileMime = $matches[1];
                    }
                }
            }
            fclose($fp);
            // If still no mimetype, give up and serve application/octet-stream
            if(empty($fileMime))
                $fileMime = "application/octet-stream";
                
            //Send headers
            HTMLWriter::generateInlineHeaders(basename($file), $filesize, $fileMime);

            $class = $streamData["classname"];
            $stream = fopen("php://output", "a");
            call_user_func(array($streamData["classname"], "copyFileInStream"), $destStreamURL . $file, $stream);
            fflush($stream);
            fclose($stream);

            $node = new AJXP_Node($destStreamURL.$file);
            AJXP_Controller::applyHook("node.read", array($node));

        }
    }
}

?>