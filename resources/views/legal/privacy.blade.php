@extends('layouts.app')

@section('body')
    <main class="mx-auto max-w-3xl px-6 py-12">
        <h1 class="text-3xl font-bold">Polityka prywatności</h1>
        <p class="mt-2 text-sm text-gray-500">Ostatnia aktualizacja: {{ now()->translatedFormat('j F Y') }}</p>

        <div class="mt-8 space-y-6 leading-relaxed text-gray-800">
            <section>
                <h2 class="text-xl font-semibold">1. Kim jesteśmy</h2>
                <p class="mt-2">
                    Serwis Curvia ({{ config('app.url') }}) to autorski projekt redakcyjny
                    publikujący treści o tematyce motocyklowej. Aplikacja „Curvia Publisher"
                    służy wyłącznie do automatycznego przygotowywania i publikowania wpisów na
                    naszej własnej stronie (fanpage) na Facebooku.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold">2. Jakie dane przetwarzamy</h2>
                <p class="mt-2">
                    Aplikacja nie zbiera ani nie przechowuje danych osobowych osób
                    odwiedzających stronę Curvia. W celu publikacji wpisów korzystamy z
                    uprawnień Facebooka do zarządzania <strong>wyłącznie naszą własną stroną</strong>
                    (m.in. tworzenie i odczyt postów oraz podstawowych statystyk strony).
                    Nie pobieramy danych profilowych użytkowników Facebooka, nie czytamy
                    prywatnych wiadomości ani list znajomych.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold">3. W jakim celu</h2>
                <p class="mt-2">
                    Dostęp do Facebooka wykorzystujemy w jednym celu: aby publikować
                    przygotowane przez nas treści (tekst i grafikę) na naszej stronie oraz
                    sprawdzać status tych publikacji. Treści powstają na podstawie publicznie
                    dostępnych źródeł branżowych.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold">4. Udostępnianie danych</h2>
                <p class="mt-2">
                    Nie sprzedajemy ani nie udostępniamy danych podmiotom trzecim. Jedynym
                    odbiorcą treści, które publikujemy, jest platforma Facebook (Meta Platforms),
                    zgodnie z jej własną polityką prywatności.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold">5. Przechowywanie i usuwanie</h2>
                <p class="mt-2">
                    Przechowujemy jedynie treści, które sami tworzymy i publikujemy. Token
                    dostępu do Facebooka jest przechowywany w bezpiecznej konfiguracji serwera
                    i wykorzystywany wyłącznie do publikacji. Dostęp aplikacji można w każdej
                    chwili cofnąć w ustawieniach strony na Facebooku.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold">6. Kontakt</h2>
                <p class="mt-2">
                    W sprawach dotyczących prywatności prosimy o kontakt:
                    <a class="text-blue-600 underline" href="mailto:rafal@kwasniak.org">rafal@kwasniak.org</a>.
                </p>
            </section>
        </div>
    </main>
@endsection
