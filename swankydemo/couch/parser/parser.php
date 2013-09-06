<?php
    /*
    The contents of this file are subject to the Common Public Attribution License
    Version 1.0 (the "License"); you may not use this file except in compliance with
    the License. You may obtain a copy of the License at
    http://www.couchcms.com/cpal.html. The License is based on the Mozilla
    Public License Version 1.1 but Sections 14 and 15 have been added to cover use
    of software over a computer network and provide for limited attribution for the
    Original Developer. In addition, Exhibit A has been modified to be consistent with
    Exhibit B.
    
    Software distributed under the License is distributed on an "AS IS" basis, WITHOUT
    WARRANTY OF ANY KIND, either express or implied. See the License for the
    specific language governing rights and limitations under the License.
    
    The Original Code is the CouchCMS project.
    
    The Original Developer is the Initial Developer.
    
    The Initial Developer of the Original Code is Kamran Kashif (kksidd@couchcms.com). 
    All portions of the code written by Initial Developer are Copyright (c) 2009, 2010
    the Initial Developer. All Rights Reserved.
    
    Contributor(s):
    
    Alternatively, the contents of this file may be used under the terms of the
    CouchCMS Commercial License (the CCCL), in which case the provisions of
    the CCCL are applicable instead of those above.
    
    If you wish to allow use of your version of this file only under the terms of the
    CCCL and not to allow others to use your version of this file under the CPAL, indicate
    your decision by deleting the provisions above and replace them with the notice
    and other provisions required by the CCCL. If you do not delete the provisions
    above, a recipient may use your version of this file under either the CPAL or the
    CCCL.
    */
    
    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly
    
    define( 'K_START_TAG_IDENT', 'cms:' );
    define( 'K_END_TAG_IDENT', '/'.K_START_TAG_IDENT );
    
    define( 'K_NODE_TYPE_ROOT', 0 );
    define( 'K_NODE_TYPE_CODE', 1 );
    define( 'K_NODE_TYPE_TEXT', 2 );
    
    define( 'K_STATE_TEXT', 0 );
    define( 'K_STATE_TAG_OPEN', 1 );
    define( 'K_STATE_TAG_CLOSE', 2 );
    define( 'K_STATE_TAG_NAME', 3 );
    define( 'K_STATE_ATTR_NAME', 4 );
    define( 'K_STATE_ATTR_OP', 5 );
    define( 'K_STATE_ATTR_VAL', 6 );
    define( 'K_STATE_LOGIC_OP', 7 );

    define( 'K_VAL_TYPE_LITERAL', 1 );
    define( 'K_VAL_TYPE_VARIABLE', 2 );
    define( 'K_VAL_TYPE_SPECIAL', 3 );
    
    class KContext{
        var $ctx = array();
        // 'listfolders' and 'dropdownfolders' internally use 'folders' hence need a context
        // 'do_shortcodes' stores self object in $CTX hence needs a scope.
        var $support_scope = array('__ROOT__', 'test', 'hide', 'each', 'pages', 'folder', 'folders', 'listfolders', 'dropdownfolders', 'parentfolders', 'breadcrumbs', 'archives', 'form', 'paypal_processor', 'search', 'comments', 'query', 'link', 'calendar', 'weeks', 'days', 'entries', 'templates', 'capture', 'fields', 'do_shortcodes', 'nested_pages', 'nested_crumbs', 'menu', 'exif');
        // All tags that 'loop' (i.e. call 'foreach( $node->children as $child )' multiple times.
        var $support_zebra = array('__ROOT__',  'while', 'repeat', 'each', 'pages', 'folders', 'listfolders', 'dropdownfolders', 'parentfolders', 'archives', 'search', 'comments', 'query', 'weeks', 'days', 'entries', 'templates', 'fields', 'nested_pages', 'nested_crumbs', 'menu');
        
        function KContext(){
            
        }
        
        function push( $func_name ){
            $level = count( $this->ctx );
            $this->ctx[$level] = array();
            
            $this->ctx[$level]['name'] = $func_name;
            if( $func_name && in_array( $func_name, $this->support_scope) ){
                $this->ctx[$level]['_scope_'] = array();
                $this->ctx[$level]['_obj_'] = array();
            }
            if( $func_name && in_array( $func_name, $this->support_zebra) ){
                $this->ctx[$level]['_zebra_'] = array();
            }
        }
        
        function pop(){
            unset( $this->ctx[count($this->ctx)-1] );
        }
        
        /*
           'set' by default will set a variable only in the immediate scope (first scoped tag encountered)
           However if 'parent' is specified as second param, it searches
           upwards through the hierarchy and sets the variable at any parent's
           scope it finds it.
           If not found anywhere, the var is set at the default scope.
           
           If 'global' is set, the var is set at the root scope.
        */
        function set( $varname, $value, $scope='' ){
            if( is_bool($value) ){ $value = (int)$value; }
            
            if( $scope=='global' ){
                $this->ctx[0]['_scope_'][$varname] = $value;
                return; 
            }
            
            if( $scope=='parent' ){
                for( $x=count($this->ctx)-1; $x>=0; $x-- ){
                    if( isset($this->ctx[$x]['_scope_']) && isset($this->ctx[$x]['_scope_'][$varname]) ){
                        $this->ctx[$x]['_scope_'][$varname] = $value;
                        return; 
                    }
                }
            }
            
            for( $x=count($this->ctx)-1; $x>=0; $x-- ){
                if( isset($this->ctx[$x]['_scope_']) ){
                    $this->ctx[$x]['_scope_'][$varname] = $value;
                    return; 
                }
            }
            
        }
        
        // Same as above. Used internally to set variables in bulk in a single scope
        function set_all( $arr_vars, $scope='' ){
            if( is_array($arr_vars) && count($arr_vars) ){ 
                if( $scope=='global' ){
                    $ctx = &$this->ctx[0]['_scope_'];
                }
                else{
                    for( $x=count($this->ctx)-1; $x>=0; $x-- ){
                        if( isset($this->ctx[$x]['_scope_']) ){
                            $ctx = &$this->ctx[$x]['_scope_'];
                            break; 
                        }
                    }
                }
                
                // Set all the array elements into the selected context
                foreach( $arr_vars as $varname=>$value ){
                    if( is_bool($value) ){ $value = (int)$value; }
                    $ctx[$varname] = $value;
                }
            }
        }
        
        /*
         * 'get' by default will fetch a var by searching upwards through the 
         * hierarchy of scopes.
         * However, if 'local' is specified, it will look only in the immediate
         * scope, returning null if var not found here.
         */
        function get( $varname, $local=false ){
            if( $local ){
                // search only in local scope
                for( $x=count($this->ctx)-1; $x>=0; $x-- ){
                    if( isset($this->ctx[$x]['_scope_']) ){
                        return $this->ctx[$x]['_scope_'][$varname];
                    }
                }
            }
            
            for( $x=count($this->ctx)-1; $x>=0; $x-- ){
                if( isset($this->ctx[$x]['_scope_']) && isset($this->ctx[$x]['_scope_'][$varname]) ){
                    return $this->ctx[$x]['_scope_'][$varname];
                }
            }
            return null;
        }
        
        // For internal use. Sets an object into the first scoped tag found fanning outwards
        // or directly into the root if 'global' set.
        function set_object( $objname, &$obj, $scope='' ){
            
            if( $scope=='global' ){
                $this->ctx[0]['_obj_'][$objname] = &$obj;
                return; 
            }
            
            for( $x=count($this->ctx)-1; $x>=0; $x-- ){
                if( isset($this->ctx[$x]['_scope_']) ){
                    $this->ctx[$x]['_obj_'][$objname] = &$obj;
                    return; 
                }
            }
            
        }
        
        // For internal use. Gets an object from (possibly a specified tag's) scope
        function &get_object( $objname, $tagname=null ){
            
            for( $x=count($this->ctx)-1; $x>=0; $x-- ){
                if( !is_null($tagname) ){
                    if( ($this->ctx[$x]['name']==$tagname) && isset($this->ctx[$x]['_scope_']) && isset($this->ctx[$x]['_obj_'][$objname]) ){
                        return $this->ctx[$x]['_obj_'][$objname];
                    }
                }
                else{
                    if( isset($this->ctx[$x]['_scope_']) && isset($this->ctx[$x]['_obj_'][$objname]) ){
                        return $this->ctx[$x]['_obj_'][$objname];
                    }
                }
            }
            return null;
        }
        
        function set_zebra( $varname, $value ){
            for( $x=count($this->ctx)-1; $x>=0; $x-- ){
                if( isset($this->ctx[$x]['_zebra_']) ){
                    $this->ctx[$x]['_zebra_'][$varname] = $value;
                    return; 
                }
            }
        }
        
        function get_zebra( $varname ){
            for( $x=count($this->ctx)-1; $x>=0; $x-- ){
                if( isset($this->ctx[$x]['_zebra_']) ){
                    return isset($this->ctx[$x]['_zebra_'][$varname]) ? $this->ctx[$x]['_zebra_'][$varname] : null;
                }
            }
            return null;
        }        
    }
    
    class KNode{
        var $type;
        var $name;
        var $attributes = array();
        var $text = NULL;
        var $ID;
        var $line_num;
        var $char_num;
        var $children = array();
        
        function KNode( $type, $name='', $attr='', $text='' ){
            $this->type = $type;
            $this->name = $name;
            if( is_array($attr) ) $this->attributes = $attr;
            $this->text = $text;
        }
        
        function get_HTML(){
            global $TAGS, $CTX, $FUNCS, $PAGE, $AUTH;
            
           
            switch( $this->type ){
                case K_NODE_TYPE_ROOT:
                    if( count($CTX->ctx)==0 ){ //The very root (not nested or embedded)
                        $CTX->push( '__ROOT__' );
                        // Set user info in context
                        if( $AUTH ){
                            $FUNCS->set_userinfo_in_context();
                        }
                        // Set page info in context
                        if( $PAGE ){
                            $PAGE->set_context();
                        }
                    }
                    else{
                        $CTX->push( '__NESTED_ROOT__' );
                    }
                    foreach( $this->children as $node ){
                        $html .= $node->get_HTML();
                    }
                    break;
                case K_NODE_TYPE_TEXT:
                    $CTX->push( $this->name );
                    $html = $this->text;
                    break;
                case K_NODE_TYPE_CODE:
                    $CTX->push( $this->name );
                    $func = $this->name;
                    if( $this->name=='if' || $this->name=='else' || $this->name=='while' ) $func = 'k_'.$func;
                    
                    if( method_exists($TAGS, $func) ){
                       $params = $FUNCS->resolve_parameters( $this->attributes );
                       $html = call_user_func( array($TAGS, $func), $params, $this );
                    }
                    else{
                        $tagname = $this->name;
                        if( array_key_exists( $tagname, $FUNCS->tags) ){
                            $params = $FUNCS->resolve_parameters( $this->attributes );
                            $html = call_user_func( $FUNCS->tags[$tagname]['handler'], $params, $this );
                        }
                        else{
                            // after search in installed modules..
                            ob_end_clean();
                            die( 'ERROR! Unknown tag: "'. $this->name . '"'  );
                        }
                    }
                    break;
            }
            
            // Pop the context
            $CTX->pop();
            return $html;
        }
        
        function get_info( $level=0 ){
            for( $x=0; $x<$level*5; $x++ ){
                $lead .= '&nbsp;';
            }
            
            switch( $this->type ){
                case K_NODE_TYPE_ROOT:
                    break;
                case K_NODE_TYPE_TEXT:
                    if( strlen(trim($this->text)) ){
                        $html = 'TEXT: ';
                        $html .= htmlentities(substr( $this->text, 0, 10 ), ENT_QUOTES) . '.....' .htmlentities(substr( $this->text, -10 ), ENT_QUOTES);
                        $html = $lead. $html. '<BR>';
                    }
                    break;
                case K_NODE_TYPE_CODE;
                    $html = 'TAG: ';
                    $html .= $this->name . '  (';
                    $sep = '';
                    foreach( $this->attributes as $attr ){
                        $name = isset($attr['name'])?$attr['name']:'unnamed';
                        $html .=  $sep . $name . ' ' . $attr['op'] . ' ';
                        if( $attr['value_type'] != K_VAL_TYPE_SPECIAL ){
                            $html .=  htmlentities($attr['value'])  ;
                            ($attr['value_type']=='literal') ? $type='literal' : $type='variable';
                            $html .= ' ['. $type . ']';
                        }
                        else{
                            $node = &$attr['value'];
                            $html .=  '<br>' . $node->get_info( $level+1 );
                            $html .= ' [special]';   
                        }
                        $sep = ', ';
                    }
                    $html .= ')';
                    $html = $lead. $html. '<BR>';
                    break;
            }
           
            // Now for the children
            foreach( $this->children as $node ){
                $html .= $node->get_info( $level+1 );
            }
            return $html;
        }
        
    }
    
    class KParser{
        var $str;
        var $line_num;
        var $pos;
        var $id_prefix;
        var $DOM;
        var $curr_node;
        var $stack;
        var $parsed;
        var $quit_at_char;
        var $cond_ops = array("==", "!=", "lt", "gt", "le", "ge", "eq", "ne");
        var $logical_ops = array("&&", "||");
        
        function KParser( &$str, $line_num=0, $pos=0, $quit_at_char='', $id_prefix='' ){
            $this->str = &$str;
            $this->line_num = $line_num;
            $this->pos = $pos;
            $this->quit_at_char = $quit_at_char;
            $this->id_prefix = $id_prefix;
            
            $this->state = K_STATE_TEXT;
            $this->stack = array();
            $this->curr_node = new KNode( K_NODE_TYPE_ROOT );
            $this->DOM = &$this->curr_node;
        }
        
        
        function &get_DOM(){
            if( !$this->parsed ){
                $starts = $this->pos;
                $len = strlen( $this->str );
                
                $tag_name = '';
                $closing_tag_name = '';
                $attributes = array();
                $attr = null;
                $quote_type = 0;
                
                $processing_cond = false; // Conditional tags requires special consideration
                $brackets_count=0;
                
                while( $this->pos<$len ){
                    $c = $this->str{$this->pos};
                    if( $c=="\n" ) $this->line_num++;
                    
                    switch( $this->state ){
                        case K_STATE_TEXT:
                            if( $c=='<' ){
                                if( substr($this->str, $this->pos+1, strlen(K_START_TAG_IDENT))==K_START_TAG_IDENT ){
                                    $text = substr($this->str, $starts, $this->pos-$starts);
                                    if( $this->quit_at_char == '"' ) $text = str_replace( '\\"', '"', $text );
                                    $this->add_child( K_NODE_TYPE_TEXT, '', '', $text );
                                    $this->pos += strlen(K_START_TAG_IDENT);
                                    $starts = $this->pos + 1;
                                    $this->state = K_STATE_TAG_NAME;
                                }
                                elseif( substr($this->str, $this->pos+1, strlen(K_END_TAG_IDENT))==K_END_TAG_IDENT ){
                                    $text = substr($this->str, $starts, $this->pos-$starts);
                                    if( $this->quit_at_char == '"' ) $text = str_replace( '\\"', '"', $text );
                                    $this->add_child( K_NODE_TYPE_TEXT, '', '', $text );
                                    $this->pos += strlen(K_END_TAG_IDENT);
                                    $starts = $this->pos+1;
                                    $this->state = K_STATE_TAG_CLOSE;
                                }
                            }
                            elseif( $this->quit_at_char && $c==$this->quit_at_char ){
                                if( $this->str{$this->pos-1} != '\\' ){
                                    break 2;
                                }
                            }
                            break;
                        case K_STATE_TAG_OPEN:
                            if( $processing_cond && $brackets_count ){
                                $this->raise_error( "Unclosed bracket in \"" .$tag_name. "\"" , $this->line_num, $this->pos );
                            }
                            
                            if( isset($attr) ) $attributes[] = $attr;
                            for( $x=0; $x<count($attributes); $x++ ){
                                $attr = &$attributes[$x];
                                if( !isset($attr['value']) && isset($attr['name']) ){
                                    $attr['value'] = $attr['name'];
                                    $attr['value_type'] = K_VAL_TYPE_VARIABLE;
                                    if( !$processing_cond ) $attr['op'] = '=';
                                    unset( $attr['name'] );
                                }
                                elseif( !$processing_cond && !isset($attr['name']) && isset($attr['value']) ){
                                    $attr['op'] = '=';
                                }
                                
                                if( $attr['value_type'] == K_VAL_TYPE_LITERAL ){
                                    $quote_type = $attr['quote_type'];
                                    $attr['value'] = str_replace( '\\'.$quote_type, $quote_type, $attr['value'] );
                                }
                            }
                            $push = $this->str{$this->pos-1} != '/';
                            $this->add_child( K_NODE_TYPE_CODE, $tag_name, $attributes, '', $push );
                            
                            $processing_cond = false;
                            $brackets_count = 0;
                            
                            $starts = $this->pos+1;
                            $this->state = K_STATE_TEXT;
                            break;
                        case K_STATE_TAG_CLOSE:
                            if( $c=='>' ){
                                $closing_tag_name = trim(substr( $this->str, $starts, $this->pos-$starts ));
                                if( $this->curr_node->name != $closing_tag_name ){
                                    $this->raise_error( "Closing tag \"".$closing_tag_name ."\" has no matching opening tag" , $this->line_num, $this->pos );
                                }
                                
                                unset( $this->curr_node );
                                $this->curr_node = &$this->stack[count($this->stack)-1];
                                unset( $this->stack[count($this->stack)-1] );
                                
                                $starts = $this->pos+1;
                                $this->state = K_STATE_TEXT;
                            }
                            break;
                        case K_STATE_TAG_NAME:
                            if( !($this->pos == $starts ? $this->is_valid_for_label($c, 0) : $this->is_valid_for_label($c)) ){
                                if( $this->is_white_space($c) && $this->pos!=$starts ){
                                    $tag_name = substr( $this->str, $starts, $this->pos-$starts );
                                    if( $tag_name == 'if' || $tag_name == 'while' || $tag_name == 'not' ) $processing_cond = true;
                                    
                                    $starts = $this->pos+1;
                                    $this->state = K_STATE_ATTR_NAME;
                                }
                                elseif( ($c=='>') ||  ($c=='/' && $this->str{$this->pos+1}=='>') ){ 
                                    $tag_name = substr( $this->str, $starts, $this->pos-$starts );
                                    if( $c=='>') $this->pos--;
                                    $this->state = K_STATE_TAG_OPEN;
                                }
                                else{
                                    $this->raise_error( "TAG_NAME: Invalid char \"".$c."\" in tagname", $this->line_num, $this->pos );
                                }
                            }
                            else{
                                if( $this->pos==$starts ){ //First valid char
                                    $attributes = array();
                                    unset( $attr );
                                }
                            }
                            break;
                        case K_STATE_ATTR_NAME:
                            if( !($this->pos == $starts ? $this->is_valid_for_label($c, 0) : $this->is_valid_for_label($c)) ){
                                if( $this->is_white_space($c) ){
                                    if( $this->pos!=$starts ){
                                        $attr['name'] = substr( $this->str, $starts, $this->pos-$starts );
                                        $this->state = K_STATE_ATTR_OP;
                                    }
                                    else{
                                        $starts++;
                                    }
                                }                               
                                elseif( ($c=='"' || $c=="'") && $this->pos==$starts ){
                                    if( isset($attr) ){ $attributes[] = $attr; }
                                    $attr = array();
                                    
                                    $this->pos--;
                                    $this->state = K_STATE_ATTR_VAL;
                                }
                                elseif( $processing_cond && $this->pos==$starts && $c=='(' ){
                                    if( isset($attr) ){ $attributes[] = $attr; }
                                    $attr = array();
                                    $attr['op']=$c;
                                    $brackets_count++;
                                    $starts++;
                                }
                                elseif( $processing_cond && $this->pos!=$starts && ($this->is_logical_op() || $c==')')){
                                    $attr['name'] = substr( $this->str, $starts, $this->pos-$starts );
                                    $starts = $this->pos;
                                    $this->pos--;
                                    $this->state = K_STATE_LOGIC_OP;
                                }
                                elseif( $c=='=' || ($processing_cond && $this->pos!=$starts && $this->is_cond_op()) ){
                                    if( isset($attr['value_type']) ){ // a prev standalone 'value' remains unprocessed
                                        $this->raise_error( "ATTRIB_NAME: Invalid char \"".$c ."\"", $this->line_num, $this->pos );
                                    }
                                    
                                    $attr['name'] = substr( $this->str, $starts, $this->pos-$starts );
                                    $this->pos--;
                                    $this->state = K_STATE_ATTR_OP;
                                }                               
                                elseif( ($c=='>') ||  ($c=='/' && $this->str{$this->pos+1}=='>') ){
                                    if( isset($attr) && in_array($attr['op'], $this->logical_ops) ){
                                        $this->raise_error( "ATTRIB_NAME: Orphan \"".$attr['op'] ."\"", $this->line_num, $this->pos );
                                    }
                                    if( $this->pos != $starts ){
                                        $attr['name'] = substr( $this->str, $starts, $this->pos-$starts );
                                    }
                                    if( $c=='>') $this->pos--;
                                    $this->state = K_STATE_TAG_OPEN;
                                }
                                else{
                                    $this->raise_error( "ATTRIB_NAME: Invalid char \"".$c ."\"", $this->line_num, $this->pos );
                                }
                            }
                            else{
                                if( $this->pos==$starts ){ //First valid char
                                    if( isset($attr) ){ $attributes[] = $attr; }
                                    $attr = array();
                                }
                            }
                            break;
                        case K_STATE_ATTR_OP:
                            if( $this->is_white_space($c) ){
                            }
                            elseif( $processing_cond && ($op = $this->is_logical_op() || $c==')' ) ){
                                $starts = $this->pos;
                                $this->pos--;
                                $this->state = K_STATE_LOGIC_OP;
                            }  
                            elseif( $processing_cond && ($op = $this->is_cond_op()) ){
                                $this->pos++;
                                $attr['op'] = $op;
                                $starts = $this->pos+1;
                                $this->state = K_STATE_ATTR_VAL;
                            }                            
                            elseif( ($c=='>') ||  ($c=='/' && $this->str{$this->pos+1}=='>') ){
                                if( $c=='>') $this->pos--;
                                $this->state = K_STATE_TAG_OPEN;
                            }
                            elseif( $c=='=' ){
                                $op = '=';
                                $attr['op'] = $op;
                                $starts = $this->pos+1;
                                $this->state = K_STATE_ATTR_VAL;
                                
                            }
                            elseif( ($this->is_valid_for_label($c, 0)) || ($c=='"') || ($c=="'") ){
                                $starts = $this->pos;
                                $this->pos--;
                                $this->state = K_STATE_ATTR_NAME;
                            }
                            else{
                                $this->raise_error( "OPERATOR: Invalid char \"".$c."\"", $this->line_num, $this->pos );
                            }
                            break;
                        case K_STATE_ATTR_VAL:
                            if( $starts == $this->pos ){
                                if( $this->is_white_space($c) ){
                                    $starts++;
                                }
                                elseif( ($c=='"') || ($c=="'") ){
                                    $quote_type = $c;
                                    
                                    // A double-quoted value might contain nested code.
                                    if( $quote_type=='"' ){
                                        $code_starts = strpos($this->str, '<'.K_START_TAG_IDENT, $this->pos+1);
                                        $next_quote = $this->find_next_quote( $this->pos+1 );
                                        if( ($code_starts !== false && $next_quote !== false) && ($code_starts < $next_quote) ){
                                            $attr['value_type'] = K_VAL_TYPE_SPECIAL;
                                            $parser = new KParser( $this->str, $this->line_num, $this->pos+1, '"', $this->id_prefix );
                                            $attr['value'] = $parser->get_DOM();
                                            $this->line_num = $parser->line_num;
                                            $this->pos = $parser->pos;
                                            
                                            $starts = $this->pos+1;
                                            if( $processing_cond ){
                                                $this->state = K_STATE_LOGIC_OP;
                                            }
                                            else{
                                                $this->state = K_STATE_ATTR_NAME;
                                            }
                                        }
                                    }
                                }
                                else{
                                    $quote_type = 0;
                                    if( !$this->is_valid_for_label($c, 0) ){
                                        $this->raise_error( "ATTRIB_VALUE: Invalid first char \"".$c."\"" , $this->line_num, $this->pos );
                                    }
                                }
                            }
                            else{
                                if( !$quote_type ){
                                    if( !$this->is_valid_for_label($c) ){
                                        if( ($c=='>') ||  ($c=='/' && $this->str{$this->pos+1}=='>') ){
                                            $attr['value'] = substr( $this->str, $starts, $this->pos-$starts );
                                            $attr['value_type'] = K_VAL_TYPE_VARIABLE;
                                            if( $c=='>') $this->pos--;
                                            $this->state = K_STATE_TAG_OPEN;
                                        }
                                        elseif( $this->is_white_space($c) ){
                                            $attr['value'] = substr( $this->str, $starts, $this->pos-$starts );
                                            $attr['value_type'] = K_VAL_TYPE_VARIABLE;
                                            
                                            $starts = $this->pos+1;
                                            if( $processing_cond ){
                                                $this->state = K_STATE_LOGIC_OP;
                                            }
                                            else{
                                                $this->state = K_STATE_ATTR_NAME;
                                            }
                                        }
                                        elseif( $processing_cond && ($this->is_logical_op() || $c==')') ){
                                            $attr['value'] = substr( $this->str, $starts, $this->pos-$starts );
                                            $attr['value_type'] = K_VAL_TYPE_VARIABLE;
                                            
                                            $starts = $this->pos;
                                            $this->pos--;
                                            $this->state = K_STATE_LOGIC_OP;
                                        }
                                        else{
                                            $this->raise_error( "ATTRIB_VALUE: Invalid char \"".$c."\"", $this->line_num, $this->pos );
                                        }
                                        
                                    }
                                }
                                else{
                                    if( $c==$quote_type ){
                                        if( $this->str{$this->pos-1}!='\\' ){
                                            $starts++;
                                            $attr['value'] = substr( $this->str, $starts, $this->pos-$starts );
                                            $attr['value_type'] = K_VAL_TYPE_LITERAL;
                                            $attr['quote_type'] = $quote_type;
                                            
                                            $starts = $this->pos+1;
                                            if( $processing_cond ){
                                                $this->state = K_STATE_LOGIC_OP;
                                            }
                                            else{
                                                $this->state = K_STATE_ATTR_NAME;
                                            }
                                        }
                                    }
                                    
                                }
                                
                                
                            }
                            break;
                        case K_STATE_LOGIC_OP:
                            if( $this->is_white_space($c) ){
                                $starts++;
                            }
                            elseif( $op = $this->is_logical_op() ){
                                if( isset($attr) ){ $attributes[] = $attr; }
                                $attr = array();
                                $attr['op'] = substr( $this->str, $starts, 2 );
                                $this->pos++;
                                $starts = $this->pos+1;
                                $this->state = K_STATE_ATTR_NAME;
                            }
                            elseif( $processing_cond && $c==')' ){
                                $brackets_count--;
                                if( $brackets_count < 0 ){
                                    $this->raise_error( "LOGIC_OP: Closing bracket has no matching open bracket", $this->line_num, $this->pos );
                                }
                                if( isset($attr) ){ $attributes[] = $attr; }
                                $attr = array();
                                $attr['op']=$c;
                                $starts++;
                            }
                            elseif( ($c=='>') ||  ($c=='/' && $this->str{$this->pos+1}=='>') ){
                                if( $c=='>') $this->pos--;
                                $this->state = K_STATE_TAG_OPEN;
                            }
                            else{
                                $this->raise_error( "LOGIC_OP: Invalid char \"".$c."\"", $this->line_num, $this->pos );
                            }
                            break;
                    }
                    
                    $this->pos++;
                }
                if( $this->state != K_STATE_TEXT ){
                    $this->raise_error( "Parsing ended in an invalid state", $this->line_num, $this->pos );
                }
                if( count($this->stack) ){
                    if( count($this->stack) > 1 ){
                        $dangling_tag = &$this->stack[count($this->stack)-1];
                    }
                    else{
                        $dangling_tag = $this->curr_node;
                    }
                    $this->raise_error( "Tag \"".@$dangling_tag->name."\" has no matching closing tag", $this->line_num, $this->pos );
                }
              
                $text = substr($this->str, $starts, $this->pos-$starts);
                if( $this->quit_at_char=='"' ) $text = str_replace( '\\"', '"', $text );
                $this->add_child( K_NODE_TYPE_TEXT, '', '', $text );
                $this->parsed = true;
            }
            return $this->DOM;
        }
        
        function get_HTML(){
            $DOM = &$this->get_DOM();
            return $DOM->get_HTML();
        }
        
        function get_info(){
            $DOM = &$this->get_DOM();
            return $DOM->get_info();
        }
        
        function add_child( $node_type, $name='', $attr='', $text='', $push_to_stack=0 ){
            $child = new KNode( $node_type, $name, $attr, $text );
            $child->char_num = $this->pos;
            $child->line_num = $this->line_num;
            $child->ID = $this->id_prefix . '_' . $child->line_num . '_' . $child->char_num;
            $this->curr_node->children[] = &$child;
            
            if( $push_to_stack ){
                $this->stack[count($this->stack)] = &$this->curr_node;
                $this->curr_node = &$child;
            }
        }
        
        function is_valid_for_label( $char, $pos=-1 ){
            // Labels (tag names and attributes) can contain [a-z][A-Z][0-9]_ 
            // except for the first character that cannot be a numeral.
            if( ($char>='A' && $char<='Z') || ($char>='a' && $char<='z') || ($char=='_') || (($char>='0' && $char<='9')&&($pos!=0)) ){
                return true;
            }
            return false;
        }
        
        function is_white_space( $char ){
            return !( strpos("\r\n\t\0 ", $char)===false );
        }
        
        function find_next_quote( $pos ){
            $len = strlen( $this->str );
            while( $pos < $len ){
                $pos = strpos($this->str, '"', $pos);
                if( $pos && $this->str{$pos-1}!='\\' ) return $pos;
                $pos++;
            }
            return FALSE;
        }
        
        function is_cond_op( ){
            $op = substr( $this->str, $this->pos, 2);
            if( in_array($op, $this->cond_ops) ){
                return $op;
            }
            return false;
        }
        
        function is_logical_op(){
            $op = $this->str{$this->pos};
            if( in_array($op . $this->str{$this->pos+1}, $this->logical_ops) ) return $op;
            return false;
        }
        
        function raise_error( $msg, $line_num, $pos ){
            $msg = 'ERROR! ' . $msg;
            $msg .= ' (line: ' . $line_num . ' char: ' . $pos . ')';
            die( $msg );
        }
    }