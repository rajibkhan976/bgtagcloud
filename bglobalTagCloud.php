<?php

/*
  Plugin Name: bglobal Tag Cloud
  Plugin URI: http://www.bglobalsourcing.com/
  Description: bglobal Tag Cloud display tags dynamically with specified counts from related articles belonging to the category of the display article.
  Author: bglobalsourcing
  Version: 1.0.0
  Author URI: http://www.bglobalsourcing.com/
  Text Domain: bglobalTagCloud
  Domain Path: /languages
 */

require_once 'celtislabLib2.php';

add_action( 'widgets_init', create_function('', 'return register_widget("bglobalTagCloud");') );

class bglobalTagCloud extends WP_Widget {

    const   DOMAIN = 'bglobalTagCloud';    
    
    private $post_idlist = array();
    private $post_inlink = array();

	function __construct() {
		
        load_plugin_textdomain(self::DOMAIN, false, basename( dirname( __FILE__ ) ).'/languages' );
        
        $name = __('bglobal Tag Cloud', self::DOMAIN);
        $widget_ops = array('classname' => 'bglobalTagCloud', 'description' => __('bglobal tag cloud for related categories', self::DOMAIN));
        parent::__construct( false, $name, $widget_ops );

        $this->post_idlist = array();
        $this->post_inlink = array();

        if(!is_admin()) {
            
            add_action( 'the_post', array(&$this, 'get_post_to_postID') );
            
            add_filter('the_content',array(&$this, 'get_url_to_postID'));     
            
        }
        else {
            
            $urlpath = plugins_url('bglobal_tagcloud_style.css', __FILE__);
            wp_register_style('bglobal_tagcloud_style', $urlpath);
            wp_enqueue_style('bglobal_tagcloud_style');
        }
	}

    	
    function widget( $args, $instance ) {
		extract($args);

        $default  = array( 'title' => '', 'taxonomy' => 'post_tag', 'inlink'=> false, 'cattree'=> 'all', 'ex_cat' => array(), 'inc_tag' => '', 'format' => 'flat', 'orderby' => 'name', 'order' => 'ASC', 'smallest' => 8, 'largest' => 22, 'number' => 45);
        
		$instance = wp_parse_args( (array) $instance,  $default);
		
        
		$title    = apply_filters('widget_title', $instance['title'], $instance, $this->id_base);
        $taxonomy = $instance['taxonomy'];
        
        $ctree  = $instance['cattree'];
        $ex_cat = $instance['ex_cat'];

        $format = $instance['format'];
        $inc_tag = $instance['inc_tag'];
        $orderby= $instance['orderby'];
        $order  = $instance['order'];
        
		$sfont  = $instance['smallest'];
		$lfont  = $instance['largest'];
        $number = $instance['number'];

        $postidlist = $this->post_idlist;
        if($instance['inlink'] !== false){
            $postidlist += $this->post_inlink;
        }
        
		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title;
		echo '<div class="tagcloud">';
        
        $args = apply_filters('widget_tag_cloud_args', array('taxonomy' => $taxonomy, 'format' => $format, 'orderby' => $orderby, 'order' => $order,  'smallest' => $sfont, 'largest' => $lfont, 'number' => $number, 'inc_tag' => $inc_tag));
        
		$this->dc_tag_cloud( $postidlist, $ctree, $ex_cat, $args );
		echo "</div>\n";
		echo $after_widget;
	}
        
