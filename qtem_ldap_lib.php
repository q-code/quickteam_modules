<?php

// Returns true/false, in case of troubles $error will include the ldap error message

function qtem_ldap_bind($username,$password)
{
  $b = false;
  global $error;
  if ( !function_exists('ldap_connect') ) { $error='Ldap functions not available from the current webserver configuration.'; return false; }
  $c = @ldap_connect($_SESSION[QT]['ldap_host']) or $error=ldap_err2str(ldap_errno($c));
  // bind
  if ( empty($error) )
  {
    ldap_set_option($c, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($c, LDAP_OPT_REFERRALS, 0);
    $login_dn = str_replace('$username',$username,$_SESSION[QT]['ldap_login_dn']);
    $b = @ldap_bind($c,$login_dn,$password);
    if ( !$b ) $error=ldap_err2str(ldap_errno($c));
  }
  @ldap_close($c);
  return $b;
}

// Return the mail
// $email may be empty in case of wrong ldap search settings

function qtem_ldap_search($username)
{
  global $error;
  $error = '';
  $mail = '';
  $c = @ldap_connect($_SESSION[QT]['ldap_host']) or $error=ldap_err2str(ldap_errno($c));
  // admin or anonymous bind
  if ( empty($error) )
  {
    ldap_set_option($c, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($c, LDAP_OPT_REFERRALS, 0);
    if ( $_SESSION[QT]['ldap_bind']==='n' )
    {
      @ldap_bind($c,$_SESSION[QT]['ldap_bind_rdn'],$_SESSION[QT]['ldap_bind_pwd']) or $error='Connection result: '.ldap_err2str(ldap_errno($c)); // bind (anonymous by default)
    }
    else 
    {
      @ldap_bind($c) or $error='Connection result: '.ldap_err2str(ldap_errno($c)); // bind (anonymous by default)
    }
  }
  // search username
  if ( empty($error) )
  {
    $filter = str_replace('$username',$username,$_SESSION[QT]['ldap_s_filter']);
    $s = @ldap_search($c,$_SESSION[QT]['ldap_s_rdn'],$filter,explode(',',$_SESSION[QT]['ldap_s_info'])) or $error='Search result: '.ldap_err2str(ldap_errno($c));
  }
  // analyse search results
  if ( empty($error) )
  {
    $users = ldap_get_entries($c, $s);
    $intEntries = ldap_count_entries($c,$s);
    for($i=0;$i<$intEntries;$i++)
    {
      if ( isset($users[$i]['mail'][0]) ) $mail = $users[$i]['mail'][0];
      if ( empty($mail) && isset($users[$i]['email'][0]) ) $mail = $users[$i]['email'][0];
      if ( empty($mail) )
      {
        foreach($infos as $info)
        {
          if ( isset($users[$i][$info][0]) && QTismail($users[$i][$info][0]) ) { $mail = $users[$i][$info][0]; break; }
        }        
      }
      if ( !empty($mail) ) break;
    }
  }
  return $mail; 
}

// --------

function qtem_ldap_profile($username,$password,$mail='')
{
  if ( empty($mail) ) $mail = qtem_ldap_search($username); // seach the user's mail from ldap (may be empty)
  return sUser::AddUser($username,$password,$mail);
}