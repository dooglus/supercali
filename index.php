<?php
/*
Supercali Event Calendar

Copyright 2006 Dana C. Hutchins

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

For further information visit:
http://supercali.inforest.com/
*/


/* Grab Dates Function */
// Queries database and dumps events into an array.
// $start and $end are date ranges in 20060118 format
// $category_array is arrays of categories to be included
function grabDates($start,$end,$category_array) {
	$cats = implode(",",$category_array);
	global $table_prefix, $supergroup;
	global $title, $niceday, $start_time, $end_time, $venue, $city, $state, $cat,$ed, $usr, $color, $background, $css, $lang, $w, $ap, $status;
	/* get applicable events */
	$superedit = false;
	if (!$supergroup) {
		$q = "select moderate from ".$table_prefix."users_to_groups where group_id = ".$w." and user_id = ".$_SESSION["user_id"];
		$query = mysql_query($q);
		if (mysql_num_rows($query) > 0) {
			$mod = mysql_result($query,0,0);
			if ($mod > 2) {
				$superedit = true;
			}
		}
	} else {
		$superedit = true;
	}
	if (($mod > 0) || ($superedit)) {
		$q = "
			SELECT
				DATE_FORMAT({$table_prefix}dates.date, '%Y%m%d')                 as short_date,    " . /*  0 */ "
				{$table_prefix}events.event_id                                   as event_id,      " . /*  2 */ "
				{$table_prefix}events.title                                      as title,         " . /*  3 */ "
				DATE_FORMAT({$table_prefix}dates.date, '%W, %M %e, %Y')          as day_view_date, " . /*  4 */ "
				CONCAT(DATE_FORMAT({$table_prefix}dates.date, '%l:%i'),
				       LOWER(DATE_FORMAT({$table_prefix}dates.date, '%p')))      as another_time,  " . /*  5 */ "
				CONCAT(DATE_FORMAT({$table_prefix}dates.end_date, '%l:%i'),
				       LOWER(DATE_FORMAT({$table_prefix}dates.end_date, '%p')))  as end_time,      " . /*  6 */ "
				{$table_prefix}links.company                                     as company,       " . /*  7 */ "
				{$table_prefix}links.city                                        as city,          " . /*  8 */ "
				{$table_prefix}links.state                                       as state,         " . /*  9 */ "
				{$table_prefix}events.category_id                                as category_id,   " . /* 10 */ "
				{$table_prefix}events.user_id                                    as user_id,       " . /* 11 */ "
				{$table_prefix}dates.date                                        as date,          " . /* 12 */ "
				{$table_prefix}categories.color                                  as color,         " . /* 13 */ "
				{$table_prefix}categories.background                             as background,    " . /* 14 */ "
				{$table_prefix}categories.css                                    as css,           " . /* 15 */ "
				{$table_prefix}events.status_id                                  as status_id      " . /* 16 */ "
			FROM
				{$table_prefix}events,
				{$table_prefix}dates,
				{$table_prefix}links,
				{$table_prefix}categories,
				{$table_prefix}groups
			WHERE
				{$table_prefix}dates.date >= '$start' AND
				{$table_prefix}dates.date < '$end' AND
				{$table_prefix}dates.event_id = {$table_prefix}events.event_id AND
				{$table_prefix}events.venue_id = {$table_prefix}links.link_id AND
				{$table_prefix}events.category_id in ($cats) AND
				{$table_prefix}events.category_id = {$table_prefix}categories.category_id AND
				{$table_prefix}events.group_id = {$table_prefix}groups.group_id AND
				{$table_prefix}events.group_id = $w
			ORDER BY
				{$table_prefix}dates.date, {$table_prefix}events.category_id;
		";
		$query = mysql_query($q);
		//echo $q."<br>";
		while ($row = mysql_fetch_assoc($query)) {
			$edit = false;
			if ($row['user_id'] == $_SESSION["user_id"]) {
				$edit = true;
			} elseif ($superedit) {
				$edit = true;
			}
			if ($edit==true) $ed[$row['event_id']]=true;
			if ($superedit==true) $ap[$row['event_id']]=true;
			$title[$row['event_id']]=strip_tags($row['title']);
			$niceday[$row['short_date']][$row['date']][$row['event_id']]=$row['day_view_date'];
			if (($row['another_time'] == "12:00 AM") && ($row['end_time'] == "11:59 PM")) {
				$start_time[$row['short_date']][$row['date']][$row['event_id']]=$lang["all_day"];
			} elseif (($row['another_time'] == "12:00 AM") && ($row['end_time'] == "12:00 AM")) {
				$start_time[$row['short_date']][$row['date']][$row['event_id']]=$lang["tba"];
			} else {
				$start_time[$row['short_date']][$row['date']][$row['event_id']]=$row['another_time'];
				if ($row['end_time']) $end_time[$row['short_date']][$row['date']][$row['event_id']]=$row['end_time'];
			}
			if ($row['company']) $venue[$row['event_id']]=$row['company'];
			if ($row['company'] && $row['city']) $city[$row['event_id']]=$row['city'];
			if ($row['company'] && $row['city'] && $row['state']) $state[$row['event_id']]=$row['state'];
			$cat[$row['event_id']]=$row['category_id'];
			$usr[$row['event_id']]=$row['user_id'];

			$cat_id = $row['category_id'];
			$mycolor = $row['color'];
			while ($mycolor == '') {
			   $cat_id = mysql_result(mysql_query("select sub_of from {$table_prefix}categories where category_id = ".$cat_id),0,0);
			   if ($cat_id == "0") break;
			   $mycolor = mysql_result(mysql_query("select color from {$table_prefix}categories where category_id = ".$cat_id),0,0);
			}

			$cat_id = $row['category_id'];
			$mybg = $row['background'];
			while ($mybg == '') {
			   $cat_id = mysql_result(mysql_query("select sub_of from {$table_prefix}categories where category_id = ".$cat_id),0,0);
			   if ($cat_id == "0") break;
			   $mybg = mysql_result(mysql_query("select background from {$table_prefix}categories where category_id = ".$cat_id),0,0);
			}

			$cat_id = $row['category_id'];
			$mycss = $row['css'];
			while ($mycss == '') {
			   $cat_id = mysql_result(mysql_query("select sub_of from {$table_prefix}categories where category_id = ".$cat_id),0,0);
			   if ($cat_id == "0") break;
			   $mycss = mysql_result(mysql_query("select css from {$table_prefix}categories where category_id = ".$cat_id),0,0);
			}

			$color[$row['event_id']]=$mycolor;
			$background[$row['event_id']]=$mybg;
			$css[$row['event_id']]=$mycss;
			$status[$row['event_id']]=$row['status_id'];
		}
	}
}

