<?php

  /*
   * Klasse für Datenbankverbindungen
   * mit der Bibliothek d3js.org
   * Die Daten werden aus einer Datenbank gelesen
   *
   * * Tabelle:  kurse (id*, datum, Waehrung, kurs)
   *
   *
   * April 2017
   */


class DB {
    private $connected = false;
    private $hostname = "localhost";      // anpassen
    private $username = "username";       // anpassen
    private $password = "password";       // anpassen
    private $db       = "wechselkurse";   // anpassen
    
    function __construct($db, $u, $pw) {
        $this->db = $db;
        $this->username = $u;
        $this->password = $pw;
    }
    
    
    function getDBName() {
        return $this->dbname;
    }
    
    // Liefert ein Array.
    // Jeder Eintrag stelt eine Datenzeile dar, die ein assoziatives Array ist.
    function get_Query($sql) {
        try {
            $dbh = new PDO("mysql:host=" . $this->hostname.";dbname=".$this->db, $this->username, $this->password);
            $statement = $dbh->prepare($sql);
            $statement->execute(); 
            $dbh = null;   // ** close the database connection *** /
        }
        catch(PDOException $e) {
            echo $e->getMessage();
        }
        return $statement->fetchAll(PDO::FETCH_ASSOC);        
    }
    
    // liefert Abfrage im JSON-Format 
    function getJSON_Data($sql) {
        $result = $this->get_Query($sql);
        $json_data = json_encode($result);
        return  $json_data;
    }
    
    
    function getQueryAsHTMLDropDownBox($sql, $link, $default, $dropdownName) {
        $result = $this->get_Query($sql);
        $html = "";
        if ($result != null) {
            $html .= "<select name='$dropdownName'  onchange=\"window.open(this.options[this.selectedIndex].value,'_top')\" >\n";
            foreach ($result as $i => $value) {
                $row = $result[$i];         // Ein Datensatz (Hasharray)
                $k = array_values($row)[0]; // Wert ist immer in Spalte 0
                if ( $k == $default ) { $sel="selected"; }
                else                      { $sel=""; }
                $html .= "  <option VALUE='$link$k' $sel>$k</option>\n";
            }
            $html .= "</select>\n";
            return $html;
        }
        else {
            return "Error beim erstellen der Dropdown-Box.";
        }   
    }
    

    function getQueryAsTEXTTable($sql,$format) {
        $result = $this->get_Query($sql);
        $html  = "<div style='border:1px solid gray;padding 10px; width:350px;'>\n";
        $html .= "<pre style='padding-left:10px;'>\n";
        if ($result != null) {
            foreach($result as $zeile) {
                $html .= vsprintf($format, $zeile) . "\n";
            }
            $html .= "</pre>\n";
            $html .= "</div>\n";
        }
        else $html = "error";
        return $html;
    }
    
    
    
    function getQueryAsHTMLTable($sql) {
        $result = $this->get_Query($sql);
        $styleHeader   = "style='background-color: #4D72D6; color:white;'";
        $styleRowLow   = "style='background-color:white;'";
        $styleRowHigh  = "style='background-color:#E1EFF5;'";
        
        if ($result != null) {
            $html  = "<table style='border: 1px solid gray; padding:2'>\n";
            // Tasbellenüberschrift
            $zeile = $result[0];
            $html .= "<tr $styleHeader>";
            foreach($zeile as $k => $v) {
                $html .= "<td>$k</td>" ;
            }
            $html .= "</tr>\n";
            
            // Tabelleninhalt
            $high = false;
            foreach($result as $zeile) {
                if ($high)  { $st = $styleRowHigh; $high=false; }  
                else        { $st = $styleRowLow;  $high=true; }
                $html .= "<tr $st>";
                foreach($zeile as $k => $v) {
                    $html .= "<td>$v</td>" ;
                }
                $html .= "</tr>\n";
            }
            $html .= "</table>\n";
        }
        else $html = "error";
        return $html;
    }
    
    
}

/*
 * ------------------------------------------------------
 * Testder Methoden
 * ------------------------------------------------------
$db = new DB("euro","dollar","wechselkurse");

echo "Datenbank   " . $db->getDBName() . "\n";

echo "JSON \n";
echo $db->getJSON_Data("SELECT * from kurse LIMIT 2;") . "\n";

echo "PDO \n";
//print_r ( $db->get_Query("SELECT * from kurse LIMIT 2;") );

echo "getQueryAsDropDownBox \n";
//print_r ($db->getQueryAsHTMLDropDownBox("SELECT DISTINCT(waehrung) from kurse ORDER BY waehrung", "https://server.de/show.php?waehrung=", "JPY", "myDDF") );

echo "getQueryAsTEXTTable \n";
//print_r ($db->getQueryAsTEXTTable("SELECT * from kurse LIMIT 4","%-4s %-12s %-5s %10f") );

echo "getQueryAsHTMLTable \n";
print_r ($db->getQueryAsHTMLTable("SELECT * from kurse LIMIT 4") );
*/


?>
