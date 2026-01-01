Ext.define('homepage.view.charts.ReportUpload' ,{
    extend: 'Ext.panel.Panel',
    alias : 'widget.reportupload',
	require:[
    'Ext.tree.*',
    'Ext.data.*',
    'Ext.tip.*'
	],
	layout: 'anchor',
	iconCls: 'icoGraph',
	cls: 'periodChart',
	padding: '5 5 5 5',
	
	initComponent: function() {
	Ext.QuickTips.init();
	var t = this;
	var curPannel = this.id;
	
	//Check if the folder for the current year and current month is created if not the function create them
	Ext.Ajax.request({
	    	url: 'proxy/check_folder.php'
	});	
	
    //Create the store from the function get-nodes which get the tree structure of the folder archives
    var store = Ext.create('Ext.data.TreeStore', {
    proxy: {
        type: 'ajax',
        url: 'proxy/get-nodes.php'
    },
    root: {
        text: 'Archives',
        id: 'archives',
        expanded: true
    },
    folderSort: true,
	sorters: [{
        property: 'text',
        direction: 'ASC'
    }]
		
    });

	
	//create the tree component
    var tree = Ext.create('Ext.tree.Panel', {
        flex: 2,
        store: store,
        height: 300,
        anchor: '100%',
        useArrows: true,
        dockedItems: [{
            xtype: 'toolbar',
            items: [{
                text: 'Expand All',
                handler: function(){
                    tree.expandAll();
                }
            }, {
                text: 'Collapse All',
                handler: function(){
                    tree.collapseAll();
                }
            },
			{
				text: 'Refresh',
				handler: function(){
					tree.getStore().load();
				}
			}
			]
        }]
    });

	//Create each component of the contextual menu triggered by rightclick on element node 
	var upload = Ext.create('Ext.Action', {
        text: 'Upload file',
        disabled: true,
        handler: function(widget, event) {
		createfolder.hide();
		uploader.show();	
        }
    });
    var create = Ext.create('Ext.Action', {
        text: 'Create folder',
        disabled: true,
        handler: function(widget, event) {
        uploader.hide();
        createfolder.show();
        }
    });
	
	var suppr = Ext.create('Ext.Action', {
        text: 'Delete content',
        disabled: true,
        handler: function(widget, event) {
		Ext.Msg.show({
		   title:'Delete?',
		   msg: 'Are you sure you want to delete this file/folder ?',
		   buttons: Ext.Msg.OKCANCEL,
		   fn: function (buttons){
			if (buttons == 'ok')
				process(curPannel);	
		},
		   animEl: 'elId',
		   icon: Ext.MessageBox.QUESTION
		});
   
        }	
    });
	
	
	var download = Ext.create('Ext.Action', {
		text: 'Download',
        disabled: true,
		
				
		handler: function(widget, event) {
		var folder = tree.getSelectionModel().lastSelected.internalId;
				var clsfolder = tree.getSelectionModel().lastSelected.data.cls;
				
				//We check if the node element to delete is a file or a folder
				if (clsfolder == 'folder'){
					var completepath = '../'+folder+'/';
				}
				else{
					var completepath = '../'+folder+'';
				}
			//we create the url from where to download the file
			location.href = 'proxy/file-download.php?folder='+completepath+'&cls='+clsfolder;
		}
		
	});
	
	//We create a contextual menu
	var mnuContext = Ext.create('Ext.menu.Menu', {
        items: [
            upload,
            create,
			suppr,
			download
        ]
    });
	
    //We set a component Ext.Msg used to display all message
    var msg = function(title, msg) {
        Ext.Msg.show({
            title: title,
            msg: msg,
            minWidth: 200,
            modal: true,
            icon: Ext.Msg.INFO,
            buttons: Ext.Msg.OK
        });
    };
    
	//Uploader component
	var uploader = Ext.create('Ext.form.Panel', {
        flex: 1,
        id: this.id+'_uploader',
		draggable: true,
		width: 300,
		frame: true,
		title: 'File Upload Form',
        margin: '50 0 0 0', 
        bodyPadding: '10 10 0',

        defaults: {
            anchor: '100%',
            allowBlank: false,
            msgTarget: 'side',
            labelWidth: 50
        },

        items: [{
            xtype: 'filefield',
            id: this.id+'_form-file',
            emptyText: 'Select a file',
            fieldLabel: 'File',
			name: 'uploader',
            buttonText: '',
            buttonConfig: {
                iconCls: 'icoUpload'
            }
        }],

        buttons: [{
            text: 'Save',
            handler: function(){
                var form = this.up('form').getForm();
                
				var folder = tree.getSelectionModel().lastSelected.internalId;
				var completepath = '../'+folder+'/';
				if(form.isValid()){
                    form.submit({
                        url: 'proxy/file-upload.php',
						params: {
							folder: completepath
						},
                        waitMsg: 'Uploading your file...',
                        success: function(fp, o) {
                            msg('Success', 'Processed file "' + o.result.file + '" on the server');
							uploader.hide();
							tree.getStore().load();
                        },
						failure: function() {
							Ext.Msg.alert('error', 'An error occured');
                                  
						}
                    });
                }
            }
        },{
            text: 'Cancel',
            handler: function() {
                this.up('form').getForm().reset();
				uploader.hide();
            }
        }]
    });
	// We hide the component by default
	uploader.hide();
	
	//create folder component
	var createfolder = Ext.create('Ext.form.Panel', {
        flex: 1,
        id: this.id+'_createfolder',
		draggable: true,
		width: 300,
		frame: true,
		title: 'Create a new folder',
		margin: '50 0 0 0',
        bodyPadding: '10 10 0',

        defaults: {
            anchor: '100%',
            allowBlank: false,
            msgTarget: 'side',
            labelWidth: 50
        },
		defaultType: 'textfield',
        items: [{
            fieldLabel: 'Name',
            name: 'namefolder'
            
        }],
        buttons: [{
            text: 'Save',
            handler: function(){
                var form = this.up('form').getForm();
                
				var folder = tree.getSelectionModel().lastSelected.internalId;
				
					if(folder != 'ext-record-1'){
						var completepath = '../'+folder+'/';
					}
					else{
						var completepath = '../archives/';
					}
					
				if(form.isValid()){
                    form.submit({
                        url: 'proxy/folder-create.php',
						params: {
							folder: completepath
						},
                        waitMsg: 'Creating your folder...',
                        success: function(fp, o) {
                            msg('Success', 'Folder "' + o.result.folder + '" has been successfully created');
							createfolder.hide();
							tree.getStore().load();
                        },
						failure: function() {
							Ext.Msg.alert('error', 'An error occured');
                                  
						}
                    });
                }
            }
        },{
            text: 'Cancel',
            handler: function() {
                this.up('form').getForm().reset();
				createfolder.hide();
            }
        }]
    });
    //we hide the component by default
	createfolder.hide();
	
	//get the contextual menu on rightclic
	tree.on('itemcontextmenu', function(view,rec,item,index,eventObj) {  
			//On recupere l'id du noeud sur lequel on a fait un click droit
			var record = tree.getStore().getNodeById(rec.internalId);
			var folder = tree.getSelectionModel().lastFocused.internalId;
			if(record != undefined){
				//on selectionne ce noeud
				tree.getSelectionModel().select(record);
				upload.enable();
				suppr.enable();
				var cls = tree.getSelectionModel().lastSelected.data.cls;            
					 if(cls == 'folder'){
						create.enable();
						upload.enable();
						suppr.enable();
						download.disable();
					 }
					 else{
						create.disable();
						upload.disable();
						suppr.enable();
						download.enable();
					 }
			}
			else{
				var record = tree.getStore().getNodeById('ext-record-1');
				tree.getSelectionModel().select(record);
				create.enable();
				upload.disable();
				suppr.disable();
				download.disable();
			}
			
             eventObj.stopEvent();
		     mnuContext.showAt(eventObj.xy);
    },this);
	
	
	var formdel = Ext.create('Ext.form.Panel', {
        id: this.id+'_formdelete'
         });
	
	var formdown = Ext.create('Ext.form.Panel', {
        id: this.id+'_formdownload'
         });
		
	function process (panelId){
                var form = Ext.getCmp(panelId+'_formdelete').getForm();
				var folder = tree.getSelectionModel().lastSelected.internalId;
				var clsfolder = tree.getSelectionModel().lastSelected.data.cls;
				
				//On verifie si l element a supprime est un dossier ou un fichier 
				if (clsfolder == 'folder'){
					var completepath = '../'+folder+'/';
				}
				else{
					var completepath = '../'+folder+'';
				}
				
				if(form.isValid()){
                    form.submit({
                        url: 'proxy/file-delete.php',
						params: {
							folder: completepath,
							cls : clsfolder
							 
						},
                        waitMsg: 'Deletion ongoing...',
                        success: function(fp, o) {
                            msg('Success', 'File "' + o.result.file + '" deleted from the server');
							tree.getStore().load();
							
                        },
						failure: function() {
                                            Ext.Msg.alert('error', 'An error occured');
                                  
						}
                    });
                }
	};

	//We add each componant to the view
     this.items = [{
		items : [{
			xtype : 'container',
			layout : 'hbox',
			id : this.id + '_hbox',
			items : [
		     	tree,
				uploader,
		 		createfolder
			]
		}]	
     }];
    	
  		this.callParent(arguments);
	}	
		
	
});

