<?php
$getter = require_once(dirname(__FILE__) . '/view-adapter.php');
$get = $getter(isset($this) ? $this : (isset($canister) ? $canister : null));

$applicationName = $get(['applicationComposerMeta:shortName','applicationDescription'], '');
$applicationPrefix = $applicationName ? "{$applicationName}: " : '';
$mainHeader = $applicationName;
$title = $get('title', "{$applicationPrefix}{$mainHeader}");
$baseUrl = '/rooms/';
$cssUrl = "{$baseUrl}css/";
$cdnBase = 'https://cdn.lib.ncsu.edu/shared-website-assets/';
$headerVersion = 'latest';
$staticHeader = '';
$additionalIncludeTags = true;
$main = 'An Application Error Occurred';
$additional = isset($e) ? $e->getMessage() : $get(['defaultHelpText','applicationComposerMeta:defaultHelpText'], '');
?><!DOCTYPE html>
<!--[if IE 8]><html class="error-page lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="error-page" lang="en"> <!--<![endif]-->
    <head>
		<title><?php print($title); ?></title>

        <script src="<?php print($baseUrl);?>foundation/js/vendor/modernizr.js" type="text/javascript" charset="utf"></script>
		<script src="<?php print($baseUrl);?>foundation/js/vendor/jquery.js" type="text/javascript" charset="utf"></script>

        <link href="<?php print($baseUrl);?>foundation/css/foundation.min.css" rel="stylesheet" type="text/css" media="screen"/>
		
		<link href="https://cdn.ncsu.edu/brand-assets/fonts/include.css" rel="stylesheet" type="text/css"/>
		<link href="<?php print($cssUrl); ?>main.css" rel="stylesheet" type="text/css" media="screen"/>
		<link href="<?php print($cssUrl); ?>saf.css" rel="stylesheet" type="text/css" media="screen"/>

        <noscript>
				<link href="<?php print($cdnBase . $headerVersion); ?>/header/header.css" rel="stylesheet" type="text/css">
		</noscript>

		<script src="<?php print($baseUrl);?>javascript/saf.js" type="text/javascript" charset="utf"></script>
		<script type="text/javascript">
			$(function() {
				saf.init(<?php print($initString); ?>);
			});


  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
  ga('create', 'UA-17138302-1', 'auto');
  ga('send', 'pageview');


		</script>
	</head>
	<body>
	<div id="pop-back"></div>
	<script src="<?php print($cdnBase . $headerVersion); ?>/header/header.js?hours=true" id="ncsu-lib-header"></script>
	<noscript>
		<?php print($staticHeader); ?>
	</noscript>
<?php
//Saf_Layout::debugHeader();

if ($additionalIncludeTags) {
?>
	<div id="content" role="document" class="page">
		<header id="header-content" role="banner" class="row l-header">
<?php
}
?>
			<div class="small-12 columns"> 
				<h1 class="centered insetHeader"><?php print($mainHeader);?></h1>

			</div>
        </header>

        <main id="main-content" role="main" class="row l-main">
			<div class="small-12 columns">
                <p>
<?php 
print($main);
?>
                </p>
<?php
if ($additional) {
?>
                <p><?php print($additional); ?></p>
<?php
}
?>
			</div>

			<div class="small-12 columns">
<?php 
//Saf_Layout::debugFooter();
?>
			</div>

<?php
if ($additionalIncludeTags) {
?>
		</main>
	</div>
<?php
}

?>
<script src="<?php print($cdnBase . $headerVersion); ?>/footer/footer.js" id="ncsu-lib-footer"></script>
	</body>
</html>