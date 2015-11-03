<?php
/**
 * Plugin Name: Image Gallery Import
 * Plugin URI: http://www.wptrack.com/image-gallery-import-wordpress-plugin/
 * Description: Plugin takes remote page from the entered URL, examines its content and lists all found images for your choice. Then you are able to select which images you want to import, apply some options such as resize, rename, add your personal titles and more and then in one click to import all images into your wordpress media gallery and automatically create post with imported images in it (as gallery shortcode or embedded with titles).
 * Version: 1.0
 * Author: Michael Shevtsov
 * Author URI: http://www.wptrack.com/
*/ 

require_once(ABSPATH . 'wp-includes/pluggable.php');
require_once(ABSPATH . 'wp-admin/admin-functions.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');


if(!is_admin()) return;

if ( ! ( ( $uploads = wp_upload_dir() ) && false === $uploads['error'] ) ) return false;

set_time_limit(180); // 3 mins is ok ?

define('IMGLEECH_AGENT', $_SERVER['HTTP_USER_AGENT']);

// leave my signature as your donation to my work!
define('IMPORT_SIGNATURE', "\r\n\r\n<small>This post is created with <a href=\"ht" . 'tp://www.wpt' . "rack.com/\">Image import plugin</a></small>");

// default link to source - change to yours , if you want
define('IMGLEECH_SRCTEXT', "<p><a href='%url%'>Original source</a></p>");

// here goes the code, no need to modify, just learn :) not OOP style, but task is too simple to use guns

if(isset($_REQUEST['act']) && strlen($_REQUEST['act']) ) imgleech_actions($_REQUEST['act']);

add_action('admin_menu', 'imgleech_menu');

function imgleech_menu() {
	add_submenu_page('upload.php', 'Import Gallery', 'Import Gallery', 8, __FILE__, 'imgleech_front');
}

function imgleech_front(){

$_myself = $GLOBALS['PHP_SELF'] . "?page=" . $_REQUEST['page'];

$srcbase = IMGLEECH_SRCTEXT;

echo <<< EOF

<style tyle="text/css">

#images{display:none;}

#imglist table { border: 0; border-collapse: collapse; width: 100%; }

#imglist table td {
	padding: 5px 10px 5px 10px;
	border-bottom: 1px solid #ccc;
	text-align: center;
	overflow:hidden;
}

#imglist table thead td {
	padding: 10px 10px;
	background-color: #f8f8f8;
	font-weight: bolder;
	color: #333;
}

#imglist {
	margin: 10px 0 10px 0;
	border: 1px solid #ccc;
	border-radius: 3px;	
}

.il-title { width: 90%; }

td.ic { width: 36px;}
td.id { width: 100px;}

.lab { 	display: inline-block; 	width: 140px; }

.lline{line-height: 30px;}

#posttitle, #srclink {width: 400px;}
#postdescr  {width: 600px;}
#remoteurl {width: 360px;}
#ir-width, #ir-height { width: 50px; }

#wait-msg{
	background: #fffff0;
	text-align: center;
	padding: 5px 0 5px 0;
	margin: 5px 0 5px 0;
	border: 1px solid #ccc;
	border-radius: 3px;
	display:none;
}

.import-panel {
	margin: 10px 0 10px 0;
	padding: 10px 5px;
	border: 1px solid #ccc;
	border-radius: 3px;
	background-color: #fcfcfc;
}

</style>

<div class="wrap">
<div id="icon-upload" class="icon32"><br></div>
<h2>Import Images From External Webpage</h2>


<div id="wait-msg"></div>

<div class="import-panel" id="source-params">

	<p class="description">
	Enter the URL of the webpage that has embedded images.
	Specify optional filter parameters (minimum width or height or both), so plugin will filter out those images that are
	smaller specified dimensions. Then you will be able to selected found images and import them to your media gallery and optionally create Draft post which will have them embedded.
	</p>

	<form id="url-form" class="ajaxform" action="$_myself&noheader=1" method="post">
		<input type="hidden" name="mode" id="img" value="img">
		<div class="lline"><span class="lab"><label for="remoteurl">Remote URL</label></span>
			<input type="text" name="remoteurl" id="remoteurl" size="50" />
			<input type="submit" name="" id="doaction" class="button action" value="Fetch">
		</div>
		<!-- <div class="lline"><span class="lab">Work with</span>
			<label for="img"><input type="radio" name="mode" id="img" value="img" checked="checked">Images</label>
			&nbsp; <label for="links"><input type="radio" name="mode"  id="links" value="links">Links</label>
		</div> -->
		<div class="lline"><span class="lab">Image filter</span> <label for="minwidth">min width</label> <input type="text" name="minwidth" id="minwidth" size="5" value="400" />
			<label for="minheight">min height</label> <input type="text" name="minheight" id="minheight" size="5" value="0" />
		</div>
	</form>
	
