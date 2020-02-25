<?php
/**
 * Highscores
 *
 * @package   MyAAC
 * @author    Gesior <jerzyskalski@wp.pl>
 * @author    Slawkens <slawkens@gmail.com>
 * @copyright 2017 MyAAC
 * @link      http://my-aac.org
 */
defined('MYAAC') or die('Direct access not allowed!');
$title = 'Highscores';

if($config['account_country'] && $config['highscores_country_box'])
	require(SYSTEM . 'countries.conf.php');

$list = isset($_GET['list']) ? $_GET['list'] : '';
$_page = isset($_GET['page']) ? $_GET['page'] : 0;
$vocation = isset($_GET['vocation']) ? $_GET['vocation'] : NULL;

$add_sql = '';
$config_vocations = $config['vocations'];
if($config['highscores_vocation_box'] && isset($vocation))
{
	foreach($config['vocations'] as $id => $name) {
		if(strtolower($name) == $vocation) {
			$add_vocs = array($id);
	
			$i = $id + $config['vocations_amount'];
			while(isset($config['vocations'][$i])) {
				$add_vocs[] = $i;
				$i += $config['vocations_amount'];
			}
	
			$add_sql = 'AND `vocation` IN (' . implode(', ', $add_vocs) . ')';
			break;
		}
	}
}

$skill = POT::SKILL__LEVEL;
if(is_numeric($list))
{
	$list = intval($list);
	if($list >= POT::SKILL_FIRST && $list <= SKILL__LAST)
		$skill = $list;
}
else
{
	switch($list)
	{
		case 'fist':
			$skill = POT::SKILL_FIST;
			break;

		case 'club':
			$skill = POT::SKILL_CLUB;
			break;

		case 'sword':
			$skill = POT::SKILL_SWORD;
			break;

		case 'axe':
			$skill = POT::SKILL_AXE;
			break;

		case 'distance':
			$skill = POT::SKILL_DIST;
			break;

		case 'shield':
			$skill = POT::SKILL_SHIELD;
			break;

		case 'fishing':
			$skill = POT::SKILL_FISH;
			break;

		case 'level':
			$skill = POT::SKILL_LEVEL;
			break;

		case 'magic':
			$skill = POT::SKILL__MAGLEVEL;
			break;

		case 'frags':
			if($config['highscores_frags'] && $config['otserv_version'] == TFS_03)
				$skill = 666;
			break;
	}
}

$promotion = '';
if(fieldExist('promotion', 'players'))
	$promotion = ',promotion';

$online = '';
if(fieldExist('online', 'players'))
	$online = ',online';

$deleted = 'deleted';
if(fieldExist('deletion', 'players'))
	$deleted = 'deletion';

$outfit_addons = false;
$outfit = '';
if($config['highscores_outfit']) {
	$outfit = ', lookbody, lookfeet, lookhead, looklegs, looktype';
	if(fieldExist('lookaddons', 'players')) {
		$outfit .= ', lookaddons';
		$outfit_addons = true;
	}
}

$offset = $_page * $config['highscores_length'];
if($skill <= POT::SKILL_LAST) { // skills
	if(fieldExist('skill_fist', 'players')) {// tfs 1.0
		$skill_ids = array(
			POT::SKILL_FIST => 'skill_fist',
			POT::SKILL_CLUB => 'skill_club',
			POT::SKILL_SWORD => 'skill_sword',
			POT::SKILL_AXE => 'skill_axe',
			POT::SKILL_DIST => 'skill_dist',
			POT::SKILL_SHIELD => 'skill_shielding',
			POT::SKILL_FISH => 'skill_fishing',
		);

		$skills = $db->query('SELECT accounts.country, players.id,players.name' . $online . ',level,vocation' . $promotion . $outfit . ', ' . $skill_ids[$skill] . ' as value FROM accounts,players WHERE players.id NOT IN (' . implode(', ', $config['highscores_ids_hidden']) . ') AND players.' . $deleted . ' = 0 AND players.group_id < '.$config['highscores_groups_hidden'].' '.$add_sql.' AND players.id > 6 AND accounts.id = players.account_id ORDER BY ' . $skill_ids[$skill] . ' DESC LIMIT 101 OFFSET '.$offset);
	}
	else
		$skills = $db->query('SELECT accounts.country, players.id,players.name' . $online . ',value,level,vocation' . $promotion . $outfit . ' FROM accounts,players,player_skills WHERE players.id NOT IN (' . implode(', ', $config['highscores_ids_hidden']) . ') AND players.' . $deleted . ' = 0 AND players.group_id < '.$config['highscores_groups_hidden'].' '.$add_sql.' AND players.id > 6 AND players.id = player_skills.player_id AND player_skills.skillid = '.$skill.' AND accounts.id = players.account_id ORDER BY value DESC, count DESC LIMIT 101 OFFSET '.$offset);
}
else if($skill == 666 && $config['otserv_version'] == TFS_03) // frags
{
	$skills = $db->query('SELECT accounts.country, players.id,players.name' . $online . ',level,vocation' . $promotion . $outfit . ',COUNT(`player_killers`.`player_id`) as value' .
			' FROM `accounts`, `players`, `player_killers` ' .
			' WHERE players.id NOT IN (' . implode(', ', $config['highscores_ids_hidden']) . ') AND players.' . $deleted . ' = 0 AND players.group_id < '.$config['highscores_groups_hidden'].' '.$add_sql.' AND players.id = player_killers.player_id AND accounts.id = players.account_id' .
			' GROUP BY `player_id`' .
			' ORDER BY value DESC' .
			' LIMIT 101 OFFSET '.$offset);
}
else
{
	if($skill == POT::SKILL__MAGLEVEL) {
		$skills = $db->query('SELECT accounts.country, players.id,players.name' . $online . ',maglevel,level,vocation' . $promotion . $outfit . ' FROM accounts, players WHERE players.id NOT IN (' . implode(', ', $config['highscores_ids_hidden']) . ') AND players.' . $deleted . ' = 0 '.$add_sql.' AND players.group_id < '.$config['highscores_groups_hidden'].' AND players.id > 6 AND accounts.id = players.account_id ORDER BY maglevel DESC, manaspent DESC LIMIT 101 OFFSET '.$offset);
	}
	else { // level
		$skills = $db->query('SELECT accounts.country, players.id,players.name' . $online . ',level,experience,vocation' . $promotion . $outfit . ' FROM accounts, players WHERE players.id NOT IN (' . implode(', ', $config['highscores_ids_hidden']) . ') AND players.' . $deleted . ' = 0 '.$add_sql.' AND players.group_id < '.$config['highscores_groups_hidden'].' AND players.id > 6 AND accounts.id = players.account_id ORDER BY level DESC, experience DESC LIMIT 101 OFFSET '.$offset);
		$list = 'experience';
	}
}

