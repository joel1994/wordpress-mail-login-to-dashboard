<?php
function mail_login_access_check($hash){
	$email = generrate_access_token($hash,$operation='DECODE');
	if ($email != '') login_required($email);
	wp_die('认证失败！', 'Authorization Not Allowed | '.get_option('blogname'), array('response' => '403'));
}

function login_required($user_email){
	if (is_user_logged_in()) return;
	if ($user = get_user_by('email',$user_email)) {
		wp_set_current_user($user->ID);
		wp_set_auth_cookie($user->ID);
		do_action('wp_login', $user->user_login);
		$redirect_to=home_url();
		wp_safe_redirect($redirect_to);
		exit();
	}
}

function send_mail_login_token($email){
	if (get_user_by('email',$email)) {
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		$wp_email = 'blog@' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME']));
		$subject = '[' . $blogname . '] 后台登录授权申请';
		$message = '如果您确定该申请，请点击链接授权：';
		$message .= mail_login_access_link($email);
		$headers[] = 'From: "'.$blogname.'" <'.$wp_email.">";
		$headers[] = 'Content-Type: text/plain; charset="UTF-8"';
		wp_mail( $email, $subject, $message, $headers );
		wp_die('邮件已发送！', '后台登录授权申请 | '.get_option('blogname'), array('response' => '200'));
	}
	wp_die('拒绝访问！', '后台登录授权申请 | '.get_option('blogname'), array('response' => '403'));
}

function mail_login_access_link($email){
	$authkey=generrate_access_token($email,$operation='ENCODE');
	return get_template_directory_uri().'/mail-login.php?hash='.$authkey;
}

function generrate_access_token($string, $operation = 'ENCODE', $key = 'Mail-Login-Key', $expiry = 600) {
	$hash = substr(md5(time().$string.rand()),8,16);
	if($operation == 'DECODE') { 
		if($result = get_transient($key.'_'.$string)){
			delete_transient($key.'_'.$string);
			return $result;
		}else{
			return '';
		}
	} else { 
		  set_transient($key.'_'.$hash, $string, $expiry);
		  return $hash;
	}
}
?>