</div>

<div class="results-panel" id="images">
	<h3>Import Parameters</h3>
	<form id="foundimages" method="post" action="$_myself&noheader=1">
	<div id="imgactions" class="import-panel">
		<div class="lline"><span class="lab">Import selected and</span>
			<select name="perform" id="perform">
				<option value="import">Only import images</option>
				<option value="post">Insert post with embedded images</option>
				<option value="gallery">Insert as gallery</option>
			</select>
			&nbsp;&nbsp;&nbsp;
			<input type="submit" id="proceed" class="button action" value="Proceed &raquo;">
		</div>
		
	
		<div class="lline"><span class="lab">Post title</span><input type="text" name="posttitle" id="posttitle" value="" /></div>
		<div class="lline"><span class="lab">Post excerpt</span><input type="text" name="postdescr" id="postdescr" /></div>
		
		<div class="lline"><span class="lab"><label><input type="checkbox" name="addsrc" id="ir-addsrc" value="1" checked="checked" /> Add source link</label></span>
			<input type="text" name="srclink" id="srclink" value="$srcbase" />
		</div>

		<div class="lline"><span class="lab"><label><input type="checkbox" name="resize" id="ir-resize" value="1" /> Resize images to</label></span>
			<input type="text" id="ir-width" name="width" value="" /> x
			<input type="text" id="ir-height" name="height" value="" /> px
		</div>

		<div class="lline"><span class="lab">Options</span>
			<label><input type="checkbox" name="usehash" id="ir-usehash" value="1" /> Convert image names to 32 char md5 of their names (more unique names)</label> Also use this if all images on remote page has same name. 
		</div>

		<div class="lline"><span class="lab"></span>
			<label><input type="checkbox" name="addtitles" id="ir-addtitles" value="1" /> Add titles under images (in embedded images post)</label>
		</div>

		<div class="lline"><span class="lab"></span>
			<label><input type="checkbox" name="addftr" id="ir-addftr" value="1" checked="checked" /> Add random image as featured in post</label>
		</div>
		
		
	</div>
	<h3>Found images</h3>
	<div id="imglist"><table>
	    <thead>
		<tr class="il-head"><td class="ic"><input type="checkbox" name="checkall" id="checkall" /></td>
		<td class="is">Image/Name</td><td class="id">Dimensions</td></tr>
	    </thead>
	    <tbody id="imglistb"/></table>
	</div>
	</form>

</div>

</div>

<!-- -=*=- -->

<script type="text/javascript">

jQuery('#url-form').submit(il_fetch);
jQuery('#foundimages').submit(il_perform_img_import);
jQuery('#ir-width, #ir-height').focus(function(){
	jQuery('#ir-resize').attr('checked', 'checked');
});

var li_mode = 'img', li_mw = 0, li_mh = 0, fetchurl, fetchtitle = '', fetchdescr = '';

jQuery('#checkall').click(function(){
	var m = jQuery(this).is(':checked'), mm;
	mm = jQuery('#imglistb .ic input');
	mm.each(function(){
		if(m) jQuery(this).attr('checked', 'checked'); else jQuery(this).removeAttr('checked');
	});
});


function il_fetch(){
	
	var ru = jQuery('#remoteurl').val();
	if(ru.indexOf('http') != 0){
		jQuery('#remoteurl').select().focus();
		return false;
	}
	
	fetchurl = ru;
	
	var form = jQuery(this).serialize() + '&act=fetch', url = this.action;

	jQuery('#doaction, #proceed').attr('disabled', 'disabled');
	jQuery('#wait-msg').text('Examining resource... Please wait.').show();

	if(jQuery('#links').is(':checked')) li_mode = 'links'; else li_mode = 'img';
	
	

	jQuery.ajax({
		url: url,
		dataType: 'json',
		type: "POST",
                data: form,
                cache: false,
                success: il_render_images,
		error: il_error
	});
	
	return false;
}

