<?php

/**
 * Description of celtislabLib2
 *
 * Author: enomoto@celtislab
 * Version: 0.6.0
 * Author URI: http://celtislab.net/
 */


class CeltisLib2 {
//---- CeltisLib1 -----------------------------------------------------------
    
    public static function isnot_exclude_page($exclude_id)
    {
        $exclude = (count($exclude_id) > 0 ) && is_page($exclude_id);
        return( ! $exclude );
    }
   
    public static function is_include_page($include_id)
    {
        $include = (count($include_id) > 0 ) && is_page($include_id);
        return( $include );
    }

    public static function isnot_exclude_single($exclude_cat, $exclude_id)
    {
        $exclude1 = in_category($exclude_cat);
        $exclude2 = (count($exclude_id) > 0 ) && is_single($exclude_id);
        return( (! $exclude1) && (! $exclude2) );
    }
    
 
    public static function is_include_single($include_cat, $include_id)
    {
        $include1 = (count($include_cat) > 0 ) && in_category($include_cat);
        $include2 = (count($include_id) > 0 ) && is_single($include_id);
        return( $include1 || $include2 );
    }

   
    public static function in_dynamic_sidebar()
    {
        $in_flag = false;
        $trace = debug_backtrace();
        foreach ($trace as $stp) {
            if(isset($stp['function'])){
                if($stp['function'] === "dynamic_sidebar"){
                    $in_flag = true;
                    break;
                }
            }
        }
        return $in_flag;
    }

    //dyndamic_sidebar
    public static function get_mydynamic_sidebar($index = 1)
    {
        ob_start();
        dynamic_sidebar($index);
        $sidebar_contents = ob_get_clean();
        return $sidebar_contents;
    }
 
    
    
    public static function get_shorturl($permalink, $type = 1) 
    {
        $url = $permalink;
        if($type == 1){ //tinyurl
            $maketiny = 'http://tinyurl.com/api-create.php?url='.$url;

            $ch = curl_init();
            $timeout = 5;
            curl_setopt($ch, CURLOPT_URL, $maketiny);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

            $tinyurl = curl_exec($ch);
            if(! curl_errno($ch)){
                $url = $tinyurl;
            }
            curl_close($ch);
        }
        return $url;
    }
   
    
    public static function get_check_date($date)
    {
        if (isset($date)) {
            $dateval = strtotime( trim($date) );
            if($dateval !== FALSE){
                if( checkdate(date('m', $dateval), date('d', $dateval), date('Y', $dateval)) !== FALSE ){
                    $date = date('Y-m-d', $dateval);
                    return $date;
                }
            }
        }
        return NULL;
    }  
 
    public static function htmltagpos($html, $stag, $etag, $ofset=0)
    {
        $pos = FALSE;
        $start = stripos($html, $stag, $ofset);
        if($start !== FALSE){
            $end = stripos($html, $etag, $start);
            if($end !== FALSE){
                $end += (strlen($etag)-1);
                $pos = array($start, $end);
            }
        }
        return $pos;
    }
    
    
    public static function htmltagsplit($html, $stag, $etag, $ofset=0)
    {
        $newhtml[0] = '';
        if(strlen($html) > 0){
            $start = $ofset;
            $end = strlen($html) - $ofset - 1;
            if($start != 0)
                $newhtml[0] = substr($html, 0, $start);
            for($cnt=0; ($pos = CeltisLib2::htmltagpos($html, $stag, $etag, $start)) !== FALSE; $cnt++){
                $newhtml[0] = $newhtml[0] . substr($html, $start, $pos[0] - $start);
                $len = $pos[1] - $pos[0] + 1;
                $newhtml[$cnt+1] = substr($html, $pos[0], $len);
                $start = $pos[1] + 1;
            }
            if($start < $end)
                $newhtml[0] = $newhtml[0] . substr($html, $start, $end - $start + 1);
        }
        return $newhtml;
    }

//---- CeltisLib2 -----------------------------------------------------------

