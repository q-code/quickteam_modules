<?php

/**
* PHP versions 5
*
* LICENSE: This source file is subject to version 3.0 of the PHP license
* that is available through the world-wide-web at the following URI:
* http://www.php.net/license. If you did not receive a copy of
* the PHP License and are unable to obtain it through the web, please
* send a note to license@php.net so we can mail you a copy immediately.
*
* @package    LDAP
* @author     Philippe Vandenberghe <info@qt-cute.org>
* @copyright  2014 The PHP Group
* @version    3.0 build:20140608
*/

session_start();
require_once 'bin/qte_init.php';
if ( sUser::Role()!='A' ) die('Access is restricted to administrators only');
include Translate('qte_adm.php');
include 'qtem_ldap_lib.php';

// INITIALISE

$tt=0;
$oVIP->selfurl = 'qtem_ldap_adm.php';
$oVIP->selfname = 'LDAP/AD stettings';
$oVIP->exiturl = 'qte_adm_secu.php';

QThttpvar('tt','int');
if ( $tt<0 || $tt>2) $tt=0;

if ( !isset($_SESSION[QT]['m_ldap']) ) $_SESSION[QT]['m_ldap']='0';

// --------
// SUBMITTED
// --------

if ( isset($_POST['ok']) && $tt==0 )
{
  $oDB->Query('DELETE FROM '.TABSETTING.' WHERE param="m_ldap" OR param="m_ldap_users"');

  $_SESSION[QT]['m_ldap_users'] = $_POST['m_ldap_users']; if ( $_SESSION[QT]['m_ldap_users']!=='ldap' ) $_SESSION[QT]['m_ldap_users']='all';
  $oDB->Query('INSERT INTO '.TABSETTING.' VALUES ("m_ldap_users","'.$_SESSION[QT]['m_ldap_users'].'")');

  // Enable module (m_ldap=0 means disable, m_ldap=qtem_ldap_login.php means enabled
  $_SESSION[QT]['m_ldap']='0';
  if ( $_POST['m_ldap']=='1' )
  {
    if ( empty($_SESSION[QT]['m_ldap_host']) ) $error = 'First define Ldap settings...';
    if ( !function_exists('ldap_connect') ) $error = 'Ldap function not found, unable to start the module.';
    if ( empty($error) ) $_SESSION[QT]['m_ldap']='1';

  }
  $oDB->Query('INSERT INTO '.TABSETTING.' VALUES ("m_ldap","'.$_SESSION[QT]['m_ldap'].'")');

  // exit
  $_SESSION['pagedialog'] = (empty($error) ? 'O|'.$L['S_save'] : 'E|'.$error);
}

