<?php
/*
 * Copyright 2007-2011 Charles du Jeu <contact (at) cdujeu.me>, mosen
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

if(!defined('LOG_LEVEL_DEBUG')){
	define("LOG_LEVEL_DEBUG", "Debug");
	define("LOG_LEVEL_INFO", "Info");
	define("LOG_LEVEL_NOTICE", "Notice");
	define("LOG_LEVEL_WARNING", "Warning");
	define("LOG_LEVEL_ERROR", "Error");
}

/**
 * @package AjaXplorer_Plugins
 * @subpackage Core
 * @class AbstractLogDriver
 * @author mosen
 * @abstract
 * Abstraction of the logging system
 * The output stream/file/device will be implemented by the plugin which extends this class.
 * The object has a chance to open its stream or file from the init() method. all subsequent calls assume
 * the availability of the stream or file.
 */
class AbstractLogDriver extends AJXP_Plugin {

	/**
	 * Driver type
	 *
	 * @var String type of driver
	 */
	var $driverType = "log";

	/**
	 * Write an entry to the log.
	 *
	 * @param String $textMessage The message to log
	 * @param String $severityLevel The severity level, see LOG_LEVEL_ constants
	 * 
	 */
	function write($textMessage, $severityLevel = LOG_LEVEL_DEBUG) {}
	
	
	/**
	 * Format an array as a readable string
	 * 
	 * Base implementation which can be used by other loggers to format arrays of parameters
	 * nicely.
	 *
	 * @param Array $params
	 * @return String readable list of parameters.
	 */
	function arrayToString($params){
		$st = "";	
		$index=0;	
		foreach ($params as $key=>$value){
			$index++;
			if(!is_numeric($key)){
				$st.="$key=";
			}
			if(is_string($value) || is_numeric($value)){				
				$st.=$value;
			}else if(is_array($value)){
				$st.=$this->arrayToString($value);
			}else if(is_bool($value)){
				$st.=($value?"true":"false");
			}else if(is_a($value, "UserSelection")){
				$st.=$this->arrayToString($value->getFiles());
			}
			
			if($index < count($params)){
				if(is_numeric($key)){
					$st.=",";
				}else{
					$st.=";";
				}
			}
		}
		return $st;
		
	}

    /**
     * List available log files in XML
     *
     * @param string $nodeName
     * @param null $year
     * @param null $month
     * @param string $rootPath
     * @return void
     * @internal param $String [optional] $nodeName
     * @internal param $String [optional] $year
     * @internal param $String [optional] $month
     */
	function xmlListLogFiles($nodeName="file", $year=null, $month=null, $rootPath = "/logs", $print = true){}

    /**
     * List log contents in XML
     *
     * @param $parentDir
     * @param String $date Assumed to be m-d-y format.
     * @param string $nodeName
     * @param string $rootPath
     * @return void
     * @internal param $String [optional] $nodeName
     */
	function xmlLogs($parentDir, $date, $nodeName = "log", $rootPath = "/logs"){}
}