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
 * The godzilla of AjaXplorer, should be split in smaller pieces.. 
 * This grid displays either a table of rows or a grid of thumbnail.
 */
Class.create("FilesList", SelectableElements, {
	
	__implements : ["IAjxpWidget", "IFocusable", "IContextMenuable", "IActionProvider"],

    __allObservers : null,
    __currentInstanceIndex:1,
    _dataModel:null,
    _doubleClickListener:null,
    _previewFactory : null,
    _detailThumbSize : 28,
    _inlineToolbarOptions: null,
    _instanciatedToolbars : null,
	/**
	 * Constructor
	 * @param $super klass Reference to the constructor
	 * @param oElement HTMLElement
	 * @param initDefaultDispOrOptions Object Instance parameters
	 */
	initialize: function($super, oElement, initDefaultDispOrOptions)
	{
		$super(null, true);
		$(oElement).ajxpPaneObject = this;
		this.htmlElement = $(oElement);
        this._previewFactory = new PreviewFactory();
        this._previewFactory.sequencialLoading = true;
        this.__allObservers = $A();

		if(typeof initDefaultDispOrOptions == "string"){
			this.options = {};
			this._displayMode = initDefaultDispOrOptions;
		}else{
			this.options = initDefaultDispOrOptions;
            if(this.options.displayMode) {
                this._displayMode = this.options.displayMode;
            }else {
                this._displayMode = 'detail';
            }
            if(this.options.dataModel){
                this._dataModel = this.options.dataModel;
            }
            if(this.options.doubleClickListener){
                this._doubleClickListener = this.options.doubleClickListener;
            }
            if(this.options.detailThumbSize){
                this._detailThumbSize = this.options.detailThumbSize;
            }
            if(this.options.inlineToolbarOptions){
                this._inlineToolbarOptions = this.options.inlineToolbarOptions;
                this._instanciatedToolbars = $A();
            }
		}
        //this.options.replaceScroller = false;
        if(!FilesList.staticIndex) {
            FilesList.staticIndex = 1;
        }else{
            FilesList.staticIndex ++;
        }
        this.__currentInstanceIndex = FilesList.staticIndex;

        var userLoggedObserver = function(){
			if(!ajaxplorer || !ajaxplorer.user) return;
			disp = ajaxplorer.user.getPreference("display");
			if(disp && (disp == 'thumb' || disp == 'list' || disp == 'detail')){
				if(disp != this._displayMode) this.switchDisplayMode(disp);
			}
			this._thumbSize = parseInt(ajaxplorer.user.getPreference("thumb_size"));
			if(this.slider){
				this.slider.setValue(this._thumbSize);
				this.resizeThumbnails();
			}
		}.bind(this);
        this._registerObserver(document, "ajaxplorer:user_logged", userLoggedObserver);
		
		
		var loadObserver = this.contextObserver.bind(this);
		var childAddedObserver = this.childAddedToContext.bind(this);
		var loadingObs = this.setOnLoad.bind(this);
		var loadEndObs = this.removeOnLoad.bind(this);
        var contextChangedObserver = function(event){
			var newContext = event.memo;
			var previous = this.crtContext;
			if(previous){
				previous.stopObserving("loaded", loadEndObs);
				previous.stopObserving("loading", loadingObs);
				previous.stopObserving("child_added", childAddedObserver);
			}
			this.crtContext = newContext;
			if(this.crtContext.isLoaded()) {
				this.contextObserver(event);
			}else{
				var oThis = this;
				this.crtContext.observeOnce("loaded", function(){
					oThis.crtContext = this ;
					loadObserver();
				});
			}
			this.crtContext.observe("loaded",loadEndObs);
			this.crtContext.observe("loading",loadingObs);
			this.crtContext.observe("child_added",childAddedObserver);

		}.bind(this);
        var componentConfigObserver = function(event){
			if(event.memo.className == "FilesList"){
				var refresh = this.parseComponentConfig(event.memo.classConfig.get('all'));
				if(refresh){
					this.initGUI();
				}
			}
		}.bind(this) ;
        var selectionChangedObserver = function(event){
			if(event.memo._selectionSource == null || event.memo._selectionSource == this) return;
            var dm = (this._dataModel?this._dataModel:ajaxplorer.getContextHolder());
            var origFC = this._fireChange;
			this._fireChange = false;
            this.setSelectedNodes(dm.getSelectedNodes());
            this._fireChange = origFC;
		}.bind(this);

        if(this._dataModel){
            this._registerObserver(this._dataModel, "context_changed", contextChangedObserver, true );
            this._registerObserver(this._dataModel, "context_loading", loadingObs, true);
            this._registerObserver(this._dataModel, "component_config_changed", componentConfigObserver, true);
            this._registerObserver(this._dataModel, "selection_changed", selectionChangedObserver, true);
        }else{
            this._registerObserver(document, "ajaxplorer:context_changed", contextChangedObserver, false );
            this._registerObserver(document, "ajaxplorer:context_loading", loadingObs, false);
            this._registerObserver(document, "ajaxplorer:component_config_changed", componentConfigObserver, false);
            this._registerObserver(document, "ajaxplorer:selection_changed", selectionChangedObserver, false);
        }

		this._thumbSize = 64;
		this._crtImageIndex = 0;
	
		this._pendingFile = null;
		this.allDraggables = new Array();
		this.allDroppables = new Array();		
		
		// List mode style : file list or tableur mode ?
		this.gridStyle = "file";
		this.paginationData = null;
		this.even = true;
		
		// Default headersDef
		this.hiddenColumns = $A([]);
        if(this.options.columnsDef){
            this.columnsDef = this.options.columnsDef;
            this.defaultSortTypes = this.options.defaultSortTypes;
            if(this.options.columnsTemplate){
                this.columnsTemplate = this.options.columnsTemplate;
            }
        }else{
            this.columnsDef = $A([]);
            this.columnsDef.push({messageId:1,attributeName:'ajxp_label'});
            this.columnsDef.push({messageId:2,attributeName:'filesize'});
            this.columnsDef.push({messageId:3,attributeName:'mimestring'});
            this.columnsDef.push({messageId:4,attributeName:'ajxp_modiftime'});
            // Associated Defaults
            this.defaultSortTypes = ["StringDirFile", "NumberKo", "String", "MyDate"];
        }
        this._oSortTypes = this.defaultSortTypes;

		this.initGUI();
        var keydownObserver = this.keydown.bind(this);
        var repoSwitchObserver = this.setOnLoad.bind(this);
		this._registerObserver(document, "keydown", keydownObserver);
        if(!this._dataModel){
            this._registerObserver(document, "ajaxplorer:trigger_repository_switch", repoSwitchObserver);
        }
	},

    _registerObserver:function(object, eventName, handler, objectEvent){
        if(objectEvent){
            object.observe(eventName, handler);
        }else{
            Event.observe(object, eventName, handler);
        }
        this.__allObservers.push({
            object:object,
            event:eventName,
            handler:handler,
            objectEvent:objectEvent
        });
    },

    _clearObservers:function(){
        this.__allObservers.each(function(el){
            if(el.objectEvent){
                el.object.stopObserving(el.event, el.handler);
            }else{
                Event.stopObserving(el.object, el.event, el.handler);
            }
        });
        if(this.observer){
            this.stopObserving("resize", this.observer);
        }
        if(this.scrollSizeObserver){
            this.stopObserving("resize", this.scrollSizeObserver);
        }
    },

	/**
	 * Implementation of the IAjxpWidget methods
	 */
	getDomNode : function(){
		return this.htmlElement;
	},
	
	/**
	 * Implementation of the IAjxpWidget methods
	 */
	destroy : function(){
        this._clearObservers();
        if(window[this.htmlElement.id]){
            try{delete window[this.htmlElement.id];}catch(e){}
        }
		this.htmlElement = null;
	},
	
	
	/**
	 * Gets the currently defined columns that are visible
	 * @returns $A()
	 */
	getVisibleColumns : function(){
		var visible = $A([]);
		this.columnsDef.each(function(el){
			if(!this.hiddenColumns.include(el.attributeName)) visible.push(el);
		}.bind(this) );		
		return visible;
	},
	
	/**
	 * Gets the current sort types associated to the currently visible columns
	 * @returns $A()
	 */
	getVisibleSortTypes : function(){
		var visible = $A([]);
		var index = 0;
		for(var i=0;i<this.columnsDef.length;i++){			
			if(!this.hiddenColumns.include(this.columnsDef[i].attributeName)) visible.push(this.columnsDef[i].sortType);
		}
		return visible;		
	},
	
	/**
	 * Sets a column visible/invisible by its name
	 * @param attName String Column name
	 * @param visible Boolean Visible or invisible
	 */
	setColumnVisible : function (attName, visible){
		var change = false;
		if(visible && this.hiddenColumns.include(attName)){			
			this.hiddenColumns = this.hiddenColumns.without(attName);
			change = true;
		}
		if(!visible && !this.hiddenColumns.include(attName)){
			this.hiddenColumns.push(attName);
			change = true;
		}
		if(change){
			if(ajaxplorer && ajaxplorer.user){
				var data = ajaxplorer.user.getPreference("columns_visibility", true) || {};
				data = new Hash(data);
				data.set(ajaxplorer.user.getActiveRepository(), this.hiddenColumns);
				ajaxplorer.user.setPreference("columns_visibility", data, true);				
			}			
			this.initGUI();
            this.empty(true);
			this.fill(this.crtContext);
			if(ajaxplorer && ajaxplorer.user){
				ajaxplorer.user.savePreference("columns_visibility");
			}
		}
		
	},
	
	/**
	 * Handler for contextChange event 
	 */
	contextObserver : function(e){
		if(!this.crtContext) return;
		//console.log('FILES LIST : FILL');
        this.empty();
		this.fill(this.crtContext);
		this.removeOnLoad();
	},
	
	extractComponentConfig : function(){
		return {
			gridStyle : {value:this.gridStyle},
			_displayMode : {value : this._displayMode },
			columnsTemplate : {value : this.columnsTemplate},
			columnsDef : {value : (this.columnsDef?this.columnsDef.clone():this.columnsDef) },
			oSortTypes : {value : (this._oSortTypes?this._oSortTypes.clone():this._oSortTypes) },
			_thumbSize : {value : this._thumbSize },
			_fixedThumbSize : {value : this._fixedThumbSize}
		};
	},
	
	applyComponentConfig : function(config){
		for(var key in config){
			this[key] = config[key].value;
		}
	},
	
	/**
	 * Apply the config of a component_config node
	 * Returns true if the GUI needs refreshing
	 * @param domNode XMLNode
	 * @returns Boolean
	 */
	parseComponentConfig : function(domNode){
		if(domNode.getAttribute("local") && !this.restoreConfig){			
			this.restoreConfig = this.extractComponentConfig();
		}
		var refreshGUI = false;
		this.columnsTemplate = false;
		// CHECK FOR COLUMNS DEFINITION DATA
		var columnsNode = XPathSelectSingleNode(domNode, "columns");
		if(columnsNode){
			// DISPLAY INFO
			if(columnsNode.getAttribute('switchGridMode')){
				this.gridStyle = columnsNode.getAttribute('switchGridMode');
				refreshGUI = true;
			}
			if(columnsNode.getAttribute('switchDisplayMode')){
				var dispMode = columnsNode.getAttribute('switchDisplayMode');
				this._fullview = false;
				if(dispMode == "full"){
					this._fullview = true;
					dispMode = "list";
				}
				if(dispMode != this._displayMode){
					this.switchDisplayMode(dispMode);
                    refreshGUI = true;
				}				
			}
			if(columnsNode.getAttribute('template_name')){
				this.columnsTemplate = columnsNode.getAttribute('template_name');
			}
			// COLUMNS INFO
			var columns = XPathSelectNodes(columnsNode, "column");
			var addColumns = XPathSelectNodes(columnsNode, "additional_column");
			if(columns.length){
				var newCols = $A([]);
				var sortTypes = $A([]);
				columns.concat(addColumns);
			}else{
				var newCols = this.columnsDef;
				var sortTypes = this._oSortTypes;
				columns = addColumns;
			}
			columns.each(function(col){
				var obj = {};
				$A(col.attributes).each(function(att){
					obj[att.nodeName]=att.nodeValue;
					if(att.nodeName == "sortType"){
						sortTypes.push(att.nodeValue);
					}else if(att.nodeName == "defaultVisibilty" && att.nodeValue == "hidden"){
                        this.hiddenColumns.push(col.getAttribute("attributeName"));
                    }
				}.bind(this));
				newCols.push(obj);					
			}.bind(this));
			if(newCols.size()){
				this.columnsDef=newCols;
				this._oSortTypes=sortTypes;
				if(this._displayMode == "list"){
					refreshGUI = true;
				}			
			}
		}
		var properties = XPathSelectNodes(domNode, "property");
		if(properties.length){
			for( var i=0; i<properties.length;i++){
				var property = properties[i];
				if(property.getAttribute("name") == "thumbSize"){
					this._thumbSize = parseInt(property.getAttribute("value"));
					refreshGUI = true;
				}else if(property.getAttribute("name") == "fixedThumbSize"){
					this._fixedThumbSize = parseInt(property.getAttribute("value"));
					refreshGUI = true;
				}else if(property.getAttribute("name") == "displayMode"){
					var displayMode = property.getAttribute("value");
					if(!(ajaxplorer && ajaxplorer.user && ajaxplorer.user.getPreference("display"))){
						this._displayMode = displayMode;
						refreshGUI = true;
					}
				}
			}
		}
		return refreshGUI;
	},

	/**
	 * Gets the action of this component
	 * @returns $H
	 */
	getActions : function(){
		// function may be bound to another context
		var oThis = this;
		var options1 = {
			name:'multi_display',
			src:'view_icon.png',
            icon_class:'icon-th-large',
			text_id:150,
			title_id:151,
			text:MessageHash[150],
			title:MessageHash[151],
			hasAccessKey:false,
			subMenu:true,
			subMenuUpdateImage:true,
			callback: function(){
				if(window.actionArguments){
                    var command;
					if(Object.isString(window.actionArguments[0])){
                        command = window.actionArguments[0];
					}else{
                        command = window.actionArguments[0].command;
					}
                    oThis.switchDisplayMode(command);
                    /*
                    window.setTimeout(function(){
                        var item = this.subMenuItems.staticItems.detect(function(item){return item.command == command;});
                        this.notify("submenu_active", item);
                    }.bind(window.listenerContext), 500);
                    */
                }
			},
			listeners : {
				init:function(){
					window.setTimeout(function(){					
						var displayMode = oThis.getDisplayMode();
						var item = this.subMenuItems.staticItems.detect(function(item){return item.command == displayMode;});
						this.notify("submenu_active", item);
					}.bind(window.listenerContext), 500);								
				}
			}
	    };
		var context1 = {
			selection:false,
			dir:true,
			actionBar:true,
			actionBarGroup:'default',
			contextMenu:false,
			infoPanel:false			
			};
		var subMenuItems1 = {
			staticItems:[
                {text:226,title:227,src:'view_text.png',icon_class:'icon-table',command:'list',hasAccessKey:true,accessKey:'list_access_key'},
                {text:460,title:461,src:'view_list_details.png',icon_class:'icon-th-list',command:'detail',hasAccessKey:true,accessKey:'detail_access_key'},
                {text:228,title:229,src:'view_icon.png',icon_class:'icon-th',command:'thumb',hasAccessKey:true,accessKey:'thumbs_access_key'}
            ]
		};
		// Create an action from these options!
		var multiAction = new Action(options1, context1, {}, {}, subMenuItems1);

        var options2 = {
			name:'thumb_size',
			src:'view_icon.png',
            icon_class:'icon-resize-full',
			text_id:452,
			title_id:453,
			text:MessageHash[452],
			title:MessageHash[453],
			hasAccessKey:false,
			subMenu:false,
			subMenuUpdateImage:false,
			callback: function(){
                oThis.slider.show($('thumb_size_button'));
			},
			listeners : {
				init:function(){
                    var actBar = window.ajaxplorer.actionBar;
                    oThis.observe('switch-display-mode', function(e){
                        if(oThis._displayMode != 'thumb') actBar.getActionByName("thumb_size").disable();
                        else actBar.getActionByName("thumb_size").enable();
                    });
                    window.setTimeout(function(){
                        if(oThis._displayMode != 'thumb') actBar.getActionByName("thumb_size").disable();
                        else actBar.getActionByName("thumb_size").enable();
                    }.bind(window.listenerContext), 800);
                }
			}
	    };
		var context2 = {
			selection:false,
			dir:true,
			actionBar:true,
			actionBarGroup:'default',
			contextMenu:false,
			infoPanel:false
		};
		// Create an action from these options!
		var thumbsizeAction = new Action(options2, context2, {}, {});

        var options3 = {
			name:'thumbs_sortby',
			src:'view_icon.png',
            icon_class:'icon-sort',
			text_id:450,
			title_id:451,
			text:MessageHash[450],
			title:MessageHash[451],
			hasAccessKey:false,
			subMenu:true,
			subMenuUpdateImage:false,
			callback: function(){
                //oThis.slider.show($('thumb_size_button'));
			},
			listeners : {
				init:function(){
                    var actBar = window.ajaxplorer.actionBar;
                    oThis.observe('switch-display-mode', function(e){
                        if(oThis._displayMode == 'list') actBar.getActionByName("thumbs_sortby").disable();
                        else actBar.getActionByName("thumbs_sortby").enable();
                    });
                    window.setTimeout(function(){
                        if(oThis._displayMode == 'list') actBar.getActionByName("thumbs_sortby").disable();
                        else actBar.getActionByName("thumbs_sortby").enable();
                    }.bind(window.listenerContext), 800);
                }
			}
	    };
		var context3 = {
			selection:false,
			dir:true,
			actionBar:true,
			actionBarGroup:'default',
			contextMenu:false,
			infoPanel:false
		};
        var submenuItems3 = {
            dynamicBuilder : function(protoMenu){
                "use strict";
                var items = $A([]);
                var index = 0;
                oThis.columnsDef.each(function(column){
                    var isSorted = this._sortableTable.sortColumn == index;
                    items.push({
                        name:(column.messageId?MessageHash[column.messageId]:column.messageString),
                        alt:(column.messageId?MessageHash[column.messageId]:column.messageString),
                        image:resolveImageSource((isSorted?"column-visible":"transp")+".png", '/images/actions/ICON_SIZE', 16),
                        icon_class:(isSorted?'icon-caret-'+(this._sortableTable.descending?'down':'up'):''),
                        isDefault:false,
                        callback:function(e){
                            var clickIndex = this.columnsDef.indexOf(column);
                            var sorted = (this._sortableTable.sortColumn == clickIndex);
                            if(sorted) this._sortableTable.descending = !this._sortableTable.descending;
                            this._sortableTable.sort(clickIndex, this._sortableTable.descending);
                        }.bind(this)
                    });
                    index++;
                    protoMenu.options.menuItems = items;
                    protoMenu.refreshList();
                }.bind(oThis) );
            }
        };
		// Create an action from these options!
		var thumbSortAction = new Action(options3, context3, {}, {}, submenuItems3);

		return $H({thumb_size:thumbsizeAction, thumb_sort:thumbSortAction, multi_display:multiAction});
	},
	
	/**
	 * Creates the base GUI, depending on the displayMode
	 */
	initGUI: function()
	{
		if(this.observer){
			this.stopObserving("resize", this.observer);
		}
        if(this.scrollSizeObserver){
            this.stopObserving("resize", this.scrollSizeObserver);
        }
        if(this.slider){
            this.slider.destroy();
        }
		if(this._displayMode == "list")
		{
			var buffer = '';
			if(ajaxplorer && ajaxplorer.user && ajaxplorer.user.getPreference("columns_visibility", true)){
				var data = new Hash(ajaxplorer.user.getPreference("columns_visibility", true));
				if(data.get(ajaxplorer.user.getActiveRepository())){
					this.hiddenColumns = $A(data.get(ajaxplorer.user.getActiveRepository()));
				}else{
					this.hiddenColumns = $A();
				}
			}
			var visibleColumns = this.getVisibleColumns();			
			var userPref;
			if(ajaxplorer && ajaxplorer.user && ajaxplorer.user.getPreference("columns_size", true)){
				var data = new Hash(ajaxplorer.user.getPreference("columns_size", true));
				if(this.columnsTemplate && data.get(this.columnsTemplate)){
					userPref = new Hash(data.get(this.columnsTemplate));
				}else if(data.get(ajaxplorer.user.getActiveRepository())){
					userPref = new Hash(data.get(ajaxplorer.user.getActiveRepository()));
				}
			}
			var headerData = $A();
			for(var i=0; i<visibleColumns.length;i++){
				var column = visibleColumns[i];
				var userWidth = 0;
                if(column.defaultWidth){
                    userWidth = column.defaultWidth.replace('%', '');
                }
				if((this.gridStyle != "grid" || this.columnsTemplate) && userPref && userPref.get(i) && i<(visibleColumns.length-1)){
					userWidth = userPref.get(i);
				}
				if(column.fixedWidth){
					userWidth = column.fixedWidth;
				}
				var label = (column.messageId?MessageHash[column.messageId]:column.messageString);
				var leftPadding = this.options.cellPaddingCorrection || 0 ;
				if(column.attributeName == "ajxp_label"){// Will contain an icon
					leftPadding = 24;
				}
				headerData.push({label:label, size:userWidth, leftPadding:leftPadding});				
			}
			buffer = '<div id="selectable_div_header-'+this.__currentInstanceIndex+'" class="sort-table"></div>';
			buffer = buffer + '<div id="table_rows_container-'+this.__currentInstanceIndex+'" class="table_rows_container"><table id="selectable_div-'+this.__currentInstanceIndex+'" class="selectable_div sort-table" width="100%" cellspacing="0"><tbody></tbody></table></div>';
			this.htmlElement.update(buffer);
            var contentContainer = this.htmlElement.down("div.table_rows_container");
            contentContainer.setStyle((this.gridStyle!="grid")?{overflowX:"hidden",overflowY:(this.options.replaceScroller?"hidden":"auto")}:{overflow:"auto"});
			attachMobileScroll(contentContainer, "vertical");
            var scrollElement = contentContainer;
			var oElement = this.htmlElement.down(".selectable_div");
			
			if(this.paginationData && parseInt(this.paginationData.get('total')) > 1 ){				
				contentContainer.insert({before:this.createPaginator()});
			}

            if(this.options.selectable == undefined || this.options.selectable === true){
                this.initSelectableItems(oElement, true, contentContainer, true);
            }else{
                this.initNonSelectableItems(oElement);
            }
			this._headerResizer = new HeaderResizer(this.htmlElement.down('div.sort-table'), {
				headerData : headerData,
				body : contentContainer,
				initSizesType : 'percent',
				bodyIsMaster : (this.gridStyle == 'grid'),
                scrollerWidth : this.options.replaceScroller?0:18,
                handleWidth : (this.options.replaceScroller)?1:3
			});
			this._headerResizer.observe("drag_resize", function(){
				if(this.prefSaver) window.clearTimeout(this.prefSaver);
				this.prefSaver = window.setTimeout(function(){
					if(!ajaxplorer.user || (this.gridStyle == "grid" && !this.columnsTemplate)) return;
					var sizes = this._headerResizer.getCurrentSizes('percent');
					var data = ajaxplorer.user.getPreference("columns_size", true);
					data = (data?new Hash(data):new Hash());
					sizes['type'] = 'percent';
					var id = (this.columnsTemplate?this.columnsTemplate:ajaxplorer.user.getActiveRepository());
					data.set(id, sizes);
					ajaxplorer.user.setPreference("columns_size", data, true);
					ajaxplorer.user.savePreference("columns_size");
				}.bind(this), 2000);				
			}.bind(this) );
			this._sortableTable = new AjxpSortable(oElement, this.getVisibleSortTypes(), this.htmlElement.down('div.sort-table'));
			this._sortableTable.onsort = function(){
				this.redistributeBackgrounds();
				var ctxt = this.getCurrentContextNode();
				ctxt.getMetadata().set("filesList.sortColumn", ''+this._sortableTable.sortColumn);
				ctxt.getMetadata().set("filesList.descending", this._sortableTable.descending);
			}.bind(this);
			if(this.paginationData && this.paginationData.get('remote_order') && parseInt(this.paginationData.get('total')) > 1){
				this._sortableTable.setPaginationBehaviour(function(params){
					this.reload(params);
				}.bind(this), this.columnsDef, this.paginationData.get('currentOrderCol')||-1, this.paginationData.get('currentOrderDir') );
			}
			this.disableTextSelection(this.htmlElement.down('div.sort-table'), true);
			this.disableTextSelection(contentContainer, true);
			this.observer = function(e){
				fitHeightToBottom(contentContainer, this.htmlElement);
				if(Prototype.Browser.IE){
					this._headerResizer.resize(contentContainer.getWidth());
				}else{
                    var width = this.htmlElement.getWidth();
                    width -= parseInt(this.htmlElement.getStyle("borderLeftWidth")) + parseInt(this.htmlElement.getStyle("borderRightWidth"));
					this._headerResizer.resize(width);
				}
			}.bind(this);
			this.observe("resize", this.observer);
		
			if(this.headerMenu){
				this.headerMenu.destroy();
				delete this.headerMenu;
			}
			this.headerMenu = new Proto.Menu({
			  selector: '#selectable_div_header-'+this.__currentInstanceIndex,
			  className: 'menu desktop',
			  menuItems: [],
			  fade:true,
			  zIndex:2000,
			  beforeShow : function(){
			  	var items = $A([]);
			  	this.columnsDef.each(function(column){
					var isVisible = !this.hiddenColumns.include(column.attributeName);
					items.push({
						name:(column.messageId?MessageHash[column.messageId]:column.messageString),
						alt:(column.messageId?MessageHash[column.messageId]:column.messageString),
						image:resolveImageSource((isVisible?"column-visible":"transp")+".png", '/images/actions/ICON_SIZE', 16),
						isDefault:false,
						callback:function(e){this.setColumnVisible(column.attributeName, !isVisible);}.bind(this)
					});
				}.bind(this) );		
				this.headerMenu.options.menuItems = items;
				this.headerMenu.refreshList();
			  }.bind(this)
			});
		}
		else if(this._displayMode == "thumb" || this._displayMode == "detail")
		{
			if(this.headerMenu){
				this.headerMenu.destroy();
				delete this.headerMenu;
			}
			var buffer = '<div class="panelHeader"><div style="float:right;padding-right:5px;font-size:1px;height:16px;"><input type="image" height="16" width="16" src="'+ajxpResourcesFolder+'/images/actions/16/zoom-in.png" id="slider-input-1" style="border:0px;width:16px;height:16px;margin-top:0px;padding:0px;" value="64"/></div>'+MessageHash[126]+'</div>';
			buffer += '<div id="selectable_div-'+this.__currentInstanceIndex+'" class="selectable_div'+(this._displayMode == "detail" ? ' detailed':'')+'" style="overflow:auto;">';
			this.htmlElement.update(buffer);
			attachMobileScroll(this.htmlElement.down(".selectable_div"), "vertical");
			if(this.paginationData && parseInt(this.paginationData.get('total')) > 1 ){				
                this.htmlElement.down(".selectable_div").insert({before:this.createPaginator()});
			}
            var scrollElement = this.htmlElement.down(".selectable_div");
			this.observer = function(e){
				fitHeightToBottom(scrollElement, this.htmlElement);
			}.bind(this);
			this.observe("resize", this.observer);
			
			if(ajaxplorer && ajaxplorer.user && ajaxplorer.user.getPreference("thumb_size")){
				this._thumbSize = parseInt(ajaxplorer.user.getPreference("thumb_size"));
			}
			if(this._fixedThumbSize){
				this._thumbSize = parseInt(this._fixedThumbSize);
			}

            this._sortableTable = new AjxpSortable(scrollElement, null, null);
            this._sortableTable.setMetaSortType(this.columnsDef);
            this._sortableTable.onsort = function(){
                var ctxt = this.getCurrentContextNode();
                ctxt.getMetadata().set("filesList.sortColumn", ''+this._sortableTable.sortColumn);
                ctxt.getMetadata().set("filesList.descending", this._sortableTable.descending);
            }.bind(this);
            if(this.headerMenu){
                this.headerMenu.destroy();
                delete this.headerMenu;
            }
            this.headerMenu = new Proto.Menu({
                selector: '#content_pane div.panelHeader',
                className: 'menu desktop',
                menuItems: [],
                fade:true,
                zIndex:2000,
                beforeShow : function(){
                    var items = $A([]);
                    var index = 0;
                    this.columnsDef.each(function(column){
                        var isSorted = this._sortableTable.sortColumn == index;
                        items.push({
                            name:(column.messageId?MessageHash[column.messageId]:column.messageString),
                            alt:(column.messageId?MessageHash[column.messageId]:column.messageString),
                            image:resolveImageSource((isSorted?"column-visible":"transp")+".png", '/images/actions/ICON_SIZE', 16),
                            isDefault:false,
                            callback:function(e){
                                var clickIndex = this.columnsDef.indexOf(column);
                                var sorted = (this._sortableTable.sortColumn == clickIndex);
                                if(sorted) this._sortableTable.descending = !this._sortableTable.descending;
                                this._sortableTable.sort(clickIndex, this._sortableTable.descending);
                            }.bind(this)
                        });
                        index++;
                    }.bind(this) );
                    this.headerMenu.options.menuItems = items;
                    this.headerMenu.refreshList();
                }.bind(this)
            });

			this.slider = new SliderInput($("slider-input-1"), {
				range : $R(30, 250),
				sliderValue : this._thumbSize,
				leftOffset:0,
				onSlide : function(value)
				{
					this._thumbSize = value;
					this.resizeThumbnails();
				}.bind(this),
				onChange : function(value){
                    if(this.options.replaceScroller){
                        this.notify("resize");
                    }
					if(!ajaxplorer || !ajaxplorer.user) return;
					ajaxplorer.user.setPreference("thumb_size", this._thumbSize);
					ajaxplorer.user.savePreference("thumb_size");								
				}.bind(this)
			});

			this.disableTextSelection(scrollElement, true);
            if(this.options.selectable == undefined || this.options.selectable === true){
			    this.initSelectableItems(scrollElement, true, scrollElement, true);
            }else{
                this.initNonSelectableItems(scrollElement);
            }
		}

        if(this.options.replaceScroller){
            this.scroller = new Element('div', {id:'filelist_scroller'+this.__currentInstanceIndex, className:'scroller_track', style:"right:0px"});
            this.scroller.insert('<div id="filelist_scrollbar_handle'+this.__currentInstanceIndex+'" class="scroller_handle"></div>');
            scrollElement.insert({before:this.scroller});
            if(this.gridStyle == "grid"){
                scrollElement.setStyle({overflowY:"hidden",overflowX:"auto"});
            }else{
                scrollElement.setStyle({overflow:"hidden"});
            }
            this.scrollbar = new Control.ScrollBar(scrollElement,'filelist_scroller'+this.__currentInstanceIndex);
            if(this.scrollSizeObserver){
                this.stopObserving("resize", this.scrollSizeObserver);
            }
            this.scrollSizeObserver = function(){
                this.scroller.setStyle({height:parseInt(scrollElement.getHeight())+"px"});
                this.scrollbar.recalculateLayout();
            }.bind(this);
            this.observe("resize", this.scrollSizeObserver);
        }

		this.notify("resize");
	},
	
	/**
	 * Adds a pagination navigator at the top of the current GUI
	 * @returns HTMLElement
	 */
	createPaginator: function(){
		var current = parseInt(this.paginationData.get('current'));
		var total = parseInt(this.paginationData.get('total'));
		var div = new Element('div').addClassName("paginator");
		var currentInput = new Element('input', {value:current, className:'paginatorInput'});
		div.update(MessageHash[331]);
		div.insert(currentInput);
		div.insert('/'+total);
		if(current>1){
			div.insert({top:this.createPaginatorLink(current-1, '<b>&lt;</b>', 'Previous')});
			if(current > 2){
				div.insert({top:this.createPaginatorLink(1, '<b>&lt;&lt;</b>', 'First')});
			}
		}
		if(total > 1 && current < total){
			div.insert({bottom:this.createPaginatorLink(current+1, '<b>&gt;</b>', 'Next')});
			if(current < (total-1)){
				div.insert({bottom:this.createPaginatorLink(total, '<b>&gt;&gt;</b>', 'Last')});
			}
		}
		currentInput.observe("focus", function(){this.blockNavigation = true;}.bind(this));
		currentInput.observe("blur", function(){this.blockNavigation = false;}.bind(this));
		currentInput.observe("keydown", function(event){
			if(event.keyCode == Event.KEY_RETURN){
				Event.stop(event);
				var new_page = parseInt(currentInput.getValue());
				if(new_page == current) return; 
				if(new_page < 1 || new_page > total){
					ajaxplorer.displayMessage('ERROR', MessageHash[335] +' '+ total);
					currentInput.setValue(current);
					return;
				}
				var node = this.getCurrentContextNode();
				node.getMetadata().get("paginationData").set("new_page", new_page);
				ajaxplorer.updateContextData(node);
			}
		}.bind(this) );
		return div;
	},
	
	/**
	 * Utility for generating pagination link
	 * @param page Integer Target page
	 * @param text String Label of the link
	 * @param title String Tooltip of the link
	 * @returns HTMLElement
	 */
	createPaginatorLink:function(page, text, title){
		var node = this.getCurrentContextNode();
		return new Element('a', {href:'#', style:'font-size:12px;padding:0 7px;', title:title}).update(text).observe('click', function(e){
			node.getMetadata().get("paginationData").set("new_page", page);
			ajaxplorer.updateContextData(node);
			Event.stop(e);
		}.bind(this));		
	},
	
	/**
	 * Sets the columns definition object
	 * @param aColumns $H
	 */
	setColumnsDef:function(aColumns){
		this.columnsDef = aColumns;
		if(this._displayMode == "list"){
			this.initGUI();
		}
	},
	
	/**
	 * Gets the columns definition object
	 * @returns $H
	 */
	getColumnsDef:function(){
		return this.columnsDef;
	},
	
	/**
	 * Sets the contextual menu
	 * @param protoMenu Proto.Menu
	 */
	setContextualMenu: function(protoMenu){
		this.protoMenu = protoMenu;	
	},

    getCurrentContextNode : function(){
        if(this._dataModel) return this._dataModel.getContextNode();
        else return ajaxplorer.getContextNode();
    },

	/**
	 * Resizes the widget
	 */
	resize : function(){
    	if(this.options.fit && this.options.fit == 'height'){
    		var marginBottom = 0;
    		if(this.options.fitMarginBottom){
    			var expr = this.options.fitMarginBottom;
    			try{marginBottom = parseInt(eval(expr));}catch(e){}
    		}
    		fitHeightToBottom(this.htmlElement, (this.options.fitParent?$(this.options.fitParent):null), expr);
    	}		
    	if(this.htmlElement.down('.table_rows_container') && Prototype.Browser.IE && this.gridStyle == "file"){
            this.htmlElement.down('.table_rows_container').setStyle({width:'100%'});
    	}
		this.notify("resize");
        document.fire("ajaxplorer:resize-FilesList-" + this.htmlElement.id, this.htmlElement.getDimensions());
    },
	
	/**
	 * Link focusing to ajaxplorer main
	 */
	setFocusBehaviour : function(){
        var clickObserver = function(){
			if(ajaxplorer) ajaxplorer.focusOn(this);
		}.bind(this) ;
        this._registerObserver(this.htmlElement, "click", clickObserver);
	},
	
	/**
	 * Do nothing
	 * @param show Boolean
	 */
	showElement : function(show){
		
	},
	
	/**
	 * Switch between various display modes. At the moment, thumb and list.
	 * Should keep the selected nodes after switch
	 * @param mode String "thumb" or "list
	 * @returns String
	 */
	switchDisplayMode: function(mode){
        if(this.options.fixedDisplayMode) {
            if(this.options.fixedDisplayMode == this._displayMode) return this._displayMode;
            else mode = this.options.fixedDisplayMode;
        }
        this.removeCurrentLines(true);

        if(mode){
            this._displayMode = mode;
        }else{
            this._displayMode = (this._displayMode == "thumb"?"list":"thumb");
        }
        this.notify('switch-display-mode');
		this.initGUI();
        this.empty(true);
		this.fill(this.getCurrentContextNode());
		this.fireChange();
		if(ajaxplorer && ajaxplorer.user){
			ajaxplorer.user.setPreference("display", this._displayMode);
			ajaxplorer.user.savePreference("display");
		}
		return this._displayMode;
	},
	
	/**
	 * Returns the display mode
	 * @returns {String}
	 */
	getDisplayMode: function(){
		return this._displayMode;
	},

    initRowsBuffered : function(){
        if(this.iRBTimer){
            window.clearTimeout(this.iRBTimer);
            this.iRBTimer = null;
        }
        this.iRBTimer = window.setTimeout(function(){
            this.initRows();
            this.iRBTimer = null;
        }.bind(this), 200);
    },

	/**
     *
	 * Called after the rows/thumbs are populated
	 */
	initRows: function(){
        this.notify("rows:willInitialize");
		// Disable text select on elements
		if(this._displayMode == "thumb" || this._displayMode == "detail")
		{
			this.resizeThumbnails();		
			if(this.protoMenu) this.protoMenu.addElements('#selectable_div-'+this.__currentInstanceIndex);
			window.setTimeout(function(){
                this._previewFactory.setThumbSize((this._displayMode=='detail'? this._detailThumbSize:this._thumbSize));
                this._previewFactory.loadNextImage();
            }.bind(this),10);
		}
		else
		{
			if(this.protoMenu) this.protoMenu.addElements('#table_rows_container-'+this.__currentInstanceIndex);
			if(this._headerResizer){
				this._headerResizer.resize(this.htmlElement.getWidth()-2);
			}
		}
		if(this.protoMenu)this.protoMenu.addElements('.ajxp_draggable');
		var allItems = this.getItems();
		for(var i=0; i<allItems.length;i++)
		{
			this.disableTextSelection(allItems[i], true);
		}
        this.notify("resize");
        this.notify("rows:didInitialize");
	},


	/**
	 * Triggers a reload of the rows/thumbs
	 * @param additionnalParameters Object
	 */
	reload: function(additionnalParameters){
		if(this.getCurrentContextNode()){
            this.empty();
			this.fill(this.getCurrentContextNode());
		}
	},
	/**
	 * Attach a pending selection that will be applied after rows are populated
	 * @param pendingFilesToSelect $A()
	setPendingSelection: function(pendingFilesToSelect){
		this._pendingFile = pendingFilesToSelect;
	},
     */

    empty : function(skipFireChange){
        this._previewFactory.clear();
        if(this.protoMenu){
            this.protoMenu.removeElements('.ajxp_draggable');
            this.protoMenu.removeElements('.selectable_div');
        }
        for(var i = 0; i< AllAjxpDroppables.length;i++){
            var el = AllAjxpDroppables[i];
            if(this.isItem(el)){
                Droppables.remove(AllAjxpDroppables[i]);
                delete(AllAjxpDroppables[i]);
            }
        }
        for(i = 0;i< AllAjxpDraggables.length;i++){
            if(AllAjxpDraggables[i] && AllAjxpDraggables[i].element && this.isItem(AllAjxpDraggables[i].element)){
                  if(AllAjxpDraggables[i].element.IMAGE_ELEMENT){
                      try{
                          if(AllAjxpDraggables[i].element.IMAGE_ELEMENT.destroyElement){
                              AllAjxpDraggables[i].element.IMAGE_ELEMENT.destroyElement();
                          }
                          AllAjxpDraggables[i].element.IMAGE_ELEMENT = null;
                          delete AllAjxpDraggables[i].element.IMAGE_ELEMENT;
                      }catch(e){}
                  }
                Element.remove(AllAjxpDraggables[i].element);
            }
        }
        AllAjxpDraggables = $A([]);

        var items = this.getSelectedItems();
        var setItemSelected = this.setItemSelected.bind(this);
        for(var i=0; i<items.length; i++)
        {
            setItemSelected(items[i], false);
        }
        this.removeCurrentLines(skipFireChange);
    },

    makeItemRefreshObserver: function (ajxpNode, item, renderer){
        return function(){
            //try{
                if(item.ajxpNode) {
                    if(item.REMOVE_OBS) item.ajxpNode.stopObserving("node_removed", item.REMOVE_OBS);
                    if(item.REPLACE_OBS) item.ajxpNode.stopObserving("node_replaced", item.REPLACE_OBS);
                }
                if(!item.parentNode){
                    return;
                }
                var newItem = renderer(ajxpNode, item);
                item.insert({before: newItem});
                item.remove();
                newItem.ajxpNode = ajxpNode;
                this.initRows();
                item.ajxpNode = null;
                delete item;
                newItem.REPLACE_OBS = this.makeItemRefreshObserver(ajxpNode, newItem, renderer);
                ajxpNode.observe("node_replaced", newItem.REPLACE_OBS);
                var dm = (this._dataModel?this._dataModel:ajaxplorer.getContextHolder());
                if(dm.getSelectedNodes() && dm.getSelectedNodes().length)
                {
                    var selectedNodes = dm.getSelectedNodes();
                    this._selectedItems = [];
                    for(var f=0;f<selectedNodes.length; f++){
                        if(Object.isString(selectedNodes[f])){
                            this.selectFile(selectedNodes[f], true);
                        }else{
                            this.selectFile(selectedNodes[f].getPath(), true);
                        }
                    }
                    this.hasFocus = true;
                }
            //}catch(e){

            //}
        }.bind(this);
    },

    makeItemRemovedObserver: function (ajxpNode, item){
        return function(){
            try{
                if(this.loading) return;
                this.setItemSelected(item, false);
                if(item.ajxpNode) {
                    if(item.REMOVE_OBS) item.ajxpNode.stopObserving("node_removed", item.REMOVE_OBS);
                    if(item.REPLACE_OBS) item.ajxpNode.stopObserving("node_replaced", item.REPLACE_OBS);
                    if(!item.parentNode){
                        item =  this.htmlElement.down('[id="'+item.ajxpNode.getPath()+'"]');
                    }
                }
                item.ajxpNode = null;
                new Effect.Fade(item, {afterFinish:function(){
                    try{item.remove();}catch(e){}
                    delete item;
                    this.initRowsBuffered();
                    //this.initRows();
                }.bind(this), duration:0.2});
                /*
                item.remove();
                delete item;
                this.initRowsBuffered();
                */
            }catch(e){

            }
        }.bind(this);
    },

    childAddedToContext : function(childPath){

        if(this.loading) return;
        var renderer = this.getRenderer(); //(this._displayMode == "list"?this.ajxpNodeToTableRow.bind(this):this.ajxpNodeToDiv.bind(this));
        var child = this.crtContext.findChildByPath(childPath);
        if(!child) return;
        var newItem;
        newItem = renderer(child);
        newItem.ajxpNode = child;
        newItem.addClassName("ajxpNodeProvider");
        newItem.REPLACE_OBS = this.makeItemRefreshObserver(child, newItem, renderer);
        newItem.REMOVE_OBS = this.makeItemRemovedObserver(child, newItem);
        child.observe("node_replaced", newItem.REPLACE_OBS);
        child.observe("node_removed", newItem.REMOVE_OBS);

        if(this._sortableTable){
            var sortColumn = this.crtContext.getMetadata().get("filesList.sortColumn");
         	var descending = this.crtContext.getMetadata().get("filesList.descending");
            if(sortColumn == undefined) {
                sortColumn = 0;
            }
            if(sortColumn != undefined){
                sortColumn = parseInt(sortColumn);
                var sortFunction = this._sortableTable.getSortFunction(this._sortableTable.getSortType(sortColumn), sortColumn);
                var sortCache = this._sortableTable.getCache(this._sortableTable.getSortType(sortColumn), sortColumn);
                sortCache.sort(sortFunction);
                for(var i=0;i<sortCache.length;i++){
                    if(sortCache[i].element == newItem){
                        if(i == 0) $(newItem.parentNode).insert({top:newItem});
                        else {
                            if(sortCache[i-1].element.ajxpNode.getPath() == newItem.ajxpNode.getPath()){
                                $(newItem.parentNode).remove(newItem);
                                break;
                            }
                            sortCache[i-1].element.insert({after:newItem});
                        }
                        break;
                    }
                }
                this._sortableTable.destroyCache(sortCache);
            }
        }
        this.initRows();


    },

    getRenderer : function(){
        if(this._displayMode == "thumb") return this.ajxpNodeToDiv.bind(this);
        else if(this._displayMode == "detail") return this.ajxpNodeToLargeDiv.bind(this);
        else if(this._displayMode == "list") return this.ajxpNodeToTableRow.bind(this);
    },

	/**
	 * Populates the list with the children of the passed contextNode
	 * @param contextNode AjxpNode
	 */
	fill: function(contextNode){

		var refreshGUI = false;
		this.gridStyle = 'file';
		this.even = false;
		this._oSortTypes = this.defaultSortTypes;
		
		var hasPagination = (this.paginationData?true:false);
		if(contextNode.getMetadata().get("paginationData")){
			this.paginationData = contextNode.getMetadata().get("paginationData");
			refreshGUI = true;
		}else{
			this.paginationData = null;
			if(hasPagination){
				refreshGUI = true;
			}
		}
		var clientConfigs = contextNode.getMetadata().get("client_configs");
		if(clientConfigs){
			var componentData = XPathSelectSingleNode(clientConfigs, 'component_config[@className="FilesList"]');
			if(componentData){
				refreshGUI = this.parseComponentConfig(componentData);
			}
		}else if(this.restoreConfig){
			this.applyComponentConfig(this.restoreConfig);
			this.restoreConfig = null;
			refreshGUI = true;
		}
		
		if(refreshGUI){
			this.initGUI();
		}
		
		// NOW PARSE LINES
		this.parsingCache = new Hash();		
		var children = contextNode.getChildren();
        var renderer = this.getRenderer();// (this._displayMode == "list"?this.ajxpNodeToTableRow.bind(this):this.ajxpNodeToDiv.bind(this));
		for (var i = 0; i < children.length ; i++) 
		{
			var child = children[i];
			var newItem;
            newItem = renderer(child);
			newItem.ajxpNode = child;
            newItem.addClassName("ajxpNodeProvider");
            newItem.REPLACE_OBS = this.makeItemRefreshObserver(child, newItem, renderer);
            newItem.REMOVE_OBS = this.makeItemRemovedObserver(child, newItem);
            child.observe("node_replaced", newItem.REPLACE_OBS);
            child.observe("node_removed", newItem.REMOVE_OBS);
		}
		this.initRows();
		
		if((!this.paginationData || !this.paginationData.get('remote_order')))
		{
			this._sortableTable.sortColumn = -1;
			this._sortableTable.updateHeaderArrows();
		}
		if(contextNode.getMetadata().get("filesList.sortColumn")){
			var sortColumn = parseInt(contextNode.getMetadata().get("filesList.sortColumn"));
			var descending = contextNode.getMetadata().get("filesList.descending");
			this._sortableTable.sort(sortColumn, descending);
			this._sortableTable.updateHeaderArrows();
		}
        var dm = (this._dataModel?this._dataModel:ajaxplorer.getContextHolder());
		if(dm.getSelectedNodes() && dm.getSelectedNodes().length)
		{
			var selectedNodes = dm.getSelectedNodes();
            for(var f=0;f<selectedNodes.length; f++){
                if(Object.isString(selectedNodes[f])){
                    this.selectFile(selectedNodes[f], true);
                }else{
                    this.selectFile(selectedNodes[f].getPath(), true);
                }
            }
			this.hasFocus = true;
		}
		if(this.hasFocus){
			window.setTimeout(function(){ajaxplorer.focusOn(this);}.bind(this),200);
		}
		//if(modal.pageLoading) modal.updateLoadingProgress('List Loaded');
	},
		
	/**
	 * Inline Editing of label
	 * @param callback Function Callback after the label is edited.
	 */
	switchCurrentLabelToEdition : function(callback){
		var sel = this.getSelectedItems();
		var item = sel[0]; // We assume this action was triggered with a single-selection active.
		var offset = {top:0,left:0};
		var scrollTop = 0;
        var addStyle = {fontSize: '12px'};
        if(this._displayMode == "list"){
            var span = item.select('span.text_label')[0];
            var posSpan = item.select('span.list_selectable_span')[0];
            offset.top=-3;
            offset.left=25;
            scrollTop = this.htmlElement.down('div.table_rows_container').scrollTop;
        }else if(this._displayMode == "thumb"){
            var span = item.select('div.thumbLabel')[0];
            var posSpan = span;
            offset.top=-2;
            offset.left=3;
            scrollTop = this.htmlElement.down('.selectable_div').scrollTop;
        }else if(this._displayMode == "detail"){
            var span = item.select('div.thumbLabel')[0];
            var posSpan = span;
            offset.top=0;
            offset.left= 0;
            scrollTop = this.htmlElement.down('.selectable_div').scrollTop;
            addStyle = {
                fontSize : '20px',
                paddingLeft: '2px',
                color: 'rgb(111, 121, 131)'
            };
        }
		var pos = posSpan.cumulativeOffset();
		var text = span.innerHTML;
		var edit = new Element('input', {value:item.ajxpNode.getLabel('text'), id:'editbox'}).setStyle({
			zIndex:5000, 
			position:'absolute',
			marginLeft:'0px',
			marginTop:'0px',
			height:'24px',
            padding: 0
		});
        edit.setStyle(addStyle);
		$(document.getElementsByTagName('body')[0]).insert({bottom:edit});				
		modal.showContent('editbox', (posSpan.getWidth()-offset.left)+'', '26', true, false, {opacity:0.25, backgroundColor:'#fff'});
		edit.setStyle({left:(pos.left+offset.left)+'px', top:(pos.top+offset.top-scrollTop)+'px'});
		window.setTimeout(function(){
			edit.focus();
			var end = edit.getValue().lastIndexOf("\.");
			if(end == -1){
				edit.select();
			}else{
				var start = 0;  
				if(edit.setSelectionRange)
				{				
					edit.setSelectionRange(start,end);
				}
				else if (edit.createTextRange) {
					var range = edit.createTextRange();
					range.collapse(true);
					range.moveStart('character', start);
					range.moveEnd('character', end);
					range.select();
				}
			}
			
		}, 300);
		var onOkAction = function(){
			var newValue = edit.getValue();
			hideLightBox();
			modal.close();			
			callback(item.ajxpNode, newValue);
		};
		edit.observe("keydown", function(event){
			if(event.keyCode == Event.KEY_RETURN){				
				Event.stop(event);
				onOkAction();
			}
		}.bind(this));
		// Add ok / cancel button, for mobile devices among others
		var buttons = modal.addSubmitCancel(edit, null, false, "after");
        buttons.addClassName("inlineEdition");
		var ok = buttons.select('input[name="ok"]')[0];
		ok.observe("click", onOkAction);
		var origWidth = edit.getWidth()-44;
		var newWidth = origWidth;
		if(origWidth < 70){
			// Offset edit box to be sure it's always big enough.
			edit.setStyle({left:pos.left+offset.left - 70 + origWidth});
			newWidth = 70;
		}
        if(this._displayMode == "detail") {
            origWidth -= 20;
            newWidth -= 20;
        }
		edit.setStyle({width:newWidth+'px'});
		
		buttons.select('input').invoke('setStyle', {
			margin:0,
			width:'22px',
			border:0,
			backgroundColor:'transparent'
		});
		buttons.setStyle({
			position:'absolute',
			width:'46px',
			zIndex:2500,
			left:(pos.left+offset.left+origWidth)+'px',
			top:((pos.top+offset.top-scrollTop)+1)+'px'
		});
		var closeFunc = function(){
			span.setStyle({color:''});
			edit.remove();
			buttons.remove();
		};
		span.setStyle({color:'#ddd'});
		modal.setCloseAction(closeFunc);
	},
	
	/**
	 * Populate a node as a TR element
	 * @param ajxpNode AjxpNode
     * @param HTMLElement replaceItem
	 * @returns HTMLElement
	 */
	ajxpNodeToTableRow: function(ajxpNode, replaceItem){
		var metaData = ajxpNode.getMetadata();
		var newRow = new Element("tr", {id:slugString(ajxpNode.getPath())});
		var tBody = this.parsingCache.get('tBody') || $(this._htmlElement).select("tbody")[0];
		this.parsingCache.set('tBody', tBody);
		metaData.each(function(pair){
			//newRow.setAttribute(pair.key, pair.value);
			if(Prototype.Browser.IE && pair.key == "ID"){
				newRow.setAttribute("ajxp_sql_"+pair.key, pair.value);
			}			
		});
		var attributeList;
		if(!this.parsingCache.get('attributeList')){
			attributeList = $H();
			var visibleColumns = this.getVisibleColumns();
			visibleColumns.each(function(column){
				attributeList.set(column.attributeName, column);
			});
			this.parsingCache.set('attributeList', attributeList);
		}else{
			attributeList = this.parsingCache.get('attributeList');
		}
		var attKeys = attributeList.keys();
		for(var i = 0; i<attKeys.length;i++ ){
			var s = attKeys[i];			
			var tableCell = new Element("td");			
			var fullview = '';
			if(this._fullview){
				fullview = ' full';
			}
			if(s == "ajxp_label")
			{
                var textLabel = new Element("span", {
                    id          :'ajxp_label',
                    className   :'text_label'+fullview
                }).update(metaData.get('text'));

                var backgroundPosition = this.options.iconBgPosition || '4px 2px';
                var backgroundImage = 'url("'+resolveImageSource(metaData.get('icon'), "/images/mimes/ICON_SIZE", 16)+'")';
                if(metaData.get('overlay_icon') && Modernizr.multiplebgs){
                    var ovIcs = metaData.get('overlay_icon').split(',');
                    switch(ovIcs.length){
                        case 1:
                            backgroundPosition = '14px 11px, ' + backgroundPosition;
                            backgroundImage = 'url("'+resolveImageSource(ovIcs[0], "/images/overlays/ICON_SIZE", 8)+'"), url("'+resolveImageSource(metaData.get('icon'), "/images/mimes/ICON_SIZE", 16)+'")';
                        break;
                        case 2:
                            backgroundPosition = '2px 11px, 14px 11px, ' + backgroundPosition;
                            backgroundImage = 'url("'+resolveImageSource(ovIcs[0], "/images/overlays/ICON_SIZE", 8)+'"), url("'+resolveImageSource(ovIcs[1], "/images/overlays/ICON_SIZE", 8)+'"), url("'+resolveImageSource(metaData.get('icon'), "/images/mimes/ICON_SIZE", 16)+'")';
                        break;
                        case 3:
                            backgroundPosition = '14px 2px, 2px 11px, 14px 11px, ' + backgroundPosition;
                            backgroundImage = 'url("'+resolveImageSource(ovIcs[0], "/images/overlays/ICON_SIZE", 8)+'"), url("'+resolveImageSource(ovIcs[1], "/images/overlays/ICON_SIZE", 8)+'"), url("'+resolveImageSource(ovIcs[2], "/images/overlays/ICON_SIZE", 8)+'"), url("'+resolveImageSource(metaData.get('icon'), "/images/mimes/ICON_SIZE", 16)+'")';
                        break;
                        case 4:
                        default:
                            backgroundPosition = '2px 2px, 14px 2px, 2px 11px, 14px 11px, ' + backgroundPosition;
                            backgroundImage = 'url("'+resolveImageSource(ovIcs[0], "/images/overlays/ICON_SIZE", 8)+'"), url("'+resolveImageSource(ovIcs[1], "/images/overlays/ICON_SIZE", 8)+'"), url("'+resolveImageSource(ovIcs[2], "/images/overlays/ICON_SIZE", 8)+'"), url("'+resolveImageSource(ovIcs[3], "/images/overlays/ICON_SIZE", 8)+'"), url("'+resolveImageSource(metaData.get('icon'), "/images/mimes/ICON_SIZE", 16)+'")';
                        break;
                    }
                }
                textLabel.setStyle({
                    paddingLeft:'24px',
                    backgroundRepeat:'no-repeat',
                    backgroundPosition:backgroundPosition,
                    backgroundImage:backgroundImage
                });

				var innerSpan = new Element("span", {
					className:"list_selectable_span", 
					style:"cursor:default;display:block;"
				}).update(textLabel);

				innerSpan.ajxpNode = ajxpNode; // For draggable
				tableCell.insert(innerSpan);
				
				// Defer Drag'n'drop assignation for performances
                if(this.options.draggable == undefined || this.options.draggable === true){
                    window.setTimeout(function(){
                        if(ajxpNode.getAjxpMime() != "ajxp_recycle"){
                            var newDrag = new AjxpDraggable(
                                innerSpan,
                                {
                                    revert:true,
                                    ghosting:true,
                                    scroll:($('tree_container')?'tree_container':null),
                                    containerScroll: this.htmlElement.down('div.table_rows_container')
                                },
                                this,
                                'filesList'
                            );
                            if(this.protoMenu) this.protoMenu.addElements(innerSpan);
                        }
                        if(!ajxpNode.isLeaf())
                        {
                            AjxpDroppables.add(innerSpan, ajxpNode);
                        }
                    }.bind(this), 500);
                }
				
			}else if(s=="ajxp_modiftime"){
				var date = new Date();
				date.setTime(parseInt(metaData.get(s))*1000);
				newRow.ajxp_modiftime = date;
				tableCell.update('<span class="text_label'+fullview+'">' + formatDate(date) + '</span>');
			}
			else
			{
				var metaValue = metaData.get(s) || "";
				tableCell.update('<span class="text_label'+fullview+'">' + metaValue  + "</span>");
			}
			if(this.gridStyle == "grid"){
				tableCell.setAttribute('valign', 'top');				
				tableCell.setStyle({
					verticalAlign:'top', 
					borderRight:'1px solid #eee'
				});
				if(this.even){
					tableCell.setStyle({borderRightColor: '#fff'});					
				}
				if (tableCell.innerHTML == '') tableCell.innerHTML = '&nbsp;';
			}
			if(this._headerResizer && !this._headerResizer.options.useCSS3){
				tableCell.addClassName("resizer_"+i);
			}
			newRow.appendChild(tableCell);
			if(attributeList.get(s).modifier){
				var modifier = eval(attributeList.get(s).modifier);
				modifier(tableCell, ajxpNode, 'row');
			}
		}
        // test hidden modifiers
        var hiddenModifiers = $A();
        if(this.parsingCache.get("hiddenModifiers")){
            hiddenModifiers = this.parsingCache.get("hiddenModifiers");
        }else{
            this.hiddenColumns.each(function(col){
                try{
                    this.columnsDef.each(function(colDef){
                        if(colDef.attributeName == col && colDef.modifier){
                           var mod = eval(colDef.modifier);
                           hiddenModifiers.push(mod);
                        }
                    });
                }catch(e){}
            }.bind(this) );
            this.parsingCache.set("hiddenModifiers", hiddenModifiers);
        }
        hiddenModifiers.each(function(mod){
            mod(null,ajxpNode,'row', newRow);
        });
		tBody.appendChild(newRow);
        if(!replaceItem){
            if(this.even){
                $(newRow).addClassName('even');
            }
            this.even = !this.even;
        }else{
            if(replaceItem.hasClassName('even')) $(newRow).addClassName('even');
        }

        this.addInlineToolbar(textLabel ? textLabel : tableCell, ajxpNode);


        return newRow;
	},
	
	/**
	 * Populates a node as a thumbnail div
	 * @param ajxpNode AjxpNode
	 * @returns HTMLElement
	 */
	ajxpNodeToDiv: function(ajxpNode){
		var newRow = new Element('div', {
            className:"thumbnail_selectable_cell",
            id:slugString(ajxpNode.getPath())});
		var metadata = ajxpNode.getMetadata();
				
		var innerSpan = new Element('span', {style:"cursor:default;"});

        var img = this._previewFactory.generateBasePreview(ajxpNode);

        var textNode = ajxpNode.getLabel();
		var label = new Element('div', {
			className:"thumbLabel",
			title:textNode
		}).update(textNode);
		
		innerSpan.insert({"bottom":img});
		innerSpan.insert({"bottom":label});
		newRow.insert({"bottom": innerSpan});
		newRow.IMAGE_ELEMENT = img;
		newRow.LABEL_ELEMENT = label;
        if(ajxpNode.getMetadata().get("overlay_icon")){
            var ovDiv = new Element("div");
            var ovIcs = $A(ajxpNode.getMetadata().get("overlay_icon").split(","));
            var bgPos = $A();
            var bgImg = $A();
            var bgRep = $A();
            var index = 0;
            ovIcs.each(function(ic){
                bgPos.push('0px '+((index*12)+(index>0?2:0))+'px');
                bgImg.push("url('"+resolveImageSource(ovIcs[index], "/images/overlays/ICON_SIZE", 12)+"')");
                bgRep.push('no-repeat');
                index++;
            });


            ovDiv.setStyle({
                position: "absolute",
                top: "3px",
                right: "2px",
                height: ((ovIcs.length*12) + (ovIcs.length > 1 ? (ovIcs.length-1)*2 : 0 )) + "px",
                width: "12px",
                backgroundImage:bgImg.join(', '),
                backgroundPosition:bgPos.join(', '),
                backgroundRepeat:bgRep.join(', ')
            });
            innerSpan.insert({after:ovDiv});
        }

		this._htmlElement.insert(newRow);
			
		var modifiers ;
		if(!this.parsingCache.get('modifiers')){
			modifiers = $A();
			this.columnsDef.each(function(column){
				if(column.modifier){
					try{
						modifiers.push(eval(column.modifier));
					}catch(e){}
				}
			});
			this.parsingCache.set('modifiers', modifiers);			
		}else{
			modifiers = this.parsingCache.get('modifiers');
		}
		modifiers.each(function(el){
			el(newRow, ajxpNode, 'thumb');
		});

        this._previewFactory.enrichBasePreview(ajxpNode, newRow);
		
		// Defer Drag'n'drop assignation for performances
		if(!ajxpNode.isRecycle()){
			window.setTimeout(function(){
				var newDrag = new AjxpDraggable(newRow, {
					revert:true,
					ghosting:true,
					scroll:($('tree_container')?'tree_container':null),
					containerScroll:this.htmlElement.down(".selectable_div")
				}, this, 'filesList');
			}.bind(this), 500);
		}
		if(!ajxpNode.isLeaf())
		{
			AjxpDroppables.add(newRow, ajxpNode);
		}

        this.addInlineToolbar(newRow, ajxpNode);

        return newRow;
	},
		
	/**
	 * Populates a node as a thumbnail div
	 * @param ajxpNode AjxpNode
	 * @returns HTMLElement
	 */
	ajxpNodeToLargeDiv: function(ajxpNode){

        var largeRow = new Element('div', {
            className:"thumbnail_selectable_cell detailed",
            id:slugString(ajxpNode.getPath())+"-cont"
        });
        var metadataDiv = new Element("div", {className:"thumbnail_cell_metadata"});

        var newRow = new Element('div', {className:"thumbnail_selectable_cell", id:ajxpNode.getPath()});
		var metaData = ajxpNode.getMetadata();

		var innerSpan = new Element('span', {style:"cursor:default;"});

        var img = this._previewFactory.generateBasePreview(ajxpNode);

        var textNode = ajxpNode.getLabel();
		var label = new Element('div', {
			className:"thumbLabel",
			title:textNode
		}).update(textNode);

		innerSpan.insert({"bottom":img});
		//newRow.insert({"bottom":label});
		newRow.insert({"bottom": innerSpan});
		newRow.IMAGE_ELEMENT = img;
		newRow.LABEL_ELEMENT = label;
        if(ajxpNode.getMetadata().get("overlay_icon")){
            var ovDiv = new Element("div");
            var ovIcs = $A(ajxpNode.getMetadata().get("overlay_icon").split(","));
            var bgPos = $A();
            var bgImg = $A();
            var bgRep = $A();
            var index = 0;
            ovIcs.each(function(ic){
                bgPos.push('0px '+((index*12)+(index>0?2:0))+'px');
                bgImg.push("url('"+resolveImageSource(ovIcs[index], "/images/overlays/ICON_SIZE", 12)+"')");
                bgRep.push('no-repeat');
                index++;
            });


            ovDiv.setStyle({
                position: "absolute",
                top: "3px",
                right: "2px",
                height: ((ovIcs.length*12) + (ovIcs.length > 1 ? (ovIcs.length-1)*2 : 0 )) + "px",
                width: "12px",
                backgroundImage:bgImg.join(', '),
                backgroundPosition:bgPos.join(', '),
                backgroundRepeat:bgRep.join(', ')
            });
            newRow.setStyle({position:'relative'});
            innerSpan.insert({after:ovDiv});
        }

        largeRow.insert(newRow);
        largeRow.insert(label);
        largeRow.insert(metadataDiv);

        var attributeList;
        if(!this.parsingCache.get('attributeList')){
            attributeList = $H();
            var visibleColumns = this.getVisibleColumns();
            visibleColumns.each(function(column){
                attributeList.set(column.attributeName, column);
            });
            this.parsingCache.set('attributeList', attributeList);
        }else{
            attributeList = this.parsingCache.get('attributeList');
        }
        var first = false;
        var attKeys = attributeList.keys();
        for(var i = 0; i<attKeys.length;i++ ){
            var s = attKeys[i];
            var cell = new Element("span", {className:'metadata_chunk'});
            if(s == "ajxp_label")
            {
                continue;
            }else if(s=="ajxp_modiftime"){
                var date = new Date();
                date.setTime(parseInt(metaData.get(s))*1000);
                newRow.ajxp_modiftime = date;
                cell.update('<span class="text_label">' + formatDate(date) + '</span>');
            }else if(s == "filesize" && metaData.get(s) == "-"){

                continue;

            }else
            {
                var metaValue = metaData.get(s) || "";
                if(!metaValue) continue;
                cell.update('<span class="text_label">' + metaValue  + "</span>");
            }
            if(!first){
                metadataDiv.insert(new Element('span', {className:'icon-angle-right'}));
            }
            metadataDiv.insert(cell);
            first = false;
            if(attributeList.get(s).modifier){
                var modifier = eval(attributeList.get(s).modifier);
                modifier(cell, ajxpNode, 'row');
            }
        }

        this._htmlElement.insert(largeRow);



		var modifiers ;
		if(!this.parsingCache.get('modifiers')){
			modifiers = $A();
			this.columnsDef.each(function(column){
				if(column.modifier){
					try{
						modifiers.push(eval(column.modifier));
					}catch(e){}
				}
			});
			this.parsingCache.set('modifiers', modifiers);
		}else{
			modifiers = this.parsingCache.get('modifiers');
		}
		modifiers.each(function(el){
			el(newRow, ajxpNode, 'thumb');
		});

        this._previewFactory.enrichBasePreview(ajxpNode, newRow);

		// Defer Drag'n'drop assignation for performances
		if(!ajxpNode.isRecycle()){
			window.setTimeout(function(){
				var newDrag = new AjxpDraggable(largeRow, {
					revert:true,
					ghosting:true,
					scroll:($('tree_container')?'tree_container':null),
					containerScroll:this.htmlElement.down(".selectable_div")
				}, this, 'filesList');
			}.bind(this), 500);
		}
		if(!ajxpNode.isLeaf())
		{
			AjxpDroppables.add(largeRow, ajxpNode);
		}

        this.addInlineToolbar(largeRow, ajxpNode);

        return largeRow;
	},

    addInlineToolbar : function(element, ajxpNode){
        if(this._inlineToolbarOptions){
            var options = this._inlineToolbarOptions;
            if(!this._inlineToolbarOptions.unique){
                options = Object.extend(this._inlineToolbarOptions, {
                    attachToNode: ajxpNode
                });
            }
            var tBarElement = new Element('div', {id:"FL-tBar-"+this._instanciatedToolbars.size(), className:"FL-inlineToolbar" + (this._inlineToolbarOptions.unique?' FL-inlineToolbarUnique':' FL-inlineToolbarMultiple')});
            element.insert(tBarElement);
            var aT = new ActionsToolbar(tBarElement, options);
            aT.actions = ajaxplorer.actionBar.actions;
            aT.initToolbars();
            if(!this._inlineToolbarOptions.unique){
                var dm = (this._dataModel?this._dataModel:ajaxplorer.getContextHolder());
                aT.registeredButtons.each(function(button){
                    // MAKE SURE THE CURRENT ROW IS SELECTED BEFORE TRIGGERING THE ACTION
                    button.stopObserving('click');
                    button.observe("click", function(event){
                        Event.stop(event);
                        dm.setSelectedNodes([ajxpNode]);
                        window.setTimeout(function(){
                            button.ACTION.apply();
                        }, 20);
                    });
                });
            }
            this._instanciatedToolbars.push(aT);
        }
    },

	partSizeCellRenderer : function(element, ajxpNode, type){
        if(!element) return;
		element.setAttribute("data-sorter_value", ajxpNode.getMetadata().get("bytesize"));
		if(!ajxpNode.getMetadata().get("target_bytesize")){
			return;
		}
		var percent = parseInt( parseInt(ajxpNode.getMetadata().get("bytesize")) / parseInt(ajxpNode.getMetadata().get("target_bytesize")) * 100  );
		var uuid = 'ajxp_'+(new Date()).getTime();		
		var div = new Element('div', {style:'padding-left:3px;', className:'text_label'}).update('<span class="percent_text" style="line-height:19px;padding-left:5px;">'+percent+'%</span>');
		var span = new Element('span', {id:uuid}).update('0%');		
		var options = {
			animate		: true,										// Animate the progress? - default: true
			showText	: false,									// show text with percentage in next to the progressbar? - default : true
			width		: 80,										// Width of the progressbar - don't forget to adjust your image too!!!
			boxImage	: window.ajxpResourcesFolder+'/images/progress_box_80.gif',			// boxImage : image around the progress bar
			barImage	: window.ajxpResourcesFolder+'/images/progress_bar_80.gif',	// Image to use in the progressbar. Can be an array of images too.
			height		: 8,										// Height of the progressbar - don't forget to adjust your image too!!!
            visualStyle : 'position:relative;'
		};
		element.update(div);
		div.insert({top:span});
		if(ajxpNode.getMetadata().get("process_stoppable")){
			var stopButton = new Element('a', {className:'pg_cancel_button'}).update("X");
			stopButton.observe("click", function(){
				var conn = new Connexion();
				conn.setParameters({
					action: 'stop_dl',
					file : ajxpNode.getPath(),
					dir : this.getCurrentContextNode().getPath()
				});
				conn.onComplete = function(transport){
					if(transport.responseText == 'stop' && $(uuid).pe) {
						$(uuid).pe.stop();
						$(uuid).pgBar.setPercentage(0);
						window.setTimeout(function(){
							ajaxplorer.actionBar.fireAction("refresh");
						}, 2);
					}
				};
				conn.sendAsync();
			});
			div.insert({bottom:stopButton});			
		}
		span.setAttribute('data-target_size', ajxpNode.getMetadata().get("target_bytesize"));
		window.setTimeout(function(){
			span.pgBar = new JS_BRAMUS.jsProgressBar(span, percent, options);
			var pe = new PeriodicalExecuter(function(){
				if(!$(uuid)){ 
					pe.stop();
					return;
				}
				var conn = new Connexion();
				conn.setParameters({
					action: 'update_dl_data',
					file : ajxpNode.getPath()
				});
				conn.onComplete = function(transport){
					if(transport.responseText == 'stop'){
						pe.stop();
						ajaxplorer.actionBar.fireAction("refresh");
					}else{
						var newPercentage = parseInt( parseInt(transport.responseText)/parseInt($(uuid).getAttribute('data-target_size'))*100 );
						$(uuid).pgBar.setPercentage(newPercentage);
						$(uuid).next('span.percent_text').update(newPercentage+"%");
					}
				};
				conn.sendAsync();
			}, 2);
			$(uuid).pe = pe;
		}, 2);
	},
	
	/**
	 * Resize the thumbnails
	 * @param one_element HTMLElement Optionnal, if empty all thumbnails are resized.
	 */
	resizeThumbnails: function(one_element){
			
		var elList;
		if(one_element) elList = [one_element]; 
		else elList = this._htmlElement.select('div.thumbnail_selectable_cell');
		elList.each(function(element){
            if(element.up('div.thumbnail_selectable_cell.detailed')) return;
			var node = element.ajxpNode;
			var image_element = element.IMAGE_ELEMENT || element.down('img');
			var label_element = element.LABEL_ELEMENT || element.down('.thumbLabel');
            var elementsAreSiblings = (label_element && (label_element.siblings().indexOf(image_element) !== -1));
            var tSize = (this._displayMode=='detail'? this._detailThumbSize:this._thumbSize);
            if(element.down('div.thumbnail_selectable_cell')){
                element.down('div.thumbnail_selectable_cell').setStyle({width:tSize+5+'px', height:tSize+10 +'px'});
            }else{
                element.setStyle({width:tSize+25+'px', height:tSize+ 30 +'px'});
            }
            this._previewFactory.setThumbSize(tSize);
            if(image_element){
                this._previewFactory.resizeThumbnail(image_element);
            }
            if(label_element){
                // RESIZE LABEL
                var el_width = (!elementsAreSiblings ? (element.getWidth() - tSize - 10)  : (tSize + 25) ) ;
                var charRatio = 6;
                var nbChar = parseInt(el_width/charRatio);
                var label = new String(label_element.getAttribute('title'));
                //alert(element.getAttribute('text'));
                label_element.innerHTML = label.truncate(nbChar, '...');
            }

		}.bind(this));
		
	},
	/**
	 * For list mode, recompute alternate BG distribution
	 * Should use CSS3 when possible!
	 */
	redistributeBackgrounds: function(){
		var allItems = this.getItems();		
		this.even = false;
		for(var i=0;i<allItems.length;i++){
			if(this.even){
				$(allItems[i]).addClassName('even').removeClassName('odd');				
			}else{
				$(allItems[i]).removeClassName('even').addClassName('odd');
			}
			this.even = !this.even;
		}
	},
	/**
	 * Clear the current lines/thumbs 
	 */
	removeCurrentLines: function(skipFireChange){
        this.notify("rows:willClear");
        if(this._instanciatedToolbars && this._instanciatedToolbars.size()){
            this._instanciatedToolbars.invoke('destroy');
            this._instanciatedToolbars = $A();
        }
		var rows;
		if(this._displayMode == "list") rows = $(this._htmlElement).select('tr');
		else rows = $(this._htmlElement).select('div.thumbnail_selectable_cell');
		for(var i=0; i<rows.length;i++)
		{
			try{
                if(rows[i].ajxpNode){
                    if(rows[i].REPLACE_OBS) rows[i].ajxpNode.stopObserving("node_replaced", rows[i].REPLACE_OBS);
                    if(rows[i].REMOVE_OBS) rows[i].ajxpNode.stopObserving("node_removed", rows[i].REMOVE_OBS);
                }
                rows[i].innerHTML = '';
				if(rows[i].IMAGE_ELEMENT){
                    if(rows[i].IMAGE_ELEMENT.destroyElement){
                        rows[i].IMAGE_ELEMENT.destroyElement();
                    }
					rows[i].IMAGE_ELEMENT = null;
					// Does not work on IE, silently catch exception
					delete(rows[i].IMAGE_ELEMENT);
				}

            }catch(e){
			}			
			if(rows[i].parentNode){
				rows[i].remove();
			}
		}
		if(!skipFireChange) this.fireChange();
        this.notify("rows:didClear");
	},
	/**
	 * Add a "loading" image on top of the component
	 */
	setOnLoad: function()	{
		if(this.loading) return;
        this.htmlElement.setStyle({position:'relative'});
        var element = this.htmlElement; // this.htmlElement.down('.selectable_div,.table_rows_container') || this.htmlElement;
		addLightboxMarkupToElement(element);
		var img = new Element('img', {
			src : ajxpResourcesFolder+'/images/loadingImage.gif'
		});
		var overlay = this.htmlElement.down("#element_overlay");
		overlay.insert(img);
		img.setStyle({marginTop : Math.max(0, (overlay.getHeight() - img.getHeight())/2) + "px"});
		this.loading = true;
	},
	/**
	 * Remove the loading image
	 */
	removeOnLoad: function(){
        var element = this.htmlElement; //this.htmlElement.down('.selectable_div,.table_rows_container') || this.htmlElement;
		removeLightboxFromElement(element);
		this.loading = false;
	},
	
	/**
	 * Overrides base fireChange function
	 */
	fireChange: function()
	{		
		if(this._fireChange){
            if(this._dataModel){
                this._dataModel.setSelectedNodes(this.getSelectedNodes());
            }else{
                ajaxplorer.updateContextData(null, this.getSelectedNodes(), this);
            }
		}
	},
	
	/**
	 * Overrides base fireDblClick function
	 */
	fireDblClick: function (e) 
	{
		if(this.getCurrentContextNode().getAjxpMime() == "ajxp_recycle")
		{
			return; // DO NOTHING IN RECYCLE BIN
		}
		selRaw = this.getSelectedItems();
		if(!selRaw || !selRaw.length)
		{
			return; // Prevent from double clicking header!
		}
		var selNode = selRaw[0].ajxpNode;
        if(this._doubleClickListener){
            this._doubleClickListener(selNode);
            return;
        }
        if(this._dataModel) return;
		if(selNode.isLeaf())
		{
			ajaxplorer.getActionBar().fireDefaultAction("file");
		}
		else
		{
			ajaxplorer.getActionBar().fireDefaultAction("dir", selNode);
		}
	},

	/**
	 * Select a row/thum by its name
	 * @param fileName String
	 * @param multiple Boolean
	 */
	selectFile: function(fileName, multiple)
	{
		fileName = getBaseName(fileName);
		if(!ajaxplorer.getContextHolder().fileNameExists(fileName, true))
		{
			return;
		}
		var allItems = this.getItems();
		for(var i=0; i<allItems.length; i++)
		{
			if(getBaseName(allItems[i].ajxpNode.getPath()) == getBaseName(fileName))
			{
				this.setItemSelected(allItems[i], true);
			}
			else if(multiple==null)
			{
				this.setItemSelected(allItems[i], false);
			}
		}
		return;
	},
	
	/**
	 * Utilitary for selection behaviour
	 * @param target HTMLElement
	 */
	enableTextSelection : function(target){
		if (target.origOnSelectStart)
		{ //IE route
			target.onselectstart=target.origOnSelectStart;
		}
		target.unselectable = "off";
		target.style.MozUserSelect = "text";
	},
	
	/**
	 * Utilitary for selection behaviour
	 * @param target HTMLElement
	 * @param deep Boolean
	 */
	disableTextSelection: function(target, deep)
	{
		if (target.onselectstart)
		{ //IE route
			target.origOnSelectStart = target.onselectstart;
			target.onselectstart=function(){return false;};
		}
		target.unselectable = "on";
		target.style.MozUserSelect="none";
		$(target).addClassName("no_select_bg");
		if(deep){
			$(target).select("td,img,div,span").each(function(td){
				this.disableTextSelection(td);
			}.bind(this));
		}
	},
	
	/**
	 * Handler for keyDown event
	 * @param event Event
	 * @returns Boolean
	 */
	keydown: function (event)
	{
		if(ajaxplorer.blockNavigation || this.blockNavigation) return false;
		if(event.keyCode == 9 && !ajaxplorer.blockNavigation) return false;
		if(!this.hasFocus) return true;
		var keyCode = event.keyCode;
		if(this._displayMode != "thumb" && keyCode != Event.KEY_UP && keyCode != Event.KEY_DOWN && keyCode != Event.KEY_RETURN && keyCode != Event.KEY_END && keyCode != Event.KEY_HOME)
		{
			return true;
		}
		if(this._displayMode == "thumb" && keyCode != Event.KEY_UP && keyCode != Event.KEY_DOWN && keyCode != Event.KEY_LEFT && keyCode != Event.KEY_RIGHT &&  keyCode != Event.KEY_RETURN && keyCode != Event.KEY_END && keyCode != Event.KEY_HOME)
		{
			return true;
		}
		var items = this._selectedItems;
		if(items.length == 0) // No selection
		{
			return false;
		}
		
		// CREATE A COPY TO COMPARE WITH AFTER CHANGES
		// DISABLE FIRECHANGE CALL
		var oldFireChange = this._fireChange;
		this._fireChange = false;
		var selectedBefore = this.getSelectedItems();	// is a cloned array
		
		
		Event.stop(event);
		var nextItem;
		var currentItem;
		var shiftKey = event['shiftKey'];
		currentItem = items[items.length-1];
		var allItems = this.getItems();
		var currentItemIndex = this.getItemIndex(currentItem);
		var selectLine = false;
		//ENTER
		if(event.keyCode == Event.KEY_RETURN)
		{
			for(var i=0; i<items.length; i++)
			{
				this.setItemSelected(items[i], false);
			}
			this.setItemSelected(currentItem, true);
			this.fireDblClick(null);
			this._fireChange = oldFireChange;
			return false;
		}
		if(event.keyCode == Event.KEY_END)
		{
			nextItem = allItems[allItems.length-1];
			if(shiftKey && this._multiple){
				selectLine = true;
				nextItemIndex = allItems.length -1;
			}
		}
		else if(event.keyCode == Event.KEY_HOME)
		{
			nextItem = allItems[0];
			if(shiftKey && this._multiple){
				selectLine = true;
				nextItemIndex = 0;
			}
		}
		// UP
		else if(event.keyCode == Event.KEY_UP)
		{
			if(this._displayMode != 'thumb') nextItem = this.getPrevious(currentItem);
			else{			
				 nextItemIndex = this.findOverlappingItem(currentItemIndex, false);
				 if(nextItemIndex != null){ nextItem = allItems[nextItemIndex];selectLine = true;}
			}
		}
		else if(event.keyCode == Event.KEY_LEFT)
		{
			nextItem = this.getPrevious(currentItem);
		}
		//DOWN
		else if(event.keyCode == Event.KEY_DOWN)
		{
			if(this._displayMode != 'thumb') nextItem = this.getNext(currentItem);
			else{
				 nextItemIndex = this.findOverlappingItem(currentItemIndex, true);
				 if(nextItemIndex != null){ nextItem = allItems[nextItemIndex];selectLine = true;}
			}
		}
		else if(event.keyCode == Event.KEY_RIGHT)
		{
			nextItem = this.getNext(currentItem);
		}
		
		if(nextItem == null)
		{
			this._fireChange = oldFireChange;
			return false;
		}
		if(!shiftKey || !this._multiple) // Unselect everything
		{ 
			for(var i=0; i<items.length; i++)
			{
				this.setItemSelected(items[i], false);
			}		
		}
		else if(selectLine)
		{
			if(nextItemIndex >= currentItemIndex)
			{
				for(var i=currentItemIndex+1; i<nextItemIndex; i++) this.setItemSelected(allItems[i], !allItems[i]._selected);
			}else{
				for(var i=nextItemIndex+1; i<currentItemIndex; i++) this.setItemSelected(allItems[i], !allItems[i]._selected);
			}
		}
		this.setItemSelected(nextItem, !nextItem._selected);
		
		
		// NOW FIND CHANGES IN SELECTION!!!
		var found;
		var changed = selectedBefore.length != this._selectedItems.length;
		if (!changed) {
			for (var i = 0; i < selectedBefore.length; i++) {
				found = false;
				for (var j = 0; j < this._selectedItems.length; j++) {
					if (selectedBefore[i] == this._selectedItems[j]) {
						found = true;
						break;
					}
				}
				if (!found) {
					changed = true;
					break;
				}
			}
		}
	
		this._fireChange = oldFireChange;
		if (changed && this._fireChange){
			this.fireChange();
		}		
		
		return false;
	},
	/**
	 * Utilitary to find the next item to select, depending on the key (up or down) 
	 * @param currentItemIndex Integer
	 * @param bDown Boolean
	 * @returns Integer|null
	 */
	findOverlappingItem: function(currentItemIndex, bDown)
	{	
		if(!bDown && currentItemIndex == 0) return null;
		var allItems = this.getItems();
		if(bDown && currentItemIndex == allItems.length - 1) return null;
		
		var element = $(allItems[currentItemIndex]);	
		var pos = Position.cumulativeOffset(element);
		var dims = Element.getDimensions(element);
		var searchingPosX = pos[0] + parseInt(dims.width/2);
		if(bDown){
			var searchingPosY = pos[1] + parseInt(dims.height*3/2);
			for(var i=currentItemIndex+1; i<allItems.length;i++){
				if(Position.within($(allItems[i]), searchingPosX, searchingPosY))
				{
					return i;
				}
			}
			return null;
		}else{
			var searchingPosY = pos[1] - parseInt(dims.height/2);
			for(var i=currentItemIndex-1; i>-1; i--){
				if(Position.within($(allItems[i]), searchingPosX, searchingPosY))
				{
					return i;
				}
			}
			return null;
		}
	},	
	
	/**
	 * Check if a domnode is indeed an item of the list
	 * @param node DOMNode
	 * @returns Boolean
	 */
	isItem: function (node) {
		if(this._displayMode == "list")
		{
			return node != null && ( node.tagName == "TR" || node.tagName == "tr") &&
				( node.parentNode.tagName == "TBODY" || node.parentNode.tagName == "tbody" )&&
				node.parentNode.parentNode == this._htmlElement;
		}
		else
		{
			return node != null && ( node.tagName == "DIV" || node.tagName == "div") &&
				node.parentNode == this._htmlElement;
		}
	},
	
	/* Indexable Collection Interface */
	/**
	 * Get all items
	 * @returns Array
	 */
	getItems: function () {
		if(this._displayMode == "list")
		{
			return this._htmlElement.rows;
		}
		else
		{
			var tmp = [];
			var j = 0;
			var cs = this._htmlElement.childNodes;
			var l = cs.length;
			for (var i = 0; i < l; i++) {
				if (cs[i].nodeType == 1)
					tmp[j++] = cs[i];
			}
			return tmp;
		}
	},
	/**
	 * Find an item index
	 * @param el HTMLElement
	 * @returns Integer
	 */
	getItemIndex: function (el) {
		if(this._displayMode == "list")
		{
			return el.rowIndex;
		}
		else
		{
			var j = 0;
			var cs = this._htmlElement.childNodes;
			var l = cs.length;
			for (var i = 0; i < l; i++) {
				if (cs[i] == el)
					return j;
				if (cs[i].nodeType == 1)
					j++;
			}
			return -1;		
		}
	},
	/**
	 * Get an item by its index
	 * @param nIndex Integer
	 * @returns HTMLElement
	 */
	getItem: function (nIndex) {
		if(this._displayMode == "list")
		{
			return this._htmlElement.rows[nIndex];
		}
		else
		{
			var j = 0;
			var cs = this._htmlElement.childNodes;
			var l = cs.length;
			for (var i = 0; i < l; i++) {
				if (cs[i].nodeType == 1) {
					if (j == nIndex)
						return cs[i];
					j++;
				}
			}
			return null;
		}
	}

/* End Indexable Collection Interface */
});