function il_error(){
	jQuery('#wait-msg').hide();
	jQuery('#doaction, #proceed').removeAttr('disabled');
	alert('Internal error');
}

function il_render_images(ret){
	jQuery('#wait-msg').hide();
	jQuery('#doaction, #proceed').removeAttr('disabled');
	
	
	li_mw = parseInt(jQuery('#minwidth').val());
	li_mh = parseInt(jQuery('#minheight').val());
	
	var url, data, ss, nn = 0; 
	if('img' == li_mode){
		jQuery('#imglistb').html('');
		jQuery('#images').show();
		
		for( url in ret.img){
			data = ret.img[url];
			ss = '<tr id="tr-img-' + nn + '"><td class="ic"><input type="checkbox" value="' + url +'" name="icheck[' + nn + ']" /></td>' +
				'<td class="is"><img id="il-img-' + nn + '" src="' + url + '" class="il-tmb"/><br/>' +
				'<input type="text" class="il-title" name="ititle[' + nn + ']" id="il-title-' + nn + '" />' +
				'</td><td class="id">&nbsp;</td></tr>';
			jQuery('#imglistb').append(ss);
			jQuery('#il-img-' + nn).load(il_img_data);
			jQuery('#il-img-' + nn).error(il_img_data);
			jQuery('#il-img-' + nn).click(il_img_click);
			jQuery('#il-title-' + nn).val(data.n);
			nn++;
		}
		

		
	}
	
	if(ret.title){ fetchtitle = ret.title; jQuery('#posttitle').val(fetchtitle); }
	if(ret.descr){ fetchdescr = ret.descr; jQuery('#postdescr').val(fetchdescr); }
	
	//console.log(ret);
	
}

function il_perform_img_import(){
	var form = jQuery('#foundimages').serialize() + '&act=import&mode=' + li_mode + '&baseurl=' + escape(fetchurl), url = this.action;

	jQuery('#doaction, #proceed').attr('disabled', 'disabled');
	jQuery('#wait-msg').text('Importing ... Please wait, this may take a while...').show();

	jQuery.ajax({
		url: url,
		dataType: 'json',
		type: "POST",
                data: form,
                cache: false,
                success: il_import_result,
		error: il_error
	});
	
	return false;
}

function il_img_data(){

	var w = jQuery(this).width(), h = jQuery(this).height();
	if(w == 0 || h == 0 || (li_mw > 0 && w < li_mw) || (li_mh>0 && h < li_mh) ){
		jQuery(this).parents('tr').remove();
	}else{
		jQuery(this).parents('tr').find('.id').first().html(w + 'x' + h);
	}
}

function il_img_click(){
	var cb = jQuery(this).parents('tr').find('.ic input').first();
	if(!cb.is(':checked')) cb.attr('checked', 'checked'); else cb.removeAttr('checked');
}

function il_import_result(ret){
	jQuery('#wait-msg').hide();
	jQuery('#doaction, #proceed').removeAttr('disabled');
	
	if(ret.error){
		alert(ret.error);
	}
	
	// console.log(ret);
	if(ret.post_id) document.location = 'post.php?action=edit&post=' + ret.post_id;
}




</script>
EOF;
}

function imgleech_combine_url( $url1, $url2)
{
    if( preg_match('!^(http[s]*|ftp|mailto):!', $url2) ) return $url2;
    $pst = parse_url( $url1 );

    if( $url2{0} == '/' ) {
	return "{$pst[scheme]}://{$pst[host]}{$url2}";
    }else{
	$path = $pst['path'];
	if( !preg_match('|/$|', $path ) ) $path = preg_replace('|/([^/]+)$|', '/', $path );
	if( !strlen($path) ) $path = '/';

	return "{$pst[scheme]}://{$pst[host]}{$path}{$url2}";
    }
}


function imgleech_out($out){
	echo json_encode($out); die(0);
}

