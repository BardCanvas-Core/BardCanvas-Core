<?php
/**
 * Posts index for the home layout
 * 
 * @var post_record[] $posts
 * @var post_record[] $featured_posts
 * @var array         $pagination
 */

use hng2_modules\posts\post_record;

$has_featured_posts = count($featured_posts) > 0 ? "true" : "false";
?>

<div id="home_index_wrapper" data-with-featured-posts="<?= $has_featured_posts ?>" class="clearfix">
    
    <div id="featured_posts_column">
        
        <? foreach($featured_posts as $post): ?>
            
            <div class="featured_post">
                <div class="thumbnail">
                    <img src="http://www.foro-elite.com/wp-content/uploads/2016/07/Katherine-Jackman-255339.13-201927-e1468460131434-375x210.png?8f7ba1">
                </div>
                <h3><?= $post->get_processed_title() ?></h3>
            </div>
            
        <? endforeach; ?>
        
    </div>
    
    <div id="posts_index_wrapper">
        
        <div id="posts_index" class="posts_home posts_index clearfix">
            
            <? foreach($posts as $post): ?>
                <? $author = $post->get_author(); ?>
                
                <article class="post clearfix" data-post-id="<?= $post->id_post ?>"
                         data-main-category="<?= $post->main_category_slug ?>"
                         data-author-role="<?= $author->get_role() ?>">
                    
                    <div class="post_meta clearfix">
                        
                        <img class="user_avatar" src="<?= $config->full_root_url ?>/media/default_avatar.jpg">
                        
                        <div class="meta_section upper">
                            <a class="meta_field" href="<?= $config->full_root_url ?>/user/<?= $author->user_name ?>/">
                                <span class="fa fa-user fa-fw"></span>
                                <?= $author->get_processed_display_name() ?>
                            </a>
                            <a class="meta_field" href="<?= $config->full_root_url ?>/category/<?= $post->main_category_slug ?>/">
                                <span class="fa fa-folder fa-fw"></span>
                                <?= $post->main_category_title ?>
                            </a>
                        </div>
                        <div class="meta_section">
                            <a class="meta_field dimmed" href="<?= $config->full_root_url ?>/date/<?= date("Y", strtotime($post->publishing_date)) ?>/<?= date("m", strtotime($post->publishing_date)) ?>/<?= date("d", strtotime($post->publishing_date)) ?>/">
                                <span class="fa fa-calendar fa-fw"></span>
                                <?= time_elapsed_string($post->publishing_date) ?>
                            </a>
                        </div>
                        
                    </div>
                    
                    <h1><?= $post->get_processed_title() ?></h1>
                    
                    <div class="post_featured_image">
                        <img src="http://www.foro-elite.com/wp-content/uploads/2016/07/Katherine-Jackman-255339.13-201927-e1468460131434-375x210.png?8f7ba1">
                    </div>
                    
                    <div class="post_contents post_excerpt">
                        <?= $post->get_processed_excerpt() ?>
                    </div>
                    
                </article>
                
            <? endforeach; ?>
        
        </div>
        
        <div id="posts_pagination">
            
            <? $posts_browser->render_pagination_links("$config->full_root_url/", $pagination); ?>
        
        </div>
    </div>
    
</div>
