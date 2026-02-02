<?php
$base = 'https://www.coolmate.me/blog/wp-admin/admin-ajax.php?action=flatsome_ajax_apply_shortcode'
    . '&tag=blog_posts'
    . '&atts%5Bstyle%5D=normal'
    . '&atts%5Btype%5D=row'
    . '&atts%5Bcolumns%5D=3'
    . '&atts%5Bcolumns__md%5D=1'
    . '&atts%5Brelay%5D=load-more'
    . '&atts%5Brelay_class%5D=amlab_blog_load_more'
    . '&atts%5Bcat%5D=16'
    . '&atts%5Bposts%5D=6'
    . '&atts%5Bshow_date%5D=text'
    . '&atts%5Bimage_height%5D=52%25'
    . '&atts%5Bimage_size%5D=original'
    . '&atts%5Btext_align%5D=left'
    . '&atts%5Bclass%5D=border_8_img'
    . '&atts%5Bpage_number%5D=';

$start = 1;
$end   = 785;

for ($i = $start; $i <= $end; $i++) {
    echo $base . $i . PHP_EOL;
}