?>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
	<tr>
		<td><img src="<?php echo $template_path; ?>/images/general/blank.gif" width="10" height="1" border="0"></td>
		<td>
			<center><h2>Ranking for <?php echo ($skill == 666 ? 'Frags' : getSkillName($skill)); if(isset($vocation)) echo ' (' . $vocation . ')';?> on <?php echo $config['lua']['serverName']; ?></h2></center><br/>
			<table border="0" cellpadding="4" cellspacing="1" width="100%"></table>
			<table border="0" cellpadding="4" cellspacing="1" width="100%">
				<tr bgcolor="<?php echo $config['vdarkborder']; ?>">
					<?php if($config['account_country']): ?>
					<td width="11px" class="white">#</td>
					<?php endif; ?>
					<td width="10%" class="white"><b>Rank</b></td>
					<?php if($config['highscores_outfit']): ?>
					<td class="white"><b>Outfit</b></td>
					<?php endif; ?>
					<td width="75%" class="white"><b>Name</b></td>
					<td width="15%" class="white"><b><?php echo ($skill != 666 ? 'Level' : 'Frags'); ?></b></td>
					<?php if($skill == POT::SKILL__LEVEL): ?>
					<td class="white"><b>Points</b></td>
					<?php endif; ?>
				</tr>
				<tr>

<?php

$show_link_to_next_page = false;
$i = 0;

$online_exist = false;
if(fieldExist('online', 'players'))
	$online_exist = true;

foreach($skills as $player)
{
	if(!$online_exist) {
		$query = $db->query('SELECT `player_id` FROM `players_online` WHERE `player_id` = ' . $player['id']);
		$player['online'] = $query->rowCount() > 0;
	}

	if(++$i <= $config['highscores_length'])
	{
		if($skill == POT::SKILL__MAGIC)
			$player['value'] = $player['maglevel'];
		else if($skill == POT::SKILL__LEVEL)
			$player['value'] = $player['level'];
echo '
		<tr bgcolor="' . getStyle($i) . '">';
		if($config['account_country'])
			echo '<td>' . getFlagImage($player['country']) . '</td>';
echo '
			<td>' . ($offset + $i) . '.</td>';
			if($config['highscores_outfit'])
			echo '<td><img style="position:absolute;margin-top:' . (in_array($player['looktype'], array(75, 266, 302)) ? '-15px;margin-left:5px' : '-45px;margin-left:-25px') . ';" src="' . $config['outfit_images_url'] . '?id=' . $player['looktype'] . ($outfit_addons ? '&addons=' . $player['lookaddons'] : '') . '&head=' . $player['lookhead'] . '&body=' . $player['lookbody'] . '&legs=' . $player['looklegs'] . '&feet=' . $player['lookfeet'] . '" alt="" /></td>';
		
echo '
			<td>
				<a href="' . getPlayerLink($player['name'], false) . '">
					<font color="' . ($player['online'] > 0 ? 'green' : 'red') . '">' . $player['name'] . '</font>
				</a>';
				if($config['highscores_vocation']) {
					if(isset($player['promotion'])) {
						if((int)$player['promotion'] > 0)
							$player['vocation'] += ($player['promotion'] * $config['vocations_amount']);
					}

					$tmp = 'Unknown';
					if(isset($config['vocations'][$player['vocation']])) {
						$tmp = $config['vocations'][$player['vocation']];
					}
					echo '<br/><small>' . $tmp . '</small>';
				}
echo '
			</td>
			<td>
				<center>'.$player['value'].'</center>
			</td>';

		if($skill == POT::SKILL__LEVEL)
			echo '<td><center>' . number_format($player['experience']) . '</center></td>';

		echo '</tr>';
	}
	else
		$show_link_to_next_page = true;
}