	function update( $new_instance, $old_instance ) {
		$instance['title'] = strip_tags(stripslashes($new_instance['title']));

        
        $instance['inlink']   = isset($new_instance['inlink']);
        
        $instance['cattree'] = 'current';
		if ( in_array( $new_instance['cattree'], array( 'all', 'current', 'childof', 'parentof' ) ) )
			$instance['cattree'] = $new_instance['cattree'];

        $instance['ex_cat'] = array();
        if(isset($_POST['post_category'])){
            $cat = array();
            foreach((array) $_POST['post_category'] AS $val){
                if(!empty($val) && is_numeric($val))
                    $cat[] = (int)$val;
            }
            $instance['ex_cat'] = $cat;
        }

        $instance['format'] = 'flat';
		if ( in_array( $new_instance['format'], array( 'flat', 'list' ) ) )
			$instance['format'] = $new_instance['format'];
			
			$instance['inc_tag'] = $new_instance['inc_tag'];
        
        $instance['orderby'] = 'name';
		if ( in_array( $new_instance['orderby'], array( 'name', 'count' ) ) )
			$instance['orderby'] = $new_instance['orderby'];

        $instance['order'] = 'ASC';
		if ( in_array( $new_instance['order'], array( 'ASC', 'DESC', 'RAND' ) ) )
			$instance['order'] = $new_instance['order'];

		$instance['smallest'] = (intval($new_instance['smallest']) > 0 ) ? intval( $new_instance['smallest'] ) : 8;
		$instance['largest']  = (intval($new_instance['largest']) > 0 )  ? intval( $new_instance['largest'] ) : 22;
        if($instance['smallest'] > $instance['largest'])
            $instance['smallest'] = $instance['largest'];
		$instance['number']   = (int) $new_instance['number'];
   
        return $instance;
	}
       