    public static function htmltagsplit_exclude($html, $stag, $etag, $exkeylist, $ofset=0)
    {
        $newhtml[0] = '';
        $exkeystr = implode('|', $exkeylist);
        
        if(strlen($html) > 0){
            $start = $ofset;
            $end = strlen($html) - $ofset - 1;
            if($start != 0)
                $newhtml[0] = substr($html, 0, $start);
            for($cnt=0; ($pos = CeltisLib2::htmltagpos($html, $stag, $etag, $start)) !== FALSE; ){
                $len = $pos[1] - $pos[0] + 1;
                $sephtml = substr($html, $pos[0], $len);
               
                if (! preg_match("/$exkeystr/", $sephtml)){
                    $newhtml[0] = $newhtml[0] . substr($html, $start, $pos[0] - $start);
                    $newhtml[$cnt+1] = $sephtml;
                    $cnt++;
                }
                else {
                    $newhtml[0] = $newhtml[0] . substr($html, $start, $pos[0] - $start) . $sephtml;
                }
                $start = $pos[1] + 1;
            }
            if($start < $end)
                $newhtml[0] = $newhtml[0] . substr($html, $start, $end - $start + 1);
        }
        return $newhtml;
    }

    
    public static function linkurl_postid($atagstr)
    {
        $pattern = '/(https?):\/\/([-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)/u';
        $match = array();
        if(preg_match($pattern, $atagstr, $match)){
            $pid = url_to_postid($match[0]);
            if($pid > 0){
                $p = get_post($pid);
                return( array($p->ID => array('post_title'=>$p->post_title, 'post_status'=>$p->post_status)));
            }
        }
        return false;
    }
    
    
    public static function html_linkurl_postsid($html)
    {
        
        $exkey = array('<img', '<embed', '<iframe', '<object', '<param', '<video', '<audio', '<source', '<track', '<canvas', '<map', '<area');
        $alink = CeltisLib2::htmltagsplit_exclude($html, '<a', '/a>', $exkey);
        
        $pidlist = array();
        for($n=1; $n <count($alink); $n++ ){
            $pid = CeltisLib2::linkurl_postid($alink[$n]);
            if($pid !== false)
                $pidlist += $pid;
        }
        return $pidlist;
    }
    
    public static function postid_related_categories($postid, $mode, $ex_catid=array())
    {
        $cidlist = array();
        
        foreach ( (array) get_the_category($postid) as $cat ) {
            if ( empty($cat->name )) 
                continue;

           
            if($mode === 'parentof'){
                
                while(1){
                    if ( $cat->parent == '0' )
                        break;
                    $cat = get_category( $cat->parent );
                }
            }
            
            if ($cat->count > 0 && in_array($cat->term_id, $ex_catid)===false)
                $cidlist += array($cat->term_id => array_slice((array)$cat, 0, 9));
            
            
            if($mode === 'childof' || $mode === 'parentof'){
                $child = get_terms( 'category', array( 'child_of' => $cat->term_id ) );
                if(isset($child)){
                    foreach ( $child as $cat) {
                        if($cat->count > 0 && in_array($cat->term_id, $ex_catid)===false)
                            $cidlist += array($cat->term_id => (array)$cat);
                    }
                }
            }
        }
        return $cidlist;
    }

    
    public static function categoryid_postsid($catidlist)
    {
        $pidlist = array();
        if(!empty($catidlist)) {
            $cateidstr = implode(',', $catidlist);
            $posts = get_posts(array('category' => $cateidstr, 'numberposts' => -1));
            foreach ($posts as $p){
                if($p->post_status === 'publish')
                    $pidlist += array($p->ID => array('post_title'=>$p->post_title, 'post_status'=>$p->post_status));
            }
        }
        return $pidlist;
    }

    
    public static function postsid_tags($postsid)
    {
        
        $taglist = array();
        foreach ($postsid as $pid){
            foreach ( (array) get_the_tags($pid) as $tag ) {
                if ( empty($tag->name ) )
                    continue;
                $taglist += array($tag->term_id => $tag);
            }
        }
        return $taglist;
    }    
}

?>