function imgl_extract_data($data, $orig_url, $opts = array()){
	preg_match_all( '!<img.+?src=["\']{0,1}(.+?)["\']{0,1}[\s>]!ims', $data, $iout, PREG_SET_ORDER);

	$aaa = $img = array();

	$url_c = parse_url(get_settings('siteurl'));
	$localhost =  $url_c['scheme'] . '://' . $url_c['host'];

	if(count($iout)) {
		for( $i = 0; $i < count($iout); $i++ ){
			$url = imgleech_combine_url( $orig_url, $iout[$i][1]);		
			$url_c = parse_url($url);
								
			$img[$url]++;
			
			/* if(preg_match('!<.+?' . preg_quote($orig_url) .'.+?>' , $data, $oiy)){}*/
		}
	}

	preg_match_all( '!<a.+?href=["\']{0,1}(.+?)["\']{0,1}[\s>]!ims', $data, $iout, PREG_SET_ORDER);

	if(count($iout)) {
		for( $i = 0; $i < count($iout); $i++ ){
			$url = imgleech_combine_url( $orig_url, $iout[$i][1]);
			$url_c = parse_url($url);

			if( $url_c['host'] != '' ) { // && ( ( $iurl = $url_c['scheme'] . '://' . $url_c['host'] ) != $localhost ) 
				$aaa[$url]++;				
			}
		}
	}
	
	asort($img);
	asort($aaa);
	
	foreach($img as $a=>$b){
		$img[$a] = array('n' => basename($a), 's' => 0, 'm' => '', 'u' => $b );
	}

	foreach($aaa as $a=>$b){
		$aaa[$a] = array('n' => basename($a), 's' => 0, 'm' => '', 'u' => $b );
	}

	$ret = array('img' => $img, 'href' => $aaa);
	
	if(preg_match( '!<title>(.+?)</title>!i', $data, $iout)){
		$ret['title'] = $iout[1];
	}

	if(preg_match( '!<meta([^>]+)name=[\'"]{0,1}description[\'"]{0,1}([^>]*)>!i', $data, $iout)){
		if(preg_match('!content=([\'"]){0,1}(.+?)\1!i', $iout[0], $ioux)){
			$ret['descr'] = $ioux[2];
		}
		
	}

	// $ret['io1'] = $iout;
	// $ret['io2'] = $ioux;
	return $ret;
}


// remote url wrapper
function imgleech_url($url, $ref = ''){
	$opts = array( 'timeout' => 30, 'httpversion' => '1.1', 'sslverify' => false, 'user-agent'  => IMGLEECH_AGENT);
	if($ref)
	$opts['headers'] = array("Referer: $ref");
	$data = wp_remote_get( $url, $opts );
	return $data;
}


function imgleech_actions($action){
	$action = preg_replace('![^a-z^0-9^\-^_]!', '', strtolower($action));
	if(function_exists($func = "imgleech_act_$action")) $func($_REQUEST);
	die(0);
}

// action handlers

function imgleech_act_fetch(&$args){

	$data = imgleech_url($args['remoteurl']);

	// $url_c = parse_url($args['remoteurl']);

	if(is_wp_error($data)) imgleech_out((array('error' => $data->get_error_message() )));

	$ret = imgl_extract_data($data['body'], $args['remoteurl']);
	$ret['imgc'] = count($ret['img']);
	$ret['hrefc'] = count($ret['href']);
	$ret['len'] = strlen($data['body']);
	
	imgleech_out($ret);
}

