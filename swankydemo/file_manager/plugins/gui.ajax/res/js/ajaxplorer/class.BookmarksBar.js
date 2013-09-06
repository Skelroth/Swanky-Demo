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
 * Manages the display of the bookmarks menus. Was a "bookmark bar" but is now a Bookmark button and menu
 */
 Class.create("BookmarksBar", {

     bookmarksMenuOptions: {
         className: 'menu bookmarksMenu',
         mouseClick:'left',
         createAnchor:false,
         topOffset:2,
         leftOffset:0,
         fade:true,
         zIndex:2000
     },

	/**
	 * Constructor
	 * @param oElement HTMLElement The main element 
	 */
	initialize: function(oElement, options){
		this.element = $(oElement);
		this.currentCount = 0;	
		this.bookmarks = $A([]);
        if(options && options.bookmarksMenuOptions){
            this.bookmarksMenuOptions.menuTitle = MessageHash[145];
            this.bookmarksMenuOptions = Object.extend(this.bookmarksMenuOptions, options.bookmarksMenuOptions);

        }
		this.createMenu();
		document.observe("ajaxplorer:registry_loaded", function(event){
			this.parseXml(event.memo);
		}.bind(this) );
		document.observeOnce("ajaxplorer:actions_loaded", function(){
			var bmAction = ajaxplorer.actionBar.actions.get('bookmark');
			this.addBookmarkObject = {
				name:bmAction.getKeyedText(),
				alt:bmAction.options.title,
				image:ajxpResourcesFolder+'/images/actions/16/bookmark_add.png',
				callback:function(e){
                    document.notify("ajaxplorer:add_bookmark");
				}.bind(this)
			};		
		}.bind(this));
		document.observe("ajaxplorer:add_bookmark", function(){
			var node = ajaxplorer.getUserSelection().getUniqueNode();
            if(node.getMetadata().get('ajxp_bookmarked') && node.getMetadata().get('ajxp_bookmarked') == 'true'){
                this.removeBookmark(node.getPath(), function(){ajaxplorer.fireNodeRefresh(node);});
            }else{
                this.addBookmark(node.getPath(), node.getLabel(),function(){ajaxplorer.fireNodeRefresh(node);});
            }
		}.bind(this) );
	},
	/**
	 * Parses the registry to find the bookmarks definition
	 * @param registry XMLDocument
	 */
	parseXml: function(registry){
		this.clear();
		var childNodes = XPathSelectNodes(registry, "user/bookmarks/bookmark");
		for (var i=0; i < childNodes.length;i++)
		{
			var bookmark = {
				name:childNodes[i].getAttribute('title'),
				alt:childNodes[i].getAttribute('path'),
				image:ajxpResourcesFolder+'/images/mimes/16/folder.png',
                icon_class:'icon-star-empty'
			};
			bookmark.callback = function(e){ajaxplorer.goTo(this.alt);}.bind(bookmark);
			bookmark.moreActions = this.getContextActions(bookmark.alt, bookmark.name);
			this.bookmarks.push(bookmark);
		}
        if(this.bmMenu){
    		this.bmMenu.options.menuItems = this.bookmarks;
	    	this.bmMenu.refreshList();
        }
		if(this.bookmarks.length && this.element) this.element.removeClassName('inline_disabled');
		if(modal.pageLoading) modal.updateLoadingProgress('Bookmarks Loaded');
	},
	/**
	 * Creates the sub menu
	 */
	createMenu : function(){
        if(!this.element) return;
		this.bmMenu = new Proto.Menu(Object.extend(this.bookmarksMenuOptions, {
            anchor:this.element,
            menuItems: this.bookmarks
        }));
	},
		
	/**
	 * Remove all bookmarks and elements
	 */
	clear: function(){
		this.currentCount = 0;
		if(this.addBookmarkObject){
			this.bookmarks = $A([this.addBookmarkObject,{separator:true}]);
		}else{
			this.bookmarks = $A();
		}
		if(this.element) this.element.addClassName('inline_disabled');
        if(this.bmMenu){
            this.bmMenu.options.menuItems = this.bookmarks;
            this.bmMenu.refreshList();
        }
	},
	
	/**
	 * Gets the bookmark actions for a bookmark
	 * @param bmPath String
	 * @param bmTitle String
	 */
	getContextActions: function(bmPath, bmTitle){
		
		var removeAction = {
				name:MessageHash[146],
				alt:MessageHash[146],
				image:ajxpResourcesFolder+'/images/actions/16/delete_bookmark.png',
                icon_class:'icon-remove',
				disabled:false,
				className:"edit",
				callback:function(e){
					this.removeBookmark(bmPath);
				}.bind(this)
			};
		
		var renameAction = {
				name:MessageHash[6],
				alt:MessageHash[6],
				image:ajxpResourcesFolder+'/images/actions/16/applix.png',
                icon_class:'icon-edit',
				disabled:false,
				className:"edit",
				callback:function(e){
					this.toggleRenameForm(bmPath, bmTitle);
				}.bind(this)
			};
		
			
			
		return new Array(renameAction, removeAction);
	},
	
	/**
	 * Create a rename form for renaming bookmark
	 * @param bmPath String
	 * @param bmTitle String
	 */
	toggleRenameForm:function(bmPath, bmTitle){
		
		modal.prepareHeader(MessageHash[225], ajxpResourcesFolder+'/images/actions/16/bookmark.png');
	 	var onLoad = function(newForm){
	 		$(newForm).bm_path.value = bmPath;
	 		$(newForm).bm_title.value = bmTitle;
	 	};
	 	var onComplete = function(){	 		
	 		this.renameBookmark(modal.getForm().bm_path.value, modal.getForm().bm_title.value);
	 		hideLightBox(true);
	 	}.bind(this);
		modal.showDialogForm('Rename', 'rename_bookmark', onLoad, onComplete);
	},
	
	/**
	 * Reload the bookmarks via the registry loading
	 * @param actionsParameters Hash
	 */
	load: function(actionsParameters, silently, onComplete){
        if(!ajaxplorer || !ajaxplorer.user) return;
		var connexion = new Connexion();
		if(!actionsParameters) actionsParameters = new Hash();
		actionsParameters.set('get_action', 'get_bookmarks');
		connexion.setParameters(actionsParameters);
        if(onComplete){
            connexion.onComplete = onComplete;
        }else{
            connexion.onComplete = function(transport){
                document.observeOnce("ajaxplorer:registry_part_loaded", function(event){
                    if(event.memo != "user/bookmarks") return;
                    this.parseXml(ajaxplorer.getXmlRegistry());
                }.bind(this) );
                ajaxplorer.loadXmlRegistry(false, "user/bookmarks");
                if(this.bmMenu){
                    this.bmMenu.refreshList();
                    if(!silently) this.bmMenu.show();
                }
            }.bind(this);
        }
		connexion.sendAsync();
	},
	
	/**
	 * Add a bookmark
	 * @param path String
	 * @param title String
	 */
	addBookmark: function(path,title, onComplete){
		var parameters = new Hash();
		parameters.set('bm_action', 'add_bookmark');
		parameters.set('bm_path', path);
		if(title){
			parameters.set('bm_title', title);
		}
		this.load(parameters, false, onComplete);
	},
	
	/**
	 * Remove a bookmark
	 * @param path String
	 */
	removeBookmark: function(path, onComplete){
		var parameters = new Hash();
		parameters.set('bm_action', 'delete_bookmark');
		parameters.set('bm_path', path);
		this.load(parameters, false, onComplete);
	},
	
	/**
	 * Rename a bookmark
	 * @param path String
	 * @param title String
	 */
	renameBookmark: function(path, title){
		var parameters = new Hash();
		parameters.set('bm_action', 'rename_bookmark');
		parameters.set('bm_path', path);
		parameters.set('bm_title', title);
		this.load(parameters);		
	}
	
});
