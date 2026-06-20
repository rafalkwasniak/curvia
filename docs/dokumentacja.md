# Curvia - Specyfikacja MVP

## Cel projektu

Automatyczne wyszukiwanie zagranicznych newsów motoryzacyjnych (głównie motocykle), generowanie polskich postów przy pomocy AI, tworzenie grafik i publikacja na Facebook Fanpage "Curvia".

Projekt ma charakter hobbystyczny i testowy.

Priorytet:

1. Motocykle
2. Samochody
3. Prosta administracja
4. Niskie koszty utrzymania
5. Stopniowa automatyzacja

---

# Architektura

```text
RSS / Atom
    ↓
Laravel Fetcher
    ↓
Baza danych
    ↓
AI (DeepSeek)
    ↓
Polski post
    ↓
Generator grafiki
    ↓
Panel akceptacji
    ↓
Facebook Fanpage
```

---

# Technologie

## Backend

- Laravel 13
- PHP 8.4+
- MySQL / MariaDB
- Laravel Queue
- Laravel Scheduler

## AI

### Teksty

DeepSeek API

Zastosowanie:

- streszczenie artykułu
- tłumaczenie na PL
- generowanie posta Facebook
- generowanie prompta do grafiki

### Grafiki

Preferowane:

- Flux API

Alternatywnie:

- Stable Diffusion XL API
- OpenAI GPT Image

---

# Paczki Laravel

## RSS / Atom

```bash
composer require laminas/laminas-feed
```

lub

```bash
composer require willvincent/feeds
```

Preferowany:

```text
laminas-feed
```

---

## Obróbka grafik

```bash
composer require intervention/image
```

Zastosowanie:

- dodawanie logo
- dodawanie nagłówków
- tworzenie okładek Curvia

---

## HTTP API

Laravel posiada:

```php
Http::post(...)
```

więc dodatkowa paczka nie jest potrzebna.

---

# Źródła RSS

## Motocykle

- RideApart
- Cycle World
- Motorcycle.com
- Visordown
- MCN

## Samochody

- Motor1
- Carscoops
- Autoblog
- Top Gear

---

# Etap 1 - Pobieranie newsów

Komenda:

```bash
php artisan curvia:fetch-news
```

Uruchamiana:

```php
everyHour()
```

Pobiera:

- tytuł
- opis
- URL
- datę publikacji
- źródło

Zapisuje do bazy.

---

# Tabela news_articles

```sql
id
source_name
source_url
title
description
published_at

ai_summary
ai_post

image_prompt
image_path

status

facebook_post_id

created_at
updated_at
```

---

# Statusy

```text
new
generated
waiting_review
approved
published
rejected
```

---

# Duplikaty

Przed dodaniem:

sprawdzać

```php
source_url
```

Jeżeli istnieje:

```text
pomijaj
```

---

# Etap 2 - Generowanie treści

Komenda:

```bash
php artisan curvia:generate-posts
```

Dla rekordów:

```sql
status = new
```

AI otrzymuje:

- tytuł
- opis
- źródło

AI zwraca:

- streszczenie
- polski post Facebook
- prompt grafiki

Status:

```sql
generated
```

---

# Styl postów

Nie pisać jak portal informacyjny.

Przykład:

ŹLE:

```text
Honda zaprezentowała nowy model...
```

DOBRZE:

```text
Nowa Honda wygląda naprawdę ciekawie.

Producent właśnie ujawnił pierwsze informacje o modelu,
który może sporo namieszać w swojej klasie.

Co sądzicie o takim kierunku?

#Honda #Motocykle
```

Post powinien zachęcać do komentarzy.

---

# Źródło informacji

Na końcu każdego posta:

```text
────────────
Źródło: RideApart
```

Bez linków.

Bez URL.

Tylko nazwa serwisu.

---

# Etap 3 - Generowanie grafiki

Dla każdego posta:

AI tworzy prompt.

Przykład:

```text
Professional motorcycle magazine cover.
New Yamaha R9.
Dynamic road.
Premium photography.
Vertical composition.
Space for headlines.
```

Generator:

```text
Flux API
```

lub

```text
SDXL API
```

Zapis:

```sql
image_path
```

---

# Branding Curvia

Grafika generowana przez AI.

Laravel dodaje:

```text
CURVIA
```

oraz opcjonalnie:

```text
Nowości motoryzacyjne
```

przy użyciu:

```php
Intervention Image
```

---

# Etap 4 - Panel administracyjny

Widok:

## Lista artykułów

Kolumny:

- Tytuł
- Źródło
- Data
- Status

---

## Podgląd

Oryginalny artykuł:

- tytuł
- opis

Wygenerowane:

- post
- grafika

---

## Akcje

Przyciski:

```text
Akceptuj
Odrzuć
Generuj ponownie
Publikuj
```

---

# Najważniejsza zasada MVP

NIE publikować automatycznie.

Workflow:

```text
RSS
 ↓
AI
 ↓
waiting_review
 ↓
Akceptacja
 ↓
Facebook
```

Przynajmniej przez pierwsze tygodnie.

---

# Etap 5 - Facebook

Wymagane:

- Fanpage Curvia
- Konto Meta Developer
- Aplikacja Meta

Potrzebne:

```env
FACEBOOK_PAGE_ID=
FACEBOOK_PAGE_ACCESS_TOKEN=
```

Publikacja:

```http
POST /{page-id}/photos
```

lub

```http
POST /{page-id}/feed
```

---

# Harmonogram

## Scheduler

```php
Schedule::command('curvia:fetch-news')
    ->hourly();

Schedule::command('curvia:generate-posts')
    ->everyTwoHours();
```

Publikacja:

ręczna z panelu.

---

# Rozwój v2

## AI Ranking

AI ocenia atrakcyjność:

```text
1-10
```

Publikować tylko:

```text
>= 7
```

---

## Kategorie

- motocykle
- samochody
- elektryki
- plotki
- premiery

---

## Social Media

Dodatkowo:

- Instagram
- Threads
- X

---

## Strona WWW

W przyszłości:

```text
curvia.pl
```

z automatycznie publikowanymi artykułami.

---

# Założenia biznesowe

Projekt hobbystyczny.

Cel:

- nauka AI
- nauka automatyzacji
- test Facebook Fanpage
- test RSS → AI → Grafika → Social Media

Zakładana publikacja:

```text
1-2 posty dziennie
```

Koszt AI:

```text
około 2-10 PLN miesięcznie
```

przy wykorzystaniu:

- DeepSeek
- Flux API / SDXL API