function grab($start,$end,$category) {
	global $include_child_categories, $include_parent_categories, $category_array,$supercategory,$supergroup,$category_permissions,$w,$table_prefix;
	$canview = false;
	$groupview = false;
	if (!$supergroup) {
		$q = "SELECT * from {$table_prefix}users_to_groups where group_id = ".$w." and  user_id = ".$_SESSION["user_id"];
		$query = mysql_query($q);
		if (mysql_num_rows($query) > 0) $groupview = true;
	} else {
		$groupview = true;
	}
	if ($groupview) {
		if (!$supercategory) {
			//build permission array
			$q = "SELECT category_id from {$table_prefix}users_to_categories where user_id = ".$_SESSION["user_id"];
			//echo $q."<br>";
			$query = mysql_query($q);
			if (mysql_num_rows($query) > 0) {
				while ($row = mysql_fetch_row($query)) {
					$category_permissions[] = $row[0];

				}
			}
			if (in_array($category,$category_permissions)) $canview = true;
		} else {
			$canview = true;
		}
		if ($canview) {
			$category_array[] = $category;
			if ($include_child_categories) grab_child($start,$end,$category,true);
			if ($include_parent_categories) grab_parent($start,$end,$category,true);
			grabDates($start,$end,$category_array);

		}

	}
}

function grab_child($start,$end,$category,$starter=false) {
	global $table_prefix, $category_array,$supercategory,$category_permissions;
	$canview = false;
	if (!$supercategory) {
		if ($category_permissions) {
			if (in_array($category,$category_permissions)) $canview = true;
		}
	} else {
		$canview = true;
	}
	if ($canview) {
		if (!$starter) $category_array[] = $category;
		$q = "select category_id from {$table_prefix}categories where sub_of = ".$category;
		//echo $q."<br>";
		$query = mysql_query($q);
		if (!$query) $msg = "Database Error : ".$q;
		else {
			while ($row = mysql_fetch_row($query)) {
				grab_child($start,$end,$row[0],false);
			}
		}
	}
}

function grab_parent($start,$end,$category,$starter=false) {
	global $table_prefix, $category_array, $supercategory,$category_permissions;
	$canview = false;
	if (!$supercategory) {
		if ($category_permissions) {
			if (in_array($category,$category_permissions)) $canview = true;
		}
	} else {
		$canview = true;
	}
	if ($canview) {
		if (!$starter) $category_array[] = $category;

		$q = "select sub_of from {$table_prefix}categories where category_id = ".$category;
		//echo $q."<br>";
		$query = mysql_query($q);
		if (!$query) $msg = "Database Error : ".$q;
		else {
			while ($row = mysql_fetch_row($query)) {
				grab_parent($start,$end,$row[0],false);
			}
		}
	}
}



include "includes/start.php";
$canview = false;
//if no access, then kick them out!
if (!$superview) {
	mysql_close($link);
	$msg = $lang["must_log_in"];
	header("Location: login.php?msg=".$msg."&".$common_get);
}

if (($supergroup) && ($supercategory)) {
	$canview = true;

} else {

	if (!$supercategory) {
		$canview = false;
		$q = "select * from {$table_prefix}users_to_categories where category_id = ".$c." and user_id = ".$_SESSION["user_id"];
		//echo $q;
		$qu = mysql_query($q);
		if (mysql_num_rows($qu) > 0) {
			$canview = true;

		} else {
			$msg .= "<p>".$lang["no_permission_to_view_category"]."</p>";
			$canview = false;

		}
	}
	if ((!$supergroup) && $canview) {
		$q = "select * from {$table_prefix}users_to_groups where group_id = ".$w." and user_id = ".$_SESSION["user_id"];
		//echo $q;
		$qu = mysql_query($q);
		if (mysql_num_rows($qu) > 0) {
			$canview = true;

		} else {
			$msg .= "<p>".$lang["no_permission_to_view_group"]."</p>";
			$canview = false;

		}
	}
}
if (($canview == true)&& $script) {
	include "modules/".$script;
} else {
	include "includes/header.php";
	include "includes/footer.php";
}



?>
