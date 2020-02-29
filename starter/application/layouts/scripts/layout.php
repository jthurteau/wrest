<?php //#SCOPE_NCSU_PUBLIC
/*******************************************************************************
 #LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

*******************************************************************************/
$applicationName = Zend_Registry::get('language')->get('applicationName', 'Application Name');
//$applicationName = Saf_Language::get('applicationName', 'Application Name');
$isLoggedIn = Saf_Auth::isLoggedIn();
$showLoggedInOptions = $isLoggedIn && $this->showLoggedInOptions;
?><!DOCTYPE html>
<html class="no-js" lang="en">
    <head>
    	<title><?php print($this->title ? $this->title : $applicationName); ?></title>
<?php 
if ($this->rssLink) {
	Saf_Layout::printRssLink($this->rssLink);
}
Saf_Layout::printPreloadJs();
Saf_Layout::printCoreCss();
Saf_Layout::printAutoCss();
Saf_Layout::printCoreJs();
Saf_Layout::printAutoJs();
Saf_Layout::printPostloadJs();
?>
	</head>
	<body>
<?php 
Saf_Layout::debugHeader();
?>
<div class="main">
	<div class="row">
		<div class="small-12 medium-6 columns"> 
			<h1 class="centered insetHeader"><?php print($this->mainHeader ? $this->mainHeader : $applicationName);?></h1>
		</div>
		<div class="small-12 medium-6 columns rightAlign noPrint"> 
			<div class="row collapse">
				<div class="verticalCenter alignRight small-12 columns">
					<a class="buttonLink prominent noWrap" href="<?php Saf_Layout::printLink('demo/');?>">Do A Thing</a>
				</div>
			</div>
<?php 
$username = Saf_Auth::getPluginProvidedUsername();
$fullName = Saf_Auth::getPluginUserInfo(Saf_Auth::PLUGIN_INFO_FULLNAME);
$userString =
	$fullName
	? "{$fullName} ({$username})"
	: $username;
if ($username) {
?>
			<p class="show-for-medium-up no-margin">Logged in as: <?php print($userString); ?></p> 
<?php 
}
?>
		</div>
		<div class="small-12 columns noPrint">			
<?php
Saf_Layout::printBreadCrumbs(); 
Saf_Layout::outputMessages();
?>
		</div>
		<div class="small-12 columns">
<?php 
print($this->layout()->content);
?>
		</div>
		<div class="small-12 columns">
<?php 
Saf_Layout::outputMessages();
Saf_Layout::debugFooter();
?>
		</div>
	</div>
	<div class="footerRow">
<?php 

?>
	</div>
</div>
	</body>
</html>
