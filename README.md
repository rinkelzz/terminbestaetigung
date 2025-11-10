# AutoNotify – Terminbestätigung via WhatsApp

AutoNotify ist ein Micro-SaaS-Konzept für Kleinbetriebe, die Buchungen über eine bestehende Website oder ein WordPress-Plugin erhalten. Das Projekt demonstriert einen möglichen technischen Stack aus PHP und der WhatsApp Cloud API, um vollautomatische Terminbestätigungen zu versenden – ohne zwingend Node-RED einsetzen zu müssen.

## Komponenten

### 1. Webhook (PHP)
- Endpunkt `backend/notify.php`
- Erwartet JSON-Daten (`name`, `datum`, `phone`, optional `template` und `extra`)
- Erstellt daraus den Textinhalt und ruft direkt die WhatsApp Cloud API auf
- Nutzt Umgebungsvariablen `WHATSAPP_ACCESS_TOKEN`, `WHATSAPP_PHONE_ID` und optional `WHATSAPP_API_VERSION`
- Unterstützt zusätzliche Platzhalter wie `{{business_name}}`, die vom WordPress-Plugin als `extra`-Felder mitgesendet werden
- Rückgabe: `{ "status": "ok", "response": {...} }` oder ein Fehlerobjekt

### 2. WordPress Plugin
- Ordner: `wordpress-plugin/autonotify`
- Erstellt Admin-Seite zur Eingabe von Betriebsname, WhatsApp-Nummer, Nachrichtenvorlage und Backend-URL (`notify.php`)
- Generiert individuellen Webhook-Link (`/wp-json/autonotify/v1/notify?shopid=XYZ`)
- REST-Endpoint leitet Buchungsdaten an den PHP-Webhook weiter

### 3. Node-RED Flow (optional)
- Datei: `node-red/whatsapp-flow.json`
- Kann importiert werden, falls du statt PHP lieber Node-RED als Integrations-Layer verwendest
- Falls du ihn nicht nutzt, kannst du diesen Ordner ignorieren

## Einrichtung

1. **WhatsApp Cloud API aktivieren**
   - Bei [developers.facebook.com](https://developers.facebook.com/) Projekt anlegen
   - `phone_id` und `access_token` notieren

2. **PHP Webhook auf Keyhelp/V-Server installieren**
   - Datei `backend/notify.php` nach `/var/www/<deine-domain>/web/` (oder passenden Unterordner) kopieren
   - In Keyhelp im gewünschten vHost einen Unterordner „backend“ anlegen und die Datei dort platzieren
   - Über Keyhelp → *Domain-Einstellungen* → *Verzeichnisschutz/Umgebungsvariablen* die Variablen setzen:
     - `WHATSAPP_ACCESS_TOKEN` – dauerhaftes Token aus der WhatsApp Cloud API
     - `WHATSAPP_PHONE_ID` – die Phone-ID deiner WhatsApp Business Nummer
     - Optional `WHATSAPP_API_VERSION` (z. B. `v18.0`)
     - Optional `ALLOWED_SHOP_IDS` – kommagetrennte Liste erlaubter `shopid`-Werte aus dem WordPress-Plugin
   - HTTPS aktivieren (Let’s Encrypt) und URL notieren, z. B. `https://deinedomain.de/backend/notify.php`

3. **WordPress Plugin installieren**
   - Ordner `wordpress-plugin/autonotify` zippen und in WordPress hochladen
   - Einstellungen ausfüllen und Webhook-Link kopieren

4. **Webhook-Ziel im Plugin hinterlegen**
   - In den AutoNotify-Einstellungen die URL zu deiner `notify.php` (siehe Schritt 2) eintragen
   - Backend meldet „Webhook-Ziel ist konfiguriert“
   - Notiere die generierte `shopid` und trage sie – falls `ALLOWED_SHOP_IDS` gesetzt ist – im Server als erlaubten Wert ein

5. **Integration testen**
   - Buchung in der bestehenden Website auslösen
   - Prüfen, ob WhatsApp-Nachricht mit personalisiertem Text eingeht

## Monetarisierungsvorschlag

| Modell | Beschreibung | Ertrag |
| ------ | ------------ | ------ |
| Abo | 9 €/Monat pro Betrieb (bis 200 Nachrichten) | Wiederkehrender Umsatz |
| Einrichtung | 49 € einmalig (Logo + Textvorlage) | Direkter Umsatz |
| Upsell | +10 € für Google Kalender oder SumUp Integration | Zusatzumsatz |

Beispiel: 10 Betriebe ⇒ ca. 590 € monatlich bei geringem Aufwand.

## Erweiterungsideen
- SumUp-Zahlungsbestätigungen automatisieren
- WhatsApp-Benachrichtigung bei neuen WordPress-Buchungen
- Mehrsprachige Templates (DE/EN)

## To-Do Checkliste
- [ ] WhatsApp Cloud API aktivieren
- [ ] PHP Webhook auf dem Keyhelp-Server veröffentlichen
- [ ] WordPress-Plugin konfigurieren (inkl. notify.php URL)
- [ ] Optional: Node-RED-Flow testen, falls benötigt
- [ ] Lokale Betriebe für Beta-Test ansprechen (30 Tage gratis)

