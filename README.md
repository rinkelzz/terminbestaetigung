# AutoNotify – Terminbestätigung via WhatsApp

AutoNotify ist ein Micro-SaaS-Konzept für Kleinbetriebe, die Buchungen über eine bestehende Website oder ein WordPress-Plugin erhalten. Das Projekt demonstriert einen möglichen technischen Stack aus PHP, Node-RED und der WhatsApp Cloud API, um vollautomatische Terminbestätigungen zu versenden.

## Komponenten

### 1. Webhook (PHP)
- Endpunkt `backend/notify.php`
- Erwartet JSON-Daten (`name`, `datum`, `phone`, optional `template` und `extra`)
- Rendert Platzhalter in einer Vorlage und ruft den lokalen Node-RED-Flow auf
- Rückgabe: `{ "status": "ok" }` oder ein Fehlerobjekt

### 2. Node-RED Flow
- Datei: `node-red/whatsapp-flow.json`
- HTTP-In Node lauscht auf `/whatsapp`
- Function Node baut WhatsApp-Cloud-API Payload auf
- HTTP Request Node sendet POST an `https://graph.facebook.com/v18.0/{phone_id}/messages`
- Flow-Kontext benötigt `whatsapp_token` und `phone_id`

### 3. WordPress Plugin
- Ordner: `wordpress-plugin/autonotify`
- Erstellt Admin-Seite zur Eingabe von Betriebsname, WhatsApp-Nummer und Nachrichtenvorlage
- Generiert individuellen Webhook-Link (`/wp-json/autonotify/v1/notify?shopid=XYZ`)
- REST-Endpoint leitet Buchungsdaten an den PHP-Webhooks weiter

## Einrichtung

1. **WhatsApp Cloud API aktivieren**
   - Bei [developers.facebook.com](https://developers.facebook.com/) Projekt anlegen
   - `phone_id` und `access_token` notieren

2. **Node-RED Flow importieren**
   - Node-RED starten (`node-red` auf Server oder VPS)
   - Flow aus `node-red/whatsapp-flow.json` importieren
   - `whatsapp_token` und `phone_id` per Change-Node oder Umgebungsvariable setzen

3. **PHP Webhook bereitstellen**
   - `backend/notify.php` auf Webserver/Hosting hochladen
   - `NODERED_URL` per Environment auf Node-RED-Endpoint zeigen lassen (optional)

4. **WordPress Plugin installieren**
   - Ordner `wordpress-plugin/autonotify` zippen und in WordPress hochladen
   - Einstellungen ausfüllen und Webhook-Link kopieren

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
- [ ] Node-RED Flow deployen
- [ ] PHP Webhook veröffentlichen
- [ ] WordPress-Demo-Seite erstellen
- [ ] Lokale Betriebe für Beta-Test ansprechen (30 Tage gratis)

