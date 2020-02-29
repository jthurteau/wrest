<div class="row">
	<div class="small-12 columns">
		<h2>System Status</h2>
		<p class="<?php print($this->versionData['success'] ? '' : 'error'); ?>">
			<?php  print($this->versionData['id']); ?> - 
			Client Version 
<?php  
print("{$this->versionData['version']} ({$this->versionData['environment']})"); 
?>
		</p>
		<p>
			Authentication Mode <?php print($this->versionData['authMethod']); ?>
<?php 
if (array_key_exists('authUser', $this->versionData) ) {
	print(" - logged in as {$this->versionData['authUser']}");
}
?>
		</p>
<?php 
if (array_key_exists('exception', $this->versionData) ) {
	$e = $this->versionData['exception'];
?>
		<h2 class="exceptionClass"><?php print(get_class($e)); ?></h2>
		<pre class="exceptionTrace"><?php print($e->getTraceAsString()); ?></pre>
<?php 
	$previous = $e->getPrevious();
	if ($previous) {
?>
		<p>Additional exceptions detected.</p>
		<ul>
<?php 
		while($previous) {
?>
			<li>
				<h3><?php print(get_class($previous));?></h3>
				<p class="exceptionMessage">
				<pre><code><?php print(html_entity_decode($previous->getMessage())); ?></code></pre></p>
				<pre class="exceptionTrace"><?php print($previous->getTraceAsString()); ?></pre>   	
			</li>
<?php 
			$previous = $previous->getPrevious();
		}
?>
		</ul>
<?php
	}
}
?>
	</div>
</div>