if(!$i) {
	$extra = ($config['highscores_outfit'] ? 1 : 0);
	echo '<tr bgcolor="' . $config['darkborder'] . '"><td colspan="' . ($skill == POT::SKILL__LEVEL ? 5 + $extra : 4 + $extra) . '">No records yet.</td></tr>';
}

?>
</table>
<table border="0" cellpadding="4" cellspacing="1" width="100%">
<?php
//link to previous page if actual page is not first
if($_page > 0)
	echo '<TR><TD WIDTH=100% ALIGN=right VALIGN=bottom><A HREF="' . getLink('highscores') . '/' . $list . (isset($vocation) ? '/' . $vocation : '') . '/' . ($_page - 1) . '" CLASS="size_xxs">Previous Page</A></TD></TR>';

//link to next page if any result will be on next page
if($show_link_to_next_page)
	echo '<TR><TD WIDTH=100% ALIGN=right VALIGN=bottom><A HREF="' . getLink('highscores') . '/' . $list . (isset($vocation) ? '/' . $vocation : '') . '/' . ($_page + 1) . '" CLASS="size_xxs">Next Page</A></TD></TR>';

//end of page
echo '</TABLE>
</TD>
<TD WIDTH=5%>
	<IMG SRC="'.$template_path.'/images/general/blank.gif" WIDTH=1 HEIGHT=1 BORDER=0></TD>
	<TD WIDTH=15% VALIGN=top ALIGN=right>';
/*
if($config['highscores_country_box'])
{
	echo
	'<TABLE BORDER=0 width="100%" CELLPADDING=4 CELLSPACING=1>
		<TR BGCOLOR="' . $config['vdarkborder'] . '">
			<TD CLASS=white><B>Choose a country</B></TD>
		</TR>
		<TR BGCOLOR="'.$config['lightborder'].'">
			<TD>
				<A HREF="?subtopic=highscores&list=' . $list . '" CLASS="size_xs">[ALL]</A><BR>';
				for($i = 1; $i < count($config_vocations); $i++)
					echo '<A HREF="?subtopic=highscores&list=' . $list . '&vocation=' . strtolower($config_vocations[$i]) . '" CLASS="size_xs">' . $config_vocations[$i] . '</A><BR>';
		echo '
			</TD>
		</TR>
	</TABLE>';
}*/

echo '
<TABLE BORDER=0 width="100%" CELLPADDING=4 CELLSPACING=1>
	<TR BGCOLOR="'.$config['vdarkborder'].'">
		<TD CLASS=white><B>Choose a skill</B></TD>
	</TR>
	<TR BGCOLOR="'.$config['lightborder'].'">
		<TD>';
			$types = array(
				'experience' => 'Experience',
				'magic' => 'Magic',
				'shield' => 'Shielding',
				'distance' => 'Distance',
				'club' => 'Club',
				'sword' => 'Sword',
				'axe' => 'Axe',
				'fist' => 'Fist',
				'fishing' => 'Fishing',
			);
				
			foreach($types as $link => $name) {
				echo '<A HREF="' . getLink('highscores') . '/' . $link . (isset($vocation) ? '/' . $vocation : '') . '" CLASS="size_xs">' . $name . '</A><BR>';
			}

if($config['highscores_frags'])
	echo '<a href="' . getLink('highscores') . '/frags' . (isset($vocation) ? '/' . $vocation : '') . '" CLASS="size_xs">Frags</a><br/>';
echo 	'</td>
	</tr>
</table><br>';

if($config['highscores_vocation_box'])
{
	echo
	'<table border="0" width="100%" cellpadding="4" cellspacing="1">
		<tr bgcolor="' . $config['vdarkborder'] . '">
			<td class="white"><b>Choose a vocation</b></td>
		</tr>
		<tr bgcolor="'.$config['lightborder'].'">
			<td>
				<a href="' . getLink('highscores') . '/' . $list . '" class="size_xs">[ALL]</A><BR>';
				for($i = 1; $i <= $config['vocations_amount']; $i++) {
					echo '<a href="' . getLink('highscores') . '/' . $list . '/' . strtolower($config_vocations[$i]) . '" class="size_xs">' . $config_vocations[$i] . '</a><br/>';
				}
		echo '
			</td>
		</tr>
	</table>';
}
?>
		</td>
		<td><img src="<?php echo $template_path; ?>/images/general/blank.gif" width="10" height="1" border="0"></td>
	</tr>
</table>
