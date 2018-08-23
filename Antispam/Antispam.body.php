<?php

class CTBody {
    /**
     * Builds MD5 secret hash for JavaScript test (self::JSTest) 
     * @return string 
     */
    public static function getJSChallenge() {
        global $wgCTAccessKey, $wgEmergencyContact;

        return md5( $wgCTAccessKey . '+' . $wgEmergencyContact ); 
    } 
    
    /**
     * Tests hidden field falue for secret hash 
     * @return 0|1|null 
     */
    public static function JSTest() {
        global $wgRequest, $wgCTHiddenFieldName;
        
        $result = null;
         
        $jsPostValue = $wgRequest->getVal( $wgCTHiddenFieldName );
        if ( $jsPostValue ) {
            $challenge = self::getJSChallenge();

            if ( preg_match( "/$/", $jsPostValue ) ) {
                $result = 1;
            } else {
                $result = 0;
            } 
        }
            
        return $result; 
    } 
    /**
     * Cookie test 
     * @return 
     */
    public static function ctSetCookie() {
        global $wgCTAccessKey;
        
        
        
        // Cookie names to validate
        $cookie_test_value = array(
            'cookies_names' => array(),
            'check_value' => $wgCTAccessKey,
        );
            
        // Submit time
        $apbct_timestamp = time();
        setcookie('apbct_timestamp', $apbct_timestamp, 0, '/');
        $cookie_test_value['cookies_names'][] = 'apbct_timestamp';
        $cookie_test_value['check_value'] .= $apbct_timestamp;

        // Pervious referer
        if(!empty($_SERVER['HTTP_REFERER'])){
            setcookie('apbct_prev_referer', $_SERVER['HTTP_REFERER'], 0, '/');
            $cookie_test_value['cookies_names'][] = 'apbct_prev_referer';
            $cookie_test_value['check_value'] .= $_SERVER['HTTP_REFERER'];
        }
        
        // Cookies test
        $cookie_test_value['check_value'] = md5($cookie_test_value['check_value']);
        setcookie('apbct_cookies_test', json_encode($cookie_test_value), 0, '/');
    }
    public static function ctTestCookie()
    {
        global $wgCTAccessKey;
        if(isset($_COOKIE['apbct_cookies_test'])){
            
            $cookie_test = json_decode(stripslashes($_COOKIE['apbct_cookies_test']), true);
            
            $check_srting = $wgCTAccessKey;
            foreach($cookie_test['cookies_names'] as $cookie_name){
                $check_srting .= isset($_COOKIE[$cookie_name]) ? $_COOKIE[$cookie_name] : '';
            } unset($cokie_name);
            
            if($cookie_test['check_value'] == md5($check_srting)){
                return 1;
            }else{
                return 0;
            }
        }else{
            return null;
        }        
    } 
    public static function createSFWTables()
    {
        $dbr = wfGetDB(DB_MASTER);

        $dbr->query("CREATE TABLE IF NOT EXISTS `cleantalk_sfw` (
            `network` int(11) unsigned NOT NULL,
            `mask` int(11) unsigned NOT NULL,
            INDEX (  `network` ,  `mask` )
            ) ENGINE = MYISAM ;");  

        $dbr->query("CREATE TABLE IF NOT EXISTS `cleantalk_sfw_logs` (
            `ip` varchar(15) NOT NULL,
            `all_entries` int(11) NOT NULL,
            `blocked_entries` int(11) NOT NULL,  
            `entries_timestamp` int(11) NOT NULL,   
            PRIMARY KEY `ip` (`ip`)
            ) ENGINE=MyISAM;");        
     
    } 
    public static function onSpamCheck($method, $params)
    {
        global $wgCTAccessKey, $wgCTServerURL, $wgCTAgent;

        $result = null;

        $ct = new Cleantalk();
        $ct->server_url = $wgCTServerURL;
        
        $ct_request = new CleantalkRequest;

        foreach ($params as $k => $v) {
            $ct_request->$k = $v;
        }
        $ct_request->auth_key = $wgCTAccessKey;
        $ct_request->agent = $wgCTAgent; 
        $ct_request->submit_time = isset($_COOKIE['apbct_timestamp']) ? time() - intval($_COOKIE['apbct_timestamp']) : 0; 
        $ct_request->sender_ip = CleantalkHelper::ip_get(array('real'), false);
        $ct_request->x_forwarded_for = CleantalkHelper::ip_get(array('x_forwarded_for'), false);
        $ct_request->x_real_ip       = CleantalkHelper::ip_get(array('x_real_ip'), false);
        $ct_request->js_on = CTBody::JSTest();         
        $ct_request->sender_info=json_encode(
        Array(
            'page_url' => htmlspecialchars(@$_SERVER['SERVER_NAME'].@$_SERVER['REQUEST_URI']),
            'REFFERRER' => $_SERVER['HTTP_REFERER'],
            'USER_AGENT' => $_SERVER['HTTP_USER_AGENT'],
            'cookies_enabled' => CTBody::ctTestCookie(),            
            'REFFERRER_PREVIOUS' => isset($_COOKIE['apbct_prev_referer'])?$_COOKIE['apbct_prev_referer']:0,
        ));  
        switch ($method) {
            case 'check_message':
                $result = $ct->isAllowMessage($ct_request);
                break;
            case 'send_feedback':
                $result = $ct->sendFeedback($ct_request);
                break;
            case 'check_newuser':
                $result = $ct->isAllowUser($ct_request);
                break;
            default:
                return NULL;
        } 
        return $result;                              
    }          
    /**
     * Adds hidden field to form for JavaScript test 
     * @return string 
     */
    public static function AddJSCode() {
        global $wgCTHiddenFieldName, $wgCTHiddenFieldDefault, $wgCTExtName;
        
        $ct_checkjs_key = CTBody::getJSChallenge(); 
        
        $field_id = $wgCTHiddenFieldName . '_' . md5( rand( 0, 1000 ) );
        $html = '
<input type="hidden" id="%s" name="%s" value="%s" />
<script type="text/javascript">
// <![CDATA[
var ct_input_name = \'%s\';
var ct_input_value = document.getElementById(ct_input_name).value;
var ct_input_challenge = \'%s\'; 

document.getElementById(ct_input_name).value = document.getElementById(ct_input_name).value.replace(ct_input_value, ct_input_challenge);

if (document.getElementById(ct_input_name).value == ct_input_value) {
    document.getElementById(ct_input_name).value = ct_set_challenge(ct_input_challenge); 
}

function ct_set_challenge(val) {
    return val; 
}; 

// ]]>
</script>
';
        $html = sprintf( $html, $field_id, $wgCTHiddenFieldName, $wgCTHiddenFieldDefault, $field_id, $ct_checkjs_key );
        
        $html .= '<noscript><p><b>Please enable JavaScript to pass antispam protection!</b><br />Here are the instructions how to enable JavaScript in your web browser <a href="http://www.enable-javascript.com" rel="nofollow" target="_blank">http://www.enable-javascript.com</a>.<br />' . $wgCTExtName . '.</p></noscript>';

        return $html;
    }

