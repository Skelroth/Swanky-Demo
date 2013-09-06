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
 * Abstract container any type of pane that can resize
 */
Class.create("AjxpPane", {	
	
	__implements : "IAjxpWidget",
    childrenPanes : null,
	
	/**
	 * Constructor
	 * @param htmlElement HTMLElement The Node anchor
	 * @param options Object The pane parameters
	 */
	initialize : function(htmlElement, options){
		this.htmlElement = $(htmlElement);
		if(!this.htmlElement){
			throw new Error('Cannot find element for AjxpPane : ' + this.__className);
		}
		this.options = options || {};
		this.htmlElement.ajxpPaneObject = this;
		if(this.htmlElement.getAttribute('ajxpPaneHeader')){
			this.addPaneHeader(
				this.htmlElement.getAttribute('ajxpPaneHeader'), 
				this.htmlElement.getAttribute('ajxpPaneIcon'));
		}
        if(this.htmlElement && this.options.elementStyle){
            this.htmlElement.setStyle(this.options.elementStyle);
        }
		this.childrenPanes = $A([]);
		this.scanChildrenPanes(this.htmlElement);
        if(this.options.bindSizeTo){
            if(this.options.bindSizeTo.width){
                this.options.bindSizeTo.width.events.each(function(eventName){
                    document.observe("ajaxplorer:" + eventName, this.resizeBound.bind(this));
                }.bind(this) );

            }
        }

    },

    resizeBound : function(event){
        "use strict";
        if(!$(this.options.bindSizeTo.width.id)) return;
        var min = this.options.bindSizeTo.width.min;
        if(Object.isString(min) && min.indexOf("%") != false) min = this.htmlElement.parentNode.getWidth() * min / 100;
        var w = Math.max($(this.options.bindSizeTo.width.id).getWidth() + this.options.bindSizeTo.width.offset, min);
        if(this.options.bindSizeTo.width.max) {
            var max = this.options.bindSizeTo.width.max;
            if(Object.isString(max) && max.indexOf("%") != false) max = this.htmlElement.parentNode.getWidth() * max / 100;
            w = Math.min(max, w);
        }
        if(this.options.bindSizeTo.width.checkSiblings){
            w = this.filterWidthFromSiblings(w);
        }
        this.htmlElement.setStyle({width: w + "px"});
        this.resize();

        if(this.options.bindSizeTo.width.checkSiblings){
            this.htmlElement.siblings().each(function(s){
                if(s.ajxpPaneObject){
                    s.ajxpPaneObject.resize();
                }
            });
        }
    },

    filterWidthFromSiblings : function(original){
        "use strict";
        var parentWidth = this.htmlElement.parentNode.getWidth();
        var siblingWidth = 0;
        this.htmlElement.siblings().each(function(s){
            if(s.hasClassName('skipSibling')) return;
            if(s.ajxpPaneObject && s.ajxpPaneObject.getActualWidth){
                siblingWidth+=s.ajxpPaneObject.getActualWidth();
            }else{
                siblingWidth+=s.getWidth();
            }
        });
        original = Math.min(original, parentWidth - siblingWidth - 20);
        return original;
    },

	/**
	 * Called when the pane is resized
	 */
	resize : function(){		
		// Default behaviour : resize children
    	if(this.options.fit && this.options.fit == 'height'){
    		var marginBottom = 0;
    		if(this.options.fitMarginBottom){
    			var expr = this.options.fitMarginBottom;
    			try{marginBottom = parseInt(eval(expr));}catch(e){}
    		}
    		fitHeightToBottom(this.htmlElement, (this.options.fitParent?$(this.options.fitParent):null), expr);
    	}
    	this.childrenPanes.invoke('resize');
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
        this.childrenPanes.each(function(child){
            child.destroy();
        });
        this.htmlElement.update("");
        if(window[this.htmlElement.id]){
            try{delete window[this.htmlElement.id];}catch(e){}
        }
		this.htmlElement = null;

	},
	
	/**
	 * Find and reference direct children IAjxpWidget
	 * @param element HTMLElement
	 */
	scanChildrenPanes : function(element){
        if(!element.childNodes) return;
		$A(element.childNodes).each(function(c){
			if(c.ajxpPaneObject) {
				if(!this.childrenPanes){
                    this.childrenPanes = $A();
                }
                this.childrenPanes.push(c.ajxpPaneObject);
			}else{
				this.scanChildrenPanes(c);
			}
		}.bind(this));
	},
	
	/**
	 * Show the main html element
	 * @param show Boolean
	 */
	showElement : function(show){
		if(show){
			this.htmlElement.show();
		}else{
			this.htmlElement.hide();
		}
	},
	
	/**
	 * Adds a simple haeder with a title and icon
	 * @param headerLabel String The title
	 * @param headerIcon String Path for the icon image
	 */
	addPaneHeader : function(headerLabel, headerIcon){
        var label = new Element('span', {ajxp_message_id:headerLabel}).update(MessageHash[headerLabel]);
        var header = new Element('div', {className:'panelHeader'}).update(label);
        if(headerIcon){
            var ic = resolveImageSource(headerIcon, '/images/actions/ICON_SIZE', 16);
            header.insert({top: new Element("img", {src:ic, className:'panelHeaderIcon'})});
            header.addClassName('panelHeaderWithIcon');
        }
        if(this.options.headerClose){
            var ic = resolveImageSource(this.options.headerClose.icon, '/images/actions/ICON_SIZE', 16);
            var img = new Element("img", {src:ic, className:'panelHeaderCloseIcon', title:MessageHash[this.options.headerClose.title]});
            header.insert({top: img});
            var sp = this.options.headerClose.splitter;
            img.observe("click", function(){
                window[sp]["fold"]();
            });
        }
		this.htmlElement.insert({top : header});
		disableTextSelection(header);

        if(this.options.headerToolbarOptions){
            var tbD = new Element('div', {id:"display_toolbar"});
            header.insert({top:tbD});
            var tb = new ActionsToolbar(tbD, this.options.headerToolbarOptions);
        }


    },
	
	/**
	 * Sets a listener when the htmlElement is focused to notify ajaxplorer object
	 */
	setFocusBehaviour : function(){
		this.htmlElement.observe("click", function(){
			if(ajaxplorer) ajaxplorer.focusOn(this);
		}.bind(this));
	},


    getUserPreference : function(prefName){
        if(!ajaxplorer || !ajaxplorer.user) return;
        var gui_pref = ajaxplorer.user.getPreference("gui_preferences", true);
        if(!gui_pref || !gui_pref[this.htmlElement.id+"_"+this.__className]) return;
        return gui_pref[this.htmlElement.id+"_"+this.__className][prefName];
    },

    setUserPreference : function(prefName, prefValue){
        if(!ajaxplorer || !ajaxplorer.user) return;
        var guiPref = ajaxplorer.user.getPreference("gui_preferences", true);
        if(!guiPref) guiPref = {};
        if(!guiPref[this.htmlElement.id+"_"+this.__className]) guiPref[this.htmlElement.id+"_"+this.__className] = {};
        if(guiPref[this.htmlElement.id+"_"+this.__className][prefName] && guiPref[this.htmlElement.id+"_"+this.__className][prefName] == prefValue){
            return;
        }
        guiPref[this.htmlElement.id+"_"+this.__className][prefName] = prefValue;
        ajaxplorer.user.setPreference("gui_preferences", guiPref, true);
        ajaxplorer.user.savePreference("gui_preferences");
    }

});