# wechselkurse

Die europäische Zentralbank veröffentlicht jeden Werkag gegen 16.30 Uhr die Tageskurse wichtiger Währungen. Diese XML-Datei kann heruntergeladen und ausgewertet werden.

Das Projekt verwendet für die Datenauswertung die Programmiersprache python. Die Daten werden in einer MySQL-Datenbank gespeichert. Für die Darstellung wird ein Webserver (Apache2) verwendet. Die grafische Anzeige basiert auf https://d3js.org/d3.v3.min.js. 

![Beispielausgabe](img/kursentwicklung.png)

## Python-Programm

Vorbemerkung: Das Pythonprogramm läuft hier auf demselben Server wie MySQL. 

Zuerst müssen die aktuellen Kurse von der Seite http://www.ecb.int/stats/eurofxref/eurofxref-daily.xml abgerufen werden. Außerdem wird das aktuelle Datum ausgelesen.

```
import MySQLdb
import requests
from xml.etree import ElementTree as ET

# --------------------------------------------------------------------
# Globale Variable
# --------------------------------------------------------------------
root = 0
namespaces = 0
datum = 0

# --------------------------------------------------------------------
# Kurse abrufen
# --------------------------------------------------------------------
def getValues():
    global root
    global namespaces
    global datum
    r = requests.get('http://www.ecb.int/stats/eurofxref/eurofxref-daily.xml', stream=True)
    tree = ET.parse(r.raw)
    root = tree.getroot()
    namespaces = {'ex': 'http://www.ecb.int/vocabulary/2002-08-01/eurofxref'}

    zeit = root.find('.//ex:Cube[@time]', namespaces=namespaces)
    datum = zeit.attrib['time']
    print("aktuelles Datum = " + datum)
```

Nun kann für Testzwecke der Kurs einer Währung mit der Funktion getKurs(waehrung) ausgelesen und angezeigt werden:
```
# --------------------------------------------------------------------
# liefert zu einer Währung den passenden Kurs
# --------------------------------------------------------------------
def getKurs(waehrung):
    match = root.find('.//ex:Cube[@currency="{}"]'.format(waehrung.upper()), namespaces=namespaces)
    if match is not None:
        print("Währung=" + waehrung + "  Kurs=" + match.attrib['rate'])
        return match.attrib['rate']
    else:
        print("kein Eintrag gefunden")
        return 0
```

Im nächsten Schritt werden alle Kurse in eine Datenbank eingetragen. Dazu muss eine Verbindung zu einer Datenbank hergestellt werden. Die hier verwendete MySQL-Datenbank hat eine Tabelle kurse mit folgender Struktur:
```
+----------+--------------+------+-----+---------+----------------+
| Field    | Type         | Null | Key | Default | Extra          |
+----------+--------------+------+-----+---------+----------------+
| id       | int(11)      | NO   | PRI | NULL    | auto_increment |
| datum    | date         | YES  |     | NULL    |                |
| waehrung | varchar(10)  | YES  |     | NULL    |                |
| kurs     | decimal(8,4) | YES  |     | NULL    |                |
+----------+--------------+------+-----+---------+----------------+
```

Verbindung zu Datenbank wechselkurse:

```
# open database connection
db = MySQLdb.connect("localhost",<username>,<password>,"wechselkurse" )
```

Alle Kurse eines Tages in Datenbank eintragen 

```
# --------------------------------------------------------------------
# fügt alle Kurse in die Datenbank ein
# Vor.: datum gibt es noch nicht
# --------------------------------------------------------------------
def saveData(datum):
    for cube in root.findall('.//ex:Cube[@currency]', namespaces=namespaces):
        insertData(datum, cube.attrib['currency'], cube.attrib['rate'])

# --------------------------------------------------------------------
# fügt einen neuen Datensatz der Tabelle kurse hinzu
# wenn kurs=0 ist, dann ist nichts passiert
# --------------------------------------------------------------------
def insertData(zeit, waehrung, kurs):
    if kurs == 0:
        return

    # Prepare SQL query to INSERT a record into the database.
    sql = "INSERT INTO kurse (datum,waehrung,kurs) \
           VALUES ('%s', '%s', '%s' )" %  (zeit, waehrung, kurs)
    print(sql)
    try:
        cursor = db.cursor()
        cursor.execute(sql)
        db.commit()
        print("Kurs hinzugefügt")
    except:
        # Rollback in case there is any error
        db.rollback()
        print("ERROR insertData")
```