	function form( $instance ) {
        $default  = array( 'title' => '', 'taxonomy' => 'post_tag', 'inlink'=> false, 'cattree'=> 'all', 'ex_cat' => '', 'inc_tag' => '', 'format' => 'flat', 'orderby' => 'name', 'order' => 'ASC', 'smallest' => 8, 'largest' => 22, 'number' => 45);
		$instance = wp_parse_args( (array) $instance,  $default);
		
        
        $ttl    = array('id'  => $this->get_field_id('title'),
                        'name'=> $this->get_field_name('title'),
                        'val' => strip_tags($instance['title']) );
        
        $taxnm  = array('id'  => $this->get_field_id('taxonomy'), 
                        'name'=> $this->get_field_name('taxonomy'),
                        'val' => $instance['taxonomy'] );
        
        
        $inlink = array('id'  => $this->get_field_id('inlink'),
                        'name'=> $this->get_field_name('inlink'),
                        'val' => $instance['inlink'] );
        
        $ctree  = array('id'  => $this->get_field_id('cattree'), 
                        'name'=> $this->get_field_name('cattree'),
                        'val' => $instance['cattree'] );
        
        $ex_cat = array('id'  => $this->get_field_id('ex_cat'),
                        'name'=> $this->get_field_name('ex_cat'),
                        'val' => $instance['ex_cat'] );
        
        $fmt    = array('id'  => $this->get_field_id('format'), 
                        'name'=> $this->get_field_name('format'),
                        'val' => $instance['format'] );
                        
        $inc_tag = array('id'  => $this->get_field_id('inc_tag'),
						 'name'=> $this->get_field_name('inc_tag'),
						 'val' => $instance['inc_tag'] );                
                        
        $orderby = array('id' => $this->get_field_id('orderby'), 
                        'name'=> $this->get_field_name('orderby'),
                        'val' => $instance['orderby'] );
        
        $order  = array('id'  => $this->get_field_id('order'), 
                        'name'=> $this->get_field_name('order'),
                        'val' => $instance['order'] );
        
        $sfont  = array('id'  => $this->get_field_id('smallest'), 
                        'name'=> $this->get_field_name('smallest'),
                        'val' => $instance['smallest'] );
        $lfont  = array('id'  => $this->get_field_id('largest'), 
                        'name'=> $this->get_field_name('largest'),
                        'val' => $instance['largest'] );
       
        $maxnum = array('id'  => $this->get_field_id('number'), 
                        'name'=> $this->get_field_name('number'),
                        'val' => $instance['number'] );
        
?>
	<p>
        <label for="<?php echo $ttl['id']; ?>"><?php _e('Title:') ?></label>
        <input type="text" class="widefat" id="<?php echo $ttl['id']; ?>" name="<?php echo $ttl['name']; ?>" value="<?php echo $ttl['val']; ?>" />
    </p>

    <p><?php _e('Related dynamic category', self::DOMAIN ); ?></p>
    <div class="bg_tagcloud_related" >
        <p>
            <?php _e('Related range', self::DOMAIN ); ?>
            <select class="widefat" id="<?php echo $ctree['id']; ?>" name="<?php echo $ctree['name']; ?>">
                <option value="all"      <?php selected( $ctree['val'], 'all' ); ?>><?php _e('All category', self::DOMAIN); ?></option>
                <option value="current"  <?php selected( $ctree['val'], 'current' ); ?>><?php _e('Current category', self::DOMAIN); ?></option>
                <option value="childof"  <?php selected( $ctree['val'], 'childof' ); ?>><?php _e('Include child category tree', self::DOMAIN); ?></option>
    			<option value="parentof" <?php selected( $ctree['val'], 'parentof' ); ?>><?php _e('Include parent category tree', self::DOMAIN); ?></option>
            </select>
        </p>
        <p>
            <input id="<?php echo $inlink['id']; ?>" name="<?php echo $inlink['name']; ?>" type="checkbox" <?php checked(isset($inlink['val']) ? $inlink['val'] : 0); ?> />&nbsp;
            <label for="<?php echo $inlink['id']; ?>"><?php _e('internal link article', self::DOMAIN); ?></label><br />
        </p>
        <p>
            <?php _e('Exclude Category', self::DOMAIN ); ?>
            <div class="bg_tagcloud_categorydiv">
                <ul class="categorychecklist"  id="<?php echo $ex_cat['id']; ?>" name="<?php echo $ex_cat['name']; ?>"  >
                    <?php wp_category_checklist(0,0,$ex_cat['val'],FALSE,NULL,FALSE); ?>
                </ul>
            </div>            
        </p>
    </div>
    <p>
        <?php _e('Display format:', self::DOMAIN) ?>
        <select class="widefat" id="<?php echo $fmt['id']; ?>" name="<?php echo $fmt['name']; ?>">
            <option value="flat" <?php selected( $fmt['val'], 'flat' ); ?>><?php _e('flat', self::DOMAIN); ?></option>
			<option value="list" <?php selected( $fmt['val'], 'list' ); ?>><?php _e('list', self::DOMAIN); ?></option>
        </select>
    </p>
    
    <p>
    <?php _e('Minimum tag count:', self::DOMAIN ) ?>
    <input type="number" class="widefat" id="<?php echo $inc_tag['id']; ?>" name="<?php echo $inc_tag['name']; ?>" value="<?php echo $inc_tag['val']; ?>" min="1">
    </p>
    
    <p>
        <?php _e('Sort by:') ?>
        <select class="widefat" id="<?php echo $orderby['id']; ?>" name="<?php echo $orderby['name']; ?>">
            <option value="name"  <?php selected( $orderby['val'], 'name' ); ?>><?php _e('Tag name', self::DOMAIN); ?></option>
			<option value="count" <?php selected( $orderby['val'], 'count' ); ?>><?php _e('Number of posts', self::DOMAIN); ?></option>
        </select>
    </p>
    <p>
        <?php _e('Order:') ?>
        <select class="widefat" id="<?php echo $order['id']; ?>" name="<?php echo $order['name']; ?>">
            <option value="ASC"  <?php selected( $order['val'], 'ASC' ); ?>><?php _e('Ascending'); ?></option>
			<option value="DESC" <?php selected( $order['val'], 'DESC' ); ?>><?php _e('Descending'); ?></option>
			<option value="RAND" <?php selected( $order['val'], 'RAND' ); ?>><?php _e('Random'); ?></option>
        </select>
    </p>
	<p>
        <?php _e('Font size (pt):', self::DOMAIN); ?>&nbsp;
		<input id="<?php echo $sfont['id']; ?>" name="<?php echo $sfont['name']; ?>" type="text" value="<?php echo $sfont['val']; ?>" size="3" />
        <?php _e(' - ' ); ?>
        <input id="<?php echo $lfont['id']; ?>" name="<?php echo $lfont['name']; ?>" type="text" value="<?php echo $lfont['val']; ?>" size="3" />
    </p>
	<p>
        <?php _e('Max number of tags to display:', self::DOMAIN); ?>
		<input id="<?php echo $maxnum['id']; ?>" name="<?php echo $maxnum['name']; ?>" type="text" value="<?php echo $maxnum['val']; ?>" size="3" />
    </p>

<?php

	}
    
