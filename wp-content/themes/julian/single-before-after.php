<?php
/**
 * Single Post Template
 *
 * This template is the default page template. It is used to display content when someone is viewing a
 * singular view of a post ('post' post_type).
 * @link http://codex.wordpress.org/Post_Types#Post
 *
 * @package WooFramework
 * @subpackage Template
 */

get_header();
?>

    <!-- #content Starts -->
	<?php woo_content_before(); ?>
    <div id="content" class="col-full">

    	<div id="main-sidebar-container">

            <!-- #main Starts -->
            <?php woo_main_before(); ?>
            <section id="main">
<?php
	woo_loop_before();

	if (have_posts()) { $count = 0;
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
		<?php }
	}

	woo_loop_after();
?>
            </section><!-- /#main -->
            <?php woo_main_after(); ?>

            <?php get_sidebar(); ?>

		</div><!-- /#main-sidebar-container -->

		<?php get_sidebar('alt'); ?>

    </div><!-- /#content -->
	<?php woo_content_after(); ?>

<?php get_footer(); ?>