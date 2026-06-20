# CLAUDE.md

Wytyczne dla pracy nad projektem curvia.kwasniak.org.

Uniwersalne zasady współpracy są w `FOUNDATION.md` i obowiązują zawsze. Ten projekt
**nie jest API**, więc zapisy API z FOUNDATION (sekcja 4, kształt odpowiedzi API,
paginacja) nie mają zastosowania. Poniżej tylko specyfika i rozstrzygnięte decyzje
tego projektu (mają pierwszeństwo w sprawach projektowych).

## Komunikacja
- Rozmawiaj ze mną **po polsku**.
- Kod, nazwy klas, metod, zmiennych, endpointów oraz commit messages pisz **po angielsku**.

## Środowisko
- Runtime to **PHP 8.5** — uruchamiaj artisan i composer jawnie przez `php8.5`
  (domyślny `php` w PATH to 8.3). Composer: `php8.5 /usr/local/bin/composer`.
- Hosting współdzielony (LiteSpeed). Document root domeny wskazuje na `public`.
- Baza: **MySQL/MariaDB** (`DB_CONNECTION=mysql`). Sesje, cache i kolejki działają
  przez bazę (`database`).

## Framework
- **Laravel 13** (MVC, monolit). Konfiguracja aplikacji wyłącznie w
  `bootstrap/app.php` — nie ma plików `Kernel.php` ani `RouteServiceProvider`.
  Routing, middleware i obsługa wyjątków rejestruje się tam.
- Wyjątki zwracają JSON tylko dla ścieżek `api/*`. Health-check pod `/up`.

## Modele i baza danych
- ORM: **Eloquent**.
- Konfiguruj modele **atrybutami PHP** w stylu Laravel 13: `#[Fillable([...])]`,
  `#[Hidden([...])]` — nie używaj właściwości `$fillable` / `$hidden`.
  Castowanie przez metodę `casts()`.
- Zmiany schematu **tylko przez migracje** (`database/migrations`).
  Dane testowe i startowe przez Factories i Seeders.

## Frontend
- **Blade** + **Tailwind CSS 4** (przez `@tailwindcss/vite`) + **Vite 8**.
- Brak frameworka JS (Vue/React/Inertia/Livewire) — domyślnie renderowanie
  serwerowe. Nie dodawaj ich bez ustalenia.
- Tailwind konfiguruje się w `resources/css/app.css` przez `@theme`
  (nie ma `tailwind.config.js`).

## Testy
- **PHPUnit 12** (nie Pest). Zestawy `tests/Unit` i `tests/Feature`.
- Testy biegną na **SQLite in-memory** (konfiguracja w `phpunit.xml`) —
  nie dotykaj prawdziwej bazy w testach.
- Uruchamianie: `php8.5 artisan test`.

## Styl kodu
- Formatowanie przez **Laravel Pint** (`php8.5 vendor/bin/pint`) — to arbiter stylu.
- PHP z typowaniem argumentów i zwracanych wartości.

## Konwencje projektu (rozstrzygnięte)
- **Tylko język polski** — `APP_LOCALE=pl`. Nie utrzymujemy drugiego locale.
  Stringi do użytkownika idą przez warstwę tłumaczeń.
- **Walidacja wyłącznie w Form Requestach** — bez inline `$request->validate()`.
  Kontrolery cienkie, logika w serwisach.
- **Edycja zawsze przez POST** (ID w URL, np. `POST /res/{id}`); upload tylko POST.
- **Logi dzienne** — `LOG_CHANNEL=daily` (rotacja per dzień).
- Stałe biznesowe w `config/`. Parytet `.env.example` z `.env`.

## Stan projektu (kontekst)
- Świeży szkielet — brak własnego kodu domenowego, brak scaffoldingu auth,
  brak integracji zewnętrznych. Nie zakładaj istnienia wzorców, których nie ma.