if ( isset($_POST['ok']) && $tt>0 )
{
  // register value used
  $_SESSION[QT]['m_ldap_host'] = $_POST['m_ldap_host'];
  $_SESSION[QT]['m_ldap_login_dn'] = $_POST['m_ldap_login_dn'];
  $_SESSION[QT]['m_ldap_bind'] = (isset($_POST['m_ldap_bind']) ? 'a' : 'n');
  $_SESSION[QT]['m_ldap_bind_rdn'] = '';
  $_SESSION[QT]['m_ldap_bind_pwd'] = '';
  if ( $_SESSION[QT]['m_ldap_bind']==='n' && !empty($_POST['m_ldap_bind_rdn']) ) $_SESSION[QT]['m_ldap_bind_rdn'] = $_POST['m_ldap_bind_rdn'];
  if ( $_SESSION[QT]['m_ldap_bind']==='n' && !empty($_POST['m_ldap_bind_pwd']) ) $_SESSION[QT]['m_ldap_bind_pwd'] = $_POST['m_ldap_bind_pwd'];
  $_SESSION[QT]['m_ldap_s_rdn'] = (empty($_POST['m_ldap_s_rdn']) ? '' : $_POST['m_ldap_s_rdn']);
  $_SESSION[QT]['m_ldap_s_filter'] = (empty($_POST['m_ldap_s_filter']) ? '' : $_POST['m_ldap_s_filter']);
  $_SESSION[QT]['m_ldap_s_info'] = (empty($_POST['m_ldap_s_info']) ? '' : $_POST['m_ldap_s_info']);

  // Save
  if ( $tt>0 )
  {
    $oDB->Query('DELETE FROM '.TABSETTING.' WHERE param="m_ldap_host" OR param="m_ldap_login_dn" OR param="m_ldap_bind" OR param="m_ldap_bind_rdn" OR param="m_ldap_bind_pwd" OR param="m_ldap_s_rdn" OR param="m_ldap_s_filter" OR param="m_ldap_s_info"');
    $oDB->Query('INSERT INTO '.TABSETTING.' VALUES ("m_ldap_host","'.$_SESSION[QT]['m_ldap_host'].'")');
    $oDB->Query('INSERT INTO '.TABSETTING.' VALUES ("m_ldap_login_dn","'.$_SESSION[QT]['m_ldap_login_dn'].'")');
    $oDB->Query('INSERT INTO '.TABSETTING.' VALUES ("m_ldap_bind","'.$_SESSION[QT]['m_ldap_bind'].'")');
    $oDB->Query('INSERT INTO '.TABSETTING.' VALUES ("m_ldap_bind_rdn","'.$_SESSION[QT]['m_ldap_bind_rdn'].'")');
    $oDB->Query('INSERT INTO '.TABSETTING.' VALUES ("m_ldap_bind_pwd","'.$_SESSION[QT]['m_ldap_bind_pwd'].'")');
    $oDB->Query('INSERT INTO '.TABSETTING.' VALUES ("m_ldap_s_rdn","'.$_SESSION[QT]['m_ldap_s_rdn'].'")');
    $oDB->Query('INSERT INTO '.TABSETTING.' VALUES ("m_ldap_s_filter","'.$_SESSION[QT]['m_ldap_s_filter'].'")');
    $oDB->Query('INSERT INTO '.TABSETTING.' VALUES ("m_ldap_s_info","'.$_SESSION[QT]['m_ldap_s_info'].'")');
    // exit
    $_SESSION['pagedialog'] = (empty($error) ? 'O|'.$L['S_save'] : 'E|'.$error);
  }

  // Test
  if ( $tt==2 )
  {
    $test_conn='<span style="color:red">pending</span>';
    $test_find='<span style="color:red">pending</span>';
    $test_login='<span style="color:red">pending</span>';

    if ( empty($_SESSION[QT]['m_ldap_host']) ) $error='Missing host';
    if ( empty($_SESSION[QT]['m_ldap_login_dn']) ) $error='Missing login dn';

    $username = $_POST['username']; if ( get_magic_quotes_gpc() ) $username = stripslashes($username);
    $password = $_POST['password']; if ( get_magic_quotes_gpc() ) $password = stripslashes($username);
    if ( empty($username) ) $error='Missing username';

    $step=0; // steps of the test
    $intEntries=0;

    // open connection
    if ( empty($error) )
    {
      $step++;
      $c = @ldap_connect($_SESSION[QT]['m_ldap_host']) or $error=ldap_err2str(ldap_errno($c));
    }
    // admin(anonymous) bind
    if ( empty($error) )
    {
      $step++;
      $test_conn='<span style="color:green">started</span>';
      $test_find='<span style="color:red">failed</span>';
      $test_login='<span style="color:red">access denied</span>';
      ldap_set_option($c, LDAP_OPT_PROTOCOL_VERSION, 3);
      ldap_set_option($c, LDAP_OPT_REFERRALS, 0);
      if ( $_SESSION[QT]['m_ldap_bind']==='n' )
      {
        @ldap_bind($c,$_SESSION[QT]['m_ldap_bind_rdn'],$_SESSION[QT]['m_ldap_bind_pwd']) or $error='Connection result: '.ldap_err2str(ldap_errno($c)); // bind (anonymous by default)
      }
      else
      {
        @ldap_bind($c) or $error='Connection result: '.ldap_err2str(ldap_errno($c)); // bind (anonymous by default)
      }
    }
    // search test user
    if ( empty($error) )
    {
      $step++;
      $filter = str_replace('$username',$username,$_SESSION[QT]['m_ldap_s_filter']);
      $s = @ldap_search($c,$_SESSION[QT]['m_ldap_s_rdn'],$filter,explode(',',$_SESSION[QT]['m_ldap_s_info'])) or $error='Search result: '.ldap_err2str(ldap_errno($c));
    }
    // analyse search results
    if ( empty($error) )
    {
      $step++;
      $intEntries = ldap_count_entries($c,$s);
      $test_find='<span style="color:green">'.$intEntries.' matching entries </span>';
      $users = ldap_get_entries($c, $s);
      $infos = explode(',',$_SESSION[QT]['m_ldap_s_info']);
      $results = array();
      for($i=0;$i<$intEntries;$i++)
      {
        $results[$i] = '';
        foreach($infos as $info)
        {
          if ( isset($users[$i][$info]) ) { $results[$i] .= $info.'='.$users[$i][$info][0].','; } else { $results[$i] .= '(missing '.$info.'),'; }
        }
        if ( $i>=1 ) { $results[]='...'; break; }
      }
      if ( $intEntries>0 ) $test_find .= '<br/><span class="small">&gt; '.implode('<br/>&gt; ',$results).'</span>';
    }
    // bind test user
    if ( empty($error) )
    {
      $step++;
      $b = qtem_ldap_bind($username,$password);
      if ( $b )
      {
        $test_login='<span style="color:green">successfull</span>';
      }
      else
      {
        $test_login='<span style="color:red">denied</span>';
        $test_login.='<br/><span class="small" style="color:red">&gt; '.$error.'<br/>&gt; Possible cause: '.($intEntries==0 ? 'the username does not exists (or is not in the group specied by the login DN)' : 'username exists but the password is invalid') .'</span>';
      }
    }
    if ( $step>0 ) ldap_close($c);

    // exit
    $_SESSION['pagedialog'] = (empty($error) ? 'O|Test completed' : 'E|'.$error);
  }
}

