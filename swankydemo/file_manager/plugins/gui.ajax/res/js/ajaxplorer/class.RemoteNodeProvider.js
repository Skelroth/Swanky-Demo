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

/**
 * Implementation of the IAjxpNodeProvider interface based on a remote server access.
 * Default for all repositories.
 */
Class.create("RemoteNodeProvider", {
	__implements : "IAjxpNodeProvider",
    discrete : false,
	/**
	 * Constructor
	 */
	initialize : function(){
		
	},
	/**
	 * Initialize properties
	 * @param properties Object
	 */
	initProvider : function(properties){
		this.properties = $H(properties);
        if(this.properties && this.properties.get('connexion_discrete')){
            this.discrete = true;
            this.properties.unset('connexion_discrete');
        }
	},
	/**
	 * Load a node
	 * @param node AjxpNode
	 * @param nodeCallback Function On node loaded
	 * @param childCallback Function On child added
	 */
	loadNode : function(node, nodeCallback, childCallback){
		var conn = new Connexion();
        if(this.discrete) conn.discrete = true;
		conn.addParameter("get_action", "ls");
		conn.addParameter("options", "al");
		var path = node.getPath();
		// Double encode # character
		if(node.getMetadata().get("paginationData")){
			path += "%23" + node.getMetadata().get("paginationData").get("current");
		}
		conn.addParameter("dir", path);
		if(this.properties){
			$H(this.properties).each(function(pair){
				conn.addParameter(pair.key, pair.value);
			});
		}
		conn.onComplete = function (transport){
			try{				
				this.parseNodes(node, transport, nodeCallback, childCallback);
			}catch(e){
				if(ajaxplorer) ajaxplorer.displayMessage('ERROR', 'Loading error :'+e.message);
				else alert('Loading error :'+ e.message);
			}
		}.bind(this);	
		conn.sendAsync();
	},

    /**
   	 * Load a node
   	 * @param node AjxpNode
   	 * @param nodeCallback Function On node loaded
   	 */
   	loadLeafNodeSync : function(node, nodeCallback){
   		var conn = new Connexion();
   		conn.addParameter("get_action", "ls");
   		conn.addParameter("options", "al");
   		conn.addParameter("dir", getRepName(node.getPath()));
        conn.addParameter("file", getBaseName(node.getPath()));
   		if(this.properties){
   			$H(this.properties).each(function(pair){
   				conn.addParameter(pair.key, pair.value);
   			});
   		}
   		conn.onComplete = function (transport){
   			try{
   				this.parseNodes(node, transport, null, nodeCallback, true);
   			}catch(e){
   				if(ajaxplorer) ajaxplorer.displayMessage('ERROR', 'Loading error :'+e.message);
   				else alert('Loading error :'+ e.message);
   			}
   		}.bind(this);
   		conn.sendSync();
   	},

    refreshNodeAndReplace : function(node, onComplete){

        var conn = new Connexion();
        conn.addParameter("get_action", "ls");
        conn.addParameter("options", "al");
        conn.addParameter("dir", getRepName(node.getPath()));
        conn.addParameter("file", getBaseName(node.getPath()));
        if(this.properties){
            $H(this.properties).each(function(pair){
                conn.addParameter(pair.key, pair.value);
            });
        }

        var nodeCallback = function(newNode){
            node.replaceBy(newNode, "override");
            if(onComplete) onComplete(node);
        };
        conn.onComplete = function (transport){
            try{
                this.parseNodes(node, transport, null, nodeCallback, true);
            }catch(e){
                if(ajaxplorer) ajaxplorer.displayMessage('ERROR', e.message);
                else alert(e.message);
            }
        }.bind(this);
        conn.sendAsync();

    },

	/**
	 * Parse the answer and create AjxpNodes
	 * @param origNode AjxpNode
	 * @param transport Ajax.Response
	 * @param nodeCallback Function
	 * @param childCallback Function
	 */
	parseNodes : function(origNode, transport, nodeCallback, childCallback, childrenOnly){
		if(!transport.responseXML || !transport.responseXML.documentElement) {
		    if(console){
		         console.log(transport.responseText);
		    }
		    nodeCallback(origNode);
		    origNode.setLoaded(false);
		    throw new Error('Invalid XML Document (see console)');
		}
		var rootNode = transport.responseXML.documentElement;
		var children = rootNode.childNodes;
        if(!childrenOnly){
            var contextNode = this.parseAjxpNode(rootNode);
            origNode.replaceBy(contextNode, "merge");
        }

		// CHECK FOR MESSAGE OR ERRORS
		var errorNode = XPathSelectSingleNode(rootNode, "error|message");
		if(errorNode){
			if(errorNode.nodeName == "message") type = errorNode.getAttribute('type');
			if(type == "ERROR"){
				origNode.notify("error", errorNode.firstChild.nodeValue + '(Source:'+origNode.getPath()+')');				
			}			
		}
		
		// CHECK FOR PAGINATION DATA
		var paginationNode = XPathSelectSingleNode(rootNode, "pagination");
		if(paginationNode){
			var paginationData = new Hash();
			$A(paginationNode.attributes).each(function(att){
				paginationData.set(att.nodeName, att.nodeValue);
			}.bind(this));
			origNode.getMetadata().set('paginationData', paginationData);
		}else if(origNode.getMetadata().get('paginationData')){
			origNode.getMetadata().unset('paginationData');
		}

		// CHECK FOR COMPONENT CONFIGS CONTEXTUAL DATA
		var configs = XPathSelectSingleNode(rootNode, "client_configs");
		if(configs){
			origNode.getMetadata().set('client_configs', configs);
		}		

		// NOW PARSE CHILDREN
		var children = XPathSelectNodes(rootNode, "tree");
		children.each(function(childNode){
			var child = this.parseAjxpNode(childNode);
			if(!childrenOnly) origNode.addChild(child);
			if(childCallback){
				childCallback(child);
			}
		}.bind(this) );

		if(nodeCallback){
			nodeCallback(origNode);
		}
	},
	/**
	 * Parses XML Node and create AjxpNode
	 * @param xmlNode XMLNode
	 * @returns AjxpNode
	 */
	parseAjxpNode : function(xmlNode){
		var node = new AjxpNode(
			xmlNode.getAttribute('filename'), 
			(xmlNode.getAttribute('is_file') == "1" || xmlNode.getAttribute('is_file') == "true"), 
			xmlNode.getAttribute('text'),
			xmlNode.getAttribute('icon'));
		var reserved = ['filename', 'is_file', 'text', 'icon'];
		var metadata = new Hash();
		for(var i=0;i<xmlNode.attributes.length;i++)
		{
			metadata.set(xmlNode.attributes[i].nodeName, xmlNode.attributes[i].nodeValue);
			if(Prototype.Browser.IE && xmlNode.attributes[i].nodeName == "ID"){
				metadata.set("ajxp_sql_"+xmlNode.attributes[i].nodeName, xmlNode.attributes[i].nodeValue);
			}
		}
		// BACKWARD COMPATIBILIY
		//metadata.set("XML_NODE", xmlNode);
		node.setMetadata(metadata);
		return node;
	}
});