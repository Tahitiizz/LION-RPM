This folder contains tools needed to build the Query builder package.


>> smartsprites: 
Manage CSS sprite. This tool generates automatically the 2 following files: 
	- querybuilder/resources/css/QueryBuilder-sprite.css : the css file using the CSS sprite image.
	- images/querybuilder/querybuilder.png : contains over 50 images used by the query builder in one image.

To generate the files run the command: smartsprites.cmd "C:\...\querybuilder\resources\css\QueryBuilder.css"
See the smartsprites/doc folder for more information 



>> SenchaSDKTools
Window version of the Sencha SDK. It is used to 'build' JS code (http://www.sencha.com/products/sdk-tools).

To build query builder:
 - Install SenchaSDKTools-1.2.3-windows-installer.exe
 - Run the 3 batch file:
	1_create jsb file.bat			//> creates app.jsb3 file: describe all javascript files needed to run querybuilder
	2_build app.bat					//> merge & compress all javascript files found in app.jsb3
	3_move files.bat				//> move generated javascript file into JS directory and remove the app.jsb3 file

The result is an app-all.js file in the querybuilder/js directory. This file contains all the js code of querybuilder application

If you have ant installed you can also use the build.xml ant script to execute the 3 batch files