    /**
     * Sends email notificatioins to admins 
     * @return bool 
     */
    public static function SendAdminEmail( $title, $body ) {
        global $wgCTExtName, $wgCTAdminAccountId, $wgCTDataStoreFile, $wgCTAdminNotificaionInteval;
        
        if ( file_exists($wgCTDataStoreFile) ) {
            $settings = file_get_contents ( $wgCTDataStoreFile );
            if ( $settings ) {
                $settings = json_decode($settings, true);
            }
        }
        if (!isset($settings['lastAdminNotificaionSent']))
        {
            $settings['lastAdminNotificaionSent'] = time();
            fwrite( $fp, json_encode($settings) );
            fclose( $fp );            
        }
        // Skip notification if permitted interval doesn't exhaust
        if ( isset( $settings['lastAdminNotificaionSent'] ) && time() - $settings['lastAdminNotificaionSent'] < $wgCTAdminNotificaionInteval ) {
            return false; 
        }
        
        $u = User::newFromId( $wgCTAdminAccountId );
         
        $status = $u->sendMail( $title , $body );

        if ( $status->ok ) {
            $fp = fopen( $wgCTDataStoreFile, 'w' ) or error_log( 'Could not open file:' . $wgCTDataStoreFile );
            $settings['lastAdminNotificaionSent'] = time();
            fwrite( $fp, json_encode($settings) );
            fclose( $fp );   
        }

        return $status->ok;
    }
}

?>