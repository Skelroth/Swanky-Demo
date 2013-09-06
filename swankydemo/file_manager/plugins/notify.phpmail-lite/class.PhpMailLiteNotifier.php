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
 * Send notifications to user on some predefined actions
 * @package AjaXplorer_Plugins
 * @subpackage Notify
 */
class PhpMailLiteNotifier extends AJXP_Plugin {

    public function loadRegistryContributions(){
        $currentUser = AuthService::getLoggedUser();
        if($currentUser != null){
            $cData = $currentUser->getPref("CUSTOM_PARAMS");
            if($cData != null && isSet($cData["email"])){
                $this->exposeConfigInManifest("current_user_email", $cData["email"]);
            }
        }
        $actionsBranch = $this->xPath->query("registry_contributions/actions");
        $actionsNode = $actionsBranch->item(0);
        foreach(array_map("trim", explode(",",$this->pluginConf["ACTIONS"])) as $action){
            // Action node
            $prop = $this->manifestDoc->createElement("action");
            $attName = $this->manifestDoc->createAttribute("name");
            $attValue = $this->manifestDoc->createTextNode($action);
            $attName->appendChild($attValue);
            $prop->appendChild($attName);
            $actionsNode->appendChild($prop);
            // Pre_proc
            $preproc = $this->manifestDoc->createElement("pre_processing");
            $prop->appendChild($preproc);
            // Server callback
            $sC = $this->manifestDoc->createElement("serverCallback");
            $sAttName = $this->manifestDoc->createAttribute("methodName");
            $sAttValue = $this->manifestDoc->createTextNode("preProcess");
            $sAttName->appendChild($sAttValue);
            $sC->appendChild($sAttName);
            $preproc->appendChild($sC);
        }
        $this->reloadXPath();
        parent::loadRegistryContributions();


    }

	public function preProcess($action, $httpVars, $fileVars){
		if(!is_array($this->pluginConf)){
			throw new Exception("Cannot find configuration for plugin notify.phpmail-lite! Make sur that you have filled the options in the GUI, or that the .inc file was dropped inside the /conf/ folder!");
		}
        if( $action == "upload" &&  !isSet($httpVars["dir"])) return;
		require("lib/class.phpmailer-lite.php");

        // Parse options
        if(is_string($this->pluginConf["FROM"])) {
            $this->pluginConf["FROM"] = $this->parseStringOption($this->pluginConf["FROM"]);
        }
        if(is_string($this->pluginConf["TO"]) && $this->pluginConf["TO"] != ""){
            $froms = explode(",", $this->pluginConf["TO"]);
            $this->pluginConf["TO"] = array_map(array($this, "parseStringOption"), $froms);
        }

        $recipients = $this->pluginConf["TO"];

        if($this->pluginConf["SHARE"]){
            if(isSet($httpVars["PLUGINS_DATA"])){
                $pData = $httpVars["PLUGINS_DATA"];
            }else{
                $repo = ConfService::getRepository();
                $pData = $repo->getOption("PLUGINS_DATA");
            }
            if($pData != null && isSet($pData["SHARE_NOTIFICATION_ACTIVE"]) && isSet($pData["SHARE_NOTIFICATION_EMAIL"]) && $pData["SHARE_NOTIFICATION_ACTIVE"] == "on"){
                $emails = array_map(array($this, "parseStringOption"), explode(",", $pData["SHARE_NOTIFICATION_EMAIL"]));
                if(is_array($recipients)){
                    $recipients = array_merge($recipients, $emails);
                }else{
                    $recipients = $emails;
                }
            }
        }

        if($recipients == "" || !count($recipients)) {
            return;
        }


        // NOW IF THERE ARE RECIPIENTS FOR ANY REASON, GO
		$mail = new PHPMailerLite(true);
		$mail->Mailer = $this->pluginConf["MAILER"];		
		$mail->SetFrom($this->pluginConf["FROM"]["address"], $this->pluginConf["FROM"]["name"]);
		
		foreach ($recipients as $address){
			$mail->AddAddress($address["address"], $address["name"]);
		}		
		$mail->WordWrap = 50;                                 // set word wrap to 50 characters
		$mail->IsHTML(true);                                  // set email format to HTML
		
        $userSelection = new UserSelection();
        $userSelection->initFromHttpVars($httpVars);
        if($action == "upload" && !isSet($httpVars["simple_uploader"]) && !isSet($httpVars["xhr_uploader"])){
            // FLEX UPLOADER, BASE64 DECODE!
            if(isset($fileVars["userfile_0"])){
               $file = $fileVars["userfile_0"]["name"];
            }else if(isSet($httpVars["Filename"])){
                $file = $httpVars["Filename"];
            }
            $folder = base64_decode($httpVars["dir"]);
        }else{
            $folder = $httpVars["dir"];
            $file = "";
            if(!$userSelection->isEmpty()){
                $file = implode(",", array_map("basename", $userSelection->getFiles()));
                if($folder == null){
                    $folder = dirname($userSelection->getUniqueFile());
                }
            }
            if($action == "upload" && isset($fileVars["userfile_0"])){
                $file = $fileVars["userfile_0"]["name"];
            }
        }

        $directUrl = "";
        if(!empty($this->pluginConf["SERVER_URL"])){
            $directUrl = rtrim($this->pluginConf["SERVER_URL"], "/")."/?repository_id=".ConfService::getRepository()->getId()."&folder=". urlencode($folder);
            AJXP_Logger::debug($directUrl);
        }

        $subject = array("%user", "AJXP_USER", "AJXP_FILE", "AJXP_FOLDER_URL", "AJXP_FOLDER", "AJXP_ACTION","AJXP_REPOSITORY");
        $replace = array(AuthService::getLoggedUser()->getId(),
            AuthService::getLoggedUser()->getId(),
            $file,
            $directUrl,
            $folder,
            $action,
            ConfService::getRepository()->getDisplay(),
        );
        $body = str_replace($subject, $replace, $this->pluginConf["BODY"]);
        AJXP_Logger::debug($body);
        $mail->Subject = str_replace($subject, $replace, $this->pluginConf["SUBJECT"]);
		$mail->Body = nl2br($body);
		$mail->AltBody = strip_tags($mail->Body);
		
		if(!$mail->Send())
		{
			$message = "Message could not be sent. <p>";
			$message .= "Mailer Error: " . $mail->ErrorInfo;
			throw new Exception($message);
		}
		
	}

    /**
     * @param $option String
     * @return array
     */
    function parseStringOption($option){
        if(strstr($option, ":")){
            list($name, $ad) = explode(":", $option);
            $option = array("address" => trim($ad), "name" => trim($name));
        }else{
            $option = array("address" => trim($option), "name" => trim($option));
        }
        return $option;
    }

}

?>