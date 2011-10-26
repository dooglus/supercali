<?php
/*
<?xml version="1.0" encoding="utf-8"?>
<module>
        <name>Summary View</name>
        <author>Chris Moore</author>
        <url>http://supercali.inforest.com/</url>
        <version>1.0.0</version>
        <link_name>Summary</link_name>
        <description>Shows a week on one screen.</description>
        <image></image>
		<install_script></install_script>     
</module>
*/
include "modules/day_week_functions.php";
include "includes/header.php";
?>

<?php

function top_level_category($category_id)
{
    global $table_prefix;

    if ($category_id == 1)
        return 1;

    $query = mysql_query("select sub_of from ".$table_prefix."categories where category_id = $category_id");
    $row = mysql_fetch_array($query);
    $sub_of = $row['sub_of'];
    if ($sub_of == 1)
        return $category_id;
    return top_level_category($sub_of);
}

$thisweek = $now["week"]["y"][0]."-".$now["week"]["m"][0]."-".$now["week"]["a"][0];
$nextweek =  $next["week"]["y"]."-".$next["week"]["m"]."-".$next["week"]["a"];
grab($thisweek,$nextweek,$c);
echo "<div class=\"frame\">\n";
echo '<div class="cal_top">';
echo  '<table class="cal_title"><colgroup><col class="cal_title"/><col class="cal_title_middle"/><col class="cal_title"/></colgroup><tr>';
echo   '<th class="cal_title" onclick=\'document.location="',$PHP_SELF,'?o=',$o,'&w=',$w,'&c=',$c,'&m=',$prev["week"]["m"],'&a=',$prev["week"]["a"],'&y=',$prev["week"]["y"],'";\'><a href="',$PHP_SELF,'?o=',$o,'&w=',$w,'&c=',$c,'&m=',$prev["week"]["m"],'&a=',$prev["week"]["a"],'&y=',$prev["week"]["y"],'">&lt;</a></th>';
echo   '<th class="cal_title_middle">',$lang["week_of"],date('F j, Y', mktime(0,0,0,$now["week"]["m"][0],$now["week"]["a"][0],$now["week"]["y"][0])),'</th>';
echo   '<th class="cal_title" onclick=\'document.location="',$PHP_SELF,'?o=',$o,'&w=',$w,'&c=',$c,'&m=',$next["week"]["m"],'&a=',$next["week"]["a"],'&y=',$next["week"]["y"],'";\'><a href="',$PHP_SELF,'?o=',$o,'&w=',$w,'&c=',$c,'&m=',$next["week"]["m"],'&a=',$next["week"]["a"],'&y=',$next["week"]["y"],'">&gt;</a></th>';
echo  '</tr></table>';
echo '</div>'."\n";
// echo "thisweek: $thisweek<br/>\n";
// echo "nextweek: $nextweek<br/>\n";
$category_query = mysql_query("select category_id, name from ".$table_prefix."categories where sub_of = 1 order by name");
$total_row = mysql_numrows($category_query);
// echo "there are $total_row top level categories<br/>\n";
$hr = false;

