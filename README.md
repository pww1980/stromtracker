# StromTracker

Webseite zum Tracken von Stromverbrauch und Solarleistung mit Auswertungen und Charts.

## Technologien
- **PHP 8.x** (Backend, Auswertungen)
- **MySQL** (Datenspeicherung)
- **Bootstrap 5** (Responsive UI)
- **Chart.js** (Diagramme)
- **Vanilla JavaScript** (Interaktivität)

## Installation

### 1. Webserver einrichten
Kopiere die Dateien in dein Webserver-Verzeichnis (z.B. `/var/www/html/stromtracker`).

### 2. Datenbank konfigurieren
Passe die Datenbankverbindung in `includes/config.php` an oder setze Umgebungsvariablen:
```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=stromtracker
DB_USER=dein_benutzer
DB_PASS=dein_passwort
```

### 3. Setup aufrufen
Rufe `http://deine-domain.de/setup.php` auf und folge den Schritten:
1. Tabellen werden automatisch erstellt
2. Passwort für die Anmeldung festlegen
3. Initialen Strompreis eingeben

### 4. Loslegen
- Melde dich unter `/login.php` an
- Gib erste Ablesedaten unter **Eingabe** ein
- Sieh dir das **Dashboard** für Auswertungen an

## Funktionen

### Dateneingabe
- Datum der Messung
- Absoluter Zählerstand (kWh)
- Produzierter Strom (Jahr gesamt, aus Wechselrichter)
- Produzierter Strom (Tagesertrag)
- Optionale Notizen

### Dashboard-Auswertungen (YTD & Hochrechnung)
| Kennzahl | Berechnung |
|---|---|
| Verbrauch laut Zähler YTD | Aktueller Zählerstand - Zählerstand 1.1. |
| Produzierter Strom YTD | Direkt aus Wechselrichter-Wert |
| Gesamtverbrauch YTD | Zählerverbrauch + Solar |
| Ø Verbrauch/Tag | Zählerverbrauch / Tage |
| Ø Solar/Tag | Solar YTD / Tage |
| Kosten YTD | Zählerverbrauch × Strompreis |
| Ersparnis YTD | Solar YTD × Strompreis |
| Hochrechnung Verbrauch | Ø/Tag × 365 |
| Hochrechnung Solar | Ø Solar/Tag × 365 |
| Hochrechnung Kosten | Hochr. Verbrauch × Preis |
| Hochrechnung Ersparnis | Hochr. Solar × Preis |

### Monatliche Auswertung
Alle Kennzahlen auch pro Monat, berechnet aus den Messwerten.

### Charts
- Liniendiagramm: Verbrauch & Solar pro Monat
- Donut-Chart: Verteilung Netz vs. Solar YTD
- Umschaltbar zwischen Linien- und Balkendiagramm

### Mehrjahres-Support
- Daten über mehrere Jahre
- Jahresauswahl im Dashboard
- Filterung im Verlauf

## Sicherheit
- Passwortschutz (bcrypt)
- PHP-Sessions
- SQL Injection Prevention (PDO prepared statements)
- XSS Protection (htmlspecialchars)
- Security Headers via .htaccess