    public static function my_uninstall_hook()
    {
   
        //delete_option('dc_tagcloud_option');
    }

    public function get_post_to_postID()
    {
        if(is_front_page() || is_home() || is_single() || is_category() || is_tag() || is_author() || is_date() || is_archive()){
            if(CeltisLib2::in_dynamic_sidebar() === false){
                $post = get_post();
                       
                $this->post_idlist += array($post->ID => array('post_title'=>$post->post_title, 'post_status'=>$post->post_status));
            }
        }
    } 
    
    public function get_url_to_postID($text)
    {    
        if(is_single() || is_page()){
            if(CeltisLib2::in_dynamic_sidebar() === false){
                $this->post_inlink += CeltisLib2::html_linkurl_postsid($text);
            }
        }
        return $text;
    }
    
    function dc_tag_cloud($postsid, $cattree, $catexclude, $args = '' ) {
		
        $defaults = array(
            'smallest' => 8, 'largest' => 22, 'unit' => 'pt', 'number' => 45,
            'format' => 'flat', 'separator' => "\n", 'orderby' => 'name', 'order' => 'ASC',
             'include' => '', 'exclude' => '', 'link' => 'view', 'taxonomy' => 'post_tag', 'echo' => true
        ); 
        
          $args = wp_parse_args( $args, $defaults );
          //print_r($args['inc_tag']);
        
        $cidlist = array();
        if($cattree === 'all'){
            
            $excatstr = implode(',', $catexclude);
            $catlist  = get_categories(array('exclude' => $excatstr, 'hide_empty' => true));
            
            if(!empty($catlist)){
                foreach ( $catlist as $cat) {
                    $cidlist += array($cat->term_id => (array)$cat);
                }
            }
        }
        else if(!empty($postsid)) {
            
            foreach ($postsid as $pid => $pval){
                if($pval['post_status'] === 'publish'){
                    $cidlist += CeltisLib2::postid_related_categories($pid, $cattree, $catexclude);
                }
            }
        }

        
        if(!empty($cidlist)) {
            $postsid = CeltisLib2::categoryid_postsid( array_keys($cidlist));
        }
            
        $tags = array();
        
        if(!empty($postsid)) {
            
            $tags = CeltisLib2::postsid_tags( array_keys($postsid));
            
            //print_r($tags);
            
        }
//        else {
//          
//            $tags = get_terms( $args['taxonomy'], array_merge( $args, array( 'orderby' => 'count', 'order' => 'DESC' ) ) ); // Always query top tags
//        }

        if ( empty( $tags ) || is_wp_error( $tags ) )
            return;

        foreach ( $tags as $key => $tag ) {			
				
				if($tag->count < $args['inc_tag']){			
					unset($tags[$key]);
				}else{
					
					if ( 'edit' == $args['link'] )						
						$link = get_edit_tag_link( $tag->term_id, $tag->taxonomy );
					else						
						$link = get_term_link( intval($tag->term_id), $tag->taxonomy );
						
					if ( is_wp_error( $link ) )
						return false;					
					
						
						$tags[ $key ]->link = $link;
						$tags[ $key ]->id = $tag->term_id;					
					}
		
				}
      
        $return = wp_generate_tag_cloud( $tags, $args ); // Here's where those top tags get sorted according to $args
		//print_r($args);

        $return = apply_filters( 'wp_tag_cloud', $return, $args );
        
        //print_r($return);

        if ( 'array' == $args['format'] || empty($args['echo']) )
            return $return;

        echo $return;
        
    }
    
}


?>
