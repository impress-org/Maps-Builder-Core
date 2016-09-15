<a href="?post_type=google_maps&page=<?php echo $key; ?>" class="nav-tab <?php echo $active_tab == 'map_options' ? 'nav-tab-active' : ''; ?>">
	<?php _e( 'Map Options', 'google-maps-builder' ); ?>
</a>
<a href="?post_type=google_maps&page=<?php echo $key; ?>&tab=general_settings" class="nav-tab <?php echo $active_tab == 'general_settings' ? 'nav-tab-active' : ''; ?>">
	<?php _e( 'General Options', 'google-maps-builder' ); ?>
</a>
<a href="?post_type=google_maps&page=<?php echo $key; ?>&tab=system_info" class="nav-tab <?php echo $active_tab == 'system_info' ? 'nav-tab-active' : ''; ?>">
	<?php _e( 'System Info', 'google-maps-builder' ); ?>
</a>
