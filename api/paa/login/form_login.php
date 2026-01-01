<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><head>
	    <title>Application Portal</title>
                <link type="text/css" rel="stylesheet" href="style/cas.css">
				<link type="text/css" rel="stylesheet" href="style/jquery-ui-1.css">
           
        
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <script type="text/javascript" src="style/jquery-1.js"></script>
        <script type="text/javascript" src="style/jquery-ui-1.js"></script>
        <script type="text/javascript" src="style/cas.js"></script>
		<script type="text/javascript">
			$(document).ready( function() {
				$('.form-submit').button(); 
			});
		</script>
	    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
	</head>
	<body id="cas" class="fl-theme-iphone">
    <div class="flc-screenNavigator-view-container">
        <div class="fl-screenNavigator-view">
            <div id="header" class="flc-screenNavigator-navbar fl-navbar fl-table">
            	<img id="logo" src="images/logo_astellia.png">					
                <!-- <h1 id="app-name" class="fl-table-cell">Application Portal</h1> -->
            </div>		
			<div id="header2" class="flc-screenNavigator-navbar fl-navbar fl-table"></div>
            <div id="content" class="fl-screenNavigator-scroll-container">

			<form id="fm1" class="fm-v clearfix" action="validate_login.php?<?php if(isset($_GET['degraded'])) echo "degraded&"; ?>url=<?php if(isset($_GET["url"])) echo $_GET["url"]; ?>" method="post">
<?php
				if (isset($_GET['degraded']))
				{
					echo '<div id="status" class="info">Degraded Mode. Portal not available.</div>';
				}
				if (isset($_GET['error']))
				{
					echo '<div id="status" class="errors">The credentials you provided cannot be determined to be authentic.</div>';
				}
?>						    
                <div class="box fl-panel" id="login">
                <!-- Congratulations on bringing CAS online!  The default authentication handler authenticates where usernames equal passwords: go ahead, try it out.  -->
                    <h2>Enter your Login and Password</h2>
                    <div class="row fl-controls-left">
                        <label for="username" class="fl-label"><span class="accesskey">L</span>ogin:</label>
						

						
						
						<input id="username" name="username" class="required ui-widget ui-widget-content ui-corner-all" tabindex="1" accesskey="l" size="25" autocomplete="false" type="text">
						
                    </div>
                    <div class="row fl-controls-left">
                        <label for="password" class="fl-label"><span class="accesskey">P</span>assword:</label>
						
						
						<input id="password" name="password" class="required ui-widget ui-widget-content ui-corner-all" tabindex="2" accesskey="p" value="" size="25" autocomplete="off" type="password">
                    </div>
                    <div class="row check" style="display:none;">
                        <input id="warn" name="warn" value="true" tabindex="3" accesskey="w" type="checkbox">
                        <label for="warn"><span class="accesskey">W</span>arn me before logging me into other sites.</label>
                    </div>
                    <div class="row btn-row">
						<input name="lt" value="EA2F7CDC2C8473634E845FA8C216476043262BD2ADD1B25EA5EF019D4A7977A3B44FE24F4C9AB6F1A3AB83366E77486E" type="hidden">
						<input name="_eventId" value="submit" type="hidden">

                        <input aria-disabled="false" role="button" id="loginBtn" class="form-submit ui-button ui-widget ui-state-default ui-corner-all" name="submit" accesskey="l" value="LOGIN" tabindex="4" type="submit">
                        <input class="btn-reset" name="reset" accesskey="c" value="CLEAR" tabindex="5" type="reset">
                    </div>
                </div>
            </form>

            <div id="sidebar">
            </div>
			

                </div>
                <div id="footer" class="fl-panel fl-note fl-bevel-white fl-font-size-80">
						<div style="display: inline-block;">Copyright &copy;2014 <a href="http://www.astellia.com/">Astellia</a> - All rights reserved</div>
                </div>
            </div>
        </div>
    

</body></html>