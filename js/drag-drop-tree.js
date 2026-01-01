/**
 * @author Pawel Gasiorowski <p.gasiorowski@axent.pl>
 * @package Axent.DragDropTree
 * @license MIT
 * @url http://weblog.axent.pl/examples/js.drag-drop-tree/
 * @version 1.3
 */
if (Object.isUndefined(Axent)) { var Axent = { } }

Axent.DragDropTree = Class.create({
    options : {},
    serialized : [],
    initialize: function(element) {                                
        this.element = $(element);                                              // Tree container element, usually UL tag
        this.options = Object.extend({                                        
            isDraggable : true,                                                 // Enables / disables dragging tree nodes
            isDroppable : true,                                                 // Enables / disables dropping elements on tree nodes
            iconsFolder : 'img/',                                               // Path to icons folder
            plusIcon : 'plus.gif',                                              // Plus icon image
            minusIcon : 'minus.gif',                                            // Minus icon image
            addFolderIcon : true,                                               // Enables / disables adding folder icon to tree nodes
            folderIcon : 'folder.gif',                                          // Folder icon image
            treeClass : 'drag-drop-tree',
            treeNodeClass : 'drag-drop-tree-node',
            treeNodePlusClass : 'drag-drop-tree-node-plus',
            treeNodeDropOnClass : 'drag-drop-tree-dropon-node',
            treeNodeHandleClass : 'drag-drop-tree-node-handle',
            beforeDropNode : null,                                              // Callback function before node is dropped (return false to cancel drop)
            afterDropNode : null,                                               // Callback function after node is dropped
            allowDropAfter : true,                                              // Enables / disables dropping nodes after spcefic nodes (disable when application does not need / allow reordering nodes)
            dropAfterOverlap : 0.95
        }, arguments[1] || {} );
        
        this.element.addClassName(this.options.treeClass);
        this.element.select('li').each(this.initializeTreeNode.bind(this));
        
        /**
         *  Add serializeTree method to tree container element
         */                                 
        Object.extend(this.element,{
            serializeTree : function (inputName) {
                var serialized = $H();
                    if (inputName) {
                        serialized.set('inputName',inputName);
                    } else {
                        serialized.set('inputName','data[Node]');
                    }
                this.select('li').each(function(node){
                    var data = {};
                        data.id = node.identify();
    				    data.parent_id = (node.up('li') != undefined) ? node.up('li').identify() : '';
    				    data.previous_id = (node.previous('li') != undefined) ? node.previous('li').identify() : '';
    				this.set(this.get('inputName')+'['+node.identify()+'][id]',data.id);
    				this.set(this.get('inputName')+'['+node.identify()+'][parent_id]',data.parent_id);
    				this.set(this.get('inputName')+'['+node.identify()+'][previous_id]',data.previous_id);
                },serialized);
                serialized.unset('inputName');                        
                return serialized.toQueryString();
            }
        });
    },
    /**
     *  Show hide node's children
     */                                 
    showHideNode : function (event) {
        ul = Event.element(event).up('li').down('ul');
        li = Event.element(event).up('li');
        if (ul != undefined) {
            ul.toggle();
            Cookie.set(this.element.identify()+'_show_Node_'+li.identify(),ul.visible());
            Event.element(event).src = (ul.visible()) ? (this.options.iconsFolder+this.options.minusIcon) : (this.options.iconsFolder+this.options.plusIcon);
        }
    },
    onHoverNode : function (node,dropOnNode,overlap) {
        if (this.options.allowDropAfter) {
            if (overlap > this.options.dropAfterOverlap) {
                dropOnNode.addClassName('drag-drop-tree-dropafter-node');
            } else {
                dropOnNode.removeClassName('drag-drop-tree-dropafter-node');
            }
        }
    }
    ,
    /**
     *  Droappable.onDrop callback 
     */                                 
    onDropNode : function (node,dropOnNode,point) {
        if (typeof this.options.beforeDropNode == 'function') {
            node.hide();
            var ret = this.options.beforeDropNode(node,dropOnNode,point);
            node.show();
            if (ret === true || ret === false) {
                return ret;
            }
        }
        
        sourceNode = node.up('li');
        
        /**
         *  Insert after dropOnNode
         */                                 
        if (dropOnNode.hasClassName('drag-drop-tree-dropafter-node')) {
            dropOnNode.insert({after:node});
            dropOnNodeParent = dropOnNode.up('li',1)
            if (dropOnNodeParent != undefined) {
                dropOnNodePlus = dropOnNodeParent.down('img.'+this.options.treeNodePlusClass);
                dropOnNodePlus.src = this.options.iconsFolder+this.options.minusIcon;
                dropOnNodePlus.setStyle({visibility:'visible'});
            }
        }
        /**
         *  Insert under dropOnNode
         */
        else {
            ul = dropOnNode.down('ul',0);                                                   
            if (ul == undefined) {
                ul = new Element('ul');
                dropOnNode.insert(ul);
            }
            ul.show();
            ul.insert(node);
            dropOnNodePlus = dropOnNode.down('img.'+this.options.treeNodePlusClass);
            dropOnNodePlus.src = this.options.iconsFolder+this.options.minusIcon;
            dropOnNodePlus.setStyle({visibility:'visible'});
        }
        
        if (sourceNode != undefined) {
            sourceNodePlus = sourceNode.down('img.'+this.options.treeNodePlusClass);
            if (sourceNode.down('li') == undefined) {
                sourceNodePlus.setStyle({visibility:'hidden'});
            }
        }
        
        if (typeof this.options.afterDropNode == 'function') {
            var ret = this.options.afterDropNode(node,dropOnNode,point);
            if (ret === true || ret === false) {
                return ret;
            }
        }
    },
    initializeTreeNode : function (li) {
		if (!Cookie.get(this.element.identify()+'_show_Node_'+li.identify())) {
			if (li.down('ul')) {
                li.down('ul').hide();
            }
		}         		
        /**
         *  Insert folder icon at the top of li element
         */
        if (this.options.addFolderIcon) {
            li.insert({
                top : new Element('img', {
                    src : this.options.iconsFolder+this.options.folderIcon,
                    className : this.options.treeNodeHandleClass
                })
            });
        }
        /**
         *  Insert and setup plus/minus handle
         */                                                           
        liPlus = new Element('img',{
            src:this.options.iconsFolder+this.options.minusIcon,
            className:this.options.treeNodePlusClass
        });
        if (li.down('li') == undefined) {
            liPlus.setStyle({visibility:'hidden'});
        } else if (li.down('ul').visible() === false) {
            liPlus.src = this.options.iconsFolder+this.options.plusIcon;
        }
        Event.observe(liPlus,'click',this.showHideNode.bindAsEventListener(this));
        li.insert({top:liPlus});
        /**
         *  Setup li node
         */                                         
        li.addClassName(this.options.treeNodeClass);                        
        /**
         *  Make node draggable
         */
        if (this.options.isDraggable) {                                         
            new Draggable(li,{handle:this.options.treeNodeHandleClass,revert:true,starteffect:null});
        }
        /**
         *  Make node droppable
         */
        if (this.options.isDroppable) {
            Droppables.add(li, {
                accept:this.options.treeNodeClass,
                hoverclass:this.options.treeNodeDropOnClass,
                onDrop:this.onDropNode.bind(this),
                overlap:'horizontal',
                onHover:this.onHoverNode.bind(this)
            });
        }
    }
});