// --------
// HTML START
// --------

if ( !isset($_SESSION[QT]['m_ldap_host']) ) $_SESSION[QT]['m_ldap_host']='';
if ( !isset($_SESSION[QT]['m_ldap_login_dn']) ) $_SESSION[QT]['m_ldap_login_dn']='';
if ( !isset($_SESSION[QT]['m_ldap_bind']) ) $_SESSION[QT]['m_ldap_bind']='a';
if ( !isset($_SESSION[QT]['m_ldap_bind_rdn']) ) $_SESSION[QT]['m_ldap_bind_rdn']='';
if ( !isset($_SESSION[QT]['m_ldap_bind_pwd']) ) $_SESSION[QT]['m_ldap_bind_pwd']='';
if ( !isset($_SESSION[QT]['m_ldap_s_rdn']) ) $_SESSION[QT]['m_ldap_s_rdn']='';
if ( !isset($_SESSION[QT]['m_ldap_s_filter']) ) $_SESSION[QT]['m_ldap_s_filter']='';
if ( !isset($_SESSION[QT]['m_ldap_s_info']) ) $_SESSION[QT]['m_ldap_s_info']='mail';
if ( !isset($_SESSION[QT]['m_ldap_users']) ) $_SESSION[QT]['m_ldap_users']='all';

$oHtml->scripts[] = '<script type="text/javascript">
function ToggleAnonymous(checked)
{
  var doc = document.getElementById("bind_input");
  if ( doc ) doc.style.display=(checked ? "none" : "block");
}
</script>
';

include 'qte_adm_p_header.php';

// DISPLAY TABS

$arrTabs = array('Authority','Settings','Test');
echo HtmlTabs($arrTabs, $oVIP->selfurl.'?', $tt, 6, $L['E_editing']);

// DISPLAY TAB PANEL

echo '<div class="pan">
<div class="pan-top">',$arrTabs[$tt],'</div>
';

if ( !function_exists('ldap_connect') ) echo '<p class="error">LDAP function not found. It seems that module LDAP is not activated on your webserver.</p>';

echo '<form method="post" action="',Href(),'">
';