// loop for top level categories
echo "<blockquote>\n";
while ($row = mysql_fetch_array($category_query)) {
    $this_category_id = $row['category_id'];
    $name = $row['name'];
    $first = true;
    $week_query = mysql_query("select ".$table_prefix."events.event_id as event_id, ".$table_prefix."events.category_id as category_id from ".$table_prefix."dates join ".$table_prefix."events on ".$table_prefix."dates.event_id = ".$table_prefix."events.event_id where date > '$thisweek' and date < '$nextweek' order by category_id, title, date");

    // loop for events in each top level category
    $last_page_title = '';
    while ($row = mysql_fetch_array($week_query)) {
        $category_id = $row['category_id'];
        $top_level_category = top_level_category($category_id);
        if ($top_level_category == $this_category_id) {
            if ($hr)
                echo "<hr/>";
            else
                $hr = true;
            $event_id = $row['event_id'];
            if ($first) {
                $first = false;
                echo "<h2>$name</h2>\n";
                echo "<blockquote>\n";
            }
            // echo "event $event_id<br/>\n";
            $q = "SELECT * from ".$table_prefix."events where event_id =".$event_id;
            // echo "q1: $q<br/>\n";
            $query = mysql_query($q);
            if (mysql_num_rows($query) < 1) {
                echo "<p class=\"warning\">".$lang["event_not_found"]."</p>\n";
            } else {
                $row = mysql_fetch_array($query);
                if (!$query) echo "<p class=\"warning\">Database Error : ".$q."</p>\n";
		
                $q = "SELECT DATE_FORMAT(date, '%W, %M %e, %Y'), DATE_FORMAT(date,' - %l:%i %p'),  DATE_FORMAT(end_date, ' - %l:%i %p') from ".$table_prefix."dates where event_id =".$event_id." order by date";
                // echo "q2: $q<br/>\n";
                $squery = mysql_query($q);
                if (!$squery) echo "<p class=\"warning\">Database Error : ".$q."</p>\n";
                else {
                    while ($srow = mysql_fetch_row($squery)) {
                        if (($srow[1] == " - 12:00 AM") && ($srow[2] == " - 11:59 PM")) $nicedate[] = $srow[0]." - ".$lang["all_day"];
                        elseif (($srow[1] == " - 12:00 AM") && ($srow[2] == " - 12:00 AM")) $nicedate[] = $srow[0]." - ".$lang["tba"];
                        elseif ($srow[2]) $nicedate[] = $srow[0].$srow[1].$srow[2];
                        else $nicedate[] = $srow[0].$srow[1];
				
                    }
                }
                $page_title = $row["title"];
                $category_id = $row["category_id"];
                $venue_id = $row["venue_id"];
                if ($venue_id == 1)
                    $venue_id = 3;
                // echo "venue_id = $venue_id<br/>\n";
                $contact_id = $row["contact_id"];
                $description = $row["description"];

                $new_title = ($page_title != $last_page_title);

                if ($new_title) {
                    if ($last_page_title != '')
                        echo "</blockquote>\n";
                    echo "<h4>$page_title</h4>\n";
                }
            }

            if ($new_title) {
                $cate = mysql_result(mysql_query("select name from ".$table_prefix."categories where category_id = ".$category_id),0,0);
                $cdesc = mysql_result(mysql_query("select description from ".$table_prefix."categories where category_id = ".$category_id),0,0);
                if ($cdesc)
                    echo "<br/><strong>$cate</strong> - $cdesc\n";
                echo "<blockquote>\n";
            }

            if ($venue_id > 1) {
                $q = "select url, company, description, address1, address2, city, state, zip, phone, fax  FROM ".$table_prefix."links where link_id = ".$venue_id;
                $lq = mysql_query($q);
	
                echo "<strong>{$lang["venue"]}</strong>: \n";
                $li = mysql_fetch_row($lq);
                if ($li[0]) { 
                    echo "<a href=\"".$li[0]."\">".$li[1]."</a>";
                } else {
                    echo "".$li[1]."";
                }
                if ($li[3]) echo ", ".$li[3];
                if ($li[4]) echo ", ".$li[4];
                if ($li[5]) echo ", ".$li[5].", ".$li[6]."  ".$li[7];
                if ($li[8]) echo ", ".$lang["phone"].": ".$li[8];
                if ($li[9]) echo ", ".$lang["fax"].": ".$li[9];
                echo "<br/><br/>\n";
            } 
            if ($nicedate[1]) {
                echo "<strong>" . $lang["dates"] . "</strong>:<ol>\n";
                while (list($k,$v) = each($nicedate)) {
                    echo "<strong><li>".$v."</li></strong>\n";
                }
                echo "</ol>\n";
            } elseif ($nicedate[0]) {
                echo $lang["date"].": <strong>".$nicedate[0]."</strong><br/><br/>";
            }
            if ($contact_id > 1) {
                $q = "select url, company, description, address1, address2, city, state, zip, phone, fax  FROM ".$table_prefix."links where link_id = ".$contact_id;
                $lq = mysql_query($q);
	
                echo "<strong>" . $lang["contact_sponsor"] . "</strong>: \n";
                $li = mysql_fetch_row($lq);
                if ($li[0]) { 
                    echo "<strong><a href=\"".$li[0]."\">".$li[1]."</a></strong>";
                } else {
                    echo "<strong>".$li[1]."</strong>";
                }
                if ($li[3]) echo ", ".$li[3];
                if ($li[4]) echo ", ".$li[4];
                if ($li[5]) echo ", ".$li[5].", ".$li[6]."  ".$li[7];
                if ($li[8]) echo ", ".$lang["phone"].": ".$li[8];
                if ($li[9])echo ", ".$lang["fax"].": ".$li[9];
                echo "<br/><br/>\n";
            }

            echo "$description\n";

            $last_page_title = $page_title;
        }
    }
    if (!$first) {
        echo "</blockquote>\n";
        echo "</blockquote>\n";
    }
}
echo "</blockquote>\n";

echo "</div>\n";
include "includes/footer.php";
?>
