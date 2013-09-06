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
 *
 * Description : Command line access of the framework.
 */
if(php_sapi_name() !== "cli"){
	die("This is the command line version of the framework, you are not allowed to access this page");
}

include_once("base.conf.php");

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
//set_error_handler(array("AJXP_XMLWriter", "catchError"), E_ALL & ~E_NOTICE );
//set_exception_handler(array("AJXP_XMLWriter", "catchException"));

$pServ = AJXP_PluginsService::getInstance();
ConfService::init();
$confPlugin = ConfService::getInstance()->confPluginSoftLoad($pServ);
$pServ->loadPluginsRegistry(AJXP_INSTALL_PATH."/plugins", $confPlugin);
ConfService::start();


$confStorageDriver = ConfService::getConfStorageImpl();
require_once($confStorageDriver->getUserClassFileName());
//session_name("AjaXplorer");
//session_start();


$optArgs = array();
$options = array();
$regex = '/^-(-?)([a-zA-z0-9_]*)=(.*)/';
foreach ($argv as $key => $argument){
    //echo("$key => $argument \n");
	if(preg_match($regex, $argument, $matches)){
		if($matches[1] == "-"){
			$optArgs[trim($matches[2])] = SystemTextEncoding::toUTF8(trim($matches[3]));
		}else{
			$options[trim($matches[2])] = SystemTextEncoding::toUTF8(trim($matches[3]));
		}
	}
}

$optUser = $options["u"];

if(isSet($options["p"])){
	$optPass = $options["p"];
}else{
	// Consider "u" is a crypted version of u:p
	$optToken = $options["t"];
	$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND);
    $optUser = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($optToken."\1CDAFx¨op#"), base64_decode($optUser), MCRYPT_MODE_ECB, $iv), "\0");
}
if(strpos($optUser,",") !== false){
    $originalOptUser = $optUser;
    $nextUsers = explode(",", $optUser);
    $optUser = array_shift($nextUsers);
    $nextUsers = implode(",",$nextUsers);
}else if(strpos($optUser, "queue:") === 0){
    $optUserQueue = substr($optUser, strlen("queue:"));
    $optUser = false;
    //echo("QUEUE : ".$optUserQueue);
    if(is_file($optUserQueue)){
        $lines = file($optUserQueue);
        if(count($lines) && !empty($lines[0])){
            $allUsers = explode(",", $lines[0]);
            $optUser = array_shift($allUsers);
            file_put_contents($optUserQueue, implode(",", $allUsers));
        }
    }
    if($optUser === false){
        if(is_file($optUserQueue)){
            unlink($optUserQueue);
        }
        die("No more users inside queue");
    }
}


$optStatusFile = $options["s"] OR false;
$optAction = $options["a"];
$optRepoId = $options["r"] OR false;
if(strpos($optRepoId,",") !== false){
    $nextRepositories = explode(",", $optRepoId);
    $optRepoId = array_shift($nextRepositories);
    $nextRepositories = implode(",", $nextRepositories);
}

//echo("REPOSITORY : ".$optRepoId." USER : ".$optUser."\n");

$optDetectUser = $options["detect_user"] OR false;
$detectedUser = false;

if($optRepoId !== false){
	$repository = ConfService::getRepositoryById($optRepoId);
	if($repository == null){
		$repository = ConfService::getRepositoryByAlias($optRepoId);
		if($repository != null){
			$optRepoId =($repository->isWriteable()?$repository->getUniqueId():$repository->getId());
		}
	}
    if($optDetectUser != false){
        $path = $repository->getOption("PATH", true);
        if(strpos($path, "AJXP_USER") !== false){
            $path = str_replace(
                array("AJXP_INSTALL_PATH", "AJXP_DATA_PATH", "/"),
                array(AJXP_INSTALL_PATH, AJXP_DATA_PATH, DIRECTORY_SEPARATOR),
                $path
            );
            $parts = explode("AJXP_USER", $path);
            if(count($parts) == 1) $parts[1] = "";
            $first = str_replace("\\", "\\\\", $parts[0]);
            $last = str_replace("\\", "\\\\", $parts[1]);
            if(preg_match("/$first(.*)$last.*/", $optDetectUser, $matches)){
                $detectedUser = $matches[1];
            }
        }
    }
	ConfService::switchRootDir($optRepoId, true);
}else{
    if($optStatusFile){
        file_put_contents($optStatusFile, "ERROR:You must pass a -r argument specifying either a repository id or alias");
    }
    die("You must pass a -r argument specifying either a repository id or alias");
}