if ( $tt==0 )
{
echo '<table class="t-data">
<tr><td class="headgroup" colspan="3">Module status</td></tr>
<tr>
<td class="headfirst"><label for="newuser">Status</label></td>
';

if ($_SESSION[QT]['m_ldap']=='1')
{
  echo '<td style="background-color:#AAFFAA;width:120px;text-align:center"><b>',L('On_line'),'</b></td>';
}
else
{
  echo '<td style="background-color:#FFAAAA;width:120px;text-align:center"><b>',L('Off_line'),'</b></td>';
}
echo '<td style="text-align:right">',$L['Change'],'&nbsp;
<select id="m_ldap" name="m_ldap">
<option value="1"',($_SESSION[QT]['m_ldap']=='1' ? QSEL : ''),'>',$L['On_line'],'</option>
<option value="0"',($_SESSION[QT]['m_ldap']=='0' ? QSEL : ''),'>',$L['Off_line'],'</option>
</select>
</td>
</tr>
<tr><td class="headgroup" colspan="3">User authentication</td></tr>
<tr>
<td class="headfirst"><label for="newuser">Login users</label></td>
<td colspan="2">
<input type="radio" name="m_ldap_users" value="all"',($_SESSION[QT]['m_ldap_users']=='all' ? QCHE : ''),'/>Accept locally registered users AND ldap users<br/>
<input type="radio" name="m_ldap_users" value="ldap"',($_SESSION[QT]['m_ldap_users']=='ldap' ? QCHE : ''),'/>Accept ONLY valid ldap users
</td>
</tr>
';
echo '<tr>
<td class="headfirst"><label for="newuser">',L('Information'),'</label></td>
<td colspan="2">
<p class="small"><span class="bold italic">Accept locally registered users AND ldap users</span><br/>
With this option, users without ldap id must first register before using the application (users having a valid ldap id don\'t need to register).</p>
<p class="small"><span class="bold italic">Accept ONLY valid ldap users</span><br/>
With this option, register page, forgotten page, and secret question are disabled.<br/>
On first login, a local profile is created for the user having a valid ldap/ad entry.<br/>
On next login, access is granted by the ladap/ad register.</br/>
For users without ldap/ad id, the register page will show an invitation to contact the administrator in order to request the creation of a new ldap entry.</p>
</td>
</tr>
';
}

if ( $tt==1 || $tt==2 )
{
echo '<table class="t-data">
<tr class="t-data"><td class="headgroup" colspan="3">Connection and authentication</td></tr>
';
echo '<tr>
<td class="headfirst"><label for="m_ldap_host">Host</label></td>
<td><input type="text" id="m_ldap_host" name="m_ldap_host" size="32" maxlength="64" value="',$_SESSION[QT]['m_ldap_host'],'"/></td>
<td><span class="small">Host and port. Example </span><span class="small" style="color:#4444ff">ldap://localhost:10389</span></td>
</tr>
<tr>
<td class="headfirst"><label for="m_ldap_rdn">Login DN</label></td>
<td><input type="text" id="m_ldap_login_dn" name="m_ldap_login_dn" size="32" maxlength="64" value="',$_SESSION[QT]['m_ldap_login_dn'],'"/></td>
<td><span class="small">Use $username as placeholder. Example </span><span class="small" style="color:#4444ff">cn=$username,ou=users,o=mycompany</span></td>
</tr>
';
echo '<tr class="t-data"><td class="headgroup" colspan="3">Search configuration (to create new user)</td></tr>
<tr>
<td class="headfirst" style="vertical-align:top"><label for="m_ldap_bind">When searching</label></td>
<td style="vertical-align:top"><span class="small"><input type="checkbox" id="m_ldap_bind" name="m_ldap_bind" value="a"'.($_SESSION[QT]['m_ldap_bind']==='a' ? QCHE : '').' onclick="ToggleAnonymous(this.checked);"/><label for="m_ldap_bind">Server supports anonymous bind</label></span></td>
<td style="vertical-align:top">
<div id="bind_input" style="display:'.($_SESSION[QT]['m_ldap_bind']==='a' ? 'none' : 'block').'">
<span class="small"><input type="text" id="m_ldap_bind_rdn" name="m_ldap_bind_rdn" size="25" maxlength="34" value="',$_SESSION[QT]['m_ldap_bind_rdn'],'"/>&nbsp;System DN<br/>
<input type="text" id="m_ldap_bind_pwd" name="m_ldap_bind_pwd" size="25" maxlength="64" value="',$_SESSION[QT]['m_ldap_bind_pwd'],'"/>&nbsp;Password</span>
</div>
</td>
</tr>
';
echo '<tr>
<td class="headfirst"><label for="m_ldap_rdn">Search RDN</label></td>
<td><input type="text" id="m_ldap_s_rdn" name="m_ldap_s_rdn" size="32" maxlength="64" value="',$_SESSION[QT]['m_ldap_s_rdn'],'"/></td>
<td><span class="small">dn or rdn (search basis)</span></td>
</tr>
<tr>
<td class="headfirst"><label for="m_ldap_s_filter">Search filter</label></td>
<td><input type="text" id="m_ldap_s_filter" name="m_ldap_s_filter" size="32" maxlength="64" value="',$_SESSION[QT]['m_ldap_s_filter'],'"/></td>
<td><span class="small">Use $username as placeholder. Example </span><span class="small" style="color:#4444ff">(cn=$username)</span><span class="small"> allows searching the username specified in the login panel</span></td>
</tr>
<tr>
<td class="headfirst"><label for="m_ldap_s_info">Requested info</label></td>
<td><input type="text" id="m_ldap_s_info" name="m_ldap_s_info" size="32" maxlength="64" value="',$_SESSION[QT]['m_ldap_s_info'],'"/></td>
<td><span class="small">At least the mail is recommended. This is usefull when a user performs his very first login (the application will create a new profile with the same e-mail as in ldap).</span></td>
</tr>
';
}

if ( $tt==2  )
{

if ( !isset($test_conn) ) $test_conn='<span class="disabled">(none)</span>';
if ( !isset($test_find) ) $test_find='<span class="disabled">(none)</span>';
if ( !isset($test_login) ) $test_login='<span class="disabled">(none)</span>';

echo '<tr><td class="headgroup" colspan="3">Test</td></tr>
';

echo '<tr>
<td class="headfirst"><label for="username">Username</label></td>
<td colspan="2"><input type="text" id="username" name="username" size="32" maxlength="64" value=""/></td>
</tr>
<tr>
<td class="headfirst"><label for="password">Password</label></td>
<td colspan="2"><input type="text" id="password" name="password" size="32" maxlength="64" value=""/></td>
</tr>
<tr>
<td class="headfirst">Test result</label></td>
<td colspan="2">Connection: ',$test_conn,'<br/>Search user: ',$test_find,'<br/>Login: ',$test_login,'<br/></td>
</tr>
';
}

if ( $tt==2 )
{
  if ( function_exists('ldap_connect') )
  {
  echo '<tr>
  <td class="headgroup" colspan="3" style="padding:6px; text-align:center"><input type="hidden" name="tt" value="',$tt,'"/><input type="submit" name="ok" value="Test"/></td>
  </tr>
  ';
  }
  else
  {
  echo '<tr class="t-data"><td class="headgroup" colspan="3">Test</td></tr>
  <tr>
  <td class="headfirst">Warning</td>
  <td>LDAP function not found. It seems that module LDAP is not activated on your webserver.</td>
  </tr>
  ';
  }
}
else
{
echo '<tr>
<td colspan="3"><p style="margin:0 0 5px 0;text-align:center"><input type="hidden" name="tt" value="',$tt,'"/><input type="submit" name="ok" value="',$L['Save'],'"/></p></td>
</tr>
';
}

echo '</table>
</form>
';

// END TABS

echo '
</div>
';

echo '<p><a href="',$oVIP->exiturl,'" onclick="return qtEdited(bEdited,\''.$L['E_editing'].'\');">',$oVIP->exitname,'</a></p>';

// INFO
if ( $tt>0 )
{
echo '
<table class="hidden"><tr class="hidden">
<td class="hidden" style="width:210px">&nbsp;</td>
<td class="hidden">
<div class="scrollmessage">
<h2>Setting examples</h2>
<p>
Host <span style="color:#4444ff">ldap://localhost:10389</span><br/>
Login DN <span style="color:#4444ff">uid=$username,dc=example,dc=com</span><br/>
Search RDN <span style="color:#4444ff">dc=example,dc=com</span><br/>
Search filter <span style="color:#4444ff">(cn=$username)</span><br/>
Requested info <span style="color:#4444ff">cn,sn,mail,uid</span><br/>
</p>
';
echo '<p class="bold">Example with url and ssl</p>
<p class="small">If you are using OpenLDAP 2.x.x you can specify a URL instead of the hostname. To use LDAP with SSL, compile OpenLDAP 2.x.x with SSL support, configure PHP with SSL, and set this parameter as ldaps://hostname/.</p>
<p class="small">
Host <span style="color:#4444ff">ldaps://ldap.example.com</span> (port not used when using URLs)<br/>
Login DN <span style="color:#4444ff">cn=$username,ou=users,o=mycompany</span><br/>
Search RDN <span style="color:#4444ff">ou=users,o=mycompany</span><br/>
Search filter <span style="color:#4444ff">(uid=$username)</span><br/>
Requested info <span style="color:#4444ff">cn,sn,mail,uid</span><br/>
</p>
</div>
</td>
</tr>
</table>
';
}

// HTML END

include 'qte_adm_p_footer.php';