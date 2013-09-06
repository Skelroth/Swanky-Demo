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
 * Remote files downloader
 * @package AjaXplorer_Plugins
 * @subpackage Downloader
 */
class HttpDownloader extends AJXP_Plugin{

	public function switchAction($action, $httpVars, $fileVars){
		//AJXP_Logger::logAction("DL file", $httpVars);
				
		$repository = ConfService::getRepository();
		if(!$repository->detectStreamWrapper(false)){
			return false;
		}
		$plugin = AJXP_PluginsService::findPlugin("access", $repository->getAccessType());
		$streamData = $plugin->detectStreamWrapper(true);		
		$dir = AJXP_Utils::decodeSecureMagic($httpVars["dir"]);
    	$destStreamURL = $streamData["protocol"]."://".$repository->getId().$dir."/";
    	if(isSet($httpVars["file"])){
			$parts = parse_url($httpVars["file"]);
    		$getPath = $parts["path"];
			$basename = basename($getPath);
    	}		
    	if(isSet($httpVars["dlfile"])){
            $dlFile = $streamData["protocol"]."://".$repository->getId().AJXP_Utils::decodeSecureMagic($httpVars["dlfile"]);
            $realFile = file_get_contents($dlFile);
            if(empty($realFile)) throw new Exception("cannot find file $dlFile for download");
            $parts = parse_url($realFile);
            $getPath = $parts["path"];
            $basename = basename($getPath);
    	}
    	
    	switch ($action){
    		case "external_download":
				if(!ConfService::currentContextIsCommandLine() && ConfService::backgroundActionsSupported()){
										
					$unixProcess = AJXP_Controller::applyActionInBackground($repository->getId(), "external_download", $httpVars);
					if($unixProcess !== null){
                        @file_put_contents($destStreamURL.".".$basename.".pid", $unixProcess->getPid());
                    }
					AJXP_XMLWriter::header();
					AJXP_XMLWriter::triggerBgAction("reload_node", array(), "Triggering DL ", true, 2);
					AJXP_XMLWriter::close();
                    session_write_close();
					exit();
				}
				    					    	
				require_once AJXP_BIN_FOLDER."/class.HttpClient.php";
				$mess = ConfService::getMessages();		
				session_write_close();
				
				$client = new HttpClient($parts["host"]);		
				$collectHeaders = array(
					"ajxp-last-redirection" => "",
					"content-disposition"	=> "",
					"content-length"		=> ""
				);
				$client->setHeadersOnly(true, $collectHeaders);
				$client->setMaxRedirects(8);
				$client->setDebug(false);
				$client->get($getPath);
				
				$pidHiddenFileName = $destStreamURL.".".$basename.".pid";
				if(is_file($pidHiddenFileName)){
					$pid = file_get_contents($pidHiddenFileName);
					@unlink($pidHiddenFileName);
				}
				
				AJXP_Logger::debug("COLLECTED HEADERS", $client->collectHeaders);
				$collectHeaders = $client->collectHeaders;
				$totalSize = -1;
		    	if(!empty($collectHeaders["content-disposition"]) && strstr($collectHeaders["content-disposition"], "filename")!== false){
                    $ar = explode("filename=", $collectHeaders["content-disposition"]);
		    		$basename = trim(array_pop($ar));
		    		$basename = str_replace("\"", "", $basename); // Remove quotes
		    	}
		    	if(!empty($collectHeaders["content-length"])){
		    		$totalSize = intval($collectHeaders["content-length"]);
		    		AJXP_Logger::debug("Should download $totalSize bytes!");
		    	}
                if($totalSize != -1){
                    $node = new AJXP_Node($destStreamURL.$basename);
                    AJXP_Controller::applyHook("node.before_create", array($node, $totalSize));
                }
				$qData = false;
				if(!empty($collectHeaders["ajxp-last-redirection"])){
					$newParsed = parse_url($collectHeaders["ajxp-last-redirection"]);
					$client->host = $newParsed["host"];
					$getPath = $newParsed["path"];
					if(isset($newParsed["query"])){
						$qData = parse_url($newParsed["query"]);
					}
				}
				$tmpFilename = $destStreamURL.$basename.".dlpart";
				$hiddenFilename = $destStreamURL."__".$basename.".ser";
				$filename = $destStreamURL.$basename;
				
				$dlData = array(
					"sourceUrl" => $getPath,
					"totalSize" => $totalSize
				);
				if(isSet($pid)){
					$dlData["pid"] = $pid;
				}
				//file_put_contents($hiddenFilename, serialize($dlData));
                $fpHid=fopen($hiddenFilename,"w");
                fputs($fpHid,serialize($dlData));
                fclose($fpHid);

				
				$client->redirect_count = 0;
				$client->setHeadersOnly(false);
				
				$destStream = fopen($tmpFilename, "w");
				if($destStream !== false){
		
					$client->writeContentToStream($destStream);			
					$client->get($getPath, $qData);			
					fclose($destStream);
							
				}
				rename($tmpFilename, $filename);
				unlink($hiddenFilename);
				if(isset($dlFile) && isSet($httpVars["delete_dlfile"]) && is_file($dlFile)){
                    AJXP_Controller::applyHook("node.before_path_change", array(new AJXP_Node($dlFile)));
					unlink($dlFile);
                    AJXP_Controller::applyHook("node.change", array(new AJXP_Node($dlFile), null, false));
				}
                AJXP_Controller::applyHook("node.change", array(null, new AJXP_Node($filename), false));
				AJXP_XMLWriter::header();
				AJXP_XMLWriter::triggerBgAction("reload_node", array(), $mess["httpdownloader.8"]);
				AJXP_XMLWriter::close();
				exit();
    		break;
    		case "update_dl_data":
    			$file = AJXP_Utils::decodeSecureMagic($httpVars["file"]);
    			header("text/plain");
    			if(is_file($destStreamURL.$file)){ 	   			
    				echo filesize($destStreamURL.$file);
    			}else{
    				echo "stop";
    			}
    			exit;
    		break;
    		case "stop_dl":
				$newName = "__".str_replace(".dlpart", ".ser", $basename);
    			$hiddenFilename = $destStreamURL.$newName;
    			$data = @unserialize(@file_get_contents($hiddenFilename));
    			header("text/plain");
    			AJXP_Logger::debug("Getting $hiddenFilename",$data);
    			if(isSet($data["pid"])){
    				$process = new UnixProcess();
    				$process->setPid($data["pid"]);
    				$process->stop();
    				unlink($hiddenFilename);
    				unlink($destStreamURL.$basename);
    				echo 'stop';
    			}else{
    				echo 'failed';
    			}
    			exit();
    		break;
    		default:
    		break;
    	}
    	
		return true;    			
    	
	}
	
	/**
	 * @param AJXP_Node $ajxpNode
	 */
	public function detectDLParts(&$ajxpNode){
		if(!preg_match("/\.dlpart$/i",$ajxpNode->getUrl())){
			return;
		}
		$basename = basename($ajxpNode->getUrl());
		$newName = "__".str_replace(".dlpart", ".ser", $basename);
		$hidFile = str_replace($basename, $newName, $ajxpNode->getUrl());
		if(is_file($hidFile)){
			$data = unserialize(file_get_contents($hidFile));
			if($data["totalSize"] != -1){
				$ajxpNode->target_bytesize = $data["totalSize"];
				$ajxpNode->target_filesize = AJXP_Utils::roundSize($data["totalSize"]);
				$ajxpNode->process_stoppable = (isSet($data["pid"])?"true":"false");
			}
		}		
	}
}

?>