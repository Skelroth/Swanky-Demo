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
Class.create("AudioPreviewer", AbstractEditor, {

	fullscreenMode: false,
	
	initialize: function($super, oFormObject){
	},
		
	getPreview : function(ajxpNode, rich){
		if(rich){			
			var escapedFilename = base64_encode(ajxpNode.getPath());
			var player = 'dewplayer-bubble.swf';
			var flashVars = 'mp3='+ajxpBootstrap.parameters.get('ajxpServerAccess')+'%26get_action=audio_proxy%26file='+escapedFilename+'&amp;showtime=1';
			var playerWidth = '250';
			var playerHeight = '65';
			var containerStyle = 'padding:5 0px; margin-bottom: 5px;';
			if(!rich){
				player = 'dewplayer.swf';
				flashVars = 'mp3='+ajxpBootstrap.parameters.get('ajxpServerAccess')+'%26get_action=audio_proxy%26file='+escapedFilename+'&amp;nopointer=1';
				playerWidth = '40';
				playerHeight = '20';
				containerStyle = '';				
			}
			var div = new Element('div', {id:"mp3_container", style:"text-align:center;"+containerStyle});
			var content = '<object type="application/x-shockwave-flash"';
			content += 'data="plugins/editor.audio/'+player+'" width="'+playerWidth+'" height="'+playerHeight+'" id="dewplayer" name="dewplayer">';
			content += '<param name="wmode" value="transparent"/>';
			content += '<param name="flashvars" value="'+flashVars+'"/>';
			content += '<param name="movie" value="plugins/editor.audio/'+player+'" />';
			content += '</object>';
			div.update(content);
			if(rich){
				div.resizePreviewElement = function(dimensionObject){};
			}else{
				div.resizePreviewElement = function(dimensionObject){					
					var imgDim = {width:800, height:400};
					var styleObj = fitRectangleToDimension(imgDim, dimensionObject);
					// fix width artificially
					if(div.getOffsetParent()){
						styleObj.width = div.getOffsetParent().getWidth()-2 + 'px';
					}
					div.setStyle(styleObj);
				};
			}			
			return div;
		}else{
			return new Element('img', {src:resolveImageSource(ajxpNode.getIcon(),'/images/mimes/ICON_SIZE',64),align:"absmiddle"});
		}
	},
	
	getThumbnailSource : function(ajxpNode){
		return resolveImageSource(ajxpNode.getIcon(),'/images/mimes/ICON_SIZE',64);
	},
	
	createFolderPlayer : function(ajxpNode){
		var template = new Template('<head><title>#{window_title}</title></head><body style="margin:0px; padding:10px;"><div style=\"font-family:Trebuchet MS, sans-serif; color:#79f; font-size:15px; font-weight:bold;\">#{window_title}</div><div style="font-family:Trebuchet MS, sans-serif; color:#666; font-size:10px; padding-bottom: 10px;">#{reading_folder}: #{current_folder}</div><object type="application/x-shockwave-flash" data="plugins/editor.audio/dewplayer-playlist.swf" width="240" height="200"><param name="wmode" value="transparent"><param name="movie" value="plugins/editor.audio/dewplayer-playlist.swf"/><param name="flashvars" value="xml=#{playlist_url}&amp;showtime=true&amp;autoreplay=true&amp;autoplay=true"/></object></body>');
		var newWin = window.open('#', '_blank', 'width=260,height=270,directories=no,location=no,menubar=no,resizeable=yes,scrollbars=no,status=no,toolbar=no');
		try{
			var playlist_url = ajxpBootstrap.parameters.get('ajxpServerAccess')+'%26get_action=ls%26skip_history=true%26playlist=true%26dir='+base64_encode(ajxpNode.getPath());
			newWin.document.write(template.evaluate({
				window_title : "AjaXplorer MP3 Player",
				reading_folder : MessageHash[141],
				playlist_url:playlist_url, 
				current_folder:ajxpNode.getLabel()
			}));
			newWin.document.close();
		}catch(e){
			alert(e);
		}		
	}
	
});