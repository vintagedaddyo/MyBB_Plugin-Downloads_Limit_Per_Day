<?php
/** Attachment Download Limit
 * Author: Mohammad Zangeneh @ MyBBIran.com @ Iran, updated by vintagedaddyo
**/

//I've used my new pattern!:)!

// Direct Access Disallow
if(!defined("IN_MYBB"))
die("Direct access to this file is not allowed!");
//Hooks
$plugins->add_hook("attachment_start", "at_limit_on");
$plugins->add_hook("attachment_end", "at_limit_on");
$plugins->add_hook("attachment_end", "at_limit_tb");
$plugins->add_hook("admin_formcontainer_output_row", "at_limit_acp_ug");
$plugins->add_hook("admin_user_groups_edit_commit", "at_limit_acp_ug_commit");
// Plugin Info

function at_limit_info()
{
    global $lang;

    $lang->load("at_limit");
    
    $lang->acp_atdl_Desc = '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="float:right;">' .
        '<input type="hidden" name="cmd" value="_s-xclick">' . 
        '<input type="hidden" name="hosted_button_id" value="AZE6ZNZPBPVUL">' .
        '<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">' .
        '<img alt="" border="0" src="https://www.paypalobjects.com/pl_PL/i/scr/pixel.gif" width="1" height="1">' .
        '</form>' . $lang->acp_atdl_Desc;

    return Array(
        'name' => $lang->acp_atdl_Name,
        'description' => $lang->acp_atdl_Desc,
        'website' => $lang->acp_atdl_Web,
        'author' => $lang->acp_atdl_Auth,
        'authorsite' => $lang->acp_atdl_AuthSite,
        'version' => $lang->acp_atdl_Ver,
        'compatibility' => $lang->acp_atdl_Compat
    );
}

function at_limit_activate()
{
global $db, $cache;
	
// Add Table
$db->write_query ("CREATE TABLE `".TABLE_PREFIX."atdl` (
	  `did` smallint(8) UNSIGNED NOT NULL auto_increment,
	  `uid` bigint(30) UNSIGNED NOT NULL default '0',
	  `dateline` bigint(30) UNSIGNED NOT NULL default '0',
	  PRIMARY KEY  (`did`)
		) ENGINE=MyISAM");
		
// Add Field

	$db->query("ALTER TABLE ".TABLE_PREFIX."usergroups ADD attachdllimit INT(5) NOT NULL DEFAULT '5'");
	$cache->update_usergroups();

}

function at_limit_deactivate()
{
// Delete Field

global $db, $cache;
$db->query("ALTER TABLE ".TABLE_PREFIX."usergroups Drop attachdllimit");
	$cache->update_usergroups();
	
// Delete Table

if ($db->table_exists("atdl"))
$db->drop_table("atdl");
}

function at_limit_tb()
{
global $db, $attachment, $mybb;

if($attachment['thumbnail'])
{
return;
}
if(!$attachment['thumbnail'])
{
$activate = array(
'uid' => intval($mybb->user['uid']),
'dateline' => TIME_NOW,
);

$db->insert_query('atdl', $activate);
}
}


function at_limit_uid($username)
{
global $db;

$q = $db->simple_select('users', 'uid', 'username=\''.$db->escape_string($username).'\'', 1);
return $db->fetch_field ($q, 'uid');
}

function at_limit_on()
{
global $db, $mybb, $lang, $attachment;

$lang->load('at_limit');

  if ($mybb->usergroup['attachdllimit'] > 0)
  {
	$q = $db->simple_select("atdl", "COUNT(*) AS dl_num", "uid='{$mybb->user['uid']}' AND dateline >='".(TIME_NOW - (60*60*24))."'");
	$atdls = $db->fetch_field($q, 'dl_num');
	if($mybb->input['thumbnail'])
	{
	return;
	}

	if ($atdls >= $mybb->usergroup['attachdllimit'])
	{

$lang->atdl_error = $lang->sprintf($lang->atdl_error, $mybb->usergroup['attachdllimit']);
	error($lang->atdl_error);

	
	}
	
}
}

function at_limit_acp_ug($pluginargs)
{
global $db, $mybb, $form, $lang;

$lang->load('at_limit');

if($pluginargs['title'] == $lang->misc && $lang->misc)
{
	// add . before = for fixing the fact that it dropped all other content in misc before 
			$pluginargs['content'].= "{$lang->acp_atdl_title}<br /><small class=\"input\">{$lang->acp_atdl_desc}</small><br />".$form->generate_text_box('attachdllimit', $mybb->input['attachdllimit'], array('id' => 'attachdllimit', 'class' => 'field50'));

}
}

function at_limit_acp_ug_commit()
{
	global $mybb, $updated_group;
	$updated_group['attachdllimit'] = intval($mybb->input['attachdllimit']);
}


?>
