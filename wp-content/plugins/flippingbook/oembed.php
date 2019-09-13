<?php
error_reporting(0);

include ('mustache.php');

$format = htmlspecialchars( $_GET[ "format" ] );

$default_width  = 740;
$default_height = 480;
$aspect_ratio   = 1;

$get_width  = $_GET[ "maxwidth" ];
$get_height = $_GET[ "maxheight" ];

$max_width  = empty($get_width)  ? $default_width  : htmlspecialchars( $get_width );
$max_height = empty($get_height) ? $default_height : htmlspecialchars( $get_height );

if( ($max_width <=$default_width && $max_height <= $default_height) || ($max_width >= $max_height) )
{
	$width  = $max_width;
	$height = $max_height;
} else {
	$width  = $max_width;
	$height = $max_width;
}

$url = htmlspecialchars( $_GET[ "url" ] );
$url_info = parse_url( $url );

if( $url_info ) {
	$domain = $url_info['host'];

	$domain_names = explode( ".", $domain );

	if( count( $domain_names ) < 2 ){
		return;
	}
	$bottom_domain_name = $domain_names[count($domain_names)-2] . "." . $domain_names[count($domain_names)-1];
	$cld_mask = '#https?://(.*\.)?(cld\.mobi)|(cld\.bz)/.*#i';

	if( preg_match( $cld_mask , $url ) ) {
		$embed_script_url = get_embed_url( $bottom_domain_name, $url );

		if ( !empty($embed_script_url) && !stripos($embed_script_url, 'embed-boot/boot.js')) {
			$htmltext = get_embed_code( $url, $width, $height, $embed_script_url );
		} else {
			$htmltext = '<a href="' . $url . '" class="cld-embed" data-cld-width="' . $width . 'px" data-cld-height="' . $height . 'px">' . $url . '</a><script async defer src="https://'.$bottom_domain_name.'/content/embed-boot/boot.js"></script>';
		}
	}

	if( $format === "json" ) {
		echo_json_format( $htmltext, $width, $height );
	} else if( $format === "xml" ) {
		echo_xml_format( $htmltext, $width, $height );
	}
}
function get_data($url, $post_data = '') {
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	if ( !empty($post_data) ) {
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	}
	$data = curl_exec($ch);
	
	if ( curl_errno($ch) || $data === false ) {
		echo 'curl error: ' . curl_error($ch);
		return false;
	}

	if (curl_getinfo($ch, CURLINFO_HTTP_CODE) >= 400) {
		return false;
	}

	curl_close($ch);
	return $data;
}

function get_embed_url( $domain, $url ) {
	$embed_script_url = 'https://'.$domain.'/e/embed.js?url='.$url;
	$embed_url_response = get_data($embed_script_url);
	if ( !empty( $embed_url_response ) && !strpos($embed_url_response, 'CLDEmbed') ) {
		return $embed_script_url;
	} else {
		return false;
	}
}

function get_embed_url_from_api( $domain, $url ) {
	$embed_url_api_response = get_data('https://'.$domain.'/EmbedScriptUrl.aspx?url='.$url);
	if ( !empty( $embed_url_api_response ) ) {
		$embed_script_url = filter_var( trim( $embed_url_api_response), FILTER_VALIDATE_URL );
		return $embed_script_url;
	} else {
		return false;
	}
}

function get_embed_template() {
	$request_data = json_encode(
		array(
			'Services' => array(
				array(
					'$type' => 'Mediaparts.Infrastructure.ServiceRegistry.ExactServiceRequest',
					'Subsystem' => 'Publisher2',
					'Service' => 'embed-code-template'
				)
			)
		)
	);

	$embed_template_api_response = get_data('https://registry.flippingbook.com/RegistryThin.svc/json/GetServices', $request_data);

	if (!empty( $embed_template_api_response )) {
		$embed_template_api_response = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $embed_template_api_response);
		$response_array = json_decode($embed_template_api_response, true);

		if (json_last_error() !== 0) {
			return false;
		}

		if ($response_array["Responses"][0]["Success"] !== true || $response_array["Responses"][0]["Error"] !== null) {
			return false;
		}

		$endpoint = $response_array["Responses"][0]["Services"][0]["Endpoints"][0];
		$template_url = "https://" . $endpoint["Host"] . "/" . $endpoint["Path"];
		$template_text_response = get_data($template_url);

		if (!empty($template_text_response)) {
			$template_text = trim($template_text_response);
			return $template_text;
		} else {
			return false;
		}

	} else {
		return false;
	}
}

function get_embed_code( $url, $width, $height, $embed_script_url ) {
	$m = new Mustache;
	$template = get_embed_template();

	$data = array (
		'url' 		=> $url,
		'width' 	=> $width,
		'height' 	=> $height,
		'script' 	=> $embed_script_url,
		'prefix' 	=> 'fbc',
		'lightbox' 	=> true,
		'version' 	=> 'WP-1.3.0-oEmbed',
		'method' 	=> 'wp',
		'title' 	=> 'Generated by FlippingBook Publisher'
	);

	if ( strpos($data['width'], 'px') === false && strpos($data['width'], '%') === false ) {
		$data['width'] = $data['width']."px";
	}
	if ( strpos($data['height'], 'px') === false && strpos($data['height'], '%') === false ) {
		$data['height'] = $data['height']."px";
	}

	$embed_code = $m->render($template, $data);
	return $embed_code;
}

function echo_xml_format( $htmltext, $width_value, $height_value ) {
	if ( function_exists( 'simplexml_import_dom' ) && class_exists( 'DOMDocument', false ) ) {
		header( 'Content-Type: text/xml' );
		$dom = new DomDocument( '1.0' );
		$oembed = $dom->appendChild( $dom->createElement( 'oembed' ) );
		$type = $oembed->appendChild( $dom->createElement( 'type' ) );
		$type->appendChild( $dom->createTextNode( 'video' ) );
		$width = $oembed->appendChild( $dom->createElement( 'width' ) );
		$width->appendChild( $dom->createTextNode( "$width_value" ) );
		$height = $oembed->appendChild( $dom->createElement( 'height' ) );
		$height->appendChild( $dom->createTextNode( "$height_value" ) );
		$version = $oembed->appendChild( $dom->createElement( 'version' ) );
		$version->appendChild( $dom->createTextNode( '1.0' ) );
		$html = $oembed->appendChild( $dom->createElement( 'html' ) );
		$html->appendChild( $dom->createTextNode( $htmltext ) );
		echo simplexml_import_dom( $dom )->asXML();
	} else {
		header('Content-Type: text/plain');
		if( ! function_exists( 'simplexml_import_dom' ) ) {
			echo "function 'simplexml_import_dom' does not exist\r\n";
		}
		if( ! class_exists( 'DOMDocument', false ) ) {
			echo "class 'DOMDocument' does not exist\r\n";
		}
	}
}
function echo_json_format( $htmltext, $width, $height ){
	$data = array(
		'type' => 'video',
		'version' => "1.0",
		'width' => "$width",
		'height' => "$height",
		'html' => $htmltext
	);
	header( 'Content-Type: application/json' );
	echo json_encode( $data );
}
?>