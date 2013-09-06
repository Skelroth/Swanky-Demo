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
 * The Search Engine abstraction.
 */
Class.create("SearchEngine", AjxpPane, {

	/**
	 * @var HTMLElement
	 */
	htmlElement:undefined,
	_inputBox:undefined,
	_resultsBoxId:undefined,
	_searchButtonName:undefined,
	/**
	 * @var String Default 'idle'
	 */
	state: 'idle',
	_runningQueries:undefined,
	_queriesIndex:0,
	_ajxpOptions:undefined,
	
	_queue : undefined,

    _searchMode : "local",
    _even : false,

    _rootNode : null,
    _dataModel : null,
    _fileList : null,

	/**
	 * Constructor
	 * @param $super klass Superclass reference
	 * @param mainElementName String
	 * @param ajxpOptions Object
	 */
	initialize: function($super, mainElementName, ajxpOptions)
	{
        this._ajxpOptions = {
            toggleResultsVisibility : false
        };
        if($(mainElementName).getAttribute("data-globalOptions")){
            this._ajxpOptions = $(mainElementName).getAttribute("data-globalOptions").evalJSON();
        }
        if(ajxpOptions){
            this._ajxpOptions = Object.extend(this._ajxpOptions, ajxpOptions);
        }
		$super($(mainElementName), this._ajxpOptions);
        this.updateSearchModeFromRegistry();
        this.searchModeObserver = this.updateSearchModeFromRegistry.bind(this);
        document.observe("ajaxplorer:registry_loaded", this.searchModeObserver);

        this._dataModel = new AjxpDataModel(true);
        this._rootNode = new AjxpNode("/", false, "Results", "folder.png");
        this._dataModel.setRootNode(this._rootNode);

        this.initGUI();

         this._dataModel.observe("selection_changed", function(){
             var selectedNode = this._dataModel.getSelectedNodes()[0];
             if(selectedNode) ajaxplorer.goTo(selectedNode);
         }.bind(this));

    },

    updateSearchModeFromRegistry : function(){
        if($(this._resultsBoxId) && !this._rootNode){
            $(this._resultsBoxId).update('');
        }else if(this._rootNode){
            this._rootNode.clear();
        }
        var reg = ajaxplorer.getXmlRegistry();
        var indexerNode = XPathSelectSingleNode(reg, "plugins/indexer");
        if(indexerNode != null){
            if(indexerNode.getAttribute("indexed_meta_fields")){
                this.indexedFields = indexerNode.getAttribute("indexed_meta_fields").evalJSON();
                if(this.indexedFields["indexed_meta_fields"]){
                    var addColumns = this.indexedFields["additionnal_meta_columns"];
                    this.indexedFields = $A(this.indexedFields["indexed_meta_fields"]);
                    if(!this._ajxpOptions.metaColumns) this._ajxpOptions.metaColumns = {};
                    for(var key in addColumns){
                        this._ajxpOptions.metaColumns[key] = addColumns[key];
                    }
                }else{
                    this.indexedFields = $A(this.indexedFields);
                }
            }else{
                this.indexedFields = $A();
            }
            this._searchMode = "remote";
        }else{
            this._searchMode = "local";
        }

        if(this.htmlElement && this.htmlElement.down('#search_meta')) {
            this.htmlElement.down('#search_meta').remove();
        }
        if($('search_form') && $('search_form').down('#search_meta')) {
            $('search_form').down('#search_meta').remove();
        }
        if(this._ajxpOptions && this._ajxpOptions.metaColumns){
            var cols = this._ajxpOptions.metaColumns;
            if(this._ajxpOptions.toggleResultsVisibility && $(this._ajxpOptions.toggleResultsVisibility) && this.htmlElement){
                this.htmlElement.down("#" + this._ajxpOptions.toggleResultsVisibility).insert({top:'<div id="search_meta">'+MessageHash[344]+' : <span id="search_meta_options"></span></div>'});
            }else if($('search_form')){
                $('search_form').insert({bottom:'<div id="search_meta">'+MessageHash[344]+' : <span id="search_meta_options"></span></div>'});
            }
            if($('search_meta_options')){
                this.initMetaOption($('search_meta_options'), 'filename', MessageHash[1], true);
                for(var key in cols){
                    if(this.indexedFields && !this.indexedFields.include(key)) continue;
                    this.initMetaOption($('search_meta_options'), key, cols[key], false);
                }
            }
        }

    },

	/**
	 * Creates the HTML
	 */
	initGUI : function(){
		
		if(!this.htmlElement) return;
		
		this.htmlElement.insert('<div id="search_panel"><div id="search_form"><input style="float:left;" type="text" id="search_txt" placeholder="'+ MessageHash[87] +'" name="search_txt" onfocus="blockEvents=true;" onblur="blockEvents=false;"><a href="" id="search_button" class="icon-search" ajxp_message_title_id="184" title="'+MessageHash[184]+'"><img width="16" height="16" align="absmiddle" src="'+ajxpResourcesFolder+'/images/actions/16/search.png" border="0"/></a><a class="icon-remove" href="" id="stop_search_button" ajxp_message_title_id="185" title="'+MessageHash[185]+'"><img width="16" height="16" align="absmiddle" src="'+ajxpResourcesFolder+'/images/actions/16/fileclose.png" border="0" /></a></div><div id="search_results"></div></div>');
        if(this._ajxpOptions.toggleResultsVisibility){
            this.htmlElement.down("#search_results").insert({before:"<div style='display: none;' id='"+this._ajxpOptions.toggleResultsVisibility+"'></div>"});
            this.htmlElement.down("#" + this._ajxpOptions.toggleResultsVisibility).insert(this.htmlElement.down("#search_results"));
        }
        if(this.htmlElement.down('div.panelHeader')){
            this.htmlElement.down('div#search_panel').insert({top:this.htmlElement.down('div.panelHeader')});
        }
		
		this.metaOptions = [];
        if(this.htmlElement.down('#search_meta')){
            this.htmlElement.down('#search_meta').remove();
        }
		if(this._ajxpOptions && this._ajxpOptions.metaColumns){
            var cols = this._ajxpOptions.metaColumns;
            if(this._ajxpOptions.toggleResultsVisibility){
                this.htmlElement.down("#" + this._ajxpOptions.toggleResultsVisibility).insert({top:'<div id="search_meta">'+MessageHash[344]+' : <span id="search_meta_options"></span></div>'});
            }else{
                $('search_form').insert({bottom:'<div id="search_meta">'+MessageHash[344]+' : <span id="search_meta_options"></span></div>'});
            }
			this.initMetaOption($('search_meta_options'), 'filename', MessageHash[1], true);
			for(var key in cols){
                if(this.indexedFields && !this.indexedFields.include(key)) continue;
				this.initMetaOption($('search_meta_options'), key, cols[key]);
			}
		}else{
			$('search_form').insert('<div style="clear:left;height:9px;"></div>');
		}
		
		this._inputBox = $("search_txt");
		this._resultsBoxId = 'search_results';
		this._searchButtonName = "search_button";
		this._runningQueries = new Array();
		this._queue = $A([]);
		
		$('stop_'+this._searchButtonName).addClassName("disabled");


        this._fileList = new FilesList($(this._resultsBoxId), {
            dataModel:this._dataModel,
            columnsDef:[{attributeName:"ajxp_label", messageId:1, sortType:'String'},
                {attributeName:"search_score", messageString:'Score', sortType:'Number'},
                {attributeName:"filename", messageString:'Path', sortType:'String'}
            ],
            displayMode: 'detail',
            fixedDisplayMode: 'detail',
            defaultSortTypes:["String", "String", "String"],
            columnsTemplate:"search_results",
            selectable: true,
            draggable: false,
            replaceScroller:true,
            fit:'height',
            fitParent : this.options.toggleResultsVisibility,
            detailThumbSize:22
        });


        this.htmlElement.select('a', 'div[id="search_results"]').each(function(element){
			disableTextSelection(element);
		});
        
		this._inputBox.observe("keydown", function(e){
            if(e.keyCode == Event.KEY_RETURN) {
                Event.stop(e);
                this.search();
            }
			if(e.keyCode == Event.KEY_TAB) return false;
			return true;		
		}.bind(this));
		
		this._inputBox.observe("focus", function(e){
			ajaxplorer.disableShortcuts();
			ajaxplorer.disableNavigation();
			this.hasFocus = true;
			this._inputBox.select();
            if(this.hasResults && this._ajxpOptions.toggleResultsVisibility && !$(this._ajxpOptions.toggleResultsVisibility).visible()){
                this.updateSearchResultPosition($(this._ajxpOptions.toggleResultsVisibility));
                $(this._ajxpOptions.toggleResultsVisibility).setStyle({
                    display:'block'
                });
            }
			return false;
		}.bind(this));
			
		this._inputBox.observe("blur", function(e){
			ajaxplorer.enableShortcuts();
            ajaxplorer.enableNavigation();
			this.hasFocus = false;
		}.bind(this));
		
		$(this._searchButtonName).onclick = function(){
			this.search();
			return false;
		}.bind(this);
		
		$('stop_'+this._searchButtonName).onclick = function(){
			this.interrupt();
			return false;
		}.bind(this);

        this.refreshObserver = function(e){
            "use strict";
            this._inputBox.setValue("");
            this.clearResults();
            if($(this.options.toggleResultsVisibility)){
                $(this._ajxpOptions.toggleResultsVisibility).setStyle({display:'none'});
            }
        }.bind(this);

        document.observe("ajaxplorer:repository_list_refreshed", this.refreshObserver );

        this.resize();
	},
	/**
	 * Show/Hide the widget
	 * @param show Boolean
	 */
	showElement : function(show){
		if(!this.htmlElement) return;
		if(show) this.htmlElement.show();
		else this.htmlElement.hide();
	},
	/**
	 * Resize the widget
	 */
	resize: function($super){
        if(this._ajxpOptions.toggleResultsVisibility){
            fitHeightToBottom($(this._ajxpOptions.toggleResultsVisibility), null, (this._ajxpOptions.fitMarginBottom?this._ajxpOptions.fitMarginBottom:0));
            fitHeightToBottom($(this._resultsBoxId));
        }else{
            fitHeightToBottom($(this._resultsBoxId), null, (this._ajxpOptions.fitMarginBottom?this._ajxpOptions.fitMarginBottom:0));
        }
        if(this._fileList){
            this._fileList.resize();
        }

		if(this.htmlElement && this.htmlElement.visible()){
			//this._inputBox.setStyle({width:Math.max((this.htmlElement.getWidth() - this.htmlElement.getStyle("paddingLeft")- this.htmlElement.getStyle("paddingRight") -70),70) + "px"});
		}
	},
	
	destroy : function(){
        if(this._fileList){
            this._fileList.destroy();
            this._fileList = null;
        }
        if(this.htmlElement) {
            var ajxpId = this.htmlElement.id;
            this.htmlElement.update('');
        }
        document.stopObserving("ajaxplorer:repository_list_refreshed", this.refreshObserver);
        document.stopObserving("ajaxplorer:registry_loaded", this.searchModeObserver);
		this.htmlElement = null;
        if(ajxpId && window[ajxpId]){
            try {delete window[ajxpId];}catch(e){}
        }
	},
	/**
	 * Initialise the options for search Metadata
	 * @param element HTMLElement
	 * @param optionValue String
	 * @param optionLabel String
	 * @param checked Boolean
	 */
	initMetaOption : function(element, optionValue, optionLabel, checked){
		var option = new Element('span', {value:optionValue, className:'search_meta_opt'}).update('<span class="icon-ok"></span>'+ optionLabel);
		if(checked) option.addClassName('checked');
		if(element.childElements().length) element.insert(', ');
		element.insert(option);
		option.observe('click', function(event){
			option.toggleClassName('checked');
		});
		this.metaOptions.push(option);
	},
	/**
	 * Check wether there are metadata search selected
	 * @returns Boolean
	 */
	hasMetaSearch : function(){
		var found = false;
		this.metaOptions.each(function(opt){
			if(opt.getAttribute("value")!="filename" && opt.hasClassName("checked")) found = true;
		});
		return found;
	},
	/**
	 * Get the searchable columns
	 * @returns $A()
	 */
	getSearchColumns : function(){
		var cols = $A();
		this.metaOptions.each(function(opt){
			if(opt.hasClassName("checked")) cols.push(opt.getAttribute("value"));
		});
		return cols;
	},
	/**
	 * Focus on this widget (focus input)
	 */
	focus : function(){
		if(this.htmlElement && this.htmlElement.visible()){
			this._inputBox.activate();
			this.hasFocus = true;
		}
	},
	/**
	 * Blur this widget
	 */
	blur : function(){
		if(this._inputBox){
			this._inputBox.blur();
		}
		this.hasFocus = false;
	},
	/**
	 * Perform search
	 */
	search : function(){
		var text = this._inputBox.value;
		if(text == '') return;
		this.crtText = text.toLowerCase();
		this.updateStateSearching();
		this.clearResults();
		var folder = ajaxplorer.getContextNode().getPath();
		if(folder == "/") folder = "";
		window.setTimeout(function(){
			this.searchFolderContent(folder);
		}.bind(this), 0);		
	},
	/**
	 * stop search
	 */
	interrupt : function(){
		// Interrupt current search
		if(this._state == 'idle') return;
		this._state = 'interrupt';
		this._queue = $A();
	},
	/**
	 * Update GUI for indicating state
	 */
	updateStateSearching : function (){
		this._state = 'searching';
		$(this._searchButtonName).addClassName("disabled");
		$('stop_'+this._searchButtonName).removeClassName("disabled");
        if(this._ajxpOptions.toggleResultsVisibility){
            if(!$(this._ajxpOptions.toggleResultsVisibility).down("div.panelHeader.toggleResults")){
                $(this._ajxpOptions.toggleResultsVisibility).insert({top:"<div class='panelHeader toggleResults'>Results<span class='close_results icon-remove-sign'></span></div>"});
                this.resultsDraggable = new Draggable(this._ajxpOptions.toggleResultsVisibility, {
                    handle:"panelHeader",
                    zindex:999,
                    starteffect : function(element){},
                    endeffect : function(element){}
                });
            }
            if($(this._ajxpOptions.toggleResultsVisibility).down("span.close_results")){
                $(this._ajxpOptions.toggleResultsVisibility).down("span.close_results").observe("click", function(){
                    $(this._ajxpOptions.toggleResultsVisibility).setStyle({display:"none"});
                }.bind(this));
            }

            if(!$(this._ajxpOptions.toggleResultsVisibility).visible()){
                this.updateSearchResultPosition($(this._ajxpOptions.toggleResultsVisibility));
                $(this._ajxpOptions.toggleResultsVisibility).setStyle({
                    display:"block",
                    position: "absolute"
                });
            }
            this.resize();
        }
	},

    updateSearchResultPosition:function(panel){
        var top = (this._inputBox.cumulativeOffset().top + this._inputBox.getHeight() + 3);
        var left = (this._inputBox.cumulativeOffset().left);
        if((left + panel.getWidth()) > document.viewport.getWidth() + 10){
            left = document.viewport.getWidth() - panel.getWidth() - 10;
        }
        panel.setStyle({top: top + 'px', left: left + 'px'});
    },

	/**
	 * Search is finished
	 * @param interrupt Boolean
	 */
	updateStateFinished : function (interrupt){
		this._state = 'idle';
		this._inputBox.disabled = false;
		$(this._searchButtonName).removeClassName("disabled");
		$('stop_'+this._searchButtonName).addClassName("disabled");
	},
	/**
	 * Clear all results and input box
	 */
	clear: function(){
		this.clearResults();
		if(this._inputBox){
			this._inputBox.value = "";
		}
	},
	/**
	 * Clear all results
	 */
	clearResults : function(){
		// Clear the results
        this.hasResults = false;
        this._rootNode.clear();
        this._even = false;
	},
	/**
	 * Add a result to the list - Highlight search term
	 * @param folderName String
	 * @param ajxpNode AjxpNode
	 * @param metaFound String
	 */
	addResult : function(folderName, ajxpNode, metaFound){

        if(this._rootNode){
            this._rootNode.addChild(ajxpNode);
            return;
        }

		var fileName = ajxpNode.getLabel();
		var icon = ajxpNode.getIcon();
		// Display the result in the results box.
		if(folderName == "") folderName = "/";
        if(this._searchMode == "remote"){
            folderName = getRepName(ajxpNode.getPath());
        }
		var isFolder = false;
		if(icon == null) // FOLDER CASE
		{
			isFolder = true;
			icon = 'folder.png';
			if(folderName != "/") folderName += "/";
			folderName += fileName;
		}
        var imgPath = resolveImageSource(icon, '/images/mimes/16', 16);
		var imageString = '<img align="absmiddle" width="16" height="16" src="'+imgPath+'"> ';
		var stringToDisplay;
		if(metaFound){
			stringToDisplay = fileName + ' (' + this.highlight(metaFound, this.crtText, 20)+ ') ';
		}else{
			stringToDisplay = this.highlight(fileName, this.crtText);
		}
		
		var divElement = new Element('div', {title:MessageHash[224]+' '+ folderName, className:(this._even?'even':'')}).update(imageString+stringToDisplay);
        this._even = !this._even;
		$(this._resultsBoxId).insert(divElement);
        if(this._searchMode == 'remote' && ajxpNode.getMetadata().get("search_score")){
            /*divElement.insert(new Element('a', {className:"searchUnindex"}).update("X"));*/
            divElement.insert(new Element('span', {className:"searchScore"}).update("SCORE "+ajxpNode.getMetadata().get("search_score")));
        }
		if(isFolder)
		{
			divElement.observe("click", function(e){
				ajaxplorer.goTo(folderName);
			});
		}
		else
		{
			divElement.observe("click", function(e){
				ajaxplorer.goTo(folderName+"/"+fileName);
			});
		}
        this.hasResults = true;
	},
    addNoResultString : function(){
        $(this._resultsBoxId).insert(new Element('div').update("No results found."));
    },
	/**
	 * Put a folder to search in the queue
	 * @param path String
	 */
	appendFolderToQueue : function(path){
		this._queue.push(path);
	},
	/**
	 * Process the next element of the queue, or finish
	 */
	searchNext : function(){
		if(this._queue.length){
			var path = this._queue.first();
			this._queue.shift();
			this.searchFolderContent(path);
		}else{
			this.updateStateFinished();
		}
	},
	/**
	 * Get a folder content and searches its children 
	 * Should reference the IAjxpNodeProvider instead!! Still a "ls" here!
	 * @param currentFolder String
	 */
	searchFolderContent : function(currentFolder){
		if(this._state == 'interrupt') {
			this.updateStateFinished();
			return;
		}
        if(this._searchMode == "remote"){
            /* REMOTE INDEXER CASE */
            var connexion = new Connexion();
            connexion.addParameter('get_action', 'search');
            connexion.addParameter('query', this.crtText);
            if(this.hasMetaSearch()){
                connexion.addParameter('fields', this.getSearchColumns().join(','));
            }
            connexion.onComplete = function(transport){
                this._parseResults(transport.responseXML, currentFolder);
                this.updateStateFinished();
                this.removeOnLoad($(this._resultsBoxId));
            }.bind(this);
            this.setOnLoad($(this._resultsBoxId));
            connexion.sendAsync();
        }else{
            /* LIST CONTENT, SEARCH CLIENT SIDE, AND RECURSE */
            var connexion = new Connexion();
            connexion.addParameter('get_action', 'ls');
            connexion.addParameter('options', 'a' + (this.hasMetaSearch()?'l':''));
            connexion.addParameter('dir', currentFolder);
            connexion.onComplete = function(transport){
                this._parseXmlAndSearchString(transport.responseXML, currentFolder);
                this.searchNext();
            }.bind(this);
            connexion.sendAsync();
        }
	},
	
	_parseXmlAndSearchString : function(oXmlDoc, currentFolder){
		if(this._state == 'interrupt'){
			this.updateStateFinished();
			return;
		}
		if( oXmlDoc == null || oXmlDoc.documentElement == null){
			//alert(currentFolder);
		}else{
			var nodes = XPathSelectNodes(oXmlDoc.documentElement, "tree");
			for (var i = 0; i < nodes.length; i++) 
			{
				if (nodes[i].tagName == "tree") 
				{
					var node = this.parseAjxpNode(nodes[i]);					
					this._searchNode(node, currentFolder);
					if(!node.isLeaf())
					{
						var newPath = node.getPath();
						this.appendFolderToQueue(newPath);
					}
				}
			}		
		}
	},
	
	_parseResults : function(oXmlDoc, currentFolder){
		if(this._state == 'interrupt' || oXmlDoc == null || oXmlDoc.documentElement == null){
			this.updateStateFinished();
			return;
		}
		var nodes = XPathSelectNodes(oXmlDoc.documentElement, "tree");
        if(!nodes.length){
            this.addNoResultString();
        }
		for (var i = 0; i < nodes.length; i++) 
		{
			if (nodes[i].tagName == "tree") 
			{
				var ajxpNode = this.parseAjxpNode(nodes[i]);
                if(this.hasMetaSearch()){
                    var searchCols = this.getSearchColumns();
                    var added = false;
                    for(var k=0;k<searchCols.length;k++){
                        var meta = ajxpNode.getMetadata().get(searchCols[k]);
                        if(meta && meta.toLowerCase().indexOf(this.crtText) != -1){
                            this.addResult(currentFolder, ajxpNode, meta);
                            added = true;
                        }
                    }
                    if(!added){
                        this.addResult(currentFolder, ajxpNode);
                    }
                }else{
				    this.addResult(currentFolder, ajxpNode);
                }
			}
		}		
		if(this._fileList){
            this._fileList.reload();
        }
	},
	
	_searchNode : function(ajxpNode, currentFolder){
		var searchFileName = true;
		var searchCols;
		if(this.hasMetaSearch()){
			searchCols = this.getSearchColumns();
			if(!searchCols.indexOf('filename')){
				searchFileName = false;
			}
		}
		if(searchFileName && ajxpNode.getLabel().toLowerCase().indexOf(this.crtText) != -1){
			this.addResult(currentFolder, ajxpNode);
            if(this._fileList){
                this._fileList.reload();
            }
            return;
		}
		if(!searchCols) return;
		for(var i=0;i<searchCols.length;i++){
			var meta = ajxpNode.getMetadata().get(searchCols[i]);
			if(meta && meta.toLowerCase().indexOf(this.crtText) != -1){
				this.addResult(currentFolder, ajxpNode, meta);
                if(this._fileList){
                    this._fileList.reload();
                }
                return;
			}
		}
	},
	/**
	 * Parses an XMLNode and create an AjxpNode
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
		node.setMetadata(metadata);
		return node;
	},
	/**
	 * Highlights a string with the search term
	 * @param haystack String
	 * @param needle String
	 * @param truncate Integer
	 * @returns String
	 */
	highlight : function(haystack, needle, truncate){
		var start = haystack.toLowerCase().indexOf(needle);
        if(start == -1) return haystack;
		var end = start + needle.length;
		if(truncate && haystack.length > truncate){
			var midTrunc = Math.round(truncate/2);
			var newStart = Math.max(Math.round((end + start) / 2 - truncate / 2), 0);
			var newEnd = Math.min(Math.round((end + start) / 2 + truncate / 2),haystack.length);
			haystack = haystack.substring(newStart, newEnd);
			if(newStart > 0) haystack = '...' + haystack;
			if(newEnd < haystack.length) haystack = haystack + '...';
			// recompute
			start = haystack.toLowerCase().indexOf(needle);
			end = start + needle.length;
		}
		var highlight = haystack.substring(0, start)+'<em>'+haystack.substring(start, end)+'</em>'+haystack.substring(end);
		return highlight;
	},

    /**
     * Add a loading image to the given element
     * @param element Element dom node
     */
    setOnLoad : function(element){
        addLightboxMarkupToElement(element);
        var img = new Element("img", {src : ajxpResourcesFolder+"/images/loadingImage.gif", style:"margin-top: 10px;"});
        $(element).down("#element_overlay").insert(img);
        this.loading = true;
    },
    /**
     * Removes the image from the element
     * @param element Element dom node
     */
    removeOnLoad : function(element){
        removeLightboxFromElement(element);
        this.loading = false;
    }
		
});