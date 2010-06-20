<?php //$Id: search_glossaries.php,v 1.2 2006/04/15 23:57:26 stronk7 Exp $

    include_once ("../../config.php");
    include_once($CFG->dirroot.'/mod/glossary/lib.php');

    $courseid = required_param('courseid', PARAM_INT);
    $query    = required_param('query');
    $page     = optional_param('page', 0, PARAM_INT);

    require_login($courseid);

    DEFINE('MAXRESULTSPERPAGE', 10);  //Limit results per page
    DEFINE('MAXPAGEALLOWED', 99);    //Limit number of pages to show

    function search( $query, $courseid, $offset, &$countentries ) {

        global $CFG, $USER;

        $fullsearch = true;  //Search in definitions too. Parametrised, could go to config.

        /// Some differences in syntax for PostgreSQL
        if ($CFG->dbtype == "postgres7") {
            $LIKE = "ILIKE";   // case-insensitive
            $NOTLIKE = "NOT ILIKE";   // case-insensitive
            $REGEXP = "~*";
            $NOTREGEXP = "!~*";
        } else {
            $LIKE = "LIKE";
            $NOTLIKE = "NOT LIKE";
            $REGEXP = "REGEXP";
            $NOTREGEXP = "NOT REGEXP";
        }

        $conceptsearch = "";
        $aliassearch = "";
        $definitionsearch = "";

        $searchterms = explode(" ",$query);

        foreach ($searchterms as $searchterm) {

            if ($conceptsearch) {
                $conceptsearch .= " AND ";
            }
            if ($aliassearch) {
                $aliassearch .= " AND ";
            }
            if ($definitionsearch) {
                $definitionsearch .= " AND ";
            }

            if (substr($searchterm,0,1) == "+") {
                $searchterm = substr($searchterm,1);
                $conceptsearch .= " ge.concept $REGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
                $aliassearch .= " al.alias $REGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
                $definitionsearch .= " ge.definition $REGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
            } else if (substr($searchterm,0,1) == "-") {
                $searchterm = substr($searchterm,1);
                $conceptsearch .= " ge.concept $NOTREGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
                $aliassearch .= " al.alias $NOTREGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
                $definitionsearch .= " ge.definition $NOTREGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
            } else {
                $conceptsearch .= " ge.concept $LIKE '%$searchterm%' ";
                $aliassearch .= " al.alias $LIKE '%$searchterm%' ";
                $definitionsearch .= " ge.definition $LIKE '%$searchterm%' ";
            }
        }

        $userid = '';
        if ( isset($USER->id) ) {
            $userid = "OR ge.userid = $USER->id";
        }

        //Search in aliases first
        $idaliases = '';
        $listaliases = array();
        $recaliases = get_records_sql ("SELECT al.id, al.entryid
                                        FROM {$CFG->prefix}glossary_alias al,
                                             {$CFG->prefix}glossary_entries ge,
                                             {$CFG->prefix}glossary g
                                        WHERE g.course = $courseid AND 
                                              (ge.glossaryid = g.id OR
                                               ge.sourceglossaryid = g.id) AND
                                              (ge.approved != 0 $userid) AND
                                              ge.id = al.entryid AND
                                              $aliassearch");
        //Process aliases id
        if ($recaliases) {
            foreach ($recaliases as $recalias) {
                $listaliases[] = $recalias->entryid;
            }
            $idaliases = implode (',',$listaliases);
        }

        //Add seach conditions in concepts and, if needed, in definitions
        $where = "AND (( $conceptsearch) ";

        //Include aliases id if found
        if (!empty($idaliases)) {
            $where .= " OR ge.id IN ($idaliases) ";
        }

        //Include search in definitions if requested
        if ( $fullsearch ) {
            $where .= " OR ($definitionsearch) )";
        } else {
            $where .= ")";
        }

        $module = get_record('modules', 'name', 'glossary');

        $sqlselect  = "SELECT DISTINCT ge.*, ge.concept";
        $sqlfrom    = "FROM {$CFG->prefix}glossary_entries ge, 
                            {$CFG->prefix}glossary g,
                            {$CFG->prefix}course_modules cm";
        $sqlwhere   = "WHERE g.course = $courseid AND 
                             cm.module = $module->id AND
                             cm.instance = g.id AND
                             cm.visible = 1 AND
                             (ge.glossaryid = g.id OR
                              ge.sourceglossaryid = g.id) AND        
                             (ge.approved != 0 $userid)
                              $where";
        $sqlorderby = "ORDER BY ge.concept";

        if ( $offset >= 0 ) {
            $entriesbypage = MAXRESULTSPERPAGE;
            switch ($CFG->dbtype) {
            case 'postgres7':
                $sqllimit = " LIMIT $entriesbypage OFFSET $offset";
                break;
            case 'mysql':
                $sqllimit = " LIMIT $offset, $entriesbypage";
                break;
            }
        }
        //echo "select distinct ge.id $sqlfrom $sqlwhere<br />";       //Debug
        //echo "$sqlselect $sqlfrom $sqlwhere $sqlorderby $sqllimit";  //Debug

        //IMPORTANT!! The count_records_sql() function doesn't seem to work propely
        //            I've get bad results (one more) executing the same query!
        //            MacOS X, PHP 5.0.4!!, So I execute to one full select instead

        $countentries = get_records_sql("select distinct ge.id, ge.id $sqlfrom $sqlwhere");
        if (!empty($countentries)) {
            $countentries = count($countentries);
        } else {
            $countentries = 0;
        }
        $allentries =   get_records_sql("$sqlselect $sqlfrom $sqlwhere $sqlorderby $sqllimit");

        return $allentries;
    }

//////////////////////////////////////////////////////////
// The main part of this script

    $strsearchresults = get_string("searchresults","block_search_glossaries");

    if (! $course = get_record("course", "id", $courseid) ) {
            error("That's an invalid course id");
    }

    if ($course->category) {
        print_header("$course->shortname: $strsearchresults", "$course->fullname",
                     "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->
                      $strsearchresults", "form.query");
    } else {
        print_header("$course->shortname: $strsearchresults", "$course->fullname",
                     "$strsearchresults", "form.query");
    }

    //Get the format from CFG
    if (!empty($CFG->block_search_glossaries_format)) {
        $format = $CFG->block_search_glossaries_format;
    } else {
        set_config('block_search_glossaries_format','dictionary');
        $format = "dictionary";
    }

    $start = (MAXRESULTSPERPAGE*$page);

    //Process the query
    $query = trim(strip_tags($query));

    //Launch the SQL quey
    $glossarydata = search( $query, $courseid, $start, $countentries);

    $searchglossaries = get_string('glossariessearch', 'block_search_glossaries');
    $searchresults = get_string('searchresults', 'block_search_glossaries');
    $strresults = get_string('results', 'block_search_glossaries');
    $ofabout = get_string('ofabout', 'block_search_glossaries');
    $for = get_string('for', 'block_search_glossaries');
    $seconds = get_string('seconds', 'block_search_glossaries');
 

    $rowstart = '<tr><td align="center">';
    $rowend = '</td></tr>';
    
    $coursefield = '<input type="hidden" name="courseid" value="'.$courseid.'">';
    $pagefield = '<input type="hidden" name="page" value="0">';
    $searchbox = '<input type="text" name="query" size="20" maxlength="255" value="'.s($query).'">';
    $submitbutton = '<input type="submit" name="submit" value="'.$searchglossaries.'">';

    $row2content = $coursefield.$pagefield.$searchbox.$submitbutton;

    $row2 = $rowstart.$row2content.$rowend;

    $table = '<table>'.$row2.'</table>';
    $form = '<form method="GET" action="'.$CFG->wwwroot.'/blocks/search_glossaries/search_glossaries.php" name="form" id="form">'.$table.'</form>';

    echo "<center>";
    echo $form;
    echo "</center>";

    //Process $glossarydata, if present
    $startindex = $start;
    $endindex = $start + count($glossarydata);

    $countresults = $countentries;

    //Print results page tip
    $page_bar = glossary_get_paging_bar($countresults, $page, MAXRESULTSPERPAGE, "search_glossaries.php?query=".urlencode(stripslashes($query))."&amp;courseid=$courseid&amp;");

    //Iterate over results
    if (!empty($glossarydata)) {
        //Print header
        echo '<table width="100%" border="0" cellpadding="0" cellspacing="0"><tr>';
        echo '<td class="header topic" align="right" nowrap>'.$strresults.' <b>'.($startindex+1).'</b> - <b>'.$endindex.'</b> '.$ofabout.'<b> '.$countresults.' </b>'.$for.'<b> "'.s($query).'"</b>&nbsp;</td></tr></table>';
        echo '<table width="100%" border="0" cellpadding="0" cellspacing="0>"<tr><td>&nbsp;</td></tr></table>';
        echo '<table align="center" width="70%" id="intro" class="generalbox" border="0" cellpadding="5" cellspacing="0"><tr><td><center>';
        echo $page_bar;
        //Prepare each entry (hilight, footer...)
        foreach ($glossarydata as $entry) {
            $glossary = get_record('glossary', 'id', $entry->glossaryid);
            $cm = get_coursemodule_from_instance("glossary", $glossary->id, $course->id);
            //Highlight!
            //We have to strip any word starting by + and take out words starting by -
            //to make highlight works properly
            $searchterms = explode(' ', $query);    // Search for words independently
            foreach ($searchterms as $key => $searchterm) {
                if (preg_match('/^\-/',$searchterm)) {
                    unset($searchterms[$key]);
                } else {
                    $searchterms[$key] = preg_replace('/^\+/','',$searchterm);
                }
                //Avoid highlight of <2 len strings. It's a well known hilight limitation.
                if (strlen($searchterm) < 2) {
                    unset($searchterms[$key]);
                }
            } 
            $strippedsearch = implode(' ', $searchterms);    // Rebuild the string
            $entry->highlight = $strippedsearch;

            //Footer, to show where each entry belongs to
            $entry->footer = "<p align=\"right\">&raquo;&nbsp;<a href=\"$CFG->wwwroot/mod/glossary/view.php?g=$entry->glossaryid\">".format_string($glossary->name,true)."</a></p>";
            glossary_print_entry($course, $cm, $glossary, $entry, '', '', 0, $format); 
        }
        echo $page_bar;
        echo '</td></tr></table>';
    } else {
        echo '<br />';
        print_simple_box(get_string("norecordsfound","block_search_glossaries"),'CENTER');

    }
        echo '</center></td></tr></table>';


    print_footer($course);

?>
