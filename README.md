# cn5006-university-system
University Management System for CN5006_1

Web εφαρμογή “University System” για το μάθημα Web & Mobile Applications Development (CN5006_1).  
Υλοποίηση σε PHP + MySQL/MariaDB με HTML/CSS/JavaScript και χρήση Leaflet.js για τον χάρτη campus.

---

1) Απαιτήσεις (Requirements)

Για να τρέξει το project τοπικά χρειάζεσαι:

- PHP 8.x
- MySQL ή MariaDB
- Ένα local server environment:
  - XAMPP (Windows / macOS / Linux) ή
  - MAMP (macOS) ή
  - WAMP (Windows)
- Προαιρετικά:
  - phpMyAdmin (συνήθως υπάρχει ήδη στο XAMPP/MAMP/WAMP)

---

2) Δομή Project (Repository Structure)

cn5006-university-system/
├── prototype/          # Κύρια εφαρμογή (PHP/HTML/CSS/JS)
├── database/           # Export βάσης (SQL)
│   └── university.sql
├── README.md

3) Εγκατάσταση στο PC (Step-by-step Setup)

Βήμα 1: Κατέβασε το project
Επιλογή Α (από GitHub):
Download ZIP από GitHub και κάνε extract.
Επιλογή Β (με git clone):
git clone https://github.com/USERNAME/cn5006-university-system.git
Βήμα 2: Βάλε το project στο σωστό φάκελο του server
Αν χρησιμοποιείς XAMPP (Windows/macOS)
Μετακίνησε τον φάκελο prototype εδώ:
C:\xampp\htdocs\prototype (Windows)
/Applications/XAMPP/htdocs/prototype (macOS)
Αν χρησιμοποιείς MAMP (macOS)
/Applications/MAMP/htdocs/prototype
Αν χρησιμοποιείς WAMP (Windows)
C:\wamp64\www\prototype
***Σημαντικό: Ο φάκελος που πρέπει να “ανοίγει” ο browser είναι ο prototype/***
Βήμα 3: Δημιουργία Βάσης Δεδομένων + Import SQL
Άνοιξε phpMyAdmin:
XAMPP: http://localhost/phpmyadmin
MAMP: http://localhost:8888/phpMyAdmin (συνήθως)
Δημιούργησε νέα βάση π.χ.:
Όνομα: university
Πήγαινε Import
Διάλεξε το αρχείο:
database/university.sql
Πάτησε Go

4) Ρύθμιση .env 
Αν το project σου δεν έχει .env, πρεπει να το προσθέσεις.
Βήμα 1: Δημιούργησε αρχείο .env μέσα στον φάκελο prototype/
Δηλαδή:
prototype/.env
Βήμα 2: Βάλε μέσα αυτό (και άλλαξε τιμές αν χρειάζεται)
DB_HOST=localhost
DB_PORT=3306
DB_NAME=university
DB_USER=
DB_PASS=

5) Ρύθμιση σύνδεσης DB στο PHP (db.php)
Στον φάκελο:
prototype/backend/db.php βεβαιώσου ότι το db.php διαβάζει τις μεταβλητές από .env.

6) Έλεγχος σύνδεσης με τη Βάση Δεδομένων
(http://localhost/prototype/backend/test_db.php)
Αν η σύνδεση είναι σωστή, θα εμφανιστεί μήνυμα επιτυχούς σύνδεσης με τη βάση δεδομένων.

7) Εκτέλεση Seeder (Αρχικοποίηση Δεδομένων)
Για την αρχικοποίηση της βάσης δεδομένων με βασικά δεδομένα (π.χ. ρόλους, δοκιμαστικούς χρήστες, μαθήματα), παρέχεται αρχείο seeder.
Βήμα 2: Εκτέλεση Seeder
Από τον φάκελο (prototype/backend), εκτελέστε:
php db_seeder.php
Το script θα εισάγει τα απαραίτητα αρχικά δεδομένα στη βάση δεδομένων, ώστε η εφαρμογή να είναι έτοιμη προς χρήση.

9) Κωδικοί εγγραφής ρόλων (Assignment Codes)
Φοιτητές: STUD2025
Καθηγητές: PROF2025
Αν ο κωδικός είναι λάθος, η εγγραφή απορρίπτεται.
