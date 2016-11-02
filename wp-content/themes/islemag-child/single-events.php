<?php
/**
 * The template for displaying all single events.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package islemag
 */
$post_id = get_the_ID();
get_header(); ?>
<div class="container">
    <div class="row">
        <div class="col-md-12">

            <article id="post-<?php echo $post_id; ?>" <?php post_class("entry single"); ?>>
                <?php
                $islemag_single_post_hide_thumbnail = get_theme_mod('islemag_single_post_hide_thumbnail','1');
                if( !isset($islemag_single_post_hide_thumbnail) || $islemag_single_post_hide_thumbnail !='1'){
                    if ( has_post_thumbnail() ) { ?>
                        <div class="entry-media">
                            <figure>
                                <?php the_post_thumbnail(); ?>
                            </figure>
                        </div><!-- End .entry-media -->
                        <?php
                    }
                } else {
                    if (is_customize_preview()){
                        if ( has_post_thumbnail() ) { ?>
                            <div class="entry-media islemag_only_customizer">
                                <figure>
                                    <?php the_post_thumbnail(); ?>
                                </figure>
                            </div><!-- End .entry-media -->
                            <?php
                        }
                    }
                } ?>

                <span class="entry-date"><?php echo get_the_date( 'd' ); ?><span><?php echo strtoupper( get_the_date( 'M' ) ); ?></span></span>
                <?php
                $id = get_the_ID();
                $format = get_post_format( $id );
                switch ( $format ) {
                    case 'aside':
                        $icon_class = "fa-file-text";
                        break;
                    case 'chat':
                        $icon_class = "fa-comment";
                        break;
                    case 'gallery':
                        $icon_class = "fa-file-image-o";
                        break;
                    case 'link':
                        $icon_class = "fa-link";
                        break;
                    case 'image':
                        $icon_class = "fa-picture-o";
                        break;
                    case 'quote':
                        $icon_class = "fa-quote-right";
                        break;
                    case 'status':
                        $icon_class = "fa-line-chart";
                        break;
                    case 'video':
                        $icon_class = "fa-video-camera";
                        break;
                    case 'audio':
                        $icon_class = "fa-headphones";
                        break;
                }
                if( !empty( $icon_class ) ){ ?>
                    <span class="entry-format"><i class="fa <?php echo $icon_class; ?>"></i></span>
                    <?php
                } ?>
                <?php the_title( '<h2 class="entry-title">', '</h2>' ); ?>

                <div class="entry-content">
                    <?php the_content('Continue Reading'); ?>
                    <?php
                    wp_link_pages( array(
                        'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'islemag' ),
                        'after'  => '</div>',
                    ) );
                    ?>
                </div><!-- End .entry-content -->



                <?php $islemag_single_post_hide_author = get_theme_mod( 'islemag_single_post_hide_author' ); ?>
                <div class="about-author clearfix <?php if ( $islemag_single_post_hide_author == true ) echo 'islemag_hide'; ?>">
                    <h3 class="title-underblock custom"><?php echo esc_attr( 'Post Author:','islemag' ) ?> <a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>"><?php the_author(); ?></a></h3>
                    <?php
                    $author_id = get_the_author_meta( 'ID' );
                    $profile_pic = get_avatar( $author_id, 'islemag_sections_small_thumbnail' );
                    if( !empty( $profile_pic ) ){ ?>
                        <figure class="pull-left">
                            <?php echo $profile_pic; ?>
                        </figure>
                        <?php
                    } ?>
                    <div class="author-content">
                        <?php echo get_the_author_meta( 'description', $author_id ); ?>
                    </div><!-- End .athor-content -->
                </div><!-- End .about-author -->
            </article>

            <?php $islemag_single_post_hide_related_posts = get_theme_mod( 'islemag_single_post_hide_related_posts' ); ?>


        </div><!-- End .col-md-12 -->
    </div><!-- End .row -->
</div>
    <div class="mb20"></div><!-- space -->



<?php get_footer(); ?>