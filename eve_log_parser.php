<?php
        $REGEXP_CHARACTER='[.\'_-\w\s]+';
        $REGEXP_WEAPON='[.\'_-\w\s]+';
        $REGEXP_CORP='[.:_-\w\s]+';
        $REGEXP_SHIP='[\w\s]+';
        
        $involved=array();

        echo('EVE Online gamelog parser'.PHP_EOL.'(c) 2014 by Lukasz "Lukas Rox" Pozniak'.PHP_EOL.PHP_EOL);
        
        if ($argc!=2) die("Usage: ${argv[0]} log_file".PHP_EOL);
        
        echo("Loading gamelog file ${argv[1]}...".PHP_EOL);
        $gamelog=file_get_contents($argv[1]);
        if ($gamelog === FALSE) die("Cannot open gamelog file".PHP_EOL);
        $gamelog=explode("\n",str_replace("\r\n","\n",$gamelog));
        
        function addDamage(&$involved, $damage, $characterName, $corporationTicker='', $shipTypeName='unknown', $weaponTypeName='unknown') {
               $involved[$characterName]['damage']+=$damage;
               $involved[$characterName]['characterName']=$characterName;
               $involved[$characterName]['corporationTicker']=$corporationTicker;
               $involved[$characterName]['shipTypeName']=$shipTypeName;
               $involved[$characterName]['weaponTypeName']=$weaponTypeName; 
        }
        
        function array_orderby()
        {
            $args = func_get_args();
            $data = array_shift($args);
            foreach ($args as $n => $field) {
                if (is_string($field)) {
                    $tmp = array();
                    foreach ($data as $key => $row)
                        $tmp[$key] = $row[$field];
                    $args[$n] = $tmp;
                    }
            }
            $args[] = &$data;
            call_user_func_array('array_multisort', $args);
            return array_pop($args);
        }
        
        foreach ($gamelog as $line) {
                $l=trim(strip_tags($line));
                
                if (preg_match("/^\[ (\d+).(\d+).(\d+) (\d+):(\d+):(\d+) \] \(combat\) (\d+) from ($REGEXP_CHARACTER)\[($REGEXP_CORP)\]\(($REGEXP_SHIP)\) - ($REGEXP_WEAPON) - [\w\s]+$/", $l, $m)) {
                        //line with weapon info
                        //echo('  >>> "'.$m[8].'" from "'.$m[9].'" dealt '.$m[7].' damage flying "'.$m[10].'" using "'.$m[11].'"'.PHP_EOL);
                        addDamage($involved,$m[7],$m[8],$m[9],$m[10],$m[11]);
                } else if (preg_match("/^\[ (\d+).(\d+).(\d+) (\d+):(\d+):(\d+) \] \(combat\) (\d+) from ($REGEXP_CHARACTER)\[($REGEXP_CORP)\]\(($REGEXP_SHIP)\) - [\w\s]+$/", $l, $m)) {
                        //line without weapon info
                        //echo('  >>> "'.$m[8].'" from "'.$m[9].'" dealt '.$m[7].' damage flying "'.$m[10].'" using unknown weapon'.PHP_EOL);
                        addDamage($involved,$m[7],$m[8],$m[9],$m[10]);
                } else if (preg_match("/^\[ (\d+).(\d+).(\d+) (\d+):(\d+):(\d+) \] \(combat\) (\d+) from ($REGEXP_CHARACTER) - ($REGEXP_WEAPON) - [\w\s]+$/", $l, $m)) {
                        //line with weapon info, no corp and ship
                        //echo('  >>> "'.$m[8].'" from "'.$m[9].'" dealt '.$m[7].' damage flying unknown using "'.$m[8].'"'.PHP_EOL);
                        addDamage($involved,$m[7],$m[8]);
                } else if (preg_match("/^\[ (\d+).(\d+).(\d+) (\d+):(\d+):(\d+) \] \(combat\) (\d+) from ($REGEXP_CHARACTER) - [\w\s]+$/", $l, $m)) {
                        //line without weapon info, no corp and ship
                        //echo('  >>> "'.$m[8].'" from "'.$m[9].'" dealt '.$m[7].' damage flying unknown using unknown weapon'.PHP_EOL);
                        addDamage($involved,$m[7],$m[8]);
                } else if (preg_match("/^\[ (\d+).(\d+).(\d+) (\d+):(\d+):(\d+) \] \(combat\) (\d+) to ($REGEXP_CHARACTER).+$/", $l, $m)) {
                        //your own damage
                } else if (preg_match("/^\[ (\d+).(\d+).(\d+) (\d+):(\d+):(\d+) \] \(combat\) Your ($REGEXP_WEAPON) misses ($REGEXP_CHARACTER) completely .+$/", $l, $m)) {
                        //your own miss
                } else if (strpos($l,'misses you completely') !== FALSE) {
                        //missed me!
                } else if (strpos($l,'(notify)') !== FALSE) {
                        //notify, ignore
                } else if (strpos($l,'(info)') !== FALSE) {
                        //info, ignore
                } else if (strpos($l,'(question)') !== FALSE) {
                        //question, ignore
                } else if (strpos($l,'Warp scramble attempt') !== FALSE) {
                        //Warp scramble attempt, ignore
                } else {
                        echo("Parser error! Invalid line: ".PHP_EOL.$l.PHP_EOL);
                }
        }
        
        $sorted = array_orderby($involved, 'damage', SORT_DESC);
        
        foreach ($sorted as $row) {
                echo("\"${row['characterName']}\",\"${row['corporationTicker']}\",${row['damage']},\"${row['shipTypeName']}\",\"${row['weaponTypeName']}\"".PHP_EOL);
        }
?>
