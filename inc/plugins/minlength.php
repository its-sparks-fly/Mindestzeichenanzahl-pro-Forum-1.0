<?php
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("admin_formcontainer_output_row", "minlength_box");
$plugins->add_hook("admin_forum_management_edit_commit", "minlength_commit");
$plugins->add_hook("newthread_do_newthread_start", "minlength_newthread");
$plugins->add_hook("newreply_do_newreply_start", "minlength_newthread");

function minlength_info()
{
	return array(
		"name"		=> "Mindestzeichenanzahl pro Forum",
		"description"	=> "Erlaubt es Administratoren, im Admin CP für jedes Forum einzeln eine Mindestzeichenanzahl für Beiträge einzugeben.",
		"website"	=> "https://github.com/its-sparks-fly",
		"author"	=> "sparks fly",
		"authorsite"	=> "https://github.com/its-sparks-fly",
		"version"	=> "1.0",
		"compatibility" => "18*"
	);
}

function minlength_install()
{
	global $db, $cache, $mybb;

	$db->query("ALTER TABLE `".TABLE_PREFIX."forums` ADD `minlength` VARCHAR(2500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `defaultsortorder`;");
	rebuild_settings();
}

function minlength_is_installed()
{
	global $db;
	if($db->field_exists("minlength", "forums"))
	{
		return true;
	}
	return false;
}

function minlength_uninstall()
{
	global $db, $cache;
	if($db->field_exists("minlength", "forums"))
  {
    $db->drop_column("forums", "minlength");
  }

	rebuild_settings();
}

function minlength_box($above)
{
	global $mybb, $lang, $form_container, $forum_data, $form;
	if($above['title'] == $lang->misc_options && $lang->misc_options)
	{
		$above['content'] .= $form_container->output_row("Mindestzeichenanzahl", "", $form->generate_text_box('minlength', $forum_data['minlength'], array('id' => 'minlength')), 'minlength');
	}
	return $above;
}

function minlength_commit()
{
	global $mybb, $cache, $db, $fid;
	$update_array = array(
		"minlength" => $db->escape_string($mybb->get_input('minlength'))
	);

	$db->update_query("forums", $update_array, "fid='{$fid}'");

	$cache->update_forums();
}

function minlength_newthread(&$forum)
{
	global $mybb, $db, $forum, $fid;

	// check character minlength of forum
	$minlength = $db->fetch_field($db->query("SELECT minlength FROM ".TABLE_PREFIX."forums
	WHERE fid = '$fid'"), "minlength");

	// if character minlength of forum is empty, check if parent has character minlength
	if(empty($minlength)) {
		$parentlist = explode(",", $forum['parentlist']);
		foreach($parentlist as $parent) {
			$minlength = $db->fetch_field($db->query("SELECT minlength FROM ".TABLE_PREFIX."forums
			WHERE fid = '$parent'"), "minlength");
			if(!empty($minlength)) {
				break;
			}
		}
	}

	// check if message is shorter than minlengthd
	if(!empty($minlength)) {
		$long_message = $mybb->get_input('message');
		$long_message = strip_tags($long_message);
		$long_message = trim($long_message);
		if(strlen($long_message) < $minlength) {
			error('Dein Beitrag ist kürzer als die für dieses Forum vorgegebene Beitragslänge von <b>'.$minlength.'</b> Zeichen! <br /><a href="javascript:history.back()">Zur&uuml;ck zur Beitragserstellung</a>');
		}
	}
}

?>
