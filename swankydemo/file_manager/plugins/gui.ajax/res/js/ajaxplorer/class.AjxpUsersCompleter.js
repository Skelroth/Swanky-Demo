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

Class.create("AjxpUsersCompleter", Ajax.Autocompleter, {

    createUserEntry : function(isGroup, isTemporary, entryId, entryLabel, skipObservers){
        var spanLabel = new Element("span", {className:"user_entry_label"}).update(entryLabel);
        var li = new Element("div", {className:"user_entry"}).update(spanLabel);
        if(isGroup){
            li.addClassName("group_entry");
        }else if(isTemporary){
            li.addClassName("user_entry_temp");
        }
        li.writeAttribute("data-entry_id", entryId);
        li.insert({bottom:'<span style="display: none;" class="delete_user_entry">&nbsp;</span>'});

        if(!skipObservers){
            li.setStyle({opacity:0});
            li.observe("mouseover", function(event){li.down('span.delete_user_entry').show();});
            li.observe("mouseout", function(event){li.down('span.delete_user_entry').hide();});
            li.down("span.delete_user_entry").observe("click", function(){
                Effect.Fade(li, {duration:0.3, afterFinish:li.remove.bind(li)});
            });
            li.appendToList = function(htmlObject){
                htmlObject.insert({bottom:li});
                Effect.Appear(li, {duration:0.3});
            };
        }

        return li;

    },

    initialize: function(textElement, listElement, update, options) {

        var entryTplGenerator = this.createUserEntry.bind(this);

        if(!options.indicator){
            // ADD INDICATOR;
        }
        if(!options.minChars){
            options.minChars = 3;
        }
        if(options.tmpUsersPrefix){
            var pref = options.tmpUsersPrefix;
        }
        if(options.updateUserEntryAfterCreate){
            var entryTplUpdater = options.updateUserEntryAfterCreate;
        }
        if(options.createUserPanel){
            var createUserPanel = options.createUserPanel.panel;
            var createUserPass = options.createUserPanel.pass;
            var createUserConfirmPass = options.createUserPanel.confirmPass;
        }

        options = Object.extend(
        {
            paramName:'value',
            tokens:[',', '\n'],
            frequency:0.1,
            tmpUsersPrefix:'',
            updateUserEntryAfterCreate:null,
            createUserPanel:null,
            afterUpdateElement: function(element, selectedLi){
                var id = Math.random();
                var label = selectedLi.getAttribute("data-label");
                var entryId = selectedLi.getAttribute("data-entry_id");
                if(selectedLi.getAttribute("data-temporary") && pref && ! label.startsWith(pref)){
                    label = pref + label;
                }
                var li = entryTplGenerator(selectedLi.getAttribute("data-group")?true:false,
                    selectedLi.getAttribute("data-temporary")?true:false,
                    selectedLi.getAttribute("data-group")?selectedLi.getAttribute("data-group"):(entryId ? entryId : label),
                    label
                );

                if(entryTplUpdater){
                    entryTplUpdater(li);
                }

                if(selectedLi.getAttribute("data-temporary") && createUserPanel != null){
                    element.readOnly = true;
                    createUserPass.setValue(""); createUserConfirmPass.setValue("");
                    element.setValue(MessageHash["449"].replace("%s", label));
                    createUserPanel.select('div.dialogButtons>input').invoke("addClassName", "dialogButtons");
                    createUserPanel.select('div.dialogButtons>input').invoke("stopObserving", "click");
                    createUserPanel.select('div.dialogButtons>input').invoke("observe", "click", function(event){
                        Event.stop(event);
                        var close = false;
                        if(event.target.name == "ok"){
                            if( !createUserPass.value || createUserPass.value.length < ajxpBootstrap.parameters.get('password_min_length')){
                                alert(MessageHash[378]);
                            }else if(createUserPass.getValue() == createUserConfirmPass.getValue()){
                                li.NEW_USER_PASSWORD = createUserPass.getValue();
                                li.appendToList(listElement);
                                close = true;
                            }
                        }else if(event.target.name.startsWith("can")){
                            close = true;
                        }
                        if(close) {
                            element.setValue("");
                            element.readOnly = false;
                            Effect.BlindUp('create_shared_user', {duration:0.4});
                            createUserPanel.select('div.dialogButtons>input').invoke("removeClassName", "dialogButtons");
                        }
                    });
                    Effect.BlindDown(createUserPanel, {duration:0.6, transition:Effect.Transitions.spring, afterFinish:function(){createUserPass.focus();}});
                }else{
                    element.setValue("");
                    li.appendToList(listElement);
                }
            }
        }, options);

        this.baseInitialize(textElement, update, options);
        this.options.asynchronous  = true;
        this.options.onComplete    = this.onComplete.bind(this);
        this.options.defaultParams = this.options.parameters || null;
        this.url                   = this.options.url || window.ajxpServerAccessPath + "&get_action=user_list_authorized_users";


        this.options.onComplete  = function(transport){
            var tmpElement = new Element('div');
            tmpElement.update(transport.responseText);
            listElement.select("div.user_entry").each(function(li){
                var found = tmpElement.down('[data-label="'+li.getAttribute("data-entry_id")+'"]');
                if(found) {
                    found.remove();
                }
            });
            this.updateChoices(tmpElement.innerHTML);
        }.bind(this);
        if(Prototype.Browser.IE){
            $(document.body).insert(update);
        }
        textElement.observe("click", function(){
            this.activate();
        }.bind(this));
    }
});