<?php
/**
 * Template part for displaying the page content in page.php
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
<header class="entry-header text-center">
    <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
</header><!-- .entry-header -->

<div class="entry-content">
    <figure class="text-center">
        <a href="<?php the_permalink(); ?>">
            <?php
            $islemag_thumbnail_id = get_post_thumbnail_id();
            if($islemag_thumbnail_id){
                $islemag_thumb_meta = wp_get_attachment_metadata($islemag_thumbnail_id);
                if($islemag_thumb_meta['width'] > 250 && $islemag_thumb_meta['height'] > 250 ) {
                    if( $islemag_thumb_meta['width'] / $islemag_thumb_meta['height'] > 1.5 ){
                        the_post_thumbnail('islemag_blog_post');
                    } else {
                        the_post_thumbnail('islemag_blog_post_no_crop');
                    }
                }
            } else {
                echo '<img src="' . get_template_directory_uri() . '/img/blogpost-placeholder.jpg" />';
            } ?>
        </a>
    </figure>
    <?php the_content(); ?>
    <?php
    wp_link_pages( array(
        'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'islemag' ),
        'after'  => '</div>',
    ) );
    ?>
</div><!-- .entry-content -->


<?php
edit_post_link(
    sprintf(
    /* translators: %s: Name of current post */
        esc_html__( 'Edit %s', 'islemag' ),
        the_title( '<span class="screen-reader-text">"', '"</span>', false )
    ),
    '<footer class="entry-footer"><span class="edit-link">',
    '</span></footer>'
);
?>

</article><!-- #post-## -->