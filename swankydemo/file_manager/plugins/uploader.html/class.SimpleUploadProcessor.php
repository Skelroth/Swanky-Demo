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
 * Processor for standard POST upload
 * @package AjaXplorer_Plugins
 * @subpackage Uploader
 */
class SimpleUploadProcessor extends AJXP_Plugin {
	
	public function getDropBg($action, $httpVars, $fileVars){
		$lang = ConfService::getLanguage();
		$img = AJXP_INSTALL_PATH."/plugins/uploader.html/i18n/$lang-dropzone.png";
		if(!is_file($img)) $img = AJXP_INSTALL_PATH."/plugins/uploader.html/i18n/en-dropzone.png";
		header("Content-Type: image/png; name=\"dropzone.png\"");
		header("Content-Length: ".filesize($img));
		header('Cache-Control: public');
		readfile($img);
	}
	
	public function preProcess($action, &$httpVars, &$fileVars){
		if(!isSet($httpVars["input_stream"])){
			return false;
		}

	    $headersCheck = isset(
	            $_SERVER['CONTENT_LENGTH'],
	            $_SERVER['HTTP_X_FILE_NAME']
	        ) ;
        if(isSet($_SERVER['HTTP_X_FILE_SIZE'])){
            if($_SERVER['CONTENT_LENGTH'] != $_SERVER['HTTP_X_FILE_SIZE'])  {
                exit('Warning, wrong headers');
            }
        }
	    $fileNameH = $_SERVER['HTTP_X_FILE_NAME'];
	    $fileSizeH = $_SERVER['CONTENT_LENGTH'];

        if(dirname($httpVars["dir"]) == "/" && basename($httpVars["dir"]) == $fileNameH){
            $httpVars["dir"] = "/";
        }
        AJXP_Logger::debug("SimpleUpload::preProcess", $httpVars);

        if($headersCheck){
	        // create the object and assign property
        	$fileVars["userfile_0"] = array(
        		"input_upload" => true,
        		"name"		   => SystemTextEncoding::fromUTF8(basename($fileNameH)),
        		"size"		   => $fileSizeH
        	);
	    }else{
	    	exit("Warning, missing headers!");
	    }
	}
	
	public function postProcess($action, $httpVars, $postProcessData){
		if(!isSet($httpVars["simple_uploader"]) && !isSet($httpVars["xhr_uploader"])){
			return false;
		}
		AJXP_Logger::debug("SimpleUploadProc is active");
		$result = $postProcessData["processor_result"];
		
		if(isSet($httpVars["simple_uploader"])){	
			print("<html><script language=\"javascript\">\n");
			if(isSet($result["ERROR"])){
				$message = $result["ERROR"]["MESSAGE"]." (".$result["ERROR"]["CODE"].")";
				print("\n if(parent.ajaxplorer.actionBar.multi_selector) parent.ajaxplorer.actionBar.multi_selector.submitNext('".str_replace("'", "\'", $message)."');");		
			}else{
				print("\n if(parent.ajaxplorer.actionBar.multi_selector) parent.ajaxplorer.actionBar.multi_selector.submitNext();");
                if($result["CREATED_NODE"]){
                    $s = '<tree>';
                    $s .= AJXP_XMLWriter::writeNodesDiff(array("ADD"=> array($result["CREATED_NODE"])), false);
                    $s.= '</tree>';
                    print("\n var resultString = '".$s."'; var resultXML = parent.parseXml(resultString);");
                    print("\n parent.ajaxplorer.actionBar.parseXmlMessage(resultXML);");
                }
			}
			print("</script></html>");
		}else{
			if(isSet($result["ERROR"])){
				$message = $result["ERROR"]["MESSAGE"]." (".$result["ERROR"]["CODE"].")";
				exit($message);
			}else{
                AJXP_XMLWriter::header();
                if(isSet($result["CREATED_NODE"])){
                    AJXP_XMLWriter::writeNodesDiff(array("ADD" => array($result["CREATED_NODE"])), true);
                }
                AJXP_XMLWriter::close();
                /* for further implementation */
                if(!isset($httpVars["prevent_notification"])){
                    AJXP_Controller::applyHook("node.change", array(null, $result["CREATED_NODE"], false));
                }
                //exit("OK");
			}
		}
		
	}	
	
	public function unifyChunks($action, $httpVars, $fileVars){
		$repository = ConfService::getRepository();
		if(!$repository->detectStreamWrapper(false)){
			return false;
		}
		$plugin = AJXP_PluginsService::findPlugin("access", $repository->getAccessType());
		$streamData = $plugin->detectStreamWrapper(true);		
		$dir = AJXP_Utils::decodeSecureMagic($httpVars["dir"]);
    	$destStreamURL = $streamData["protocol"]."://".$repository->getId().$dir."/";    	
		$filename = AJXP_Utils::decodeSecureMagic($httpVars["file_name"]);
		$chunks = array();
		$index = 0;
		while(isSet($httpVars["chunk_".$index])){
			$chunks[] = AJXP_Utils::decodeSecureMagic($httpVars["chunk_".$index]);
			$index++;
		}
		
		$newDest = fopen($destStreamURL.$filename, "w");
		for ($i = 0; $i < count($chunks) ; $i++){
			$part = fopen($destStreamURL.$chunks[$i], "r");
			while(!feof($part)){
				fwrite($newDest, fread($part, 4096));
			}
			fclose($part);
			unlink($destStreamURL.$chunks[$i]);
		}
		fclose($newDest);
        AJXP_Controller::applyHook("node.change", array(null, new AJXP_Node($newDest), false));
	}
}
?>
