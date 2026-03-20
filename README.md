# Discord Clone Lite

Uproszczony klon Discorda zbudowany w **Symfony 7.3** z komunikacją w czasie rzeczywistym przez **Mercure**. Projekt posiada tryb demo z automatycznym resetem sesji i danych, co pozwala na bezpieczne testowanie bez trwałych zmian.

---

## Stos technologiczny

### Backend
- **PHP 8.3+** / **Symfony 7.3**
- **PostgreSQL 16**
- **Redis 7**
- **Mercure** - Server-Sent Events
- **Doctrine ORM 3.6**
- **Symfony Messenger**
- **Resend**

### Frontend
- **Twig 3** z komponentami UX
- **Hotwire** - Stimulus + Turbo
- **Bootstrap 5.3** + SASS
- **Asset Mapper**

### Testowanie i jakość kodu
- **PHPUnit 12**
- **Zenstruck Foundry**
- **Infection**
- **Psalm 6**
- **DAMA Doctrine Test Bundle**

---

## Funkcjonalności

### Zarządzanie użytkownikami
Rejestracja z weryfikacją e-mail, logowanie z rate limiterem oraz edycja profilu. Użytkownicy mogą wysyłać, akceptować i odrzucać zaproszenia do znajomych.

### Serwery
Tworzenie, edycja i usuwanie serwerów z systemem ról opartym na hierarchii pozycji. Właściciel serwera zarządza członkami - może ich wyrzucać, banować z określonym czasem trwania oraz wysyłać zaproszenia.

### Kanały
Tworzenie, edycja i usuwanie kanałów w ramach serwera z zapewnioną unikalnością nazw. Zmiany w kanałach są broadcastowane w czasie rzeczywistym do wszystkich członków serwera.

### Wiadomości
Wysyłanie wiadomości na kanałach ze strumieniowaniem w czasie rzeczywistym przez Mercure. Dostępna jest paginacja historii oraz śledzenie stanu odczytu kanałów.

### System uprawnień
Hybrydowe podejście ABAC/RBAC dla uprawnień kanałów i serwera. Uprawnienia są przypisywane do ról na poziomie serwera, z możliwością nadpisywania ich na poziomie poszczególnych kanałów. Autoryzacja realizowana jest przez dedykowane Security Voters.

### Powiadomienia
Powiadomienia w czasie rzeczywistym przez Mercure obejmujące zaproszenia na serwer, akceptacje zaproszeń, wyrzucenia, bany, zaproszenia do znajomych i usunięcia serwerów. Możliwość oznaczania jako przeczytane pojedynczo lub zbiorczo.

### Sesje z ustalonym czasem życia
Wykorzystujemy własny `FixedLifetimeRedisSessionHandler`, który przechowuje sesję w Redis z TTL oraz dodatkowy znacznik określający rzeczywisty czas wygaśnięcia. Dzięki temu sesja wygasa po określonym czasie, niezależnie od aktywności użytkownika.
### Monitor sesji
Proces nasłuchujący na zdarzenia wygaśnięcia kluczy Redis publikuje powiadomienie Mercure i czyści powiązane dane.
### Tryb demo z automatycznym resetem
Komenda czyszcząca wszystkie sesje Redis, odświeżająca fixture’y bazy i planująca kolejny reset, co pozwala na nieprzerwaną demonstrację aplikacji.
### Hotwire zamiast SPA
Używam Turbo Streams i Stimulus, aby uzyskać interaktywny UX bez pełnego SPA. Aktualizacje przychodzą jako fragmenty HTML wstrzykiwane do DOM.
### UUID v7 jako klucze główne
Wszystkie encje mają UUID v7, co eliminuje problem sekwencyjnych ID i umożliwia wykorzystanie timestampu w logice biznesowej.

---

## Dane do testowania demo

Fixture'y tworzą gotowe środowisko z 5 serwerami, kanałami, rolami i wiadomościami.

### Konta demo

| Rola          | Login              | Hasło       |
|---------------|--------------------|-------------|
| Admin (owner) | `admin@demo.test`  | `admin123`  |
| Członek       | `member@demo.test` | `member123` |

**[Strona Demo](https://matnas.pl)**