function imgleech_act_import(&$args){

	global $uploads, $wpdb;
	
	if(!$args['icheck']){
		imgleech_out(array('error' => 'No images selected' ));
	}

	$r_w = intval($args['width']);
	$r_h = intval($args['height']);
	$resize = intval($args['resize']) && ($r_w > 23 || $r_h > 23);
	
	$post_action = $args['perform'];
	$baseurl = $args['baseurl'];
	
	$usehash = intval($args['usehash']);
	$addt = intval($args['addtitles']);
	$addftr = intval($args['addftr']);

	$post_id = 0;
	
	if($post_action == 'post' || $post_action == 'gallery'){
		$post = array(
			'post_title' => $args['posttitle'] ?  $args['posttitle'] : 'Imported images',
			'post_status' => 'draft',
			'post_type' => 'post',
		);
		
		if($args['postdescr']) $post['post_excerpt'] = $args['postdescr'];
		
		$post_id = wp_insert_post($post);
		
	}
	
	// used to add some unique prefix for each resource;
	// so images with same names will not intercept, crc32 is a good way to use number rather than complex md5
	
	$urlpfx = sprintf('%u-', crc32($baseurl));
	
	$log = $attached = $urls = array();
	
	foreach($args['icheck'] as $nn => $url){
		$title = $args['ititle'][$nn];

		$filename = basename($url);
		
		if($usehash){ $ext = @pathinfo($filename, PATHINFO_EXTENSION); $filename = md5($url) . ($ext ? ".$ext" : ''); }

		$filename = $urlpfx . $filename;

		$new_file = $uploads['path'] . "/$filename";
		$new_url = $uploads['url'] . "/$filename";

		if( file_exists($uploads['path'] . "/$filename") ) {

			$att_id = $wpdb->get_var($wpdb->prepare("select ID from {$wpdb->posts} where guid = %s", $new_url ));
			if($att_id){
				$attached[] = $att_id;
				$urls[] = $new_url;
				$titles[] = $title;
			
				$log[] = 'File already exists <em>' . $url . '</em>';		
				continue;
			}
		}	
		
		$data = imgleech_url($url);
		if(is_wp_error($data)) { $log[] = 'Unable to import <em>' . $url . '</em>';  continue;}
		
		$fb = fopen( $new_file, 'wb' );
		if( $fb ) { fwrite( $fb, $data['body'], strlen($data['body']) ); fclose($fb); }
		
		if($resize) {
			$img = wp_get_image_editor($new_file);
			$img->resize( $r_w ? $r_w  : NULL , $r_h ? $r_h : NULL, false );
			$saved = $img->save($new_file);
			unset($img);
			
			if ($saved === FALSE) {
				$log[] = 'Unable to resize <em>' . $filename . '</em>';
			}
		}
		
		if( file_exists($new_file) ) {
			$info = @getimagesize($new_file);
			
			// Construct the attachment array
			$attachment = array(
				'post_title' => $title,
				'post_content' => '',
				'post_status' => 'inherit',
				'post_parent' => $post_id,
				'post_mime_type' => $info['mime'] ?  $info['mime'] : 'image/jpeg',
				'guid' => $new_url
				);
			
			// Save the data
			$att_id = wp_insert_attachment($attachment, $new_file, $post_id);
			if ( !is_wp_error($att_id) ) {
				$imagedata = wp_generate_attachment_metadata( $att_id, $new_file );
				wp_update_attachment_metadata( $att_id, $imagedata );

				$attached[] = $att_id;
				$urls[] = $new_url;
				$titles[] = $title;
			}
			
			
		}
		
		
	}
	
	if(count($attached) && $post_id){
		if($post_action == 'post') {
			$cnt = '';
			foreach($attached as $nn => $att_id){
				$att = wp_get_attachment_metadata($att_id, true);
				$alt = $titles[$nn];
				$extra = $addt ? "\r\n$alt\r\n" : '';
				$cnt .= "<img class=\"aligncenter size-full wp-image-$att_id\" src=\"{$urls[$nn]}\" alt=\"$alt\" width=\"{$att['width']}\" height=\"{$att['height']}\" />\r\n$extra\r\n";
			}
		}elseif($post_action == 'gallery') {
			$cnt = '[gallery link="file" ids="' . join(',', $attached) . '"]';
		}

		if($args['addsrc']){
			$srclink = $args['srclink'];
			if(strpos($srclink, '%url%') !== false)  $srclink = str_replace('%url%', $args['baseurl'], $srclink);
			else $srclink = "<a href=\"{$args['baseurl']}\">$srclink</a>";
			
			$cnt .=  $srclink;
		}
		
		$cnt .=  IMPORT_SIGNATURE;

		$post = array('ID' => $post_id, 'post_content' => $cnt);
		wp_update_post($post);

		if($addftr){
			$thumbnail_id = $attached[mt_rand(0, count($attached)-1)];
			set_post_thumbnail($post_id, $thumbnail_id );
		}

	}
	
	
	$ret = array('post_id' => $post_id, 'attached_ids' => $attached, 'log' => $log, 'resize' => $resize);

	// file_put_contents(ABSPATH . '/log.txt', var_export($ret, 1));

	imgleech_out($ret);
}