if(AuthService::usersEnabled())
{
	$seed = AuthService::generateSeed();
	if($seed != -1){
		$optPass = md5(md5($optPass).$seed);
	}
	$loggingResult = AuthService::logUser($optUser, $optPass, isSet($optToken), false, $seed);
	// Check that current user can access current repository, try to switch otherwise.
	$loggedUser = AuthService::getLoggedUser();
    if($loggedUser != null && $detectedUser !== false && $loggedUser->isAdmin()){
        AuthService::disconnect();
        AuthService::logUser($detectedUser, "empty", true, false, "");
        $loggedUser = AuthService::getLoggedUser();
    }

	if($loggedUser != null)
	{
		$currentRepoId = ConfService::getCurrentRepositoryId();
		$lastRepoId  = $loggedUser->getArrayPref("history", "last_repository");
		$defaultRepoId = AuthService::getDefaultRootId();
		if($defaultRepoId == -1){
			AuthService::disconnect();
			$loggingResult = -3;
		}else {
			if($lastRepoId != "" && $lastRepoId!=$currentRepoId && $optRepoId===false && $loggedUser->canSwitchTo($lastRepoId)){
				ConfService::switchRootDir($lastRepoId);
			}else if(!$loggedUser->canSwitchTo($currentRepoId)){
				ConfService::switchRootDir($defaultRepoId);
			}
		}
	}
	if($loggedUser == null)
	{
		// Try prelogging user if the session expired but the logging data is in fact still present
		// For example, for basic_http auth.
		AuthService::preLogUser((isSet($httpVars["remote_session"])?$httpVars["remote_session"]:""));
		$loggedUser = AuthService::getLoggedUser();
		if($loggedUser == null) $requireAuth = true;
	}
	if(isset($loggingResult) && $loggingResult != 1)
	{
		AJXP_XMLWriter::header();
		AJXP_XMLWriter::loggingResult($loggingResult, false, false, "");
		AJXP_XMLWriter::close();
        if($optStatusFile){
            file_put_contents($optStatusFile, "ERROR:No user logged");
        }
	}
}else{
	AJXP_Logger::debug(ConfService::getCurrentRepositoryId());
}

//Set language
$loggedUser = AuthService::getLoggedUser();
if($loggedUser != null && $loggedUser->getPref("lang") != "") ConfService::setLanguage($loggedUser->getPref("lang"));
else if(isSet($_COOKIE["AJXP_lang"])) ConfService::setLanguage($_COOKIE["AJXP_lang"]);
$mess = ConfService::getMessages();

// THIS FIRST DRIVERS DO NOT NEED ID CHECK
//$ajxpDriver = AJXP_PluginsService::findPlugin("gui", "ajax");
$authDriver = ConfService::getAuthDriverImpl();
// DRIVERS BELOW NEED IDENTIFICATION CHECK
if(!AuthService::usersEnabled() || ConfService::getCoreConf("ALLOW_GUEST_BROWSING", "auth") || AuthService::getLoggedUser()!=null){
	$confDriver = ConfService::getConfStorageImpl();
	$Driver = ConfService::loadRepositoryDriver();
}
AJXP_PluginsService::getInstance()->initActivePlugins();
require_once(AJXP_BIN_FOLDER."/class.AJXP_Controller.php");
$xmlResult = AJXP_Controller::findActionAndApply($optAction, $optArgs, array());
if($xmlResult !== false && $xmlResult != ""){
	AJXP_XMLWriter::header();
	print($xmlResult);
	AJXP_XMLWriter::close();
}else if(isset($requireAuth) && AJXP_Controller::$lastActionNeedsAuth){
	AJXP_XMLWriter::header();
	AJXP_XMLWriter::requireAuth();
	AJXP_XMLWriter::close();
}
//echo("NEXT REPO ".$nextRepositories." (".$options["r"].")\n");
//echo("NEXT USERS ".$nextUsers." ( ".$originalOptUser." )\n");
if(!empty($nextUsers) || !empty($nextRepositories) || !empty($optUserQueue) ){

    if(!empty($nextUsers)){
        sleep(1);
        $process = AJXP_Controller::applyActionInBackground($options["r"], $optAction, $optArgs, $nextUsers, $optStatusFile);
        if($process != null && is_a($process, "UnixProcess") && isSet($optStatusFile)){
            file_put_contents($optStatusFile, "RUNNING:".$process->getPid());
        }
    }
    if(!empty($optUserQueue)){
        sleep(1);
        //echo("Should go to next with $optUserQueue");
        $process = AJXP_Controller::applyActionInBackground($options["r"], $optAction, $optArgs, "queue:".$optUserQueue, $optStatusFile);
        if($process != null && is_a($process, "UnixProcess") && isSet($optStatusFile)){
            file_put_contents($optStatusFile, "RUNNING:".$process->getPid());
        }
    }
    if(!empty($nextRepositories)){
        sleep(1);
        $process = AJXP_Controller::applyActionInBackground($nextRepositories, $optAction, $optArgs, $originalOptUser, $optStatusFile);
        if($process != null && is_a($process, "UnixProcess") && isSet($optStatusFile)){
            file_put_contents($optStatusFile, "RUNNING:".$process->getPid());
        }
    }

}else if(isSet($optStatusFile)){

    $status = explode(":", file_get_contents($optStatusFile));
    file_put_contents($optStatusFile, "FINISHED".(in_array("QUEUED", $status)?":QUEUED":""));

}