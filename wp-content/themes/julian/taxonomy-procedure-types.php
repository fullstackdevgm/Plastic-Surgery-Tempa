<?php
/**
 * Archive Template
 *
 * The archive template is a placeholder for archives that don't have a template file.
 * Ideally, all archives would be handled by a more appropriate template according to the
 * current page context (for example, `tag.php` for a `post_tag` archive).
 *
 * @package WooFramework
 * @subpackage Template
 */

 global $woo_options;
 get_header();
?>
    <!-- #content Starts -->
	<?php woo_content_before(); ?>
    <div id="content" class="col-full">

    	<div id="main-sidebar-container">

            <!-- #main Starts -->
            <?php woo_main_before(); ?>
            <section id="main" class="col-left">

			<?php
			global $more; $more = 0;

			woo_loop_before();
			if (have_posts()) { $count = 0;

				$term = $wp_query->get_queried_object();

				?>

				<h1 class="title"><?php echo $term->name; ?></h1>
				<?php
				// Display the description for this archive, if it's available.
				woo_archive_description();
			?>

			<div class="fix"></div>

			<?php
				while (have_posts()) { the_post(); $count++; ?>
					<div class="before-after-section">
						<div class="twocol-one">
							<h2>Before</h2>
							<?php echo wp_get_attachment_image( get_field( 'before_side' ), 'before-after' ); ?>
						</div>
						<div class="twocol-one last">
							<h2>After</h2>

						</div>
						<?php
						if( have_rows( 'before_after_photos' ) ) {
							while( have_rows( 'before_after_photos' ) ) {
								the_row();

								$before_photo = get_sub_field( 'before_photo' );
								$before_text = get_sub_field( 'before_photo_text' );
								$after_photo = get_sub_field( 'after_photo' );
								$after_text = get_sub_field( 'after_photo_text' );
								?>

								<div class="twocol-one">
									<?php

										if( $before_photo ) {
											echo wp_get_attachment_image( $before_photo, 'before-after' );
										}

										echo '<p class="ba-text">';
											if( $before_text ) {
												echo $before_text;
											}
										echo '</p>';
									?>
								</div>

								<div class="twocol-one last">
									<?php

										if( $after_photo ) {
											echo wp_get_attachment_image( $after_photo, 'before-after' );
										}

										echo '<p class="ba-text">';
											if( $after_text ) {
												echo $after_text;
											}
										echo '</p>';
									?>
								</div>

							<?php
							}
						}
						?>
					</div>

			<?php
				} // End WHILE Loop
			} else {
				get_template_part( 'content', 'noposts' );
			} // End IF Statement

			woo_loop_after();

			woo_pagenav();

			?>

            </section><!-- /#main -->
            <?php woo_main_after(); ?>

            <?php get_sidebar(); ?>

		</div><!-- /#main-sidebar-container -->

		<?php get_sidebar( 'alt' ); ?>

    </div><!-- /#content -->
	<?php woo_content_after(); ?>

<?php get_footer(); ?>