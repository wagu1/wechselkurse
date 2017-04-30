#!/usr/bin/python3
# -*- coding: utf-8 -*-

import pymysql 
import requests
from   xml.etree import ElementTree as ET

"""
   Programm zur Abfrage der aktuellen Wechselkurse der 
   europäischen Zentralbank.
   Die Kurse werden in einer Datenbank gespeichert:
   [id | datum | waehrung | kurs]
   Referenzwährung ist der EURO
   
   *** Version PyMySQL-Treiber statt MySQLDB mit Python3 ***
   
   >W. Gussmann Juli 2016   
"""

# --------------------------------------------------------------------
# globale Variable
# --------------------------------------------------------------------
root       = 0
namespaces = 0
datum      = 0

# --------------------------------------------------------------------
# iKurse holen
# --------------------------------------------------------------------

def getKurse():
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

# --------------------------------------------------------------------
# Vor.: Datenbank ist geöffnet
# --------------------------------------------------------------------
def dateExists(zeit):
    sql = "SELECT datum FROM kurse where datum='%s';" % (zeit)
    print(sql)
    try:
        cursor = db.cursor()
        cursor.execute(sql)
        anzahl = cursor.rowcount
        if anzahl > 0:
#            print("schon vorhanden")
            return True
        else:
#            print("noch nicht vorhanden")
            return False
    except:
        print("ERROR dateExists")
        return False  

# --------------------------------------------------------------------
# gibt alle Daten in der Konsole tabellarisch aus
# --------------------------------------------------------------------
def getData():
    sql = "SELECT datum, waehrung, kurs FROM kurse ORDER BY datum DESC,waehrung"
    
    try:
        cursor = db.cursor()
        cursor.execute(sql)   # Execute the SQL command
        results = cursor.fetchall()
        for row in results:
            dat = row[0]
            w = row[1]
            k = row[2]
            print("%s   %-6s  %8.4f" % (dat, w, k ) )
    except:
        print( "Error: unable to fetch data")

# --------------------------------------------------------------------
# liefert zu einer Währung den passenden Kurs
# --------------------------------------------------------------------
def getKurs(waehrung):
    match = root.find('.//ex:Cube[@currency="{}"]'.format(waehrung.upper()), namespaces=namespaces)
    if match is not None:
        # print("Währung=" + waehrung + "  Kurs=" + match.attrib['rate'])
        return match.attrib['rate']
    else:
        print("kein Eintrag gefunden")
        return 0


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
    try:
        cursor = db.cursor()
        cursor.execute(sql)
        db.commit()
    except:
        db.rollback()
        print("ERROR insertData")


# --------------------------------------------------------------------
# Hauptprogramm
# --------------------------------------------------------------------

# alle Kurse einlesen
getKurse()

# Open database connection
db = pymysql.connect("localhost","username","password","wechselkurse" )

# wenn zu diesem Thema noch keine Kurse vorhanden sind,
# dann die aktuellen Kurse in DB eintragen
if ( not dateExists(datum) ):
    saveData(datum)
    
# Daten anzeigen
getData()

# disconnect from server
db.close()


