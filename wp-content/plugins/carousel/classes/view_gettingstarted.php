<?php
// Needed for fancy boxes...
wp_enqueue_style('dashboard');
wp_print_styles('dashboard');
wp_enqueue_script('dashboard');
wp_print_scripts('dashboard');

wp_enqueue_script( 'jquery-ui-tabs' );
wp_print_scripts( 'jquery-ui-tabs' );

if ( isset( $this->_parent ) ) { // Using parent system
	$series = $this->_parent->_series;
} else {
	$series = $this->_series;
}

// Load scripts and CSS used on this page.
$this->admin_scripts();


?>
<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('#pluginbuddy-tabs').tabs();
	});
</script>

<div class="wrap">
	<div class="postbox-container" style="width:70%;">
		<h2>Getting Started with <?php echo $series; ?></h2>
		
		<br />
		
		<div id="pluginbuddy-tabs">
			<ul>
				<?php
				global $pluginbuddy_series;
				
				$i = 0;
				foreach( $pluginbuddy_series[ $series ] as $name => $path ) {
					if ( is_array( $path ) ) { // Skip pbframework plugins. Future compat.
						continue; // Next loop.
						}
					$i++;
					echo '<li><a href="#pluginbuddy-tabs-' . $i . '"><span>' . $name . '</span></a></li>';
				}
				?>
			</ul>
			<div class="tabs-borderwrap" id="editbox" style="position: relative; height: 100%; -moz-border-radius-topleft: 0px; -webkit-border-top-left-radius: 0px;">
				<?php
				$i = 0;
				foreach( $pluginbuddy_series[ $series ] as $name => $path ) {
					if ( is_array( $path ) ) { // Skip pbframework plugins. Future compat.
						continue; // Next loop.
						}
					$i++;
					echo '<div id="pluginbuddy-tabs-' . $i . '">';
					require( $path . '/classes/view_gettingstarted_content.php' );
					echo '</div>';
				}
				?>
			</div>
		</div>
		
		<br /><br /><br />
		<a href="http://ithemes.com" style="text-decoration: none;"><img src="<?php echo $this->_pluginURL; ?>/images/pluginbuddy.png" style="vertical-align: -3px;" /> iThemes.com</a><br /><br />
	</div>
	<div class="postbox-container" style="width:20%; margin-top: 35px; margin-left: 15px;">
		<div class="metabox-holder">
			<div class="meta-box-sortables">
				
				<div id="breadcrumbslike" class="postbox">
					<div class="handlediv" title="Click to toggle"><br /></div>
					<h3 class="hndle"><span>Things to do...</span></h3>
					<div class="inside">
						<ul class="pluginbuddy-nodecor">
							<li>- <a href="http://twitter.com/home?status=<?php echo urlencode('Check out this awesome plugin, ' . $series . '! http://ithemes.com @ithemes'); ?>" title="Share on Twitter" onClick="window.open(jQuery(this).attr('href'),'ithemes_popup','toolbar=0,status=0,width=820,height=500,scrollbars=1'); return false;">Tweet about this plugin series.</a></li>
							<li>- <a href="http://ithemes.com/find/plugins/">Check out other plugins from iThemes.</a></li>
							<li>- <a href="http://ithemes.com/find/themes/">Check out the themes from iThemes.</a></li>
						</ul>
					</div>
				</div>

				<div id="breadcrumsnews" class="postbox">
					<div class="handlediv" title="Click to toggle"><br /></div>
					<h3 class="hndle"><span>Latest news from iThemes</span></h3>
					<div class="inside">
						<p style="font-weight: bold;">iThemes.com</p>
						<?php $this->get_feed( 'http://ithemes.com/feed/', 5 );  ?>
						<p style="font-weight: bold;">Twitter @ithemes</p>
						<?php
							$twit_append = '<li>&nbsp;</li>';
							$twit_append .= '<li><img src="'.$this->_pluginURL.'/images/twitter.png" style="vertical-align: -3px;" /> <a href="http://twitter.com/ithemes/">Follow @ithemes on Twitter.</a></li>';
							$twit_append .= '<li><img src="'.$this->_pluginURL.'/images/feed.png" style="vertical-align: -3px;" /> <a href="http://ithemes.com/feed/">Subscribe to RSS news feed.</a></li>';
							$twit_append .= '<li><img src="'.$this->_pluginURL.'/images/email.png" style="vertical-align: -3px;" /> <a href="http://ithemes.com/subscribe/">Subscribe to Email Newsletter.</a></li>';
							$this->get_feed( 'https://api.twitter.com/1/statuses/user_timeline.rss?screen_name=ithemes', 5, $twit_append, 'ithemes: ' );
						?>
					</div>
				</div>
				
				<div id="breadcrumbssupport" class="postbox">
					<div class="handlediv" title="Click to toggle"><br /></div>
					<h3 class="hndle"><span>Need support?</span></h3>
					<div class="inside">
						<p>See our <a href="http://ithemes.com/tutorials/">tutorials & videos</a> or visit our <a href="http://ithemes.com/forum/">support forum</a> for additional information and help.</p>
					</div>
				</div>
				
			</div>
		</div>
	</div>
</div>
