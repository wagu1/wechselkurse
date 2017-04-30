# Anzeigen der wechselkurse


Die Daten werden in aus einer MySQL-Datenbank gelesen und in Tabellenform angezeigt. Für eine Währung wird die Kursentwicklung grafisch veranschaulicht. Die Darstellung basiert auf der Bibliothek https://d3js.org/d3.v3.min.js. 


## Datenbankroutinen

Für den Datenbankzugriff wird die Klasse db.php verwendet. Der Konstruktor
verlangt nach dem Namen der Datenbank ("wechselkurse") sowi den Benutzerdaten
(username+password).

Folgende Schnittstellen stehen zur Verfügung:

```
class DB {
    
    function getDBName() // liefert den Namen der Datenbank
    
    // Liefert ein Array.
    // Jeder Eintrag stelt eine Datenzeile dar, die ein assoziatives Array ist.
    // wird intern verwendet
    function get_Query($sql) 
    
    // liefert Abfrage im JSON-Format 
    function getJSON_Data($sql) 
    
    // Für eine sql-Abfrage mit einer Spalte wird der Quelltext für 
    // eine HTML-Dropbox generiert.
    // - sql-Abfrage
    // - Zu jeder Option ein Link erzeugt
    // - es kann ein vordefinierter Wert ausgewählt werden
    // - der Name der Select-Box 
    function getQueryAsHTMLDropDownBox($sql, $link, $default, $dropdownName) 
    
    // Eine SQL-Abfrage wird in einer Texttabelle erzeugt
    // Das Format der Tabelle muss übergeben werden
    function getQueryAsTEXTTable($sql,$format) 

    // Eine SQL-Abfrage wird als HTML-Tabelle zurückgegeben
    function getQueryAsHTMLTable($sql)         
}
```

Beispielaufrufe:

```
/*------------------------------------------------------ */

echo $db->getJSON_Data("SELECT * from kurse LIMIT 2;") . "\n";
print_r ( $db->get_Query("SELECT * from kurse LIMIT 2;") );

echo "getQueryAsDropDownBox \n";
print_r ($db->getQueryAsHTMLDropDownBox("SELECT DISTINCT(waehrung) from kurse ORDER BY waehrung", "https://server.de/show.php?waehrung=", "JPY", "myDDF") );

echo "getQueryAsTEXTTable \n";
print_r ($db->getQueryAsTEXTTable("SELECT * from kurse LIMIT 4","%-4s %-12s %-5s %10f") );

echo "getQueryAsHTMLTable \n";
print_r ($db->getQueryAsHTMLTable("SELECT * from kurse LIMIT 4") );

/*------------------------------------------------------ */
```

## Grafische Anzeige des Kursverlaufs

Für die Bibliothek https://d3js.org/d3.v3.min.js wird eine angepasste Datei
chart.js verwendet, die das Format auf deutsch umstellt und im Wesentlichen
eine Methode enthält, die für die Anzeige verwendet wird.

```
// ************************************************************
// Funktion zur Erzeugung einer Grafik (Linie)
// Die Daten müssen zwei Spalten enthalten: (zeit | value)
//
// Parameter:
//  o titel    Diagrammtitel oben
//  o sql      wird z.Zt. nicht genutzt
//  o textX    Beschrfitung der x-Achse
//  o textY    Beschriftung der y-Achse
//  o breite   Breite des Diagramms
//  o hoehe    Höhe des Diagramms
//  o data     Daten im JSON-Format
//  o scaleY   0=automatisch skalieren, 1=Prozent 0..100
//  o units    Einheiten für die Tooltipps
//  o id       id in css-Klasse
//             #id  ............ Breite, Rand/farbe)
//             #id .title ...... Farbe, Größe des Titels
//             #id .y .......... Farbe der y-Beschriftung
//             #id .linecolor .. Linienfarbe
//             #id .tooltip .... Frarbe Tooltipps
// ***********************************************************

function showDiagram1Line(titel, sql, textX, textY, breite, hoehe, data, scaleY, units, id) 
```

Beispielaufruf (i"waehrung" wird als Parameter beim Aufruf mitgegen und muss
        zuvor mit $_GET oder $_POST gelesen werden):
```
<script>
    <?php 
        $sqlW = "SELECT datum as zeit, kurs as value from kurse WHERE waehrung='$waehrung' ORDER BY datum DESC";
        $json_data = $db->getJSON_Data($sqlW);
        echo "data=".$json_data.";" 
    ?>
    showDiagram1Line("Währung","","Zeit", "", 600, 300, data, 0, "<?php echo $waehrung; ?><br>=1 Euro", "#graphCurrency");
</script>
```



